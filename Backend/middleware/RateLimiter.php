<?php

namespace Middleware;

use Helpers\AuditLogger;
use Helpers\Logger;
use Helpers\Response;
use PDO;

class RateLimiter
{
    public static function enforce(
        PDO $db,
        string $bucket,
        int $limit,
        int $windowSeconds,
        ?string $identity = null
    ): void {
        if ($limit <= 0 || $windowSeconds <= 0) {
            return;
        }

        $identityValue = $identity ?: self::clientIp();
        $identityHash = hash('sha256', $bucket . '|' . strtolower(trim($identityValue)));
        $ipAddress = self::clientIp();

        self::cleanup($db, $windowSeconds);

        $stmt = $db->prepare("
            INSERT INTO rate_limit_events (bucket, identity_hash, ip_address)
            VALUES (:bucket, :identity_hash, :ip_address)
        ");
        $stmt->execute([
            ':bucket' => $bucket,
            ':identity_hash' => $identityHash,
            ':ip_address' => $ipAddress,
        ]);

        $count = self::countRecent($db, $bucket, $identityHash, $windowSeconds);
        if ($count <= $limit) {
            return;
        }

        Logger::security('Rate limit exceeded', [
            'bucket' => $bucket,
            'ip' => $ipAddress,
            'count' => $count,
            'limit' => $limit,
            'window_seconds' => $windowSeconds,
        ]);
        AuditLogger::event('rate_limit_exceeded', 'Rate limit excedido', 'warning', [
            'bucket' => $bucket,
            'count' => $count,
            'limit' => $limit,
            'window_seconds' => $windowSeconds,
        ], null, 'rate_limit', $bucket, 'block');

        header('Retry-After: ' . $windowSeconds);
        Response::json(429, 'RATE_LIMITED', [
            'retry_after_seconds' => $windowSeconds,
        ]);
    }

    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private static function countRecent(PDO $db, string $bucket, string $identityHash, int $windowSeconds): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM rate_limit_events
            WHERE bucket = :bucket
              AND identity_hash = :identity_hash
              AND created_at >= :cutoff
        ");
        $stmt->bindValue(':bucket', $bucket);
        $stmt->bindValue(':identity_hash', $identityHash);
        $stmt->bindValue(':cutoff', $cutoff);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    private static function cleanup(PDO $db, int $windowSeconds): void
    {
        if (random_int(1, 100) !== 1) {
            return;
        }

        $cutoff = date('Y-m-d H:i:s', time() - max($windowSeconds * 2, 86400));
        $stmt = $db->prepare("
            DELETE FROM rate_limit_events
            WHERE created_at < :cutoff
        ");
        $stmt->bindValue(':cutoff', $cutoff);
        $stmt->execute();
    }
}
