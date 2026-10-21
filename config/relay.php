<?php

return [
    'contact_email' => env('RELAY_CONTACT_EMAIL', 'info@tanami.ly'),

    'attachments' => [
        // Any configured filesystem disk; "local" is private (storage/app/private). Use "s3" in production if you prefer.
        'disk' => env('ATTACHMENTS_DISK', 'local'),
        // Per-file limit in KB. PHP's upload_max_filesize / post_max_size must be at least this.
        'max_kb' => (int) env('ATTACHMENTS_MAX_KB', 20480),
    ],
];
