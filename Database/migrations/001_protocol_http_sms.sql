ALTER TABLE devices
  ADD COLUMN IF NOT EXISTS mac_address VARCHAR(17) NULL AFTER device_code,
  ADD COLUMN IF NOT EXISTS shared_secret VARCHAR(128) NULL AFTER mac_address,
  ADD COLUMN IF NOT EXISTS account_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER shared_secret,
  ADD COLUMN IF NOT EXISTS activation_keyword VARCHAR(80) NOT NULL DEFAULT 'clavesecreta4321' AFTER account_enabled,
  ADD COLUMN IF NOT EXISTS sms_phones TEXT NULL AFTER activation_keyword,
  ADD COLUMN IF NOT EXISTS send_interval_seconds INT NOT NULL DEFAULT 900 AFTER sms_phones,
  ADD COLUMN IF NOT EXISTS backup_url VARCHAR(255) NULL AFTER send_interval_seconds,
  ADD COLUMN IF NOT EXISTS config_version INT NOT NULL DEFAULT 1 AFTER backup_url,
  ADD COLUMN IF NOT EXISTS last_config_version_sent INT NOT NULL DEFAULT 0 AFTER config_version,
  ADD COLUMN IF NOT EXISTS registered_model VARCHAR(80) NULL AFTER last_config_version_sent,
  ADD COLUMN IF NOT EXISTS sim_imei VARCHAR(32) NULL AFTER registered_model,
  ADD COLUMN IF NOT EXISTS protocol_version VARCHAR(20) NULL AFTER sim_imei,
  ADD COLUMN IF NOT EXISTS last_sync_at DATETIME NULL AFTER protocol_version,
  ADD COLUMN IF NOT EXISTS last_sequence INT NULL AFTER last_sync_at,
  ADD COLUMN IF NOT EXISTS last_packet_id VARCHAR(80) NULL AFTER last_sequence,
  ADD COLUMN IF NOT EXISTS last_packet_at DATETIME NULL AFTER last_packet_id,
  ADD COLUMN IF NOT EXISTS retry_base_seconds INT NOT NULL DEFAULT 30 AFTER backup_url,
  ADD COLUMN IF NOT EXISTS max_batch_size INT NOT NULL DEFAULT 120 AFTER retry_base_seconds;

CREATE UNIQUE INDEX IF NOT EXISTS idx_devices_mac_address ON devices (mac_address);

CREATE TABLE IF NOT EXISTS esp_sync_batches (
  id INT(11) NOT NULL AUTO_INCREMENT,
  device_id INT(11) NOT NULL,
  packet_id VARCHAR(80) NOT NULL,
  message_type ENUM('sync', 'command') NOT NULL DEFAULT 'sync',
  request_hash CHAR(64) NOT NULL,
  packet_timestamp DATETIME NOT NULL,
  sequence_number INT NULL,
  status ENUM('processing', 'accepted', 'duplicate', 'error') NOT NULL DEFAULT 'processing',
  inserted_count INT NOT NULL DEFAULT 0,
  duplicate_count INT NOT NULL DEFAULT 0,
  ip_address VARCHAR(45) NULL,
  payload_json JSON NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY idx_esp_sync_batches_device_packet (device_id, packet_id),
  KEY idx_esp_sync_batches_device_received (device_id, received_at),
  KEY idx_esp_sync_batches_status (status),
  CONSTRAINT esp_sync_batches_ibfk_1 FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS esp_local_alerts (
  id INT(11) NOT NULL AUTO_INCREMENT,
  device_id INT(11) NOT NULL,
  alert_type VARCHAR(80) NOT NULL,
  temperature FLOAT NULL,
  occurred_at DATETIME NOT NULL,
  payload_json JSON NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_esp_local_alerts_device (device_id),
  KEY idx_esp_local_alerts_type (alert_type),
  CONSTRAINT esp_local_alerts_ibfk_1 FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS esp_diagnostics (
  id INT(11) NOT NULL AUTO_INCREMENT,
  device_id INT(11) NOT NULL,
  uptime INT NULL,
  signal_strength INT NULL,
  battery_level INT NULL,
  payload_json JSON NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_esp_diagnostics_device (device_id),
  CONSTRAINT esp_diagnostics_ibfk_1 FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS esp_command_responses (
  id INT(11) NOT NULL AUTO_INCREMENT,
  device_id INT(11) NOT NULL,
  packet_id VARCHAR(80) NULL,
  command_type VARCHAR(80) NOT NULL,
  status VARCHAR(20) NOT NULL,
  detail TEXT NULL,
  command_time DATETIME NOT NULL,
  payload_json JSON NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_esp_command_responses_device (device_id),
  KEY idx_esp_command_responses_packet (packet_id),
  KEY idx_esp_command_responses_type (command_type),
  CONSTRAINT esp_command_responses_ibfk_1 FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE esp_command_responses
  ADD COLUMN IF NOT EXISTS packet_id VARCHAR(80) NULL AFTER device_id;

CREATE INDEX IF NOT EXISTS idx_esp_command_responses_packet ON esp_command_responses (packet_id);
