CREATE TABLE users (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone_number VARCHAR(255),
  role VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE fridges (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  location VARCHAR(255),
  min_temp DECIMAL(5, 2) NOT NULL,
  max_temp DECIMAL(5, 2) NOT NULL,
  client_id BIGINT,
  FOREIGN KEY (client_id) REFERENCES users(id)
);

CREATE TABLE fridge_groups (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  client_id BIGINT,
  FOREIGN KEY (client_id) REFERENCES users(id)
);

CREATE TABLE fridge_group_members (
  fridge_id BIGINT,
  group_id BIGINT,
  PRIMARY KEY (fridge_id, group_id),
  FOREIGN KEY (fridge_id) REFERENCES fridges(id),
  FOREIGN KEY (group_id) REFERENCES fridge_groups(id)
);

CREATE TABLE temperature_records (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  fridge_id BIGINT,
  temperature DECIMAL(5, 2) NOT NULL,
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (fridge_id) REFERENCES fridges(id)
);

CREATE TABLE alerts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  fridge_id BIGINT,
  temperature_record_id BIGINT,
  alert_type VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (fridge_id) REFERENCES fridges(id),
  FOREIGN KEY (temperature_record_id) REFERENCES temperature_records(id)
);

CREATE TABLE access_tokens (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT,
  token TEXT NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  scopes TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE access_permissions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT,
  visitor_id BIGINT,
  fridge_id BIGINT,
  can_view BOOLEAN DEFAULT TRUE,
  can_receive_alerts BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (client_id) REFERENCES users(id),
  FOREIGN KEY (visitor_id) REFERENCES users(id),
  FOREIGN KEY (fridge_id) REFERENCES fridges(id)
);

CREATE TABLE marketing_preferences (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT,
  accepts_marketing BOOLEAN DEFAULT FALSE,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insertar registros de temperaturas simuladas
DELIMITER $$
CREATE PROCEDURE InsertTemperatureRecords()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE temp DECIMAL(5, 2);
    
    WHILE i <= 95 DO
        SET temp = 2 + RAND() * 6;
        IF RAND() < 0.1 THEN
            SET temp = temp + (RAND() * 2 - 1);
        END IF;
        
        INSERT INTO temperature_records (fridge_id, temperature, recorded_at)
        VALUES (1, temp, NOW() - INTERVAL 1 DAY + INTERVAL 15 MINUTE * i);
        
        SET temp = 2 + RAND() * 6;
        IF RAND() < 0.1 THEN
            SET temp = temp + (RAND() * 2 - 1);
        END IF;
        
        INSERT INTO temperature_records (fridge_id, temperature, recorded_at)
        VALUES (2, temp, NOW() - INTERVAL 1 DAY + INTERVAL 15 MINUTE * i);
        
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;
