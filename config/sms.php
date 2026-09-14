<?php

declare(strict_types=1);

return [
    'gateway' => env('SMS_GATEWAY', 'arkesel'),
    'api_key' => env('SMS_API_KEY', ''),
    'sender_id' => env('SMS_SENDER_ID', 'PLPharma'),
    'sandbox' => filter_var(env('SMS_SANDBOX', 'false'), FILTER_VALIDATE_BOOLEAN),
    'arkesel_url' => env('SMS_ARKESEL_URL', 'https://sms.arkesel.com/api/v2/sms/send'),
    'transactional_templates' => [
        'otp',
        'password_reset',
        'legal_notice',
        'receipt',
        'invoice',
        'expiry_alert',
    ],
];
