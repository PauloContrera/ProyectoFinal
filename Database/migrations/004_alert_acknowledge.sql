-- Migracion: estado "reconocida" (acknowledged) para el alarmero.
-- Distingue 3 estados de una alerta:
--   Activa      = resolved=0 AND acknowledged=0  (sin atender)
--   Reconocida  = resolved=0 AND acknowledged=1  (vista, problema aun no resuelto)
--   Resuelta    = resolved=1                     (problema corregido)
-- Idempotente: solo agrega las columnas si no existen.

SET @col_ack := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alerts' AND COLUMN_NAME = 'acknowledged'
);
SET @sql := IF(@col_ack = 0,
  'ALTER TABLE alerts ADD COLUMN acknowledged TINYINT(1) NOT NULL DEFAULT 0 AFTER notified',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_ack_at := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alerts' AND COLUMN_NAME = 'acknowledged_at'
);
SET @sql := IF(@col_ack_at = 0,
  'ALTER TABLE alerts ADD COLUMN acknowledged_at DATETIME NULL DEFAULT NULL AFTER acknowledged',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
