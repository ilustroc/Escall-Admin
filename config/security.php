<?php

return [
    'login' => [
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('LOGIN_DECAY_SECONDS', 900),
        'ip_max_attempts_per_minute' => (int) env(
            'LOGIN_IP_MAX_ATTEMPTS_PER_MINUTE',
            60,
        ),
    ],

    'headers' => [
        'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
    ],
];
