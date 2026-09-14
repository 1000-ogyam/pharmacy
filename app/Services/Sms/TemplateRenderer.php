<?php

declare(strict_types=1);

namespace App\Services\Sms;

final class TemplateRenderer
{
    public function render(string $body, array $fields): string
    {
        foreach ($fields as $key => $value) {
            $body = str_replace('{{' . $key . '}}', (string) $value, $body);
        }

        return $body;
    }
}
