-- Observabilidad y auditoria transversal de API.
-- Ejecutar despues de security_audit.sql.

CREATE TABLE IF NOT EXISTS api_request_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id CHAR(16) NOT NULL,
  user_id INT NULL,
  method VARCHAR(10) NOT NULL,
  path VARCHAR(255) NOT NULL,
  query_string VARCHAR(500) NULL,
  status_code SMALLINT UNSIGNED NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  request_body_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(request_body_json)),
  response_summary_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(response_summary_json)),
  error_message TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL DEFAULT NULL,
  KEY idx_api_request_logs_request_id (request_id),
  KEY idx_api_request_logs_user_created (user_id, created_at),
  KEY idx_api_request_logs_path_created (path, created_at),
  KEY idx_api_request_logs_status_created (status_code, created_at),
  KEY idx_api_request_logs_created_at (created_at),
  CONSTRAINT fk_api_request_logs_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id CHAR(16) NULL,
  actor_user_id INT NULL,
  event_type VARCHAR(100) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id VARCHAR(80) NULL,
  action VARCHAR(80) NULL,
  severity ENUM('info','warning','error','critical') NOT NULL DEFAULT 'info',
  message TEXT NULL,
  metadata_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(metadata_json)),
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_events_request_id (request_id),
  KEY idx_audit_events_actor_created (actor_user_id, created_at),
  KEY idx_audit_events_entity_created (entity_type, entity_id, created_at),
  KEY idx_audit_events_type_created (event_type, created_at),
  KEY idx_audit_events_severity_created (severity, created_at),
  CONSTRAINT fk_audit_events_actor
    FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
