<?php
// Tambahkan ini ke config/services.php yang sudah ada di Laravel:
return [
    // ... existing services ...

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
    ],
];
