<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\FileUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan file upload (foto KTP, foto kondisi, dokumen kendaraan,
 * tanda tangan, PDF invoice) ke frontend SPA.
 *
 * Kenapa endpoint ini diperlukan
 * ------------------------------
 * Frontend berada di server terpisah, dan folder storage backend tidak
 * boleh di-expose lewat symlink public/storage seperti dulu. Semua file
 * sekarang lewat sini, dengan URL bertanda tangan yang dibuat oleh
 * App\Support\FileUrl.
 *
 * Otorisasi
 * ---------
 * Dua lapis:
 *   1. signature URL (dibuat server, TTL pendek) -- mencegah orang
 *      menebak-nebak nama file.
 *   2. `permission:file.view` -- hanya user dengan hak akses sah yang
 *      bisa mengunduh.
 */
class FileController extends Controller
{
    /** MIME type untuk extension yang sering dipakai di aplikasi ini. */
    private const MIME = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'html' => 'text/html; charset=UTF-8',
        'txt' => 'text/plain; charset=UTF-8',
    ];

    public function show(Request $request, string $path)
    {
        // Signature dicecek oleh middleware 'signed' di routes/api.php.
        // Di sini kita fokus pada keamanan path & response.

        $diskName = $request->query('disk', 'public');

        if (! in_array($diskName, ['public', 'local'], true)) {
            return response()->json(['message' => 'Disk tidak dikenal.'], 404);
        }

        // Cegah path traversal: '../../.env' atau 'storage/logs/*.log'
        // tidak boleh bisa dibaca lewat endpoint ini.
        if (! FileUrl::isSafePath($path)) {
            return response()->json(['message' => 'Path tidak valid.'], 400);
        }

        $disk = Storage::disk($diskName);

        if (! $disk->exists($path)) {
            return response()->json(['message' => 'File tidak ditemukan.'], 404);
        }

        $absolute = $disk->path($path);

        // Pengecekan kedua, setelah normalisasi symlink. Ini menutup
        // kasus di mana sebuah symlink di dalam storage menunjuk keluar.
        $realRoot = realpath($disk->path(''));
        $realFile = realpath($absolute);

        if ($realRoot === false || $realFile === false || ! str_starts_with($realFile, $realRoot)) {
            return response()->json(['message' => 'File tidak ditemukan.'], 404);
        }

        $mime = self::MIME[strtolower(pathinfo($path, PATHINFO_EXTENSION))]
            ?? $disk->mimeType($path)
            ?: 'application/octet-stream';

        /*
         * File di disk `local` (mis. sale-template/content.html) bisa
         * berisi HTML dari user. Kalau dilayani sebagai text/html tanpa
         * header ini, ada risiko konten itu dieksekusi sebagai halaman
         * di origin backend.
         */
        $headers = [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($diskName === 'local' || str_contains($mime, 'html')) {
            $headers['Content-Disposition'] = 'attachment';
            $headers['X-Content-Type-Options'] = 'nosniff';
        }

        // Inline stream lebih hemat memory daripada response()->download()
        // untuk file besar seperti PDF invoice.
        return $this->streamFile($disk, $path, $absolute, $mime, $headers);
    }

    /**
     * Kirim file dalam bentuk StreamedResponse supaya memory usage backend
     * tetap konstan walaupun file berukuran besar.
     */
    private function streamFile($disk, string $path, string $absolute, string $mime, array $headers): StreamedResponse
    {
        return response()->streamDownload(function () use ($disk, $path, $absolute) {
            $stream = $disk->readStream($path);

            if (! is_resource($stream)) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, basename($path), $headers);
    }
}
