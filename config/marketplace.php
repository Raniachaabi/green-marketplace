<?php

return [
    'currency' => env('MARKETPLACE_CURRENCY', 'TND'),

    // TND is a 3-decimal (millime) currency. Money is stored in millimes as
    // integers everywhere in this codebase; never store dinars as floats.
    'currency_decimals' => 3,

    'vat_rate' => (float) env('MARKETPLACE_VAT_RATE', 19),

    'commission_rate' => (float) env('MARKETPLACE_COMMISSION_RATE', 10),

    // FR-025 — days before expiry at which sellers are reminded.
    'credential_reminder_days' => array_map(
        'intval',
        explode(',', (string) env('MARKETPLACE_CREDENTIAL_REMINDER_DAYS', '60,30,7'))
    ),

    // FR-069 / NFR-09 — minors' medical data is hard-deleted this many days
    // after the visit. Keep this short.
    'participant_purge_days' => (int) env('MARKETPLACE_PARTICIPANT_PURGE_DAYS', 30),

    'locales' => [
        'ar' => ['name' => 'العربية', 'dir' => 'rtl'],
        'fr' => ['name' => 'Français', 'dir' => 'ltr'],
        'en' => ['name' => 'English',  'dir' => 'ltr'],
    ],

    // The 24 Tunisian governorates — used for addresses and delivery zones.
    'governorates' => [
        'ariana', 'beja', 'ben_arous', 'bizerte', 'gabes', 'gafsa', 'jendouba',
        'kairouan', 'kasserine', 'kebili', 'kef', 'mahdia', 'manouba', 'medenine',
        'monastir', 'nabeul', 'sfax', 'sidi_bouzid', 'siliana', 'sousse',
        'tataouine', 'tozeur', 'tunis', 'zaghouan',
    ],
];
