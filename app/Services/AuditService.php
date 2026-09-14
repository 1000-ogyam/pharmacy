<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AuditService
{
    public static function log(string $table, int $recordId, string $action, ?array $before, ?array $after): void
    {
        if ($table === 'audit_log') {
            return;
        }

        $sql = 'INSERT INTO `audit_log`
            (`user_id`, `table_name`, `record_id`, `action`, `before_json`, `after_json`, `ip_address`, `created_at`)
            VALUES (:user_id, :table_name, :record_id, :action, :before_json, :after_json, :ip_address, :created_at)';

        try {
            Database::instance()->execute($sql, [
                ':user_id' => auth()->id(),
                ':table_name' => $table,
                ':record_id' => $recordId,
                ':action' => $action,
                ':before_json' => $before === null ? null : json_encode(self::safe($before)),
                ':after_json' => $after === null ? null : json_encode(self::safe($after)),
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }

    private static function safe(array $data): array
    {
        unset($data['password'], $data['remember_token']);
        return $data;
    }
}
