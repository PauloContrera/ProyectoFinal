-- Migracion: movimientos de stock disparados por lecturas RFID.
--
-- Cierra el circuito que faltaba: el ESP lee una tarjeta en el lector de carga o
-- en el de descarga, lo reporta dentro de /api/esp/sync y el servidor suma o
-- resta la cantidad del item correspondiente, dejando el movimiento asentado.
--
-- La clave natural (device_id, rfid, direction, occurred_at) hace la operacion
-- idempotente: si el ESP reintenta un paquete que ya se proceso, el INSERT choca
-- contra el UNIQUE y el movimiento no se vuelve a aplicar. Sin esto, una
-- reconexion despues de un corte descontaria dos veces la misma extraccion.
--
-- status distingue tres desenlaces:
--   applied    la tarjeta matcheo un item y la cantidad se actualizo
--   unmatched  tarjeta desconocida en esa heladera: se registra sin tocar stock
--   ambiguous  varios items comparten el UID; se aplico al de vencimiento mas
--              proximo (FEFO) y queda marcado para revisar
--
-- Idempotente: solo crea la tabla si no existe.

CREATE TABLE IF NOT EXISTS stock_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  device_id INT(11) NOT NULL,
  stock_item_id INT NULL,
  rfid VARCHAR(64) NOT NULL,
  direction ENUM('in', 'out') NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  quantity_before INT NULL,
  quantity_after INT NULL,
  status ENUM('applied', 'unmatched', 'ambiguous') NOT NULL DEFAULT 'applied',
  occurred_at DATETIME NOT NULL,
  packet_id VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_stock_movement (device_id, rfid, direction, occurred_at),
  KEY idx_stock_movements_device (device_id, occurred_at),
  KEY idx_stock_movements_item (stock_item_id),
  CONSTRAINT fk_stock_movements_device FOREIGN KEY (device_id)
    REFERENCES devices (id) ON DELETE CASCADE,
  CONSTRAINT fk_stock_movements_item FOREIGN KEY (stock_item_id)
    REFERENCES stock_items (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indice para resolver la tarjeta rapido durante el sync.
SET @idx_rfid_lookup := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_items'
    AND INDEX_NAME = 'idx_stock_items_device_rfid'
);
SET @sql := IF(@idx_rfid_lookup = 0,
  'CREATE INDEX idx_stock_items_device_rfid ON stock_items (device_id, rfid)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- OPCIONAL, no se aplica automaticamente.
--
-- Si en tu operacion una tarjeta identifica un unico item por heladera, conviene
-- forzarlo con un UNIQUE. No se incluye arriba porque la base de produccion ya
-- tiene UID repetidos y la migracion fallaria a mitad de camino.
--
-- Para ver si hay duplicados antes de decidir:
--
--   SELECT device_id, rfid, COUNT(*) AS repetidos
--   FROM stock_items
--   WHERE rfid IS NOT NULL AND rfid <> ''
--   GROUP BY device_id, rfid
--   HAVING repetidos > 1;
--
-- Y una vez resueltos:
--
--   CREATE UNIQUE INDEX uq_stock_items_device_rfid ON stock_items (device_id, rfid);
--
-- Mientras tanto el servidor resuelve el empate por FEFO (vencimiento mas
-- proximo primero) y marca el movimiento como 'ambiguous'.
-- ---------------------------------------------------------------------------
