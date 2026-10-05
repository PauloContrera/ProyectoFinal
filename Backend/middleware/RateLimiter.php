<?php

namespace Middleware;

use Helpers\AuditLogger;
use Helpers\Logger;
use Helpers\Response;
use PDO;

class RateLimiter
{
    public static function enforce(
        PDO $db,
        string $bucket,
        int $limit,
        int $windowSeconds,
        ?string $identity = null
    ): void {
        if ($limit <= 0 || $windowSeconds <= 0) {
            return;
        }

        $identityValue = $identity ?: self::clientIp();
        $identityHash = hash('sha256', $bucket . '|' . strtolower(trim($identityValue)));
        $ipAddress = self::clientIp();

        // Poda determinista de la ventana de esta identidad: mantiene acotadas sus
        // filas sin depender del barrido global, que es probabilistico.
        self::pruneIdentity($db, $bucket, $identityHash, $windowSeconds);
        self::cleanup($db, $windowSeconds);

        // Si ya se alcanzo el limite se rechaza ANTES de insertar, para que un
        // cliente bloqueado deje de engordar la tabla mientras sigue insistiendo.
        $count = self::countRecent($db, $bucket, $identityHash, $windowSeconds);
        if ($count >= $limit) {
            self::reject($bucket, $ipAddress, $count, $limit, $windowSeconds);
        }

        $stmt = $db->prepare("
            INSERT INTO rate_limit_events (bucket, identity_hash, ip_address)
            VALUES (:bucket, :identity_hash, :ip_address)
        ");
        $stmt->execute([
            ':bucket' => $bucket,
            ':identity_hash' => $identityHash,
            ':ip_address' => $ipAddress,
        ]);

        // Recuento posterior: cubre las peticiones que entraron en paralelo entre
        // el chequeo previo y este insert.
        $count = self::countRecent($db, $bucket, $identityHash, $windowSeconds);
        if ($count <= $limit) {
            return;
        }

        self::reject($bucket, $ipAddress, $count, $limit, $windowSeconds);
    }

    /**
     * Responde 429 y corta la peticion (Response::json hace exit).
     */
    private static function reject(string $bucket, string $ipAddress, int $count, int $limit, int $windowSeconds): void
    {
        Logger::security('Rate limit exceeded', [
            'bucket' => $bucket,
            'ip' => $ipAddress,
            'count' => $count,
            'limit' => $limit,
            'window_seconds' => $windowSeconds,
        ]);
        AuditLogger::event('rate_limit_exceeded', 'Rate limit excedido', 'warning', [
            'bucket' => $bucket,
            'count' => $count,
            'limit' => $limit,
            'window_seconds' => $windowSeconds,
        ], null, 'rate_limit', $bucket, 'block');

        header('Retry-After: ' . $windowSeconds);
        Response::json(429, 'RATE_LIMITED', [
            'retry_after_seconds' => $windowSeconds,
        ]);
        exit;
    }

    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private static function countRecent(PDO $db, string $bucket, string $identityHash, int $windowSeconds): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM rate_limit_events
            WHERE bucket = :bucket
              AND identity_hash = :identity_hash
              AND created_at >= :cutoff
        ");
        $stmt->bindValue(':bucket', $bucket);
        $stmt->bindValue(':identity_hash', $identityHash);
        $stmt->bindValue(':cutoff', $cutoff);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Borra las filas de esta identidad que ya cayeron fuera de la ventana.
     * Usa idx_rate_limit_lookup (bucket, identity_hash, created_at), asi que el
     * trabajo queda acotado a las filas de un solo cliente.
     */
    private static function pruneIdentity(PDO $db, string $bucket, string $identityHash, int $windowSeconds): void
    {
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = $db->prepare("
            DELETE FROM rate_limit_events
            WHERE bucket = :bucket
              AND identity_hash = :identity_hash
              AND created_at < :cutoff
        ");
        $stmt->bindValue(':bucket', $bucket);
        $stmt->bindValue(':identity_hash', $identityHash);
        $stmt->bindValue(':cutoff', $cutoff);
        $stmt->execute();
    }

    private static function cleanup(PDO $db, int $windowSeconds): void
    {
        if (random_int(1, 100) !== 1) {
            return;
        }

        $cutoff = date('Y-m-d H:i:s', time() - max($windowSeconds * 2, 86400));
        $stmt = $db->prepare("
            DELETE FROM rate_limit_events
            WHERE created_at < :cutoff
        ");
        $stmt->bindValue(':cutoff', $cutoff);
        $stmt->execute();
    }
}
