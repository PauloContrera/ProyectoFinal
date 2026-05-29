<?php

namespace Helpers;

class Logger
{
    const LOG_DIR = __DIR__ . '/../logs';
    const LOG_FILE = 'app.log';
    const ERROR_FILE = 'errors.log';
    const SECURITY_FILE = 'security.log';

    // Niveles de log
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    const SECURITY = 'SECURITY';

    /**
     * Log general con nivel
     */
    public static function log($level, $message, $context = [])
    {
        self::ensureLogDirectory();

        $timestamp = date('Y-m-d H:i:s');
        $userId = $_SERVER['user']['id'] ?? 'anonymous';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'user_id' => $userId,
            'ip' => $ip,
            'message' => $message,
            'context' => $context
        ];

        $logString = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";

        // Determinar archivo según nivel
        $file = match($level) {
            self::ERROR, self::CRITICAL => self::ERROR_FILE,
            self::SECURITY => self::SECURITY_FILE,
            default => self::LOG_FILE
        };

        $logPath = self::LOG_DIR . '/' . $file;
        file_put_contents($logPath, $logString, FILE_APPEND | LOCK_EX);

        // Enviar a stderr en desarrollo
        if (getenv('APP_ENV') === 'development') {
            fwrite(STDERR, "[{$level}] {$message}\n");
        }
    }

    /**
     * Log de información
     */
    public static function info($message, $context = [])
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log de advertencia
     */
    public static function warning($message, $context = [])
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log de error
     */
    public static function error($message, $context = [])
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log crítico (falla de seguridad, etc)
     */
    public static function critical($message, $context = [])
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Log de seguridad (intentos de acceso, cambios sensibles)
     */
    public static function security($message, $context = [])
    {
        self::log(self::SECURITY, $message, $context);
    }

    /**
     * Log de excepción
     */
    public static function exception(\Throwable $exception, $context = [])
    {
        self::error('Exception: ' . $exception->getMessage(), array_merge($context, [
            'exception_type' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]));
    }

    /**
     * Log de evento de usuario (login, cambios de datos)
     */
    public static function userEvent($userId, $event, $details = [])
    {
        self::log(self::INFO, "User Event: {$event}", array_merge([
            'user_id' => $userId,
            'event' => $event
        ], $details));
    }

    /**
     * Log de cambio de datos (auditoría)
     */
    public static function audit($userId, $entity, $action, $before = [], $after = [])
    {
        self::log(self::INFO, "Audit: {$action} on {$entity}", [
            'user_id' => $userId,
            'entity' => $entity,
            'action' => $action,
            'before' => $before,
            'after' => $after
        ]);
    }

    /**
     * Limpia logs antiguos (más de 30 días)
     */
    public static function cleanup($daysOld = 30)
    {
        self::ensureLogDirectory();

        $files = glob(self::LOG_DIR . '/*.log');
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }

    /**
     * Obtiene últimas líneas de un log
     */
    public static function getTail($file = self::LOG_FILE, $lines = 50)
    {
        $path = self::LOG_DIR . '/' . $file;
        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $logLines = array_filter(explode("\n", $content));

        return array_slice($logLines, -$lines);
    }

    /**
     * Asegura que el directorio de logs existe
     */
    private static function ensureLogDirectory()
    {
        if (!is_dir(self::LOG_DIR)) {
            mkdir(self::LOG_DIR, 0755, true);
        }
    }
}
