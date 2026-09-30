<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceSignature;
use App\Support\QrCodePng;
use App\Support\SignatureImage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Membuat PDF invoice yang sudah memuat tanda tangan, lalu mengarsipkannya
 * ke disk public.
 *
 * Alur unduhan:
 *   1. User menggambar tanda tangan di modal signature_pad.
 *   2. PNG-nya disimpan ke storage/app/public/invoices/{id}/signature-*.png
 *   3. PDF dirender ulang lewat dompdf, TTD ikut tertanam, lalu diarsipkan
 *      sebagai invoices/{id}/invoice-*.pdf dan dicatat di invoices.file_pdf_path
 *   4. Route download hanya memakai file itu; jadi PDF yang terunduh selalu
 *      versi yang sudah bertanda tangan.
 */
class InvoicePdfService
{
    public const DISK = 'public';

    /** Sisi QR code (piksel) yang disisipkan ke PDF invoice. */
    private const QR_SIZE = 220;

    /**
     * Path relatif PDF invoice yang sudah diarsipkan.
     */
    public function pdfPath(Invoice $invoice): string
    {
        return "invoices/{$invoice->id}/invoice-{$this->safeFileName($invoice)}.pdf";
    }

    /**
     * Render PDF (bytes) dari template dompdf, tanpa menyimpan ke disk.
     */
    public function render(Invoice $invoice): string
    {
        $invoice = $this->loadRelations($invoice);

        $view = $invoice->jenis_invoice === 'BNG'
            ? 'invoices.pdf.bengkel'
            : 'invoices.pdf.sewa';

        $data = $invoice->jenis_invoice === 'BNG'
            ? $this->bengkelViewData($invoice)
            : $this->sewaViewData($invoice);

        $data += [
            'invoice' => $invoice,
            'titles' => $invoice->signatureTitles(),
            'signatureBlocks' => $this->signatureBlocks($invoice),
            'qrDataUrl' => $this->qrDataUrl($invoice),
        ];

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isPhpEnabled', false)
            ->setOption('isJavascriptEnabled', false);

        return $pdf->output();
    }

    /**
     * Render lalu arsipkan. Dipanggil setiap kali tanda tangan berubah supaya
     * PDF di arsip selalu sinkron dengan tanda tangan terbaru.
     *
     * @return string path relatif file PDF yang tersimpan
     */
    public function generateAndStore(Invoice $invoice): string
    {
        $bytes = $this->render($invoice);
        $path = $this->pdfPath($invoice);

        if (!Storage::disk(self::DISK)->put($path, $bytes)) {
            throw new RuntimeException('Gagal menyimpan PDF invoice ke arsip.');
        }

        $invoice->forceFill([
            'file_pdf_path' => $path,
            'pdf_generated_at' => now(),
            'document_hash' => hash('sha256', $bytes),
        ])->save();

        Log::info('PDF invoice diarsipkan', [
            'invoice_id' => $invoice->id,
            'path' => $path,
            'bytes' => strlen($bytes),
        ]);

        return $path;
    }

    /**
     * Hapus PDF + file tanda tangan dari arsip (dipakai saat tanda tangan dibatalkan).
     */
    public function forget(Invoice $invoice): void
    {
        $disk = Storage::disk(self::DISK);

        if ($invoice->file_pdf_path) {
            $disk->delete($invoice->file_pdf_path);
        }

        $dir = "invoices/{$invoice->id}";
        if ($disk->exists($dir)) {
            $disk->deleteDirectory($dir);
        }

        $invoice->forceFill([
            'file_pdf_path' => null,
            'pdf_generated_at' => null,
            'document_hash' => null,
        ])->save();
    }

    /**
     * Pastikan PDF ada di disk; generate ulang bila hilang atau byte-nya sudah
     * tidak cocok dengan hash yang tercatat (mis. template PDF diperbarui).
     */
    public function ensureStored(Invoice $invoice): string
    {
        $path = $invoice->file_pdf_path ?: $this->pdfPath($invoice);
        $disk = Storage::disk(self::DISK);

        if ($invoice->document_hash && $disk->exists($path)) {
            $current = @hash_file('sha256', $disk->path($path));
            if ($current === $invoice->document_hash) {
                return $path;
            }
        }

        return $this->generateAndStore($invoice);
    }

    /**
     * Data siap-siap untuk template PDF invoice sewa (SH/SK/AK).
     *
     * Perhitungan disalin dari invoices/print.blade.php agar isi PDF sama
     * dengan hasil cetak di browser.
     */
    private function sewaViewData(Invoice $invoice): array
    {
        $booking = $invoice->booking;

        $durasi = $booking
            ? \Carbon\Carbon::parse($booking->tanggal_mulai)->diffInDays(\Carbon\Carbon::parse($booking->tanggal_selesai))
            : 0;

        $rows = [];
        $subtotalKendaraan = 0;

        if ($booking) {
            foreach ($booking->bookingCars as $bc) {
                $hargaSatuan = (float) ($bc->harga_dasar_per_hari ?? 0);
                $subtotal = $booking->tipe_sewa === 'harian' ? $hargaSatuan * $durasi : $hargaSatuan;

                $subtotalKendaraan += $subtotal;

                $rows[] = [
                    'kode' => $bc->car->kode_mobil ?? $bc->car->plat_nomor,
                    'keterangan' => trim(($bc->car->merk ?? '') . ' ' . ($bc->car->model ?? '') . ' (' . ($bc->car->plat_nomor ?? '') . ')'),
                    'harga' => $hargaSatuan,
                    'qty' => $booking->tipe_sewa === 'harian' ? $durasi . ' hari' : '1',
                    'subtotal' => $subtotal,
                ];
            }

            foreach ($booking->addons as $addon) {
                $rows[] = [
                    'kode' => '',
                    'keterangan' => $addon->nama_biaya,
                    'harga' => (float) $addon->nominal,
                    'qty' => '1',
                    'subtotal' => (float) $addon->nominal,
                ];
            }
        }

        $totalAddons = $booking ? (float) $booking->total_addons : 0;
        $grandTotal = $booking ? (float) $booking->total_harga : 0;

        $jenisLabel = [
            'SH' => 'SEWA HARIAN',
            'SK' => 'SEWA KONTRAK',
            'AK' => 'ASURANSI KERUSAKAN',
        ];

        return [
            'booking' => $booking,
            'serviceLog' => null,
            'jenisText' => $jenisLabel[$invoice->jenis_invoice] ?? 'INVOICE',
            'durasi' => $durasi,
            'rows' => $rows,
            'subtotalKendaraan' => $subtotalKendaraan,
            'totalAddons' => $totalAddons,
            'grandTotal' => $grandTotal,
            'terbilangText' => $this->terbilang($grandTotal),
        ];
    }

    /**
     * Data siap-siap untuk template PDF invoice bengkel (BNG).
     */
    private function bengkelViewData(Invoice $invoice): array
    {
        $serviceLog = $invoice->serviceLog;
        $grandTotal = (float) $serviceLog->details->sum('biaya');

        return [
            'booking' => null,
            'serviceLog' => $serviceLog,
            'jenisText' => 'PERAWATAN & PERBAIKAN KENDARAAN',
            'grandTotal' => $grandTotal,
            'terbilangText' => $this->terbilang($grandTotal),
        ];
    }

    /**
     * Blok tanda tangan per role, lengkap dengan data URL PNG-nya.
     *
     * @return array<string, array{data_url: ?string, name: ?string, signed_at: ?string}>
     */
    private function signatureBlocks(Invoice $invoice): array
    {
        $disk = Storage::disk(self::DISK);
        $blocks = [];

        foreach (InvoiceSignature::ROLES as $role) {
            $blocks[$role] = [
                'data_url' => null,
                'name' => null,
                'signed_at' => null,
            ];
        }

        foreach ($invoice->signatures as $signature) {
            $dataUrl = $signature->signature_path
                ? SignatureImage::toDataUrlFromPath($disk->path($signature->signature_path))
                : null;

            $blocks[$signature->role] = [
                'data_url' => $dataUrl,
                'name' => $signature->signer_name,
                'signed_at' => $signature->signed_at?->format('j F Y H:i'),
            ];
        }

        return $blocks;
    }

    /**
     * QR code sebagai data URL PNG.
     *
     * Harus PNG (bukan SVG seperti di template cetak) karena dompdf tidak
     * merender SVG, dan harus inline base64 karena dompdf tidak bisa
     * membaca path relatif tanpa konfigurasi chroot.
     */
    private function qrDataUrl(Invoice $invoice): ?string
    {
        return QrCodePng::toDataUrl($invoice->qr_code_string, self::QR_SIZE);
    }

    private function terbilang(float $amount): string
    {
        return (new \NumberFormatter('id', \NumberFormatter::SPELLOUT))->format($amount);
    }

    private function loadRelations(Invoice $invoice): Invoice
    {
        return Invoice::with([
            'surat',
            'signatures',
            'booking.bookingCars.car',
            'booking.addons',
            'serviceLog.car',
            'serviceLog.details',
        ])->findOrFail($invoice->id);
    }

    private function safeFileName(Invoice $invoice): string
    {
        $base = $invoice->surat?->nomor_surat ?: $invoice->nomor_seri;

        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?: 'invoice';
    }
}
