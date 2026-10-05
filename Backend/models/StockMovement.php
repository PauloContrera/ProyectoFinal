<?php

namespace Models;

use Helpers\AuditLogger;
use Helpers\Logger;
use PDO;
use PDOException;

/**
 * Movimientos de stock disparados por lecturas RFID del ESP.
 *
 * El firmware tiene dos lectores: el de carga (ingreso de mercaderia) y el de
 * descarga (retiro). Cada lectura llega dentro de /api/esp/sync y aca se traduce
 * en un +1 o un -1 sobre la cantidad del item que tenga esa tarjeta.
 */
class StockMovement
{
    public const IN = 'in';
    public const OUT = 'out';

    /** La tarjeta matcheo un item y la cantidad se actualizo. */
    public const APPLIED = 'applied';
    /** Tarjeta desconocida en esa heladera: se deja asentada sin tocar stock. */
    public const UNMATCHED = 'unmatched';
    /** Varios items comparten el UID: se aplico por FEFO y queda para revisar. */
    public const AMBIGUOUS = 'ambiguous';

    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Normaliza como viene la direccion desde el firmware.
     *
     * El enum TipoRfid del ESP es 1 = carga y 2 = descarga, y el payload viejo
     * mandaba ese entero. Se aceptan tambien las etiquetas en texto para que el
     * mismo endpoint sirva a un firmware nuevo o a una prueba manual.
     */
    public static function normalizeDirection($value): ?string
    {
        if (is_numeric($value)) {
            $n = (int)$value;
            if ($n === 1) return self::IN;
            if ($n === 2) return self::OUT;
            return null;
        }

        $v = strtolower(trim((string)$value));
        if (in_array($v, ['carga', 'in', 'ingreso', 'entrada'], true)) return self::IN;
        if (in_array($v, ['descarga', 'out', 'egreso', 'salida'], true)) return self::OUT;

        return null;
    }

    /**
     * Mismo criterio que el resto del stock: hasta 64 caracteres de
     * [A-Za-z0-9:_-]. El firmware imprime el UID separado por espacios
     * ("3A 5C 33 02"), asi que se normaliza a la forma con dos puntos.
     */
    public static function normalizeUid($value): ?string
    {
        $uid = strtoupper(trim((string)$value));
        if ($uid === '') return null;

        // "3A 5C 33 02" -> "3A:5C:33:02"
        if (preg_match('/^([0-9A-F]{2})( [0-9A-F]{2})+$/', $uid)) {
            $uid = str_replace(' ', ':', $uid);
        }

        if (strlen($uid) > 64 || !preg_match('/^[A-Za-z0-9:_-]+$/', $uid)) {
            return null;
        }

        return $uid;
    }

    /**
     * Aplica una lectura. Devuelve el detalle de lo que paso, para que el sync
     * pueda devolverselo al ESP y este sepa que la operacion se registro.
     *
     * @return array{status:string, applied:bool, duplicate:bool, stock_item_id:?int,
     *               quantity_before:?int, quantity_after:?int, reason:?string}
     */
    public function apply(int $deviceId, string $uid, string $direction, int $quantity, int $occurredAt, ?string $packetId): array
    {
        $occurredAtSql = gmdate('Y-m-d H:i:s', $occurredAt);
        $matches = $this->findItemsByRfid($deviceId, $uid);

        $status = self::APPLIED;
        $item = null;

        if (!$matches) {
            $status = self::UNMATCHED;
        } else {
            // FEFO: ante varios items con el mismo UID gana el que vence antes.
            $item = $matches[0];
            if (count($matches) > 1) {
                $status = self::AMBIGUOUS;
                Logger::warning('Varios items de stock comparten el mismo RFID', [
                    'device_id' => $deviceId,
                    'rfid' => $uid,
                    'candidatos' => count($matches),
                    'elegido' => (int)$item['id'],
                ]);
            }
        }

        $before = $item !== null ? (int)$item['quantity'] : null;

        // El INSERT va primero y hace de cerrojo: si este movimiento ya se proceso
        // (mismo dispositivo, tarjeta, direccion e instante), choca contra el
        // UNIQUE y se corta antes de tocar la cantidad. Asi un reintento del ESP
        // despues de un corte de red no descuenta dos veces.
        $movementId = $this->insert(
            $deviceId,
            $item !== null ? (int)$item['id'] : null,
            $uid,
            $direction,
            $quantity,
            $before,
            null,
            $status,
            $occurredAtSql,
            $packetId
        );

        if ($movementId === null) {
            return [
                'status' => 'duplicate',
                'applied' => false,
                'duplicate' => true,
                'stock_item_id' => $item !== null ? (int)$item['id'] : null,
                'quantity_before' => $before,
                'quantity_after' => $before,
                'reason' => 'El movimiento ya estaba registrado',
            ];
        }

        if ($item === null) {
            AuditLogger::event('stock_rfid_unmatched', 'Lectura RFID sin item asociado', 'warning', [
                'device_id' => $deviceId,
                'rfid' => $uid,
                'direction' => $direction,
            ], null, 'device', (string)$deviceId, 'rfid_unmatched');

            return [
                'status' => self::UNMATCHED,
                'applied' => false,
                'duplicate' => false,
                'stock_item_id' => null,
                'quantity_before' => null,
                'quantity_after' => null,
                'reason' => 'No hay ningun item con ese RFID en la heladera',
            ];
        }

        $after = $this->adjustQuantity((int)$item['id'], $direction, $quantity);
        $this->setMovementResult($movementId, $after);

        AuditLogger::event('stock_rfid_movement', 'Movimiento de stock por RFID', 'info', [
            'device_id' => $deviceId,
            'stock_item_id' => (int)$item['id'],
            'rfid' => $uid,
            'direction' => $direction,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'status' => $status,
        ], null, 'stock_item', (string)$item['id'], $direction === self::IN ? 'rfid_in' : 'rfid_out');

        return [
            'status' => $status,
            'applied' => true,
            'duplicate' => false,
            'stock_item_id' => (int)$item['id'],
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reason' => null,
        ];
    }

    /**
     * Items de la heladera con ese RFID, ordenados por vencimiento mas proximo
     * (los sin fecha al final).
     */
    private function findItemsByRfid(int $deviceId, string $uid): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, quantity, expiration_date
            FROM stock_items
            WHERE device_id = :device_id AND UPPER(rfid) = :rfid
            ORDER BY expiration_date IS NULL, expiration_date ASC, id ASC
        ");
        $stmt->execute([':device_id' => $deviceId, ':rfid' => strtoupper($uid)]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Suma o resta en la propia sentencia SQL, nunca leyendo y reescribiendo:
     * dos lecturas simultaneas del mismo item no se pisan. GREATEST evita que la
     * cantidad quede negativa si se descarga mas de lo que figura cargado.
     */
    private function adjustQuantity(int $stockItemId, string $direction, int $quantity): int
    {
        $sql = $direction === self::IN
            ? "UPDATE stock_items SET quantity = quantity + :q, updated_at = NOW() WHERE id = :id"
            : "UPDATE stock_items SET quantity = GREATEST(0, quantity - :q), updated_at = NOW() WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':q' => $quantity, ':id' => $stockItemId]);

        $stmt = $this->conn->prepare("SELECT quantity FROM stock_items WHERE id = :id");
        $stmt->execute([':id' => $stockItemId]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Inserta el movimiento. Devuelve null si ya existia (violacion del UNIQUE).
     */
    private function insert(
        int $deviceId,
        ?int $stockItemId,
        string $uid,
        string $direction,
        int $quantity,
        ?int $before,
        ?int $after,
        string $status,
        string $occurredAtSql,
        ?string $packetId
    ): ?int {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO stock_movements (
                    device_id, stock_item_id, rfid, direction, quantity,
                    quantity_before, quantity_after, status, occurred_at, packet_id
                ) VALUES (
                    :device_id, :stock_item_id, :rfid, :direction, :quantity,
                    :quantity_before, :quantity_after, :status, :occurred_at, :packet_id
                )
            ");
            $stmt->execute([
                ':device_id' => $deviceId,
                ':stock_item_id' => $stockItemId,
                ':rfid' => $uid,
                ':direction' => $direction,
                ':quantity' => $quantity,
                ':quantity_before' => $before,
                ':quantity_after' => $after,
                ':status' => $status,
                ':occurred_at' => $occurredAtSql,
                ':packet_id' => $packetId,
            ]);

            return (int)$this->conn->lastInsertId();
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return null; // ya registrado
            }
            throw $exception;
        }
    }

    private function setMovementResult(int $movementId, int $after): void
    {
        $stmt = $this->conn->prepare("UPDATE stock_movements SET quantity_after = :after WHERE id = :id");
        $stmt->execute([':after' => $after, ':id' => $movementId]);
    }

    public function getByDevice(int $deviceId, int $limit = 100): array
    {
        $stmt = $this->conn->prepare("
            SELECT m.id, m.device_id, m.stock_item_id, m.rfid, m.direction, m.quantity,
                   m.quantity_before, m.quantity_after, m.status, m.occurred_at,
                   m.packet_id, m.created_at, s.name AS stock_item_name
            FROM stock_movements m
            LEFT JOIN stock_items s ON s.id = m.stock_item_id
            WHERE m.device_id = :device_id
            ORDER BY m.occurred_at DESC, m.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':device_id', $deviceId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
