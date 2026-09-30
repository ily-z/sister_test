<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Mengolah gambar tanda tangan yang dikirim dari browser sebagai data URL
 * (hasil SignaturePad.toDataURL('image/png')).
 *
 * Tanda tangan dikirim dari canvas dengan ukuran penuh, jadi masih ada
 * ruang kosong di sekeliling coretan. Gambar ini dipangkas (trim) supaya
 * tampilannya pas di kotak tanda tangan pada PDF, dan setiap byte yang
 * disimpan ke arsip benar-benar berisi coretan.
 */
class SignatureImage
{
    /** Batas ukuran data URL sebelum base64 di-decode (sekitar ~1.2 MB). */
    public const MAX_DATA_URL_BYTES = 1_600_000;

    /** Batas ukuran PNG hasil akhir. */
    public const MAX_PNG_BYTES = 900_000;

    /**
     * Nilai alpha GD (0 = opaque, 127 = transparan) di atas mana sebuah piksel
     * dianggap kosong. Coretan digambar selalu opaque, jadi sisanya aman
     * diperlakukan sebagai ruang kosong -- inilah yang membuat kanvas kosong
     * dari browser bisa dideteksi lalu ditolak.
     */
    private const BLANK_ALPHA_THRESHOLD = 100;

    /** Sisi canvas dibatasi supaya tidak menghasilkan sheet gambar yang besar. */
    private const MAX_SIDE = 2000;

    /**
     * Decode data URL PNG dari signature_pad menjadi binary PNG yang sudah dipangkas.
     *
     * @throws InvalidArgumentException bila data URL tidak valid / bukan PNG / kosong.
     */
    public static function fromDataUrl(string $dataUrl): string
    {
        if (strlen($dataUrl) > self::MAX_DATA_URL_BYTES) {
            throw new InvalidArgumentException('Gambar tanda tangan terlalu besar.');
        }

        if (!preg_match('#^data:image/png;base64,#i', $dataUrl)) {
            throw new InvalidArgumentException('Format gambar tanda tangan harus PNG.');
        }

        $binary = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);

        if ($binary === false || $binary === '') {
            throw new InvalidArgumentException('Gambar tanda tangan tidak dapat dibaca.');
        }

        if (strlen($binary) > self::MAX_PNG_BYTES) {
            throw new InvalidArgumentException('Gambar tanda tangan terlalu besar.');
        }

        // Verifikasi bahwa ini benar-benar PNG (magic bytes), bukan payload lain
        // yang menyamar lewat data URL.
        if (substr($binary, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new InvalidArgumentException('Berkas tanda tangan bukan PNG yang valid.');
        }

        return self::trim($binary);
    }

    /**
     * Pangkas border transparan / putih di sekeliling gambar, lalu tambahkan
     * margin kecil supaya tidak menyentuh garis tanda tangan di PDF.
     */
    public static function trim(string $pngBinary): string
    {
        if (!function_exists('imagecreatefromstring')) {
            // Tanpa GD, kirim apa adanya supaya fitur tetap jalan.
            return $pngBinary;
        }

        $image = @imagecreatefromstring($pngBinary);
        if ($image === false) {
            return $pngBinary;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > self::MAX_SIDE || $height > self::MAX_SIDE) {
            $scale = min(self::MAX_SIDE / $width, self::MAX_SIDE / $height);
            $image = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
            $width = imagesx($image);
            $height = imagesy($image);
        }

        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // Ingat konvensi GD: alpha 0 = opaque, 127 = transparan penuh.
                // Coretan digambar opaque, jadi piksel dengan alpha tinggi
                // diperlakukan sebagai ruang kosong.
                $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;
                if ($alpha >= self::BLANK_ALPHA_THRESHOLD) {
                    continue;
                }

                if ($x < $minX) $minX = $x;
                if ($x > $maxX) $maxX = $x;
                if ($y < $minY) $minY = $y;
                if ($y > $maxY) $maxY = $y;
            }
        }

        // Tidak ada satu pun piksel yang berisi coretan.
        if ($maxX < 0 || $maxY < 0) {
            throw new InvalidArgumentException('Tanda tangan kosong. Silakan coret pada kolom tersedia.');
        }

        $margin = 6;
        $minX = max(0, $minX - $margin);
        $minY = max(0, $minY - $margin);
        $maxX = min($width - 1, $maxX + $margin);
        $maxY = min($height - 1, $maxY + $margin);

        $cropW = $maxX - $minX + 1;
        $cropH = $maxY - $minY + 1;

        $cropped = imagecreatetruecolor($cropW, $cropH);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefilledrectangle($cropped, 0, 0, $cropW, $cropH, $transparent);
        imagealphablending($cropped, true);
        imagecopy($cropped, $image, 0, 0, $minX, $minY, $cropW, $cropH);

        ob_start();
        imagepng($cropped, null, 6);

        return (string) ob_get_clean();
    }

    /**
     * Ubah PNG di disk menjadi data URL untuk disisipkan ke <img> di template PDF,
     * karena dompdf tidak bisa membaca path relatif tanpa konfigurasi chroot.
     */
    public static function toDataUrlFromPath(string $absolutePath): ?string
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        $binary = @file_get_contents($absolutePath);
        if ($binary === false) {
            Log::warning('Gagal membaca file tanda tangan: ' . $absolutePath);
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($binary);
    }
}
