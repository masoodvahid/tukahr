<?php
return [
    'kavenegar' => [
        'api_key' => env('KAVENEGAR_API_KEY'),
        'otp_template' => env('KAVENEGAR_OTP_TEMPLATE', 'tukahr-login'),
        'approval_template' => env('KAVENEGAR_APPROVAL_TEMPLATE', 'tukahr-approval'),
    ],
];
