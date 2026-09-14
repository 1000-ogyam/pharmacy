<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Core\Database;
use RuntimeException;
use Throwable;

final class SmsQueueProcessor
{
    public function __construct(
        private readonly SmsGateway $gateway = new ArkeselSmsGateway(),
    ) {
    }

    public static function make(): self
    {
        return new self(SmsGatewayFactory::make());
    }

    /**
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function process(int $limit = 50): array
    {
        $db = Database::instance();
        $pending = $db->fetchAll(
            "SELECT * FROM sms_queue WHERE status = 'pending' AND attempts < 5 ORDER BY id ASC LIMIT " . max(1, $limit)
        );

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($pending as $row) {
            try {
                $result = $this->gateway->send((string) $row['to_number'], (string) $row['message']);
                if (empty($result['ok'])) {
                    throw new RuntimeException((string) ($result['error'] ?? 'Gateway rejected the message.'));
                }

                $simulated = !empty($result['simulated']);
                $status = $simulated ? 'sent' : 'sent';
                $db->execute(
                    'UPDATE sms_queue SET status = :status, attempts = attempts + 1, sent_at = :sent_at, updated_at = :updated, provider_message_id = :mid WHERE id = :id',
                    [
                        ':status' => $status,
                        ':sent_at' => date('Y-m-d H:i:s'),
                        ':updated' => date('Y-m-d H:i:s'),
                        ':mid' => (string) ($result['message_id'] ?? $result['response']['messageId'] ?? ($simulated ? 'local' : '')),
                        ':id' => $row['id'],
                    ]
                );
                $db->execute(
                    'INSERT INTO sms_log (sms_queue_id, to_number, message, status, response_json, created_at, updated_at)
                     VALUES (:qid, :to_number, :message, :status, :response, :created, :updated)',
                    [
                        ':qid' => $row['id'],
                        ':to_number' => $row['to_number'],
                        ':message' => $row['message'],
                        ':status' => $simulated ? 'simulated' : 'sent',
                        ':response' => json_encode($result),
                        ':created' => date('Y-m-d H:i:s'),
                        ':updated' => date('Y-m-d H:i:s'),
                    ]
                );
                $sent++;
            } catch (Throwable $e) {
                $attempts = (int) $row['attempts'] + 1;
                $status = $attempts >= 5 ? 'failed' : 'pending';
                $db->execute(
                    'UPDATE sms_queue SET status = :status, attempts = :attempts, updated_at = :updated WHERE id = :id',
                    [
                        ':status' => $status,
                        ':attempts' => $attempts,
                        ':updated' => date('Y-m-d H:i:s'),
                        ':id' => $row['id'],
                    ]
                );
                $db->execute(
                    'INSERT INTO sms_log (sms_queue_id, to_number, message, status, response_json, created_at, updated_at)
                     VALUES (:qid, :to_number, :message, :status, :response, :created, :updated)',
                    [
                        ':qid' => $row['id'],
                        ':to_number' => $row['to_number'],
                        ':message' => $row['message'],
                        ':status' => 'failed',
                        ':response' => json_encode(['error' => $e->getMessage()]),
                        ':created' => date('Y-m-d H:i:s'),
                        ':updated' => date('Y-m-d H:i:s'),
                    ]
                );
                if ($status === 'failed') {
                    $failed++;
                } else {
                    $skipped++;
                }
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped];
    }
}
