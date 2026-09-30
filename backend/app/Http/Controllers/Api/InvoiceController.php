<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceSignature;
use App\Models\InvoiceVerification;
use App\Models\ServiceLog;
use App\Models\Surat;
use App\Services\InvoicePdfService;
use App\Support\FileUrl;
use App\Support\SignatureImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

/**
 * Invoice: pembuatan dokumen, tanda tangan, dan unduh PDF.
 *
 * CATATAN PENTING SOAL PDF
 * -----------------------
 * PDF invoice TETAP di-render di backend dengan dompdf memakai template
 * Blade (resources/views/invoices/pdf/*.blade.php). Template itu tidak
 * dipindahkan ke Vue -- bukan karena males, tapi karena dompdf butuh
 * HTML server-side, dan font harus di-resolve dari disk backend.
 * Yang berubah hanya cara frontend MEMBUKA hasilnya: lewat signed URL
 * dari endpoint /api/files/{path}, bukan symlink public/storage.
 *
 * Logika bisnis tanda tangan (gate "harus ditandatangani dua blok
 * sebelum bisa diunduh") identik dengan versi Blade.
 */
class InvoiceController extends Controller
{
    private const JENIS_BOOKING = ['SH', 'SK', 'AK'];

    /**
     * POST /api/invoices/generate/{booking}/{jenis}
     */
    public function generate(Booking $booking, string $jenis): JsonResponse
    {
        abort_unless(in_array($jenis, self::JENIS_BOOKING, true), 404);

        $booking->load(['bookingCars.car', 'addons']);

        $existing = Invoice::where('booking_id', $booking->id)
            ->where('jenis_invoice', $jenis)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Invoice untuk booking dan jenis ini sudah ada.',
                'invoice_id' => $existing->id,
                'print_path' => "/invoices/{$existing->id}/print",
            ]);
        }

        $kodeMobil = $booking->bookingCars->first()?->car?->kode_mobil ?? 'XXX';

        $invoice = DB::transaction(function () use ($booking, $jenis, $kodeMobil) {
            $surat = $this->buatSurat($jenis);

            $nomorSeri = 'INV-'.now()->format('Ymd').'-'.str_pad($surat->id, 4, '0', STR_PAD_LEFT);

            // Token dibuat lebih dulu supaya bisa dipakai untuk URL
            // verifikasi di dalam QR.
            $token = Str::random(64);
            $qrString = $this->verificationUrl($token);

            $invoice = Invoice::create([
                'booking_id' => $booking->id,
                'surat_id' => $surat->id,
                'jenis_invoice' => $jenis,
                'nomor_seri' => $nomorSeri,
                'kode_mobil' => $kodeMobil,
                'qr_code_string' => $qrString,
                'issued_at' => now(),
            ]);

            InvoiceVerification::create([
                'invoice_id' => $invoice->id,
                'token' => $token,
            ]);

            return $invoice;
        });

        return response()->json([
            'message' => 'Invoice berhasil dibuat.',
            'invoice_id' => $invoice->id,
            'nomor_surat' => $invoice->surat?->nomor_surat,
            'nomor_seri' => $invoice->nomor_seri,
            'print_path' => "/invoices/{$invoice->id}/print",
        ], 201);
    }

    /**
     * POST /api/invoices/bengkel/{serviceLog}
     */
    public function bengkel(ServiceLog $serviceLog): JsonResponse
    {
        $serviceLog->load(['car', 'details']);

        // Filter per service_log_id, bukan cuma jenis_invoice: kalau tidak,
        // servis kedua dan seterusnya akan salah menunjuk ke invoice servis
        // pertama yang dibuat.
        $existing = Invoice::where('jenis_invoice', 'BNG')
            ->where('service_log_id', $serviceLog->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Invoice bengkel untuk servis ini sudah ada.',
                'invoice_id' => $existing->id,
                'print_path' => "/invoices/{$existing->id}/print",
            ]);
        }

        $kodeMobil = $serviceLog->car->kode_mobil ?? 'XXX';

        $invoice = DB::transaction(function () use ($serviceLog, $kodeMobil) {
            $surat = $this->buatSurat('BNG');

            $nomorSeri = 'INV-'.now()->format('Ymd').'-'.str_pad($surat->id, 4, '0', STR_PAD_LEFT);

            $token = Str::random(64);
            $qrString = $this->verificationUrl($token);

            $invoice = Invoice::create([
                'service_log_id' => $serviceLog->id,
                'surat_id' => $surat->id,
                'jenis_invoice' => 'BNG',
                'nomor_seri' => $nomorSeri,
                'kode_mobil' => $kodeMobil,
                'qr_code_string' => $qrString,
                'issued_at' => now(),
            ]);

            InvoiceVerification::create([
                'invoice_id' => $invoice->id,
                'token' => $token,
            ]);

            return $invoice;
        });

        return response()->json([
            'message' => 'Invoice bengkel berhasil dibuat.',
            'invoice_id' => $invoice->id,
            'nomor_surat' => $invoice->surat?->nomor_surat,
            'nomor_seri' => $invoice->nomor_seri,
            'print_path' => "/invoices/{$invoice->id}/print",
        ], 201);
    }

    /**
     * Data untuk halaman cetak invoice sewa.
     */
    public function print(Invoice $invoice): JsonResponse
    {
        $invoice->load(['booking.bookingCars.car', 'booking.addons', 'surat', 'signatures']);

        if ($invoice->jenis_invoice === 'BNG') {
            return response()->json([
                'redirect_path' => "/invoices/{$invoice->id}/print-bengkel",
            ]);
        }

        return response()->json([
            'invoice' => $invoice,
            'booking' => $invoice->booking,
            'signatures' => $this->signaturePayload($invoice),
        ]);
    }

    /**
     * Data untuk halaman cetak invoice bengkel.
     */
    public function printBengkel(Invoice $invoice): JsonResponse
    {
        $invoice->load(['surat', 'signatures', 'serviceLog.car', 'serviceLog.details']);

        return response()->json([
            'invoice' => $invoice,
            'serviceLog' => $invoice->serviceLog,
            'signatures' => $this->signaturePayload($invoice),
        ]);
    }

    /**
     * GET /api/invoices/{invoice}/qr
     *
     * QR code invoice sebagai SVG.
     *
     * Kenapa di-render di server dan bukan di frontend: content QR sudah
     * disimpan di kolom invoices.qr_code_string, dan pustaka
     * simplesoftwareio/simple-qrcode sudah terpasang di sini. Menambah
     * pustaka QR di frontend hanya untuk menggambar ulang teks yang sama
     * berarti menambah dependensi build untuk hasil yang identik.
     *
     * Endpoint ini mengikuti pola yang sama dengan
     * PublicInvoiceVerificationController::watermark() -- SVG asli dengan
     * Content-Type image/svg+xml, bukan JSON berisi string.
     *
     * Dibuatkan tanpa tanda tangan karena QR hanya memuat tautan verifikasi
     * dan bukan data sensitif, tapi tetap memakai auth:sanctum supaya tidak
     * bisa dipakai sebagai penghasil request tanpa login.
     */
    public function qr(Invoice $invoice): Response
    {
        try {
            $svg = QrCode::format('svg')->size(70)->margin(0)->generate($invoice->qr_code_string);
        } catch (Throwable $e) {
            Log::error('Gagal membuat QR invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            // Kotak kosong supaya layout cetak tidak bergeser. Template PDF
            // tidak boleh gagal dirender gara-gara QR.
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="70" height="70"/>';
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            // Isi QR tidak pernah berubah untuk satu invoice, tapi URL-nya
            // tidak pernah dipakai ulang setelah invoice dihapus, jadi
            // cache sebentar saja.
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * POST /api/invoices/{invoice}/signature
     *
     * Payload multi-blok:
     *   signatures[pelanggan][signer_name|signature_data|confirm_replace]
     *   signatures[finance][signer_name|signature_data|confirm_replace]
     *
     * Blok tanpa gambar diabaikan, jadi frontend boleh mengirim kedua
     * blok walau hanya satu yang digambar. Semua blok disimpan dalam satu
     * transaksi dan PDF di-generate satu kali.
     *
     * Bentuk lama (role + signer_name + signature_data) tetap diterima.
     */
    public function storeSignature(Request $request, InvoicePdfService $pdfService, Invoice $invoice): JsonResponse
    {
        $invoice->load(['signatures', 'booking', 'serviceLog']);

        $submitted = $this->collectSubmittedSignatures($request);
        $blocks = $submitted['blocks'];
        $legacy = $submitted['legacy'];

        if (! $blocks) {
            throw ValidationException::withMessages([
                $legacy ? 'signature_data' : 'signatures' => 'Isi minimal satu blok tanda tangan sebelum menyimpan.',
            ]);
        }

        $errors = [];
        $prepared = [];

        foreach ($blocks as $role => $block) {
            // Kunci error mengikuti bentuk payload yang dikirim, supaya
            // klien lama (satu blok) tetap menemukan pesannya di field
            // yang dia kirim.
            $prefix = $legacy ? '' : "signatures.$role";
            $field = fn (string $name) => $prefix === '' ? $name : "$prefix.$name";
            $label = $this->roleLabel($invoice, $role);

            if (! in_array($role, InvoiceSignature::ROLES, true)) {
                $errors[$legacy ? 'role' : $prefix] = 'Blok tanda tangan tidak dikenal.';
                continue;
            }

            $name = trim((string) $block['signer_name']);

            if ($name === '') {
                $errors[$field('signer_name')] = $label.': nama penanda tangan wajib diisi.';
                continue;
            }

            if (mb_strlen($name) > 100) {
                $errors[$field('signer_name')] = $label.': nama penanda tangan maksimal 100 karakter.';
                continue;
            }

            if (strlen($block['signature_data']) > SignatureImage::MAX_DATA_URL_BYTES) {
                $errors[$field('signature_data')] = $label.': gambar tanda tangan terlalu besar.';
                continue;
            }

            // Menandatangani ulang blok yang sudah terisi butuh konfirmasi.
            $existing = $invoice->signatureFor($role);
            if ($existing && ! $block['confirm_replace']) {
                $errors[$legacy ? 'role' : $field('confirm_replace')] = $label
                    .' sudah ditandatangani oleh '.$existing->signer_name
                    .'. Centang konfirmasi bila ingin menggantinya.';
                continue;
            }

            try {
                $binary = SignatureImage::fromDataUrl($block['signature_data']);
            } catch (\InvalidArgumentException $e) {
                $errors[$field('signature_data')] = $label.': '.$e->getMessage();
                continue;
            }

            $prepared[$role] = [
                'name' => $name,
                'binary' => $binary,
                'existing' => $existing,
            ];
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($prepared, $invoice, $request) {
            foreach ($prepared as $role => $data) {
                $path = "invoices/{$invoice->id}/signature-{$role}.png";
                Storage::disk(InvoicePdfService::DISK)->put($path, $data['binary']);

                $signature = $data['existing']
                    ?: new InvoiceSignature(['invoice_id' => $invoice->id, 'role' => $role]);

                $signature->fill([
                    'signer_name' => $data['name'],
                    'signer_title' => $invoice->signatureTitles()[$role] ?? null,
                    'user_id' => $request->user()?->id,
                    'signature_path' => $path,
                    'signed_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255) ?: null,
                ])->save();
            }
        });

        $invoice->load('signatures');

        // PDF di arsip harus mencerminkan tanda tangan terbaru.
        try {
            $pdfService->generateAndStore($invoice);
        } catch (Throwable $e) {
            Log::error('Gagal generate PDF setelah tanda tangan', [
                'invoice_id' => $invoice->id,
                'roles' => array_keys($prepared),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Tanda tangan tersimpan, tetapi PDF gagal dibuat: '.$e->getMessage(),
                'pdf_error' => true,
                'signatures' => $this->signaturePayload($invoice),
            ], 422);
        }

        return response()->json([
            'message' => $this->signatureSuccessMessage($invoice, $prepared),
            'signatures' => $this->signaturePayload($invoice),
            'pdf_url' => FileUrl::public($invoice->file_pdf_path),
        ]);
    }

    /**
     * DELETE /api/invoices/{invoice}/signature/{role}
     */
    public function destroySignature(InvoicePdfService $pdfService, Invoice $invoice, string $role): JsonResponse
    {
        abort_unless(in_array($role, InvoiceSignature::ROLES, true), 404);

        $invoice->load('signatures');
        $signature = $invoice->signatureFor($role);

        if ($signature) {
            Storage::disk(InvoicePdfService::DISK)->delete($signature->signature_path);
            $signature->delete();
        }

        $invoice->load('signatures');

        // Kalau pembatalan membuat invoice tidak lengkap lagi, PDF lama
        // harus dibuang supaya tidak bisa diunduh.
        if ($invoice->isFullySigned()) {
            $pdfService->generateAndStore($invoice);
        } else {
            $pdfService->forget($invoice);
        }

        return response()->json([
            'message' => 'Tanda tangan '.$this->roleLabel($invoice, $role).' dibatalkan.',
            'signatures' => $this->signaturePayload($invoice),
        ]);
    }

    /**
     * GET /api/invoices/{invoice}/download
     *
     * Gate utama fitur: invoice yang belum ditandatangani kedua bloknya
     * TIDAK bisa diunduh.
     *
     * Mengembalikan signed URL, bukan file langsung, supaya frontend
     * yang memutuskan mau mengunduh lewat <a download> atau membuka di
     * tab baru.
     */
    public function download(InvoicePdfService $pdfService, Invoice $invoice): JsonResponse
    {
        $invoice->load(['signatures', 'surat']);

        if (! $invoice->isFullySigned()) {
            $missing = collect($invoice->missingSignatureRoles())
                ->map(fn ($role) => $this->roleLabel($invoice, $role))
                ->implode(' dan ');

            return response()->json([
                'message' => 'Dokumen harus ditandatangani terlebih dahulu. Tanda tangan yang belum ada: '.$missing.'.',
            ], 403);
        }

        try {
            $path = $pdfService->ensureStored($invoice);
        } catch (Throwable $e) {
            Log::error('Gagal menyiapkan PDF invoice untuk diunduh', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'PDF invoice gagal dibuat: '.$e->getMessage(),
            ], 500);
        }

        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->surat?->nomor_surat ?: $invoice->nomor_seri) ?: 'invoice';

        return response()->json([
            'message' => 'PDF siap diunduh.',
            'url' => FileUrl::public($path),
            'filename' => $filename.'.pdf',
        ]);
    }

    /**
     * GET /api/invoices/{invoice}/preview
     *
     * URL untuk membuka PDF di tab baru (inline).
     */
    public function preview(InvoicePdfService $pdfService, Invoice $invoice): JsonResponse
    {
        $invoice->load(['signatures', 'surat']);

        if (! $invoice->isFullySigned()) {
            return response()->json([
                'message' => 'Dokumen harus ditandatangani terlebih dahulu sebelum dapat dilihat.',
            ], 403);
        }

        $path = $pdfService->ensureStored($invoice);

        return response()->json([
            'url' => FileUrl::public($path),
            'filename' => basename($path),
        ]);
    }

    // ── Helper ─────────────────────────────────────────────────────────

    /**
     * Buat record Surat + nomor urut untuk kode dokumen tertentu.
     * WAJIB dipanggil di dalam transaksi (lihat generate()).
     */
    private function buatSurat(string $kode): Surat
    {
        $bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $tahun = now()->year;
        $bulan = $bulanRomawi[now()->month - 1];

        $lastThisYear = Surat::where('kode', $kode)
            ->where('tahun', $tahun)
            ->orderByDesc('no_urut')
            ->lockForUpdate()
            ->first();

        $nextUrut = $lastThisYear ? intval($lastThisYear->no_urut) + 1 : 1;
        $noUrut = str_pad($nextUrut, 3, '0', STR_PAD_LEFT);

        return Surat::create([
            'jenis_dokumen' => $this->jenisDokumen($kode),
            'kode' => $kode,
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => $noUrut,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'nomor_surat' => "{$noUrut}/SRT/{$kode}/JRB/{$bulan}/{$tahun}",
        ]);
    }

    private function jenisDokumen(string $kode): string
    {
        return match ($kode) {
            'SH' => 'Invoice Sewa Harian',
            'SK' => 'Invoice Sewa Kontrak',
            'AK' => 'Invoice Asuransi Kerusakan',
            'BNG' => 'Invoice Bengkel',
            default => 'Invoice',
        };
    }

    /**
     * URL yang dicetak ke dalam QR code di PDF.
     *
     * PENTING untuk arsitektur 3-server: QR ini dicetak di kertas dan
     * dipindai pihak luar, jadi URL-nya harus menunjuk ke SERVER FRONTEND
     * (halaman publik verify), bukan ke backend. Diisi dari env
     * FRONTEND_URL. Kalau env itu kosong, jatuh ke APP_URL supaya
     * instalasi lama tidak langsung rusak.
     */
    private function verificationUrl(string $token): string
    {
        $base = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');

        return "{$base}/verify/{$token}";
    }

    /**
     * Normalisasi payload tanda tangan menjadi
     * ['blocks' => [role => [...]], 'legacy' => bool].
     */
    protected function collectSubmittedSignatures(Request $request): array
    {
        $multi = $request->input('signatures');

        if (is_array($multi)) {
            $blocks = [];

            foreach (array_keys($multi) as $role) {
                $block = $multi[$role] ?? null;
                if (! is_array($block)) {
                    continue;
                }

                $data = trim((string) ($block['signature_data'] ?? ''));
                if ($data === '') {
                    continue;
                }

                $blocks[$role] = [
                    'signer_name' => $block['signer_name'] ?? '',
                    'signature_data' => $data,
                    'confirm_replace' => (bool) ($block['confirm_replace'] ?? false),
                ];
            }

            return ['blocks' => $blocks, 'legacy' => false];
        }

        // Bentuk lama: satu blok per request.
        $blocks = [];
        $data = trim((string) $request->input('signature_data', ''));
        $role = (string) $request->input('role', '');

        if ($data !== '' && $role !== '') {
            $blocks[$role] = [
                'signer_name' => $request->input('signer_name', ''),
                'signature_data' => $data,
                'confirm_replace' => $request->boolean('confirm_replace'),
            ];
        }

        return ['blocks' => $blocks, 'legacy' => true];
    }

    /**
     * State tanda tangan untuk UI.
     */
    private function signaturePayload(Invoice $invoice): array
    {
        return [
            'lengkap' => $invoice->isFullySigned(),
            'missing' => collect($invoice->missingSignatureRoles())
                ->map(fn ($role) => $this->roleLabel($invoice, $role))
                ->values(),
            'blok' => collect(InvoiceSignature::ROLES)->map(function (string $role) use ($invoice) {
                $signature = $invoice->signatureFor($role);

                return [
                    'role' => $role,
                    'judul' => $invoice->signatureTitles()[$role] ?? $role,
                    'ada' => $signature !== null,
                    'signer_name' => $signature?->signer_name,
                    'signed_at' => $signature?->signed_at?->toIso8601String(),
                    'image_url' => FileUrl::public($signature?->signature_path),
                ];
            })->values(),
        ];
    }

    protected function signatureSuccessMessage(Invoice $invoice, array $prepared): string
    {
        $new = [];
        $replaced = [];

        foreach ($prepared as $role => $data) {
            if ($data['existing']) {
                $replaced[] = $this->roleLabel($invoice, $role);
            } else {
                $new[] = $this->roleLabel($invoice, $role);
            }
        }

        $parts = [];

        if ($new) {
            $parts[] = 'Tanda tangan '.implode(' dan ', $new).' tersimpan.';
        }

        if ($replaced) {
            $parts[] = 'Tanda tangan '.implode(' dan ', $replaced).' berhasil diganti.';
        }

        $parts[] = 'PDF invoice berhasil di-generate dan diarsipkan.';

        if ($invoice->isFullySigned()) {
            $parts[] = 'Invoice sudah lengkap dan bisa diunduh.';
        } else {
            $missing = collect($invoice->missingSignatureRoles())
                ->map(fn ($role) => $this->roleLabel($invoice, $role))
                ->implode(' dan ');

            $parts[] = 'Tanda tangan yang belum ada: '.$missing.'.';
        }

        return implode(' ', $parts);
    }

    protected function roleLabel(Invoice $invoice, string $role): string
    {
        return $invoice->signatureTitles()[$role] ?? $role;
    }
}
