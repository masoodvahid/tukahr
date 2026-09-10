<?php
return [
    'otp_ttl_seconds' => (int) env('OTP_TTL_SECONDS', 120),
    'otp_max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'otp_resend_seconds' => (int) env('OTP_RESEND_SECONDS', 60),
];
