<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // NFR-08 — credential documents are never publicly readable.
        // Served only through signed, short-lived URLs to holder or admin.
        'credentials' => [
            'driver' => 'local',
            'root' => storage_path('app/private/credentials'),
            'visibility' => 'private',
            'throw' => false,
        ],

        // Bank-transfer proof of payment — same reasoning as credentials:
        // a buyer's bank receipt is never publicly readable.
        'payment_proofs' => [
            'driver' => 'local',
            'root' => storage_path('app/private/payment-proofs'),
            'visibility' => 'private',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
