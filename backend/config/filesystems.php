<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            /*
             * Folder public backend TIDAK lagi di-expose lewat symlink
             * public/storage, karena frontend sekarang berada di server
             * terpisah. Semua file di-disini disajikan lewat endpoint
             * bertanda tangan /api/files/{path} -- lihat app/Support/FileUrl.php.
             */
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Masa Berlaku Signed URL File
    |--------------------------------------------------------------------------
    |
    | Berapa detik URL dari /api/files/{path} yang dibuat lewat
    | App\Support\FileUrl tetap dianggap sah.
    |
    | Default 600 detik (10 menit) cukup untuk halaman ERP yang sudah
    | ter-render, dan membatasi dampak kalau URL bocor lewat screenshot
    | atau log browser. Jangan dibuat terlalu lama: file di disk `public`
    | berisi foto KTP penyewa, foto kondisi kendaraan, dan tanda tangan,
    | jadi bukan aset publik.
    |
    */

    'file_url_ttl' => (int) env('FILE_URL_TTL', 600),

];
