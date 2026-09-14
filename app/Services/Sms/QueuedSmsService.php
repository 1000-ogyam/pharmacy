<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Core\Database;
use App\Models\Customer;
use App\Models\SmsTemplate;

final class QueuedSmsService implements SmsService
{
    public function __construct(
        private readonly TemplateRenderer $renderer = new TemplateRenderer(),
    ) {
    }

    public function send(string $to, string $message, ?string $templateKey = null, ?int $customerId = null): void
    {
        $transactional = (array) config('sms.transactional_templates', []);
        $isTransactional = $templateKey !== null && in_array($templateKey, $transactional, true);

        if ($customerId && !$isTransactional) {
            $customer = Customer::find($customerId);
            if ($customer && !(int) $customer->sms_opt_in) {
                return;
            }
        }

        if ($templateKey) {
            $template = SmsTemplate::where('key', $templateKey)->where('is_active', 1)->first();
            if ($template) {
                $message = $this->renderer->render((string) $template->body, []);
            }
        }

        Database::instance()->execute(
            'INSERT INTO sms_queue (`customer_id`, `to_number`, `message`, `template_key`, `status`, `attempts`, `created_at`, `updated_at`)
             VALUES (:customer_id, :to_number, :message, :template_key, :status, 0, :created_at, :updated_at)',
            [
                ':customer_id' => $customerId,
                ':to_number' => $to,
                ':message' => $message,
                ':template_key' => $templateKey,
                ':status' => 'pending',
                ':created_at' => date('Y-m-d H:i:s'),
                ':updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public function sendTemplate(string $to, string $templateKey, array $fields, ?int $customerId = null): void
    {
        $template = SmsTemplate::where('key', $templateKey)->where('is_active', 1)->first();
        $body = $template ? $this->renderer->render((string) $template->body, $fields) : implode(' ', $fields);
        $this->send($to, $body, $templateKey, $customerId);
    }
}
