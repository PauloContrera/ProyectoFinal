<?php

namespace Helpers;

use PDO;
use Throwable;

class AuditLogger
{
    private static ?PDO $db = null;
    private static ?string $requestId = null;
    private static float $startedAt = 0.0;
    private static bool $requestStarted = false;
    private static bool $requestFinished = false;
    private static ?array $fatalError = null;

    public static function startRequest(PDO $db): void
    {
        if (self::$requestStarted) {
            return;
        }

        self::$db = $db;
        self::$requestStarted = true;
        self::$startedAt = microtime(true);
        self::$requestId = self::createRequestId();
        $_SERVER['REQUEST_ID'] = self::$requestId;

        header('X-Request-ID: ' . self::$requestId);
        Logger::info('API request started', self::requestContext());
    }

    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = self::createRequestId();
            $_SERVER['REQUEST_ID'] = self::$requestId;
        }

        return self::$requestId;
    }

    public static function finishRequest(): void
    {
        if (!self::$requestStarted || self::$requestFinished) {
            return;
        }

        self::$requestFinished = true;
        $statusCode = http_response_code() ?: 200;
        $durationMs = (int)round((microtime(true) - self::$startedAt) * 1000);
        $responseBody = self::currentResponseBody();
        $responseSummary = self::summarizeResponse($responseBody);
        $error = self::$fatalError;

        if ($error !== null && $statusCode < 500) {
            $statusCode = 500;
        }

        $context = array_merge(self::requestContext(), [
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'response' => $responseSummary,
            'fatal_error' => $error,
        ]);

        $level = self::levelForStatus($statusCode);
        Logger::log($level, 'API request finished', $context);
        self::insertRequestLog($statusCode, $durationMs, $responseSummary, $error);
    }

    public static function recordException(Throwable $exception): void
    {
        self::$fatalError = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        Logger::exception($exception, self::requestContext());
        self::event('unhandled_exception', 'Excepcion no controlada en API', 'critical', [
            'exception' => self::$fatalError,
        ]);
    }

    public static function recordFatalError(array $error): void
    {
        self::$fatalError = [
            'type' => 'fatal_error',
            'message' => $error['message'] ?? 'Error fatal',
            'file' => $error['file'] ?? null,
            'line' => $error['line'] ?? null,
        ];

        Logger::critical('Fatal PHP error', array_merge(self::requestContext(), [
            'error' => self::$fatalError,
        ]));
        self::event('fatal_error', 'Error fatal de PHP', 'critical', [
            'error' => self::$fatalError,
        ]);
    }

    public static function event(
        string $eventType,
        string $message,
        string $severity = 'info',
        array $metadata = [],
        ?int $actorUserId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $action = null
    ): void {
        $db = self::$db;
        if (!$db) {
            Logger::log(strtoupper($severity), $message, [
                'event_type' => $eventType,
                'metadata' => $metadata,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
            ]);
            return;
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO audit_events (
                    request_id, actor_user_id, event_type, entity_type, entity_id,
                    action, severity, message, metadata_json, ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                self::requestId(),
                $actorUserId ?? self::currentUserId(),
                $eventType,
                $entityType,
                $entityId,
                $action,
                self::normalizeSeverity($severity),
                $message,
                self::json(Logger::sanitize($metadata)),
                self::clientIp(),
                self::userAgent(),
            ]);
        } catch (Throwable $exception) {
            Logger::warning('Audit event DB insert failed', [
                'event_type' => $eventType,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private static function insertRequestLog(int $statusCode, int $durationMs, array $responseSummary, ?array $error): void
    {
        if (!self::$db) {
            return;
        }

        try {
            $stmt = self::$db->prepare(
                "INSERT INTO api_request_logs (
                    request_id, user_id, method, path, query_string, status_code, success,
                    duration_ms, ip_address, user_agent, request_body_json, response_summary_json,
                    error_message, completed_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                self::requestId(),
                self::currentUserId(),
                $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                self::path(),
                self::queryString(),
                $statusCode,
                $statusCode >= 200 && $statusCode < 400 ? 1 : 0,
                $durationMs,
                self::clientIp(),
                self::userAgent(),
                self::requestBodyForLog(),
                self::json($responseSummary),
                $error['message'] ?? null,
            ]);
        } catch (Throwable $exception) {
            Logger::warning('API request DB log insert failed', [
                'error' => $exception->getMessage(),
                'request_id' => self::requestId(),
            ]);
        }
    }

    private static function requestContext(): array
    {
        return [
            'request_id' => self::requestId(),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'path' => self::path(),
            'query_string' => self::queryString(),
            'user_id' => self::currentUserId(),
            'ip' => self::clientIp(),
            'user_agent' => self::userAgent(),
        ];
    }

    private static function requestBodyForLog(): ?string
    {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return self::json([
                '_raw_sha256' => hash('sha256', $raw),
                '_raw_bytes' => strlen($raw),
                '_json_error' => json_last_error_msg(),
            ]);
        }

        return self::json(Logger::sanitize($decoded));
    }

    private static function summarizeResponse(?string $body): array
    {
        if ($body === null || trim($body) === '') {
            return ['bytes' => 0];
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [
                'bytes' => strlen($body),
                'sha256' => hash('sha256', $body),
            ];
        }

        $summary = [
            'success' => $decoded['success'] ?? null,
            'status' => $decoded['status'] ?? null,
            'message' => $decoded['message'] ?? null,
            'error_code' => $decoded['error']['code'] ?? null,
        ];

        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $summary['data_keys'] = array_slice(array_keys($decoded['data']), 0, 20);
        }

        return Logger::sanitize($summary);
    }

    private static function currentResponseBody(): ?string
    {
        if (ob_get_level() <= 0) {
            return null;
        }

        $body = ob_get_contents();
        return $body === false ? null : $body;
    }

    private static function createRequestId(): string
    {
        return bin2hex(random_bytes(8));
    }

    private static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return self::truncate($path, 255);
    }

    private static function queryString(): ?string
    {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        if ($query === '') {
            return null;
        }

        parse_str($query, $params);
        if ($params) {
            $query = http_build_query(Logger::sanitize($params), '', '&', PHP_QUERY_RFC3986);
        }

        return self::truncate($query, 500);
    }

    private static function currentUserId(): ?int
    {
        return isset($_SERVER['user']['id']) ? (int)$_SERVER['user']['id'] : null;
    }

    private static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private static function userAgent(): ?string
    {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        return $agent ? self::truncate($agent, 500) : null;
    }

    private static function truncate(string $value, int $maxLength): string
    {
        return strlen($value) > $maxLength ? substr($value, 0, $maxLength) : $value;
    }

    private static function json($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function normalizeSeverity(string $severity): string
    {
        return in_array($severity, ['info', 'warning', 'error', 'critical'], true) ? $severity : 'info';
    }

    private static function levelForStatus(int $statusCode): string
    {
        if ($statusCode >= 500) return Logger::ERROR;
        if ($statusCode >= 400) return Logger::WARNING;
        return Logger::INFO;
    }
}
