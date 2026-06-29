SET @uid := (SELECT id FROM users WHERE username = 'devtest' LIMIT 1);

INSERT INTO users (
  name,
  username,
  password,
  email,
  phone,
  role,
  is_email_verified,
  failed_login_attempts,
  registered_at
) VALUES (
  'Usuario Dev',
  'devtest',
  '$2y$10$RzWgyv8s7Q6TBy8zv4Rye..GMbtQKAoejELpTDQxikqQCqxNxnVP2',
  'devtest@tempsegura.local',
  NULL,
  'client',
  1,
  0,
  NOW()
) ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  password = VALUES(password),
  email = VALUES(email),
  role = VALUES(role),
  is_email_verified = VALUES(is_email_verified),
  failed_login_attempts = 0;

SET @uid := (SELECT id FROM users WHERE username = 'devtest' LIMIT 1);

INSERT INTO users (
  name,
  username,
  password,
  email,
  phone,
  role,
  is_email_verified,
  failed_login_attempts,
  registered_at
) VALUES (
  'Visitante Dev',
  'devvisitor',
  '$2y$10$RzWgyv8s7Q6TBy8zv4Rye..GMbtQKAoejELpTDQxikqQCqxNxnVP2',
  'devvisitor@tempsegura.local',
  NULL,
  'visitor',
  1,
  0,
  NOW()
) ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  password = VALUES(password),
  email = VALUES(email),
  role = VALUES(role),
  is_email_verified = VALUES(is_email_verified),
  failed_login_attempts = 0;

SET @visitor_uid := (SELECT id FROM users WHERE username = 'devvisitor' LIMIT 1);

CREATE TABLE IF NOT EXISTS stock_items (
  id INT(11) NOT NULL AUTO_INCREMENT,
  device_id INT(11) NOT NULL,
  name VARCHAR(120) NOT NULL,
  quantity INT(11) NOT NULL DEFAULT 0,
  expiration_date DATE DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY device_id (device_id),
  CONSTRAINT stock_items_ibfk_1 FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO email_verifications (
  user_id,
  email,
  token,
  expires_at,
  verified,
  verified_at,
  ip_address
) VALUES (
  @uid,
  'devtest@tempsegura.local',
  'dev-local-token',
  DATE_ADD(NOW(), INTERVAL 1 DAY),
  1,
  NOW(),
  '127.0.0.1'
) ON DUPLICATE KEY UPDATE
  verified = 1,
  verified_at = NOW();

INSERT INTO email_verifications (
  user_id,
  email,
  token,
  expires_at,
  verified,
  verified_at,
  ip_address
) VALUES (
  @visitor_uid,
  'devvisitor@tempsegura.local',
  'dev-visitor-local-token',
  DATE_ADD(NOW(), INTERVAL 1 DAY),
  1,
  NOW(),
  '127.0.0.1'
) ON DUPLICATE KEY UPDATE
  verified = 1,
  verified_at = NOW();

DELETE FROM stock_items
WHERE device_id IN (
  SELECT id FROM devices
  WHERE user_id = @uid
);

DELETE FROM temperatures
WHERE device_id IN (
  SELECT id FROM devices
  WHERE user_id = @uid
);

DELETE FROM devices
WHERE user_id = @uid;

DELETE FROM device_groups
WHERE user_id = @uid;

INSERT INTO device_groups (name, description, user_id, created_at)
VALUES
  ('Laboratorio Central', 'Heladeras reales del laboratorio central', @uid, NOW()),
  ('Vacunatorio Norte', 'Heladeras reales del vacunatorio norte', @uid, NOW());

SET @g1 := (
  SELECT id FROM device_groups
  WHERE user_id = @uid AND name = 'Laboratorio Central'
  ORDER BY id DESC
  LIMIT 1
);

SET @g2 := (
  SELECT id FROM device_groups
  WHERE user_id = @uid AND name = 'Vacunatorio Norte'
  ORDER BY id DESC
  LIMIT 1
);

INSERT INTO devices (
  device_code,
  mac_address,
  shared_secret,
  name,
  location,
  user_id,
  group_id,
  min_temp,
  max_temp,
  firmware_version,
  account_enabled,
  activation_keyword,
  sms_phones,
  send_interval_seconds,
  backup_url,
  config_version,
  last_reported_at,
  created_at
) VALUES
  ('DEV-G1-H1', 'A1:B2:C3:D4:E5:01', 'local-dev-esp-secret', 'Heladera Medicamentos A', 'Sala de medicacion', @uid, @g1, 2.2, 8.4, '1.0.0', 1, 'clavesecreta4321', '["2630203044","2630203040"]', 900, 'https://backup.tempsegura.local/api/esp/sync', 1, NOW(), NOW()),
  ('DEV-G1-H2', 'A1:B2:C3:D4:E5:02', 'local-dev-esp-secret', 'Heladera Insulina B', 'Deposito frio', @uid, @g1, 2.0, 6.0, '1.0.0', 1, 'clavesecreta4321', '["2630203044","2630203040"]', 900, 'https://backup.tempsegura.local/api/esp/sync', 1, NOW(), NOW()),
  ('DEV-G2-H1', 'A1:B2:C3:D4:E5:03', 'local-dev-esp-secret', 'Heladera Vacunas A', 'Vacunatorio box 1', @uid, @g2, 3.0, 7.0, '1.0.0', 1, 'clavesecreta4321', '["2630203042"]', 900, 'https://backup.tempsegura.local/api/esp/sync', 1, NOW(), NOW()),
  ('DEV-G2-H2', 'A1:B2:C3:D4:E5:04', 'local-dev-esp-secret', 'Heladera Vacunas B', 'Vacunatorio box 2', @uid, @g2, 3.0, 8.0, '1.0.0', 1, 'clavesecreta4321', '["2630203042"]', 900, 'https://backup.tempsegura.local/api/esp/sync', 1, NOW(), NOW());

SET @d1 := (SELECT id FROM devices WHERE device_code = 'DEV-G1-H1' LIMIT 1);
SET @d2 := (SELECT id FROM devices WHERE device_code = 'DEV-G1-H2' LIMIT 1);
SET @d3 := (SELECT id FROM devices WHERE device_code = 'DEV-G2-H1' LIMIT 1);
SET @d4 := (SELECT id FROM devices WHERE device_code = 'DEV-G2-H2' LIMIT 1);

DELETE FROM device_access
WHERE user_id = @visitor_uid
  AND device_id IN (@d1, @d2, @d3, @d4);

INSERT INTO device_access (device_id, user_id, can_modify)
VALUES
  (@d1, @visitor_uid, 0),
  (@d2, @visitor_uid, 0),
  (@d3, @visitor_uid, 0),
  (@d4, @visitor_uid, 0);

INSERT INTO stock_items (device_id, name, quantity, expiration_date)
VALUES
  (@d1, 'Vacuna antigripal', 34, DATE_ADD(CURDATE(), INTERVAL 75 DAY)),
  (@d1, 'Reactivo control A', 12, DATE_ADD(CURDATE(), INTERVAL 40 DAY)),
  (@d2, 'Insulina NPH', 28, DATE_ADD(CURDATE(), INTERVAL 55 DAY)),
  (@d2, 'Insulina rapida', 18, DATE_ADD(CURDATE(), INTERVAL 35 DAY)),
  (@d3, 'Vacuna hepatitis B', 42, DATE_ADD(CURDATE(), INTERVAL 95 DAY)),
  (@d3, 'Jeringas prellenadas', 60, DATE_ADD(CURDATE(), INTERVAL 180 DAY)),
  (@d4, 'Vacuna triple viral', 25, DATE_ADD(CURDATE(), INTERVAL 68 DAY)),
  (@d4, 'Diluyente refrigerado', 14, DATE_ADD(CURDATE(), INTERVAL 30 DAY));

INSERT INTO temperatures (device_id, temperature, recorded_at)
VALUES
  (@d1, 4.6, DATE_SUB(NOW(), INTERVAL 345 MINUTE)),
  (@d1, 4.8, DATE_SUB(NOW(), INTERVAL 330 MINUTE)),
  (@d1, 5.0, DATE_SUB(NOW(), INTERVAL 315 MINUTE)),
  (@d1, 5.2, DATE_SUB(NOW(), INTERVAL 300 MINUTE)),
  (@d1, 5.4, DATE_SUB(NOW(), INTERVAL 285 MINUTE)),
  (@d1, 5.5, DATE_SUB(NOW(), INTERVAL 270 MINUTE)),
  (@d1, 5.3, DATE_SUB(NOW(), INTERVAL 255 MINUTE)),
  (@d1, 5.1, DATE_SUB(NOW(), INTERVAL 240 MINUTE)),
  (@d1, 4.9, DATE_SUB(NOW(), INTERVAL 225 MINUTE)),
  (@d1, 5.0, DATE_SUB(NOW(), INTERVAL 210 MINUTE)),
  (@d1, 5.2, DATE_SUB(NOW(), INTERVAL 195 MINUTE)),
  (@d1, 5.4, DATE_SUB(NOW(), INTERVAL 180 MINUTE)),
  (@d2, 3.6, DATE_SUB(NOW(), INTERVAL 345 MINUTE)),
  (@d2, 3.8, DATE_SUB(NOW(), INTERVAL 330 MINUTE)),
  (@d2, 4.0, DATE_SUB(NOW(), INTERVAL 315 MINUTE)),
  (@d2, 4.2, DATE_SUB(NOW(), INTERVAL 300 MINUTE)),
  (@d2, 4.4, DATE_SUB(NOW(), INTERVAL 285 MINUTE)),
  (@d2, 4.5, DATE_SUB(NOW(), INTERVAL 270 MINUTE)),
  (@d2, 4.3, DATE_SUB(NOW(), INTERVAL 255 MINUTE)),
  (@d2, 4.1, DATE_SUB(NOW(), INTERVAL 240 MINUTE)),
  (@d2, 3.9, DATE_SUB(NOW(), INTERVAL 225 MINUTE)),
  (@d2, 4.0, DATE_SUB(NOW(), INTERVAL 210 MINUTE)),
  (@d2, 4.2, DATE_SUB(NOW(), INTERVAL 195 MINUTE)),
  (@d2, 4.4, DATE_SUB(NOW(), INTERVAL 180 MINUTE)),
  (@d3, 4.6, DATE_SUB(NOW(), INTERVAL 345 MINUTE)),
  (@d3, 4.8, DATE_SUB(NOW(), INTERVAL 330 MINUTE)),
  (@d3, 5.0, DATE_SUB(NOW(), INTERVAL 315 MINUTE)),
  (@d3, 5.2, DATE_SUB(NOW(), INTERVAL 300 MINUTE)),
  (@d3, 5.4, DATE_SUB(NOW(), INTERVAL 285 MINUTE)),
  (@d3, 5.5, DATE_SUB(NOW(), INTERVAL 270 MINUTE)),
  (@d3, 5.3, DATE_SUB(NOW(), INTERVAL 255 MINUTE)),
  (@d3, 5.1, DATE_SUB(NOW(), INTERVAL 240 MINUTE)),
  (@d3, 4.9, DATE_SUB(NOW(), INTERVAL 225 MINUTE)),
  (@d3, 5.0, DATE_SUB(NOW(), INTERVAL 210 MINUTE)),
  (@d3, 5.2, DATE_SUB(NOW(), INTERVAL 195 MINUTE)),
  (@d3, 5.4, DATE_SUB(NOW(), INTERVAL 180 MINUTE)),
  (@d4, 5.1, DATE_SUB(NOW(), INTERVAL 345 MINUTE)),
  (@d4, 5.3, DATE_SUB(NOW(), INTERVAL 330 MINUTE)),
  (@d4, 5.5, DATE_SUB(NOW(), INTERVAL 315 MINUTE)),
  (@d4, 5.7, DATE_SUB(NOW(), INTERVAL 300 MINUTE)),
  (@d4, 5.9, DATE_SUB(NOW(), INTERVAL 285 MINUTE)),
  (@d4, 6.0, DATE_SUB(NOW(), INTERVAL 270 MINUTE)),
  (@d4, 5.8, DATE_SUB(NOW(), INTERVAL 255 MINUTE)),
  (@d4, 5.6, DATE_SUB(NOW(), INTERVAL 240 MINUTE)),
  (@d4, 5.4, DATE_SUB(NOW(), INTERVAL 225 MINUTE)),
  (@d4, 5.5, DATE_SUB(NOW(), INTERVAL 210 MINUTE)),
  (@d4, 5.7, DATE_SUB(NOW(), INTERVAL 195 MINUTE)),
  (@d4, 5.9, DATE_SUB(NOW(), INTERVAL 180 MINUTE));
