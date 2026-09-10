<?php
return [
    'kavenegar' => [
        'api_key' => env('KAVENEGAR_API_KEY'),
        'otp_template' => env('KAVENEGAR_OTP_TEMPLATE', 'tukahr-login'),
        'approval_template' => env('KAVENEGAR_APPROVAL_TEMPLATE', 'tukahr-approval'),
        'low_credit_threshold' => (int) env('KAVENEGAR_LOW_CREDIT_THRESHOLD', 100000),
        'timeout_seconds' => (int) env('KAVENEGAR_TIMEOUT_SECONDS', 8),
    ],
];
