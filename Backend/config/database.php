<?php

namespace Config;

use PDO;
use PDOException;
use Helpers\Logger;

class Database
{
    private static ?PDO $instance = null;
    private static string $host;
    private static string $db_name;
    private static string $username;
    private static string $password;

    public function __construct()
    {
        // Prevenir instanciación directa - usar getInstance()
    }

    /**
     * Obtiene instancia singleton de la conexión
     * Optimización: una sola conexión por request
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Conecta a la base de datos (llamado una sola vez)
     */
    private static function connect(): void
    {
        self::$host = $_ENV['DB_HOST'] ?? 'localhost';
        self::$db_name = $_ENV['DB_NAME'] ?? '';
        self::$username = $_ENV['DB_USER'] ?? '';
        self::$password = $_ENV['DB_PASS'] ?? '';

        try {
            self::$instance = new PDO(
                "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4",
                self::$username,
                self::$password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );

            Logger::info('Database connection established', [
                'host' => self::$host,
                'database' => self::$db_name
            ]);
        } catch (PDOException $e) {
            Logger::critical('Database connection failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database connection error',
                'status' => 500
            ]);
            exit(1);
        }
    }

    /**
     * Para compatibilidad con código existente
     * Retorna la instancia singleton
     */
    public function getConnection(): PDO
    {
        return self::getInstance();
    }

    /**
     * Cierra la conexión (para testing/cleanup)
     */
    public static function disconnect(): void
    {
        self::$instance = null;
    }

    /**
     * Test de conexión
     */
    public static function test(): bool
    {
        try {
            $conn = self::getInstance();
            $conn->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            Logger::error('Database test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
