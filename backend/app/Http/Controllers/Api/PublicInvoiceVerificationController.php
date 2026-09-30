<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvoiceSignature;
use App\Models\InvoiceVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Verifikasi invoice lewat QR code -- TANPA autentikasi.
 *
 * Alasan endpoint ini publik: QR-nya dicetak di dokumen fisik (kuria,
 * tender, cek karos). Orang yang memindai tidak tentu punya akun sistem,
 * jadi memaksa login akan membuat fitur ini tidak berguna.
 *
 * Keamanan tetap dijaga oleh kerahasiaan token: token UUID di dalam QR
 * adalah satu-satunya kunci, jadi yang penting adalah tokennya tidak
 * bocor (mis. ke log atau screenshot yang dibagikan bebas).
 *
 * Versi Blade-nya ada di resources/views/invoices/Verify.blade.php dan
 * selama masa transisi masih dipakai. Keduanya membaca sumber data yang
 * sama.
 */
class PublicInvoiceVerificationController extends Controller
{
    private const JENIS_LABEL = [
        'SH' => 'Invoice Sewa Harian',
        'SK' => 'Invoice Sewa Kontrak',
        'AK' => 'Invoice Asuransi Kerusakan',
        'BNG' => 'Invoice Bengkel',
    ];

    /**
     * GET /api/public/invoices/verify/{token}
     *
     * Selalu balas 200 dengan JSON, termasuk saat token tidak ditemukan.
     * Frontend cukup membaca field `valid` untuk menentukan tampilan
     * kartu hijau atau merah -- tidak perlu menangani status error.
     */
    public function verify(string $token): JsonResponse
    {
        $verification = InvoiceVerification::where('token', $token)
            ->with([
                'invoice.signatures',
                'invoice.surat:id,nomor_surat',
                'invoice.booking:id,nomor_pesanan,nama_pelanggan,total_harga',
                'invoice.booking.bookingCars.car:id,merk,model,plat_nomor',
                'invoice.serviceLog:id,nama_bengkel',
                'invoice.serviceLog.car:id,merk,model,plat_nomor',
                'invoice.serviceLog.details',
            ])
            ->first();

        if (! $verification) {
            return response()->json([
                'valid' => false,
                'message' => 'Kode QR ini tidak ditemukan dalam sistem kami.',
            ]);
        }

        // Tandai sudah pernah discan (hanya sekali, pada scan pertama).
        if (! $verification->is_verified) {
            $verification->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);
        }

        $invoice = $verification->invoice;

        return response()->json([
            'valid' => true,
            'message' => 'Dokumen ini asli & tercatat resmi di sistem.',
            'invoice' => [
                'nomor_surat' => $invoice->surat?->nomor_surat,
                'nomor_seri' => $invoice->nomor_seri,
                'jenis' => $invoice->jenis_invoice,
                'jenis_label' => self::JENIS_LABEL[$invoice->jenis_invoice] ?? 'Invoice',
                'kode_mobil' => $invoice->kode_mobil,
                'issued_at' => $invoice->issued_at?->format('d F Y, H:i'),
            ],
            'booking' => $invoice->booking ? [
                'nomor_pesanan' => $invoice->booking->nomor_pesanan,
                'nama_pelanggan' => $invoice->booking->nama_pelanggan,
                'total_harga' => (int) $invoice->booking->total_harga,
                'total_harga_terformat' => 'Rp '.number_format($invoice->booking->total_harga, 0, ',', '.'),
                'kendaraan' => $this->kendaraanBooking($invoice),
            ] : null,
            'servis' => $invoice->serviceLog ? [
                'nama_bengkel' => $invoice->serviceLog->nama_bengkel ?? '-',
                'kendaraan' => trim(($invoice->serviceLog->car?->merk ?? '').' '.($invoice->serviceLog->car?->model ?? '')),
                'total_biaya' => (int) $invoice->serviceLog->details->sum('biaya'),
                'total_biaya_terformat' => 'Rp '.number_format($invoice->serviceLog->details->sum('biaya'), 0, ',', '.'),
            ] : null,
            'tanda_tangan' => [
                'lengkap' => $invoice->isFullySigned(),
                'blok' => collect(InvoiceSignature::ROLES)->map(function (string $role) use ($invoice) {
                    $signature = $invoice->signatureFor($role);

                    return [
                        'role' => $role,
                        'judul' => $invoice->signatureTitles()[$role] ?? $role,
                        'ada' => $signature !== null,
                        'nama' => $signature?->signer_name,
                        'waktu' => $signature?->signed_at?->format('d M Y, H:i'),
                    ];
                })->values(),
            ],
            'pertama_dipescan' => $verification->verified_at?->format('d M Y, H:i'),
        ]);
    }

    private function kendaraanBooking($invoice): string
    {
        $car = $invoice->booking?->bookingCars?->first()?->car;

        return $car ? trim(($car->merk ?? '').' '.($car->model ?? '')) : '';
    }

    /**
     * GET /api/public/watermark
     *
     * Logo semi-transparan untuk pratinjau dokumen PDF di browser.
     * Dikembalikan sebagai SVG supaya tidak perlu file PNG terpisah
     * dan ukurannya tetap kecil.
     */
    public function watermark(): Response
    {
        $logoPath = storage_path('app/public/img/logo.png');

        if (! file_exists($logoPath)) {
            // Placeholder transparan supaya frontend tidak perlu
            // menangani kasus "logo tidak ada" secara terpisah.
            return response(
                '<svg xmlns="http://www.w3.org/2000/svg" width="554" height="1123"/>',
                200,
                ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=86400']
            );
        }

        $logoData = base64_encode(file_get_contents($logoPath));
        $size = getimagesize($logoPath);
        $ratio = ($size[0] ?? 1000) / ($size[1] ?? 1000);
        $logoW = 554;
        $logoH = round($logoW / $ratio);
        $y = round((1123 - $logoH) / 2);

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="554" height="1123"><image href="data:image/png;base64,%s" x="0" y="%d" width="%d" height="%d" opacity="0.45" preserveAspectRatio="xMidYMid meet"/></svg>',
            $logoData,
            $y,
            $logoW,
            $logoH
        );

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
