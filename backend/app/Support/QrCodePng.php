<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Render QR code menjadi PNG memakai GD.
 *
 * Kenapa tidak pakai QrCode::format('png')?
 * simple-qrcode memetakan format 'png' ke ImagickImageBackEnd, jadi butuh
 * ext-imagick yang tidak terpasang di server ini (yang tersedia hanya ext-gd).
 * Karena itu matrix-nya diambil langsung dari bacon-qr-code (dependency yang
 * sudah dipakai simple-qrcode) lalu digambar sendiri dengan GD.
 *
 * Pemetaan modul gelap/terang mengikuti renderer bawaan bacon-qr-code
 * (SquareModule memakai nilai ByteMatrix yang truthy sebagai modul gelap),
 * jadi hasilnya identik dengan QR bawaan simple-qrcode.
 */
class QrCodePng
{
    /** Sisi QR dalam piksel, lebar QR (bukan tinggi). */
    public const DEFAULT_SIZE = 220;

    /**
     * Panjang isi QR yang masih aman di-encode.
     * Lewat ini, encoder melempar exception.
     */
    private const MAX_CONTENT_LENGTH = 1200;

    /**
     * @return string|null Binary PNG, atau null bila isi QR tidak bisa di-encode.
     */
    public static function render(?string $content, int $size = self::DEFAULT_SIZE, int $quietZone = 2): ?string
    {
        if ($content === null || trim($content) === '' || !function_exists('imagecreatetruecolor')) {
            return null;
        }

        if (strlen($content) > self::MAX_CONTENT_LENGTH) {
            return null;
        }

        try {
            $matrix = Encoder::encode($content, ErrorCorrectionLevel::L())->getMatrix();
        } catch (Throwable $e) {
            Log::warning('Gagal meng-encode QR code', ['error' => $e->getMessage()]);

            return null;
        }

        $modules = $matrix->getWidth();

        if ($modules !== $matrix->getHeight() || $modules < 1) {
            return null;
        }

        $quietZone = max(0, $quietZone);
        $total = $modules + ($quietZone * 2);
        $scale = max(1, intdiv(max(32, $size), $total));
        $pixels = $scale * $total;

        $image = imagecreatetruecolor($pixels, $pixels);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefilledrectangle($image, 0, 0, $pixels, $pixels, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if (! $matrix->get($x, $y)) {
                    continue;
                }

                imagefilledrectangle(
                    $image,
                    ($x + $quietZone) * $scale,
                    ($y + $quietZone) * $scale,
                    ($x + $quietZone + 1) * $scale - 1,
                    ($y + $quietZone + 1) * $scale - 1,
                    $black
                );
            }
        }

        ob_start();
        imagepng($image, null, 6);
        $bytes = ob_get_clean();

        return $bytes === false || $bytes === '' ? null : $bytes;
    }

    /**
     * QR code sebagai data URL PNG, siap disisipkan ke <img> di template PDF.
     *
     * Harus inline base64: dompdf tidak bisa membaca path relatif tanpa
     * konfigurasi chroot, dan konfigurasi itu akan membuat template PDF
     * bergantung pada lokasi file di disk.
     */
    public static function toDataUrl(?string $content, int $size = self::DEFAULT_SIZE): ?string
    {
        $png = self::render($content, $size);

        return $png === null ? null : 'data:image/png;base64,' . base64_encode($png);
    }
}
