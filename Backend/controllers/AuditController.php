<?php

namespace Controllers;

use Helpers\AuditLogger;
use Helpers\Response;
use Middleware\AuthMiddleware;
use PDO;

class AuditController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function requests(): void
    {
        $user = $this->authorize();
        [$where, $params] = $this->buildRequestFilters();
        $limit = $this->limit();

        $stmt = $this->db->prepare(
            "SELECT id, request_id, user_id, method, path, query_string, status_code,
                    success, duration_ms, ip_address, user_agent, request_body_json,
                    response_summary_json, error_message, created_at, completed_at
             FROM api_request_logs
             {$where}
             ORDER BY id DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);

        AuditLogger::event('audit_requests_viewed', 'Consulta de logs HTTP', 'info', [
            'filters' => $_GET,
            'limit' => $limit,
        ], (int)$user['id'], 'api_request_logs', null, 'read');

        Response::json(200, 'SUCCESS', ['items' => $stmt->fetchAll()]);
    }

    public function events(): void
    {
        $user = $this->authorize();
        [$where, $params] = $this->buildEventFilters();
        $limit = $this->limit();

        $stmt = $this->db->prepare(
            "SELECT id, request_id, actor_user_id, event_type, entity_type, entity_id,
                    action, severity, message, metadata_json, ip_address, user_agent, created_at
             FROM audit_events
             {$where}
             ORDER BY id DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);

        AuditLogger::event('audit_events_viewed', 'Consulta de eventos de auditoria', 'info', [
            'filters' => $_GET,
            'limit' => $limit,
        ], (int)$user['id'], 'audit_events', null, 'read');

        Response::json(200, 'SUCCESS', ['items' => $stmt->fetchAll()]);
    }

    public function authEvents(): void
    {
        $user = $this->authorize();
        $limit = $this->limit();
        $where = [];
        $params = [];

        if (!empty($_GET['user_id']) && ctype_digit((string)$_GET['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = (int)$_GET['user_id'];
        }

        if (!empty($_GET['event_type'])) {
            $where[] = 'event_type = ?';
            $params[] = substr((string)$_GET['event_type'], 0, 50);
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare(
            "SELECT id, user_id, event_type, event_message, ip_address, created_at
             FROM event_logs
             {$whereSql}
             ORDER BY id DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);

        AuditLogger::event('audit_auth_events_viewed', 'Consulta de eventos de autenticacion', 'info', [
            'filters' => $_GET,
            'limit' => $limit,
        ], (int)$user['id'], 'event_logs', null, 'read');

        Response::json(200, 'SUCCESS', ['items' => $stmt->fetchAll()]);
    }

    public function changes(): void
    {
        $user = $this->authorize();
        $limit = $this->limit();
        $entity = $_GET['entity'] ?? 'all';
        $allowed = ['all', 'device', 'group', 'stock', 'user'];
        if (!in_array($entity, $allowed, true)) {
            Response::json(400, 'INVALID_DATA');
        }

        $queries = [];
        if ($entity === 'all' || $entity === 'device') {
            $queries[] = "SELECT CAST('device' AS CHAR) COLLATE utf8mb4_unicode_ci AS entity_type,
                                CAST(device_id AS CHAR) COLLATE utf8mb4_unicode_ci AS entity_id,
                                user_id AS actor_user_id,
                                CAST(action AS CHAR) COLLATE utf8mb4_unicode_ci AS action,
                                CAST(field_changed AS CHAR) COLLATE utf8mb4_unicode_ci AS field_changed,
                                CAST(old_value AS CHAR) COLLATE utf8mb4_unicode_ci AS old_value,
                                CAST(new_value AS CHAR) COLLATE utf8mb4_unicode_ci AS new_value,
                                created_at
                         FROM device_change_log";
        }
        if ($entity === 'all' || $entity === 'group') {
            $queries[] = "SELECT CAST('group' AS CHAR) COLLATE utf8mb4_unicode_ci AS entity_type,
                                CAST(group_id AS CHAR) COLLATE utf8mb4_unicode_ci AS entity_id,
                                user_id AS actor_user_id,
                                CAST(action AS CHAR) COLLATE utf8mb4_unicode_ci AS action,
                                CAST(field_changed AS CHAR) COLLATE utf8mb4_unicode_ci AS field_changed,
                                CAST(old_value AS CHAR) COLLATE utf8mb4_unicode_ci AS old_value,
                                CAST(new_value AS CHAR) COLLATE utf8mb4_unicode_ci AS new_value,
                                created_at
                         FROM device_group_change_log";
        }
        if ($entity === 'all' || $entity === 'stock') {
            $queries[] = "SELECT CAST('stock' AS CHAR) COLLATE utf8mb4_unicode_ci AS entity_type,
                                CAST(stock_item_id AS CHAR) COLLATE utf8mb4_unicode_ci AS entity_id,
                                user_id AS actor_user_id,
                                CAST(action AS CHAR) COLLATE utf8mb4_unicode_ci AS action,
                                CAST(field_changed AS CHAR) COLLATE utf8mb4_unicode_ci AS field_changed,
                                CAST(old_value AS CHAR) COLLATE utf8mb4_unicode_ci AS old_value,
                                CAST(new_value AS CHAR) COLLATE utf8mb4_unicode_ci AS new_value,
                                created_at
                         FROM stock_item_change_log";
        }
        if ($entity === 'all' || $entity === 'user') {
            $queries[] = "SELECT CAST('user' AS CHAR) COLLATE utf8mb4_unicode_ci AS entity_type,
                                CAST(user_id AS CHAR) COLLATE utf8mb4_unicode_ci AS entity_id,
                                changed_by AS actor_user_id,
                                CAST('update' AS CHAR) COLLATE utf8mb4_unicode_ci AS action,
                                CAST(field_changed AS CHAR) COLLATE utf8mb4_unicode_ci AS field_changed,
                                CAST(old_value AS CHAR) COLLATE utf8mb4_unicode_ci AS old_value,
                                CAST(new_value AS CHAR) COLLATE utf8mb4_unicode_ci AS new_value,
                                changed_at AS created_at
                         FROM user_change_log";
        }

        $sql = implode(' UNION ALL ', $queries) . " ORDER BY created_at DESC LIMIT {$limit}";
        $stmt = $this->db->query($sql);

        AuditLogger::event('audit_changes_viewed', 'Consulta de cambios de datos', 'info', [
            'entity' => $entity,
            'limit' => $limit,
        ], (int)$user['id'], 'change_logs', null, 'read');

        Response::json(200, 'SUCCESS', ['items' => $stmt->fetchAll()]);
    }

    public function summary(): void
    {
        $user = $this->authorize();
        $hours = max(1, min(168, (int)($_GET['hours'] ?? 24)));

        $requests = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(success = 1) AS successful,
                SUM(status_code >= 400 AND status_code < 500) AS client_errors,
                SUM(status_code >= 500) AS server_errors,
                ROUND(AVG(duration_ms), 2) AS avg_duration_ms,
                MAX(duration_ms) AS max_duration_ms
             FROM api_request_logs
             WHERE created_at >= (NOW() - INTERVAL {$hours} HOUR)"
        );
        $requests->execute();

        $topPaths = $this->db->prepare(
            "SELECT method, path, COUNT(*) AS total, SUM(status_code >= 400) AS errors
             FROM api_request_logs
             WHERE created_at >= (NOW() - INTERVAL {$hours} HOUR)
             GROUP BY method, path
             ORDER BY total DESC
             LIMIT 10"
        );
        $topPaths->execute();

        $events = $this->db->prepare(
            "SELECT severity, COUNT(*) AS total
             FROM audit_events
             WHERE created_at >= (NOW() - INTERVAL {$hours} HOUR)
             GROUP BY severity"
        );
        $events->execute();

        AuditLogger::event('audit_summary_viewed', 'Consulta de resumen de auditoria', 'info', [
            'hours' => $hours,
        ], (int)$user['id'], 'audit_summary', null, 'read');

        Response::json(200, 'SUCCESS', [
            'hours' => $hours,
            'requests' => $requests->fetch(),
            'top_paths' => $topPaths->fetchAll(),
            'events_by_severity' => $events->fetchAll(),
        ]);
    }

    private function authorize(): array
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        if (!in_array($user['role'], ['admin', 'superadmin'], true)) {
            AuditLogger::event('audit_access_denied', 'Intento de consultar auditoria sin permisos', 'warning', [
                'role' => $user['role'],
            ], (int)$user['id'], 'audit', null, 'deny');
            Response::json(403, 'ACCESS_DENIED');
        }

        return $user;
    }

    private function buildRequestFilters(): array
    {
        $where = [];
        $params = [];

        if (!empty($_GET['request_id'])) {
            $where[] = 'request_id = ?';
            $params[] = substr((string)$_GET['request_id'], 0, 32);
        }

        if (!empty($_GET['method'])) {
            $where[] = 'method = ?';
            $params[] = strtoupper(substr((string)$_GET['method'], 0, 10));
        }

        if (!empty($_GET['path'])) {
            $where[] = 'path LIKE ?';
            $params[] = '%' . substr((string)$_GET['path'], 0, 200) . '%';
        }

        if (!empty($_GET['status']) && ctype_digit((string)$_GET['status'])) {
            $where[] = 'status_code = ?';
            $params[] = (int)$_GET['status'];
        }

        if (!empty($_GET['user_id']) && ctype_digit((string)$_GET['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = (int)$_GET['user_id'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function buildEventFilters(): array
    {
        $where = [];
        $params = [];

        foreach (['event_type', 'entity_type', 'severity', 'action'] as $field) {
            if (!empty($_GET[$field])) {
                $where[] = "{$field} = ?";
                $params[] = substr((string)$_GET[$field], 0, 100);
            }
        }

        if (!empty($_GET['user_id']) && ctype_digit((string)$_GET['user_id'])) {
            $where[] = 'actor_user_id = ?';
            $params[] = (int)$_GET['user_id'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function limit(): int
    {
        return max(1, min(200, (int)($_GET['limit'] ?? 100)));
    }
}
