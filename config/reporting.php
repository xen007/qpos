<?php

return [
    'timezone' => 'Africa/Douala',
    'max_export_rows' => 10000,
    'delivery_attempts' => 5,
    'mailer' => env('REPORT_MAILER', env('MAIL_MAILER', 'smtp')),
    'smtp_timeout' => 20,
];
