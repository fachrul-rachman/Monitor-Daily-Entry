<?php

return [
    // API base sesuai contoh:
    // https://use.api.co.id/holidays/indonesia/?year=2025&type=Public%20Holiday&page=1
    'base_url' => env('HOLIDAYS_API_BASE_URL', 'https://use.api.co.id/holidays/indonesia/'),
    'api_key' => env('HOLIDAYS_API_CO_ID', ''),

    // Default filter untuk sinkronisasi (sesuai kebutuhan: tanggal merah saja).
    'default_type' => env('HOLIDAYS_API_TYPE', 'Public Holiday'),
];

