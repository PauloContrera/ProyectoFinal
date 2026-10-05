-- Migracion: columna RFID en los items de stock.
-- Idempotente: solo agrega la columna y el indice si no existen, para que la
-- cadena completa de migraciones se pueda reejecutar sin errores.

SET @col_rfid := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_items' AND COLUMN_NAME = 'rfid'
);
SET @sql := IF(@col_rfid = 0,
  'ALTER TABLE `stock_items` ADD COLUMN `rfid` VARCHAR(64) NULL AFTER `name`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_rfid := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_items' AND INDEX_NAME = 'idx_stock_items_rfid'
);
SET @sql := IF(@idx_rfid = 0,
  'ALTER TABLE `stock_items` ADD INDEX `idx_stock_items_rfid` (`rfid`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
