-- Auditoria de seguridad y trazabilidad de inventario.
-- Ejecutar despues de crear users, devices y stock_items.

CREATE TABLE IF NOT EXISTS stock_item_change_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stock_item_id INT NULL,
  device_id INT NULL,
  user_id INT NULL,
  action ENUM('create', 'update', 'delete') NOT NULL,
  field_changed VARCHAR(100) NOT NULL,
  old_value TEXT NULL,
  new_value TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_stock_item_change_log_stock_item (stock_item_id),
  KEY idx_stock_item_change_log_device (device_id),
  KEY idx_stock_item_change_log_user (user_id),
  KEY idx_stock_item_change_log_created_at (created_at),
  CONSTRAINT fk_stock_item_change_log_device
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
  CONSTRAINT fk_stock_item_change_log_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- En auditoria conviene conservar el id historico del item aun despues del DELETE.
SET @stock_item_log_fk := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'stock_item_change_log'
    AND COLUMN_NAME = 'stock_item_id'
    AND REFERENCED_TABLE_NAME = 'stock_items'
  LIMIT 1
);
SET @drop_stock_item_log_fk_sql := IF(
  @stock_item_log_fk IS NULL,
  'DO 0',
  CONCAT('ALTER TABLE stock_item_change_log DROP FOREIGN KEY ', @stock_item_log_fk)
);
PREPARE drop_stock_item_log_fk_stmt FROM @drop_stock_item_log_fk_sql;
EXECUTE drop_stock_item_log_fk_stmt;
DEALLOCATE PREPARE drop_stock_item_log_fk_stmt;

CREATE TABLE IF NOT EXISTS rate_limit_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket VARCHAR(80) NOT NULL,
  identity_hash CHAR(64) NOT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rate_limit_lookup (bucket, identity_hash, created_at),
  KEY idx_rate_limit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
