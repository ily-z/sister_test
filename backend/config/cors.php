<?php

/**
 * CORS untuk arsitektur 3-server.
 *
 * Frontend (Vue SPA, nginx statis) dan backend (Laravel API) berada di
 * server berbeda, jadi browser menganggapnya cross-origin. Tanpa
 * header di bawah, semua request dari SPA akan diblokir browser
 * sebelum sampai ke PHP.
 *
 * Kenapa wildcard origin aman di sini:
 *   Aplikasi memakai Sanctum BEARER TOKEN (header Authorization),
 *   bukan cookie sesi. Dengan bearer token:
 *     - Browser tidak otomatis mengirim kredensial lintas origin
 *       (supports_credentials = false), jadi tidak ada risiko
 *       request dari situs lain ikut membawa kredensial user.
 *     - Tidak butuh SameSite=None / Secure, sehingga tidak butuh
 *       HTTPS. Ini kunci supaya aplikasi tetap jalan di HTTP biasa
 *       dengan IP address seadanya.
 *     - Tidak butuh domain yang sama antara frontend dan backend.
 *
 * Kalau nanti aplikasi sudah punya HTTPS + domain sendiri, persempit
 * allowed_origins ke domain itu saja lewat env FRONTEND_ORIGINS:
 *     FRONTEND_ORIGINS=https://app.royal-tour.test
 */

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FRONTEND_ORIGINS', '*'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    /*
     * PENTING: tetap false. Browser tidak mengirim cookie lintas origin
     * dalam mode ini, dan kombinasi SameSite=None dengan wildcard origin
     * akan ditolak browser (RFC 6265bis). Backend ini memang tidak
     * bergantung pada cookie -- semua auth lewat header Authorization.
     */
    'supports_credentials' => false,

    'max_age' => 0,

    'preflight_cache' => false,

];
