<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\Invoice;
use App\Models\Booking;
use App\Models\ServiceLog;
use App\Models\InvoiceSignature;
use App\Services\InvoicePdfService;
use App\Support\SignatureImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    protected function generateNomorSurat($kode)
    {
        $bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $tahun = now()->year;
        $bulan = $bulanRomawi[now()->month - 1];

        $lastThisYear = Surat::where('kode', $kode)
            ->where('tahun', $tahun)
            ->orderByDesc('no_urut')
            ->first();

        $nextUrut = $lastThisYear ? intval($lastThisYear->no_urut) + 1 : 1;
        $noUrut = str_pad($nextUrut, 3, '0', STR_PAD_LEFT);
        $nomorSurat = "{$noUrut}/SRT/{$kode}/JRB/{$bulan}/{$tahun}";

        return compact('noUrut', 'bulan', 'tahun', 'nomorSurat');
    }

    protected function getJenisDokumen($kode)
    {
        $map = [
            'SH' => 'Invoice Sewa Harian',
            'SK' => 'Invoice Sewa Kontrak',
            'AK' => 'Invoice Asuransi Kerusakan',
            'BNG' => 'Invoice Bengkel',
        ];
        return $map[$kode] ?? 'Invoice';
    }

    public function generate(Request $request, $bookingId, $jenis)
    {
        $allowed = ['SH', 'SK', 'AK'];
        if (!in_array($jenis, $allowed)) {
            abort(404);
        }

        $booking = Booking::with(['bookingCars.car', 'addons'])->findOrFail($bookingId);

        $existing = Invoice::where('booking_id', $bookingId)
            ->where('jenis_invoice', $jenis)
            ->first();

        if ($existing) {
            return redirect()->route('admin.invoice.print', $existing->id);
        }

        $kodeMobil = $booking->bookingCars->first()?->car?->kode_mobil ?? 'XXX';

        $number = $this->generateNomorSurat($jenis);
        $jenisDokumen = $this->getJenisDokumen($jenis);

        $surat = Surat::create([
            'jenis_dokumen' => $jenisDokumen,
            'kode' => $jenis,
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => $number['noUrut'],
            'bulan' => $number['bulan'],
            'tahun' => $number['tahun'],
            'nomor_surat' => $number['nomorSurat'],
        ]);

        $nomorSeri = 'INV-' . now()->format('Ymd') . '-' . str_pad($surat->id, 4, '0', STR_PAD_LEFT);

        // Token dibuat lebih dulu supaya bisa dipakai untuk URL verifikasi di dalam QR
        $token = Str::random(64);
        $qrString = route('invoice.verify', $token);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'surat_id' => $surat->id,
            'jenis_invoice' => $jenis,
            'nomor_seri' => $nomorSeri,
            'kode_mobil' => $kodeMobil,
            'qr_code_string' => $qrString,
            'issued_at' => now(),
        ]);

        \App\Models\InvoiceVerification::create([
            'invoice_id' => $invoice->id,
            'token' => $token,
        ]);

        return redirect()->route('admin.invoice.print', $invoice->id);
    }

    public function bengkel($serviceLogId)
    {
        $serviceLog = ServiceLog::with(['car', 'details'])->findOrFail($serviceLogId);

        // FIX: filter berdasarkan service_log_id juga, bukan cuma jenis_invoice.
        // Sebelumnya query ini mengambil invoice BNG PERTAMA yang ditemukan di
        // seluruh tabel (milik servis manapun), sehingga servis kedua dan
        // seterusnya salah diarahkan ke invoice servis yang pertama kali dibuat,
        // dan nomor surat baru tidak pernah ter-generate untuk servis lainnya.
        $existing = Invoice::where('jenis_invoice', 'BNG')
            ->where('service_log_id', $serviceLogId)
            ->first();

        if ($existing) {
            return redirect()->route('admin.invoice.print', $existing->id);
        }

        $kodeMobil = $serviceLog->car->kode_mobil ?? 'XXX';
        $jenis = 'BNG';

        $number = $this->generateNomorSurat($jenis);
        $jenisDokumen = $this->getJenisDokumen($jenis);

        $surat = Surat::create([
            'jenis_dokumen' => $jenisDokumen,
            'kode' => $jenis,
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => $number['noUrut'],
            'bulan' => $number['bulan'],
            'tahun' => $number['tahun'],
            'nomor_surat' => $number['nomorSurat'],
        ]);

        $nomorSeri = 'INV-' . now()->format('Ymd') . '-' . str_pad($surat->id, 4, '0', STR_PAD_LEFT);

        // Token dibuat lebih dulu supaya bisa dipakai untuk URL verifikasi di dalam QR
        $token = Str::random(64);
        $qrString = route('invoice.verify', $token);

        $invoice = Invoice::create([
            'service_log_id' => $serviceLog->id,
            'surat_id' => $surat->id,
            'jenis_invoice' => $jenis,
            'nomor_seri' => $nomorSeri,
            'kode_mobil' => $kodeMobil,
            'qr_code_string' => $qrString,
            'issued_at' => now(),
        ]);

        \App\Models\InvoiceVerification::create([
            'invoice_id' => $invoice->id,
            'token' => $token,
        ]);

        return redirect()->route('admin.invoice.print', $invoice->id);
    }

    public function print($id)
    {
        $invoice = Invoice::with(['booking.bookingCars.car', 'booking.addons', 'surat', 'signatures'])->findOrFail($id);
        $booking = $invoice->booking;

        if ($invoice->jenis_invoice === 'BNG') {
            return redirect()->route('admin.invoice.print-bengkel', $id);
        }

        return view('invoices.print', compact('invoice', 'booking'));
    }

    public function printBengkel($id)
    {
        $invoice = Invoice::with(['surat', 'signatures', 'serviceLog.car', 'serviceLog.details'])->findOrFail($id);
        $serviceLog = $invoice->serviceLog;

        return view('invoices.print-bengkel', compact('invoice', 'serviceLog'));
    }

    /**
     * Simpan tanda tangan untuk satu atau kedua blok, lalu render ulang PDF invoice.
     *
     * Alur: gambar dari canvas dikirim sebagai data URL PNG, dipangkas server
     * dengan GD, disimpan ke arsip, dicatat di invoice_signatures, kemudian
     * PDF di-generate ulang supaya yang terarsip sudah memuat tanda tangan.
     *
     * Payload utama (modal dua blok):
     *   signatures[pelanggan][signer_name|signature_data|confirm_replace]
     *   signatures[finance][signer_name|signature_data|confirm_replace]
     *
     * Blok yang signature_data-nya kosong diabaikan, jadi menandatangani satu
     * blok saja tidak merusak blok yang lain. Semua blok disimpan dalam satu
     * transaksi dan PDF di-generate satu kali -- tidak pernah ada keadaan
     * "selesai separuh" yang ikut terarsip.
     *
     * Payload lama (satu blok: role + signer_name + signature_data) tetap
     * diterima supaya halaman yang hanya menandatangani satu blok tidak rusak.
     */
    public function storeSignature(Request $request, InvoicePdfService $pdfService, $id)
    {
        $invoice = Invoice::with(['signatures', 'booking', 'serviceLog'])->findOrFail($id);

        $submitted = $this->collectSubmittedSignatures($request);
        $blocks = $submitted['blocks'];
        $legacy = $submitted['legacy'];

        if (!$blocks) {
            throw ValidationException::withMessages([
                $legacy ? 'signature_data' : 'signatures' =>
                    'Isi minimal satu blok tanda tangan sebelum menyimpan.',
            ]);
        }

        $errors = [];
        $prepared = [];

        foreach ($blocks as $role => $block) {
            // Kunci error mengikuti bentuk payload yang dikirim, supaya klien lama
            // (satu blok) tetap menemukan pesannya di field yang dia kirim.
            $prefix = $legacy ? '' : "signatures.$role";
            $field = fn (string $name) => $prefix === '' ? $name : "$prefix.$name";
            $label = $this->roleLabel($invoice, $role);

            if (!in_array($role, InvoiceSignature::ROLES, true)) {
                $errors[$legacy ? 'role' : $prefix] = 'Blok tanda tangan tidak dikenal.';
                continue;
            }

            $name = trim((string) $block['signer_name']);

            if ($name === '') {
                $errors[$field('signer_name')] = $label . ': nama penanda tangan wajib diisi.';
                continue;
            }

            if (mb_strlen($name) > 100) {
                $errors[$field('signer_name')] = $label . ': nama penanda tangan maksimal 100 karakter.';
                continue;
            }

            if (strlen($block['signature_data']) > SignatureImage::MAX_DATA_URL_BYTES) {
                $errors[$field('signature_data')] = $label . ': gambar tanda tangan terlalu besar.';
                continue;
            }

            // Menandatangani ulang blok yang sudah terisi butuh konfirmasi eksplisit.
            $existing = $invoice->signatureFor($role);
            if ($existing && !$block['confirm_replace']) {
                // Bentuk lama tidak punya field confirm_replace sendiri, jadi
                // error-nya dikunci di "role" seperti kontrak lamanya.
                $errors[$legacy ? 'role' : $field('confirm_replace')] = $label
                    . ' sudah ditandatangani oleh ' . $existing->signer_name
                    . '. Centang konfirmasi bila ingin menggantinya.';
                continue;
            }

            try {
                $binary = SignatureImage::fromDataUrl($block['signature_data']);
            } catch (\InvalidArgumentException $e) {
                $errors[$field('signature_data')] = $label . ': ' . $e->getMessage();
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
        } catch (\Throwable $e) {
            Log::error('Gagal generate PDF setelah tanda tangan', [
                'invoice_id' => $invoice->id,
                'roles' => array_keys($prepared),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Tanda tangan tersimpan, tetapi PDF gagal dibuat: ' . $e->getMessage());
        }

        return back()->with('success', $this->signatureSuccessMessage($invoice, $prepared));
    }

    /**
     * Normalisasi payload tanda tangan menjadi ['blocks' => [role => [...]], 'legacy' => bool].
     *
     * Menerima bentuk multi (signatures[role][...]) dan bentuk lama
     * (role + signer_name + signature_data). Blok tanpa gambar diabaikan, jadi
     * klien boleh mengirim kedua blok walau hanya satu yang digambar.
     */
    protected function collectSubmittedSignatures(Request $request): array
    {
        $multi = $request->input('signatures');

        // Bentuk multi begitu array-nya ada, bentuknya sudah "dua blok" --
        // termasuk ketika semua bloknya kosong (user tidak menggambar apa pun).
        if (is_array($multi)) {
            $blocks = [];

            foreach (array_keys($multi) as $role) {
                $block = $multi[$role] ?? null;
                if (!is_array($block)) {
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
     * Pesan sukses yang menyebut blok mana yang tersimpan dan mana yang diganti.
     */
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
            $parts[] = 'Tanda tangan ' . implode(' dan ', $new) . ' tersimpan.';
        }

        if ($replaced) {
            $parts[] = 'Tanda tangan ' . implode(' dan ', $replaced) . ' berhasil diganti.';
        }

        $parts[] = 'PDF invoice berhasil di-generate dan diarsipkan.';

        if ($invoice->isFullySigned()) {
            $parts[] = 'Invoice sudah lengkap dan bisa diunduh.';
        } else {
            $missing = collect($invoice->missingSignatureRoles())
                ->map(fn ($role) => $this->roleLabel($invoice, $role))
                ->implode(' dan ');

            $parts[] = 'Tanda tangan yang belum ada: ' . $missing . '.';
        }

        return implode(' ', $parts);
    }

    /**
     * Batalkan tanda tangan pada satu blok, lalu hapus PDF lama dari arsip
     * supaya tidak ada dokumen bertanda tangan yang sudah tidak berlaku.
     */
    public function destroySignature(InvoicePdfService $pdfService, $id, $role)
    {
        abort_unless(in_array($role, InvoiceSignature::ROLES, true), 404);

        $invoice = Invoice::with('signatures')->findOrFail($id);
        $signature = $invoice->signatureFor($role);

        if ($signature) {
            Storage::disk(InvoicePdfService::DISK)->delete($signature->signature_path);
            $signature->delete();
        }

        $invoice->load('signatures');

        // Kalau blok yang dibatalkan ternyata membuat invoice tidak lengkap lagi,
        // PDF lama harus dibuang supaya tidak bisa diunduh.
        if ($invoice->isFullySigned()) {
            $pdfService->generateAndStore($invoice);
        } else {
            $pdfService->forget($invoice);
        }

        return back()->with('success', 'Tanda tangan ' . $this->roleLabel($invoice, $role) . ' dibatalkan.');
    }

    /**
     * Unduh PDF invoice yang sudah ditandatangani.
     *
     * Ini gate utama fitur: invoice yang belum ditandatangani kedua bloknya
     * TIDAK bisa diunduh.
     */
    public function download(InvoicePdfService $pdfService, $id)
    {
        $invoice = Invoice::with('signatures', 'surat')->findOrFail($id);

        if (!$invoice->isFullySigned()) {
            $missing = collect($invoice->missingSignatureRoles())
                ->map(fn ($role) => $this->roleLabel($invoice, $role))
                ->implode(' dan ');

            return back()->with('error', 'Dokumen harus ditandatangani terlebih dahulu. Tanda tangan yang belum ada: ' . $missing . '.');
        }

        try {
            $path = $pdfService->ensureStored($invoice);
        } catch (\Throwable $e) {
            Log::error('Gagal menyiapkan PDF invoice untuk diunduh', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'PDF invoice gagal dibuat: ' . $e->getMessage());
        }

        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->surat?->nomor_surat ?: $invoice->nomor_seri) ?: 'invoice';

        return Storage::disk(InvoicePdfService::DISK)->download($path, $filename . '.pdf');
    }

    /**
     * Lihat PDF di tab baru (inline) alih-alih mengunduh.
     */
    public function preview(InvoicePdfService $pdfService, $id)
    {
        $invoice = Invoice::with('signatures', 'surat')->findOrFail($id);

        if (!$invoice->isFullySigned()) {
            return back()->with('error', 'Dokumen harus ditandatangani terlebih dahulu sebelum dapat dilihat.');
        }

        $path = $pdfService->ensureStored($invoice);
        $filename = basename($path);

        return Storage::disk(InvoicePdfService::DISK)->response($path, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Nama blok tanda tangan untuk pesan flash / validasi.
     */
    protected function roleLabel(Invoice $invoice, string $role): string
    {
        return $invoice->signatureTitles()[$role] ?? $role;
    }

    /**
     * BARU: Halaman publik hasil scan QR code invoice.
     * Tidak pakai middleware auth — diakses oleh siapapun yang scan (pelanggan, pihak luar).
     */
    public function verify($token)
    {
        $verification = \App\Models\InvoiceVerification::where('token', $token)
            ->with(['invoice.signatures', 'invoice.surat', 'invoice.booking.bookingCars.car', 'invoice.serviceLog.car'])
            ->first();

        if (!$verification) {
            return view('invoices.verify', [
                'valid' => false,
                'invoice' => null,
            ]);
        }

        // Tandai sudah pernah discan (hanya diisi sekali, pada scan pertama)
        if (!$verification->is_verified) {
            $verification->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);
        }

        return view('invoices.verify', [
            'valid' => true,
            'invoice' => $verification->invoice,
            'verification' => $verification,
        ]);
    }
}