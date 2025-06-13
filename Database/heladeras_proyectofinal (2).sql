-- ============================
-- BASE DE DATOS
-- ============================
CREATE DATABASE IF NOT EXISTS sistemas_heladeras CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistemas_heladeras;

-- ============================
-- TABLA: USERS
-- ============================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    role ENUM('admin', 'client', 'visitor') DEFAULT 'client',
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    failed_login_attempts INT DEFAULT 0,
    last_login_at DATETIME,
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================
-- TABLA: DEVICE GROUPS (Grupos de heladeras)
-- ============================
CREATE TABLE IF NOT EXISTS device_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    description TEXT,
    user_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================
-- TABLA: DEVICES (heladeras)
-- ============================
CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    user_id INT,
    group_id INT,
    min_temp FLOAT DEFAULT 0,
    max_temp FLOAT DEFAULT 10,
    firmware_version VARCHAR(50),
    last_reported_at DATETIME,
    device_time DATETIME,
    time_discrepancy INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES device_groups(id) ON DELETE SET NULL
);

-- ============================
-- TABLA: TEMPERATURES
-- ============================
CREATE TABLE IF NOT EXISTS temperatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT,
    temperature FLOAT,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- ============================
-- TABLA: ALERTS
-- ============================
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT,
    temperature FLOAT,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    type ENUM('TEMP_LOW', 'TEMP_HIGH', 'PAYMENT_DUE', 'NO_DATA') NOT NULL DEFAULT 'TEMP_HIGH',
    notified BOOLEAN DEFAULT FALSE,
    resolved BOOLEAN DEFAULT FALSE,
    resolved_at DATETIME,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- ============================
-- TABLA: LOG CAMBIOS DE USUARIO
-- ============================
CREATE TABLE IF NOT EXISTS user_change_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    field_changed VARCHAR(50),
    old_value TEXT,
    new_value TEXT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    changed_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================
-- TABLA: LOG DE INACTIVIDAD
-- ============================
CREATE TABLE IF NOT EXISTS device_inactivity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    status ENUM('active', 'resolved') DEFAULT 'active',
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- ============================
-- TABLA: ALERTAS SUPRIMIDAS
-- ============================
CREATE TABLE IF NOT EXISTS alert_suppression (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME,
    reason TEXT,
    created_by INT,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============================
-- TABLA: LOG DEL SISTEMA
-- ============================
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100),
    message TEXT,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================
-- VISTAS ÚTILES
-- ============================
CREATE OR REPLACE VIEW top_fridges_by_alerts AS
SELECT d.id AS fridge_id, d.name, COUNT(a.id) AS alert_count
FROM devices d
JOIN alerts a ON a.device_id = d.id
GROUP BY d.id
ORDER BY alert_count DESC;

CREATE OR REPLACE VIEW inactive_fridges AS
SELECT d.id, d.name, d.last_reported_at
FROM devices d
WHERE d.last_reported_at < NOW() - INTERVAL 1 DAY;

CREATE OR REPLACE VIEW users_with_most_alerts AS
SELECT u.id AS user_id, u.name, COUNT(a.id) AS alert_count
FROM users u
JOIN devices d ON d.user_id = u.id
JOIN alerts a ON a.device_id = d.id
GROUP BY u.id
ORDER BY alert_count DESC;

-- ============================
-- DATOS DE EJEMPLO
-- ============================

-- Usuarios
INSERT INTO users (name, username, password, email, phone, role) VALUES
('Admin One', 'admin1', '123456', 'admin1@mail.com', '1111111111', 'admin'),
('Cliente Uno', 'cliente1', '123456', 'cliente1@mail.com', '2222222222', 'client'),
('Cliente Dos', 'cliente2', '123456', 'cliente2@mail.com', '3333333333', 'client'),
('Visitante Uno', 'visit1', '123456', 'visit1@mail.com', '4444444444', 'visitor');

-- Grupos
INSERT INTO device_groups (name, description, user_id) VALUES
('Grupo A', 'Grupo de prueba A', 2),
('Grupo B', 'Grupo de prueba B', 3);

-- Heladeras
INSERT INTO devices (name, location, user_id, group_id, min_temp, max_temp) VALUES
('Heladera A1', 'Depósito 1', 2, 1, 2, 8),
('Heladera A2', 'Cámara 2', 2, 1, 1, 7),
('Heladera B1', 'Sucursal Norte', 3, 2, 0, 6),
('Heladera B2', 'Sucursal Sur', 3, 2, 3, 9);

-- Temperaturas
INSERT INTO temperatures (device_id, temperature, recorded_at) VALUES
(1, 3.5, NOW() - INTERVAL 15 MINUTE),
(1, 9.1, NOW()),
(2, 0.8, NOW()),
(3, 7.2, NOW() - INTERVAL 1 HOUR),
(4, 10.5, NOW());

-- Alertas
INSERT INTO alerts (device_id, temperature, type) VALUES
(1, 9.1, 'TEMP_HIGH'),
(2, 0.8, 'TEMP_LOW'),
(4, 10.5, 'TEMP_HIGH');
