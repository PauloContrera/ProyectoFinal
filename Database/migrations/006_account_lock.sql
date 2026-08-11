-- Migracion: bloqueo temporal de cuenta tras intentos fallidos de login.
--
-- Antes, alcanzar ACCOUNT_LOCK_ATTEMPTS dejaba la cuenta bloqueada para siempre
-- (solo se levantaba con un reset de contraseña), lo que la convertia en un
-- vector de denegacion de servicio: bastaba con conocer un username.
--
-- locked_until marca hasta cuando dura el bloqueo:
--   NULL                 = sin ventana de bloqueo activa
--   fecha futura         = cuenta bloqueada
--   fecha pasada         = la limpia el proximo login (clearExpiredLock)
--
-- La duracion sale de ACCOUNT_LOCK_DURATION (minutos). Con 0 se mantiene el
-- comportamiento viejo: bloqueo permanente por umbral de intentos.
--
-- Idempotente: solo agrega la columna y el indice si no existen.

SET @col_locked_until := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'locked_until'
);
SET @sql := IF(@col_locked_until = 0,
  'ALTER TABLE users ADD COLUMN locked_until DATETIME NULL DEFAULT NULL AFTER failed_login_attempts',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_locked_until := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_locked_until'
);
SET @sql := IF(@idx_locked_until = 0,
  'CREATE INDEX idx_users_locked_until ON users (locked_until)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Las cuentas que hoy estan bloqueadas de forma permanente por el umbral viejo
-- quedan liberadas: a partir de ahora el bloqueo lo vuelve a aplicar el login
-- con ventana temporal.
UPDATE users SET failed_login_attempts = 0 WHERE failed_login_attempts > 0;
