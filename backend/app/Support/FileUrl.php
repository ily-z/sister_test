<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Pembuat URL file yang bisa diakses frontend SPA di server terpisah.
 *
 * MASALAH YANG DISOLUSIKAN
 * ------------------------
 * Dulu frontend (Blade di server yang sama) memuat file lewat
 * `asset('storage/' . $path)`, yang bergantung pada symlink
 * `public/storage -> storage/app/public`. Pendekatan itu tidak bisa
 * dipakai lagi karena:
 *
 *   1. Backend sekarang berada di server sendiri. Folder public backend
 *      tidak boleh di-expose langsung ke browser.
 *   2. Symlink `public/storage` sebelumnya menunjuk ke path laptop
 *      (/home/ilyas/dev/royal-tour-travel/...) dan langsung rusak
 *      begitu dipindah ke server lain.
 *
 * SOLUSI
 * ------
 * Semua file disajikan lewat endpoint bertanda tangan:
 *     GET /api/files/{path}?expires=...&signature=...
 *
 * Kenapa signed URL, bukan `?token=` di query string:
 *   - Tag <img src> / <a href> TIDAK bisa mengirim header Authorization,
 *     jadi token tidak bisa dikirim lewat header. Signed URL menyelesaikannya
 *     karena browser tetap mengirim query string biasa.
 *   - Query `?token=<jwt>` yang bocor akan masuk ke nginx access log dan
 *     bisa terkirim lewat header Referer ke situs lain. Signed URL punya
 *     masa berlaku pendek dan isinya hanya berlaku untuk satu path file.
 *
 * MASA BERLAKU
 * ------------
 * Default 10 menit (lihat config/filesystems.php -> file_url_ttl).
 * Cukup untuk halaman ERP yang sudah ter-render, dan membatasi dampak
 * kalau URL bocor lewat screenshot atau log browser.
 */
class FileUrl
{
    /**
     * URL bertanda tangan untuk file di disk `public`.
     *
     * @param  string|null  $path  Path relatif di disk public, mis. 'dokumen_mobil/a.jpg'
     * @return string|null         Null kalau path kosong atau file tidak ada
     */
    public static function public(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = ltrim($path, '/');

        return self::signed($path, 'public');
    }

    /**
     * URL bertanda tangan untuk file di disk `local` (tidak publik),
     * misalnya sale-template/content.html.
     */
    public static function local(string $path): ?string
    {
        return self::signed(ltrim($path, '/'), 'local');
    }

    /**
     * Bangun signed URL ke endpoint /api/files/{path}.
     *
     * @param  string  $path  Path relatif di dalam disk
     * @param  string  $disk  Nama disk ('public' atau 'local')
     */
    public static function signed(string $path, string $disk = 'public'): ?string
    {
        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $ttl = (int) config('filesystems.file_url_ttl', 600);

        return URL::temporarySignedRoute(
            'api.files.show',
            now()->addSeconds($ttl),
            ['path' => $path, 'disk' => $disk],
        );
    }

    /**
     * Cek apakah sebuah path aman (tidak keluar dari root disk).
     *
     * Penting untuk endpoint file: tanpa ini, path seperti
     * `../../.env` atau `../../storage/logs/laravel.log` akan membuat
     * serverbackend menaruh file sensitif ke browser.
     */
    public static function isSafePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0")) {
            return false;
        }

        // Tolak segmen path yang menanjak ke atas.
        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        // Path harus relatif (tidak boleh absolute).
        return ! str_starts_with($path, '/');
    }
}
