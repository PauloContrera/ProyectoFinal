-- Tabla de usuarios
CREATE TABLE users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    phone_number VARCHAR(20),
    role VARCHAR(50) NOT NULL
);

-- Tabla de heladeras
CREATE TABLE fridges (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    min_temp DECIMAL(5, 2) NOT NULL,
    max_temp DECIMAL(5, 2) NOT NULL,
    client_id BIGINT,
    FOREIGN KEY (client_id) REFERENCES users(id)
);

-- Tabla de grupos de heladeras
CREATE TABLE fridge_groups (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    client_id BIGINT,
    description TEXT,
    FOREIGN KEY (client_id) REFERENCES users(id)
);

-- Tabla de miembros de grupos de heladeras
CREATE TABLE fridge_group_members (
    fridge_id BIGINT,
    group_id BIGINT,
    PRIMARY KEY (fridge_id, group_id),
    FOREIGN KEY (fridge_id) REFERENCES fridges(id),
    FOREIGN KEY (group_id) REFERENCES fridge_groups(id)
);

-- Tabla de registros de temperatura
CREATE TABLE temperature_records (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    fridge_id BIGINT,
    temperature DECIMAL(5, 2) NOT NULL,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fridge_id) REFERENCES fridges(id)
);

-- Tabla de alertas
CREATE TABLE alerts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    fridge_id BIGINT,
    temperature_record_id BIGINT,
    alert_type VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fridge_id) REFERENCES fridges(id),
    FOREIGN KEY (temperature_record_id) REFERENCES temperature_records(id)
);

-- Tabla de permisos de acceso
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

-- Insertar datos de ejemplo
INSERT INTO users (username, password, email, phone_number, role) VALUES 
('superadmin', 'password1', 'superadmin@example.com', '1234567890', 'Super Admin'),
('admin', 'password2', 'admin@example.com', '0987654321', 'Admin'),
('cliente1', 'password3', 'cliente1@example.com', '1122334455', 'Cliente'),
('visitante1', 'password4', 'visitante1@example.com', '5566778899', 'Visitante');

INSERT INTO fridges (name, location, min_temp, max_temp, client_id) VALUES 
('Heladera 1', 'Ubicación 1', 2.0, 8.0, 3),
('Heladera 2', 'Ubicación 2', 1.0, 7.0, 3);

INSERT INTO fridge_groups (name, description, client_id) VALUES 
('Grupo 1', 'Descripción del Grupo 1', 3);

INSERT INTO fridge_group_members (fridge_id, group_id) VALUES 
(1, 1),
(2, 1);

-- Insertar registros de temperatura para un día completo (96 registros por heladera)
DELIMITER $$
CREATE PROCEDURE insert_temp_records()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE temp DECIMAL(5, 2);


    WHILE i < 6 DO  -- Insertar solo 6 registros para simplificar
        -- Generar temperatura aleatoria entre 2 y 8 grados, con algunos valores fuera de rango
        SET temp = 2 + (RAND() * 6);
        IF RAND() < 0.1 THEN
            SET temp = temp + (RAND() * 2 - 1); -- Añadir o restar hasta 1 grado para algunos registros
        END IF;

        -- Insertar registro para Heladera 1
        INSERT INTO temperature_records (fridge_id, temperature, recorded_at)
        VALUES (1, temp, NOW());

        -- Generar temperatura para Heladera 2
        SET temp = 2 + (RAND() * 6);
        IF RAND() < 0.1 THEN
            SET temp = temp + (RAND() * 2 - 1);
        END IF;

        -- Insertar registro para Heladera 2
        INSERT INTO temperature_records (fridge_id, temperature, recorded_at)
        VALUES (2, temp, NOW());

        -- Incrementar el contador
        SET i = i + 1;
    END WHILE;
END $$
DELIMITER ;

CALL insert_temp_records();


-- Insertar alertas basadas en los registros de temperatura
INSERT INTO alerts (fridge_id, temperature_record_id, alert_type)
SELECT fridge_id, id, 'Excede el límite'
FROM temperature_records
WHERE temperature < 2 OR temperature > 8;

-- Insertar permisos de acceso
INSERT INTO access_permissions (client_id, visitor_id, fridge_id, can_view, can_receive_alerts) VALUES 
(3, 4, 1, TRUE, TRUE),
(3, 4, 2, TRUE, FALSE);
