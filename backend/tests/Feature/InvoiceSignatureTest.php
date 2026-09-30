<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingCar;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\InvoiceSignature;
use App\Models\ServiceLog;
use App\Models\ServiceLogDetail;
use App\Models\Surat;
use App\Models\User;
use App\Services\InvoicePdfService;
use App\Support\QrCodePng;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alur tanda tangan invoice:
 *   1. PDF tidak boleh diunduh sebelum kedua blok tanda tangan terisi.
 *   2. Setelah ditandatangani, PNG disimpan ke arsip dan PDF di-generate ulang.
 *   3. Membatalkan satu blok membatalkan seluruh dokumen dari arsip.
 */
class InvoiceSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    // ── Helper pembuatan data ─────────────────────────────────────────────

    protected function makeInvoice(array $invoiceOverrides = []): Invoice
    {
        $car = Car::create([
            'merk' => 'Toyota',
            'model' => 'Avanza',
            'plat_nomor' => 'N 1234 AB',
            'tahun' => 2022,
            'kapasitas' => 7,
            'status' => 'Tersedia',
        ]);

        $booking = Booking::create([
            'nomor_pesanan' => 'ORD-TEST-1',
            'nama_pelanggan' => 'Siti Aminah',
            'kontak_pelanggan' => '08123456789',
            'tipe_sewa' => 'harian',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-04',
            'status_rental' => 'Selesai',
            'total_harga' => 450000,
            'total_addons' => 0,
        ]);

        BookingCar::create([
            'booking_id' => $booking->id,
            'car_id' => $car->id,
            'harga_dasar_per_hari' => 150000,
        ]);

        $surat = Surat::create([
            'jenis_dokumen' => 'Invoice Sewa Harian',
            'kode' => 'SH',
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => '001',
            'bulan' => 'IX',
            'tahun' => '2026',
            'nomor_surat' => '001/SRT/SH/JRB/IX/2026',
        ]);

        return Invoice::create(array_merge([
            'booking_id' => $booking->id,
            'surat_id' => $surat->id,
            'jenis_invoice' => 'SH',
            'nomor_seri' => 'INV-TEST-0001',
            'kode_mobil' => 'ABC',
            'qr_code_string' => 'http://localhost/verify/token-test',
            'issued_at' => now(),
        ], $invoiceOverrides));
    }

    protected function makeBengkelInvoice(): Invoice
    {
        $car = Car::create([
            'merk' => 'Honda',
            'model' => 'Brio',
            'plat_nomor' => 'N 5678 CD',
            'tahun' => 2023,
            'kapasitas' => 5,
            'status' => 'Masuk Bengkel',
        ]);

        $serviceLog = ServiceLog::create([
            'car_id' => $car->id,
            'nama_bengkel' => 'Bengkel Jaya',
            'km_saat_servis' => 10000,
            'tanggal_masuk' => '2026-09-10',
            'tanggal_keluar' => '2026-09-12',
            'status_bengkel' => 'closed',
        ]);

        ServiceLogDetail::create([
            'service_log_id' => $serviceLog->id,
            'deskripsi_tindakan' => 'Ganti oli',
            'biaya' => 350000,
        ]);

        $surat = Surat::create([
            'jenis_dokumen' => 'Invoice Bengkel',
            'kode' => 'BNG',
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => '001',
            'bulan' => 'IX',
            'tahun' => '2026',
            'nomor_surat' => '001/SRT/BNG/JRB/IX/2026',
        ]);

        return Invoice::create([
            'service_log_id' => $serviceLog->id,
            'surat_id' => $surat->id,
            'jenis_invoice' => 'BNG',
            'nomor_seri' => 'INV-TEST-BNG-1',
            'kode_mobil' => 'XYZ',
            'qr_code_string' => 'http://localhost/verify/token-bng',
            'issued_at' => now(),
        ]);
    }

    /**
     * Data URL PNG berisi coretan, meniru hasil SignaturePad di sisi klien.
     */
    protected function signatureDataUrl(): string
    {
        $image = imagecreatetruecolor(120, 40);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefilledrectangle($image, 0, 0, 120, 40, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagealphablending($image, true);
        imageline($image, 5, 30, 115, 10, imagecolorallocate($image, 30, 41, 59));
        imageline($image, 5, 10, 115, 30, imagecolorallocate($image, 30, 41, 59));

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();

        return 'data:image/png;base64,' . base64_encode($binary);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function sign(Invoice $invoice, string $role, string $name, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin())
            ->post(route('admin.invoice.signature.store', $invoice->id), array_merge([
                'role' => $role,
                'signer_name' => $name,
                'signature_data' => $this->signatureDataUrl(),
            ], $extra));
    }

    /**
     * Kirim banyak blok sekaligus, payload yang dipakai modal dua blok.
     *
     * Nilai signature_data => null berarti blok itu sengaja dikosongkan
     * (user tidak menggambar di blok tersebut).
     */
    protected function signBoth(Invoice $invoice, array $blocks): \Illuminate\Testing\TestResponse
    {
        $signatures = [];

        foreach ($blocks as $role => $block) {
            $signatures[$role] = [
                'signer_name' => $block['signer_name'] ?? '',
                // array_key_exists, bukan ??, supaya signature_data => null
                // berarti "blok ini dikosongkan" -- bukan "pakai default".
                'signature_data' => array_key_exists('signature_data', $block)
                    ? (string) $block['signature_data']
                    : $this->signatureDataUrl(),
                'confirm_replace' => $block['confirm_replace'] ?? false,
            ];
        }

        return $this->actingAs($this->admin())
            ->post(route('admin.invoice.signature.store', $invoice->id), [
                'signatures' => $signatures,
            ]);
    }

    /**
     * Jumlah image XObject yang tertanam di PDF.
     *
     * Dipakai sebagai pengganti pencarian teks di dalam byte PDF: dompdf
     * mengompresi content stream dengan Flate, jadi nama penanda tangan atau
     * judul dokumen tidak bisa dicari langsung dari isi file.
     */
    protected function countEmbeddedImages(string $pdf): int
    {
        return substr_count($pdf, '/Subtype /Image');
    }

    // ── Test ─────────────────────────────────────────────────────────────

    public function test_dua_blok_bisa_ditandatangani_dalam_satu_kirim(): void
    {
        $invoice = $this->makeInvoice();

        $this->signBoth($invoice, [
            'pelanggan' => ['signer_name' => 'Siti Aminah'],
            'finance' => ['signer_name' => 'Andi Finance'],
        ])->assertSessionHas('success');

        $invoice->refresh();

        $this->assertCount(2, $invoice->signatures);
        $this->assertTrue($invoice->isFullySigned());
        $this->assertSame('Siti Aminah', $invoice->signatureFor('pelanggan')->signer_name);
        $this->assertSame('Andi Finance', $invoice->signatureFor('finance')->signer_name);

        // Kedua PNG harus terarsip dan PDF ter-generate sekali untuk keduanya.
        Storage::disk('public')->assertExists("invoices/{$invoice->id}/signature-pelanggan.png");
        Storage::disk('public')->assertExists("invoices/{$invoice->id}/signature-finance.png");

        $this->assertNotNull($invoice->file_pdf_path);
        Storage::disk('public')->assertExists($invoice->file_pdf_path);

        // Setelah lengkap, PDF harus benar-benar bisa diunduh.
        $this->actingAs($this->admin())
            ->get(route('admin.invoice.download', $invoice->id))
            ->assertOk();
    }

    public function test_satu_blok_saja_tidak_menghapus_blok_lain(): void
    {
        $invoice = $this->makeInvoice();

        $this->sign($invoice, 'finance', 'Andi Finance');
        $before = $invoice->fresh()->signatureFor('finance')->signature_path;

        // Modal mengirim dua blok, tapi blok pelanggan dikosongkan.
        $this->signBoth($invoice, [
            'pelanggan' => ['signer_name' => 'Siti Aminah', 'signature_data' => null],
            'finance' => ['signer_name' => 'Andi Finance', 'confirm_replace' => true],
        ])->assertSessionHas('success');

        $invoice->refresh();

        $this->assertCount(1, $invoice->signatures, 'Blok yang dikosongkan tidak boleh ikut tersimpan.');
        $this->assertSame('Andi Finance', $invoice->signatureFor('finance')->signer_name);
        $this->assertSame($before, $invoice->signatureFor('finance')->signature_path);
        $this->assertFalse($invoice->isFullySigned());
    }

    public function test_tidak_ada_blok_yang_terisi_ditolak(): void
    {
        $invoice = $this->makeInvoice();

        $this->signBoth($invoice, [
            'pelanggan' => ['signer_name' => 'Siti Aminah', 'signature_data' => null],
            'finance' => ['signer_name' => 'Andi Finance', 'signature_data' => null],
        ])->assertSessionHasErrors('signatures');

        $this->assertCount(0, $invoice->fresh()->signatures);
    }

    public function test_gambar_kosong_di_satu_blok_menolak_seluruh_kirim(): void
    {
        $invoice = $this->makeInvoice();

        $this->signBoth($invoice, [
            'pelanggan' => ['signer_name' => 'Siti Aminah', 'signature_data' => 'data:image/png;base64,' . base64_encode('')],
            'finance' => ['signer_name' => 'Andi Finance'],
        ])->assertSessionHasErrors('signatures.pelanggan.signature_data');

        // Tidak boleh ada keadaan setengah: finance tidak boleh ikut tersimpan.
        $this->assertCount(0, $invoice->fresh()->signatures);
    }

    public function test_nama_kosong_di_satu_blok_menolak_seluruh_kirim(): void
    {
        $invoice = $this->makeInvoice();

        $this->signBoth($invoice, [
            'pelanggan' => ['signer_name' => '   '],
            'finance' => ['signer_name' => 'Andi Finance'],
        ])->assertSessionHasErrors('signatures.pelanggan.signer_name');

        $this->assertCount(0, $invoice->fresh()->signatures);
    }

    public function test_mengganti_blok_yang_ada_butuh_konfirmasi(): void
    {
        $invoice = $this->makeInvoice();

        $this->signBoth($invoice, [
            'pelanggan' => ['signer_name' => 'Siti Aminah'],
            'finance' => ['signer_name' => 'Andi Finance'],
        ])->assertSessionHas('success');

        // Tanpa konfirmasi, tidak ada blok yang boleh berubah.
        $this->signBoth($invoice, [
            'pelanggan' => ['signer_name' => 'Nama Baru'],
            'finance' => ['signer_name' => 'Finance Baru'],
        ])->assertSessionHasErrors([
            'signatures.pelanggan.confirm_replace',
            'signatures.finance.confirm_replace',
        ]);

        $invoice->refresh();
        $this->assertSame('Siti Aminah', $invoice->signatureFor('pelanggan')->signer_name);
        $this->assertSame('Andi Finance', $invoice->signatureFor('finance')->signer_name);

        // Dengan konfirmasi, keduanya diganti.
        $this->signBoth($invoice, [
            'pelanggan' => ['signer_name' => 'Nama Baru', 'confirm_replace' => true],
            'finance' => ['signer_name' => 'Finance Baru', 'confirm_replace' => true],
        ])->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('Nama Baru', $invoice->signatureFor('pelanggan')->signer_name);
        $this->assertSame('Finance Baru', $invoice->signatureFor('finance')->signer_name);
        $this->assertCount(2, $invoice->signatures, 'Mengganti tidak boleh menambah baris baru.');
    }

    public function test_blok_tidak_dikenal_ditolak(): void
    {
        $invoice = $this->makeInvoice();

        $this->signBoth($invoice, [
            'pelanggan' => ['signer_name' => 'Siti Aminah'],
            'direksi' => ['signer_name' => 'Bapak Direksi'],
        ])->assertSessionHasErrors('signatures.direksi');

        $this->assertCount(0, $invoice->fresh()->signatures);
    }

    public function test_modal_dua_blok_dirender_di_halaman_arsip(): void
    {
        $invoice = $this->makeInvoice();
        $this->sign($invoice, 'finance', 'Andi Finance');

        $this->actingAs($this->admin())
            ->get(route('admin.surat-menyurat.arsip'))
            ->assertOk()
            // Kedua blok harus ada di markup modal.
            ->assertSee('data-sig-block="pelanggan"', escape: false)
            ->assertSee('data-sig-block="finance"', escape: false)
            // Masing-masing punya kanvas dan input nama sendiri.
            ->assertSee('id="jrb-sig-canvas-pelanggan"', escape: false)
            ->assertSee('id="jrb-sig-canvas-finance"', escape: false)
            ->assertSee('name="signatures[pelanggan][signer_name]"', escape: false)
            ->assertSee('name="signatures[finance][signer_name]"', escape: false)
            // Config tombol harus membawa data kedua blok, bukan cuma satu.
            // Kunci JSON di-escape Js::from() jadi \u0022, bukan " polos.
            ->assertSee('\u0022blocks\u0022', escape: false)
            ->assertSee('\u0022focusRole\u0022', escape: false);
    }

    public function test_pdf_tidak_bisa_diunduh_sebelum_ditandatangani(): void
    {
        $invoice = $this->makeInvoice();

        $this->assertFalse($invoice->fresh()->isFullySigned());

        $response = $this->actingAs($this->admin())
            ->get(route('admin.invoice.download', $invoice->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertNull($invoice->fresh()->file_pdf_path);
    }

    public function test_preview_juga_ditolak_sebelum_ditandatangani(): void
    {
        $invoice = $this->makeInvoice();

        $this->actingAs($this->admin())
            ->get(route('admin.invoice.preview', $invoice->id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_bisa_menandatangani_kedua_blok_lalu_pdf_bisa_diunduh(): void
    {
        $invoice = $this->makeInvoice();

        $this->sign($invoice, 'pelanggan', 'Siti Aminah')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($invoice->fresh()->isFullySigned(), 'Satu blok saja belum cukup untuk mengunduh.');

        $this->sign($invoice, 'finance', 'Andi Finance')
            ->assertRedirect()
            ->assertSessionHas('success');

        $invoice->refresh();

        $this->assertTrue($invoice->isFullySigned());
        $this->assertNotNull($invoice->file_pdf_path, 'file_pdf_path harus terisi setelah ditandatangani.');
        $this->assertNotNull($invoice->document_hash);

        // PNG kedua blok diarsipkan.
        Storage::disk('public')->assertExists("invoices/{$invoice->id}/signature-pelanggan.png");
        Storage::disk('public')->assertExists("invoices/{$invoice->id}/signature-finance.png");

        // PDF benar-benar terbentuk sebagai PDF yang sah.
        $pdfPath = $invoice->file_pdf_path;
        Storage::disk('public')->assertExists($pdfPath);
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($pdfPath));

        $response = $this->actingAs($this->admin())
            ->get(route('admin.invoice.download', $invoice->id));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_tanda_tangan_mencatat_jejak_audit(): void
    {
        $invoice = $this->makeInvoice();
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('admin.invoice.signature.store', $invoice->id), [
                'role' => 'finance',
                'signer_name' => 'Andi Finance',
                'signature_data' => $this->signatureDataUrl(),
            ]);

        $signature = InvoiceSignature::where('invoice_id', $invoice->id)
            ->where('role', 'finance')
            ->firstOrFail();

        $this->assertSame('Andi Finance', $signature->signer_name);
        $this->assertSame('Hormat Kami,', $signature->signer_title);
        $this->assertSame($user->id, $signature->user_id);
        $this->assertNotNull($signature->signed_at);
        $this->assertNotNull($signature->ip_address);
    }

    public function test_mengganti_tanda_tangan_membutuhkan_konfirmasi(): void
    {
        $invoice = $this->makeInvoice();

        $this->sign($invoice, 'pelanggan', 'Siti Aminah');

        // Tanpa konfirmasi harus ditolak.
        $this->sign($invoice, 'pelanggan', 'Nama Baru')->assertSessionHasErrors('role');

        $this->assertSame(
            'Siti Aminah',
            $invoice->fresh()->signatureFor('pelanggan')->signer_name,
            'Tanda tangan lama harus tetap ada bila konfirmasi tidak dicentang.'
        );

        // Dengan konfirmasi, tanda tangan diganti.
        $this->sign($invoice, 'pelanggan', 'Nama Baru', ['confirm_replace' => '1'])
            ->assertSessionHas('success');

        $this->assertSame('Nama Baru', $invoice->fresh()->signatureFor('pelanggan')->signer_name);
    }

    public function test_membatalkan_satu_blok_menarik_pdf_dari_arsip(): void
    {
        $invoice = $this->makeInvoice();

        $this->sign($invoice, 'pelanggan', 'Siti Aminah');
        $this->sign($invoice, 'finance', 'Andi Finance');

        $invoice->refresh();
        $this->assertTrue($invoice->isFullySigned());
        $pdfPath = $invoice->file_pdf_path;
        Storage::disk('public')->assertExists($pdfPath);

        $this->actingAs($this->admin())
            ->delete(route('admin.invoice.signature.destroy', [$invoice->id, 'finance']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $invoice->refresh();

        $this->assertFalse($invoice->isFullySigned());
        $this->assertNull($invoice->signatureFor('finance'));
        $this->assertNull($invoice->file_pdf_path, 'PDF harus ditarik dari arsip saat tanda tangan dibatalkan.');
        Storage::disk('public')->assertMissing($pdfPath);
        Storage::disk('public')->assertMissing("invoices/{$invoice->id}/signature-finance.png");
    }

    public function test_tanda_tangan_kosong_ditolak(): void
    {
        $invoice = $this->makeInvoice();

        $blank = imagecreatetruecolor(60, 20);
        imagealphablending($blank, false);
        imagesavealpha($blank, true);
        imagefilledrectangle($blank, 0, 0, 60, 20, imagecolorallocatealpha($blank, 0, 0, 0, 127));
        ob_start();
        imagepng($blank);
        $binary = ob_get_clean();

        $this->actingAs($this->admin())
            ->post(route('admin.invoice.signature.store', $invoice->id), [
                'role' => 'pelanggan',
                'signer_name' => 'Siti Aminah',
                'signature_data' => 'data:image/png;base64,' . base64_encode($binary),
            ])
            ->assertSessionHasErrors('signature_data');

        $this->assertCount(0, $invoice->fresh()->signatures);
    }

    public function test_data_url_bukan_png_ditolak(): void
    {
        $invoice = $this->makeInvoice();

        $this->actingAs($this->admin())
            ->post(route('admin.invoice.signature.store', $invoice->id), [
                'role' => 'pelanggan',
                'signer_name' => 'Siti Aminah',
                'signature_data' => 'data:image/png;base64,' . base64_encode('<?php echo "halo"; ?>'),
            ])
            ->assertSessionHasErrors('signature_data');
    }

    public function test_role_tidak_dikenal_ditolak(): void
    {
        $invoice = $this->makeInvoice();

        $this->actingAs($this->admin())
            ->post(route('admin.invoice.signature.store', $invoice->id), [
                'role' => 'direksi',
                'signer_name' => 'Siti Aminah',
                'signature_data' => $this->signatureDataUrl(),
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_user_tanpa_izin_keuangan_tidak_bisa_menandatangani(): void
    {
        $invoice = $this->makeInvoice();
        $reception = User::factory()->create(['role' => 'reception']);

        // Role reception hanya punya permission *.view, jadi tidak bisa menandatangani.
        // CheckPermission membalas redirect + flash error, bukan 403, untuk request biasa.
        $this->actingAs($reception)
            ->post(route('admin.invoice.signature.store', $invoice->id), [
                'role' => 'pelanggan',
                'signer_name' => 'Siti Aminah',
                'signature_data' => $this->signatureDataUrl(),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertCount(0, $invoice->fresh()->signatures, 'Tanda tangan tidak boleh tersimpan tanpa izin.');
    }

    public function test_invoice_bengkel_memakai_label_penerima(): void
    {
        $invoice = $this->makeBengkelInvoice();

        $this->assertSame('Penerima', $invoice->signatureTitles()['pelanggan']);
        $this->assertSame('Hormat Kami,', $invoice->signatureTitles()['finance']);

        $this->sign($invoice, 'pelanggan', 'Pak Bengkel');
        $this->sign($invoice, 'finance', 'Andi Finance');

        $invoice->refresh();
        $this->assertTrue($invoice->isFullySigned());
        $this->assertSame('Penerima', $invoice->signatureFor('pelanggan')->signer_title);

        $this->actingAs($this->admin())
            ->get(route('admin.invoice.download', $invoice->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Template PDF bengkel harus benar-benar dirender dompdf, bukan halaman kosong.
        $pdf = Storage::disk('public')->get($invoice->fresh()->file_pdf_path);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThanOrEqual(2, $this->countEmbeddedImages($pdf), 'Tanda tangan tidak ikut tertanam.');
    }

    public function test_pdf_menyertakan_qr_code_dan_tanda_tangan(): void
    {
        $invoice = $this->makeInvoice();

        $this->sign($invoice, 'pelanggan', 'Siti Aminah');
        $this->sign($invoice, 'finance', 'Andi Finance');

        $pdf = Storage::disk('public')->get($invoice->fresh()->file_pdf_path);

        // Font harus di-subset: 3 style (regular/bold/oblique) tertanam sebagai
        // subset, bukan font utuh ~400 KB per style yang bikin PDF jadi > 1 MB.
        $this->assertLessThan(
            400 * 1024,
            strlen($pdf),
            'Font harus di-subset; PDF yang sebesar ini berarti font penuh ikut tertanam.'
        );
        $this->assertSame(3, substr_count($pdf, '/FontFile2'), 'Harus ada 3 font ter-subset.');

        // (Nama penanda tangan tidak bisa dicari dari byte PDF karena stream-nya
        // dikompresi Flate; teksnya sudah diverifikasi lewat halaman cetak.)
        $this->assertGreaterThanOrEqual(2, $this->countEmbeddedImages($pdf));
    }

    /**
     * QR benar-benar ikut tertanam di PDF.
     *
     * Jumlah image XObject tidak bisa dibandingkan langsung dengan "3", karena
     * dompdf menggabungkan image yang isinya identik menjadi satu XObject.
     * Jadi PDF yang sama dibandingkan dua kali: sekali dengan `qr_code_string`,
     * sekali tanpa.
     */
    public function test_qr_code_masuk_ke_pdf(): void
    {
        $invoice = $this->makeInvoice();

        $this->sign($invoice, 'pelanggan', 'Siti Aminah');
        $this->sign($invoice, 'finance', 'Andi Finance');

        $withQr = Storage::disk('public')->get($invoice->fresh()->file_pdf_path);
        $this->assertGreaterThanOrEqual(1, $this->countEmbeddedImages($withQr), 'QR tidak ikut tertanam.');

        // Kosongkan QR, lalu regenerate. Kolomnya NOT NULL, dan InvoicePdfService
        // sudah melewati QR kosong, jadi string kosong cukup.
        $invoice->update(['qr_code_string' => '']);
        $withoutQr = Storage::disk('public')->get(
            app(InvoicePdfService::class)->generateAndStore($invoice->fresh())
        );

        $this->assertSame(
            $this->countEmbeddedImages($withoutQr) + 1,
            $this->countEmbeddedImages($withQr),
            'QR harus menambah satu image XObject di PDF.'
        );
    }

    public function test_qr_code_png_valid_dan_berorientasi_benar(): void
    {
        $content = 'http://localhost/verify/token-abc-123';

        $png = QrCodePng::render($content, 300, 2);
        $this->assertIsString($png);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));

        $matrix = Encoder::encode($content, ErrorCorrectionLevel::L())->getMatrix();
        $modules = $matrix->getWidth();
        $quiet = 2;
        $scale = intdiv(300, $modules + ($quiet * 2));

        $image = imagecreatefromstring($png);
        $this->assertSame(($modules + ($quiet * 2)) * $scale, imagesx($image));

        // Sampling ulang piksel PNG harus cocok dengan matrix asli persis.
        $sample = function (int $x, int $y) use ($image, $scale, $quiet): bool {
            $px = imagecolorat(
                $image,
                ($x + $quiet) * $scale + intdiv($scale, 2),
                ($y + $quiet) * $scale + intdiv($scale, 2)
            );

            return (($px >> 16) & 0xFF) < 128;
        };

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                $this->assertSame(
                    (bool) $matrix->get($x, $y),
                    $sample($x, $y),
                    "Modul ({$x},{$y}) tidak cocok dengan matrix QR."
                );
            }
        }

        // Tiga finder pattern harus gelap di sudut atas-kiri, atas-kanan, bawah-kiri.
        $this->assertTrue($sample(3, 3), 'Finder pattern kiri-atas hilang.');
        $this->assertTrue($sample($modules - 4, 3), 'Finder pattern kanan-atas hilang.');
        $this->assertTrue($sample(3, $modules - 4), 'Finder pattern kiri-bawah hilang.');

        // Kasus degenerate tidak boleh melempar exception.
        $this->assertNull(QrCodePng::render(null));
        $this->assertNull(QrCodePng::render('   '));
    }

    public function test_halaman_arsip_menampilkan_kolom_tanda_tangan(): void
    {
        $invoice = $this->makeInvoice();

        $this->sign($invoice, 'pelanggan', 'Siti Aminah');

        $this->actingAs($this->admin())
            ->get(route('admin.surat-menyurat.arsip'))
            ->assertOk()
            ->assertSee('Tanda Tangan')
            ->assertSee('Siti Aminah')
            ->assertSee('Belum TTD')
            // Belum lengkap, jadi tombol unduh masih terkunci.
            ->assertSee('lock');
    }

    public function test_tanda_tangan_ditampilkan_di_halaman_cetak(): void
    {
        $invoice = $this->makeInvoice();

        $this->sign($invoice, 'pelanggan', 'Siti Aminah');
        $this->sign($invoice, 'finance', 'Andi Finance');

        $this->actingAs($this->admin())
            ->get(route('admin.invoice.print', $invoice->id))
            ->assertOk()
            ->assertSee('Ditandatangani')
            ->assertSee('Siti Aminah')
            ->assertSee('Andi Finance');
    }

    /**
     * `kode_mobil` adalah kolom di tabel `invoices`, bukan di `cars`.
     *
     * Dulu template bengkel membacanya dari `$serviceLog->car->kode_mobil`,
     * yang tidak pernah ada, jadi kolom "Kode Mobil" selalu tampil "-".
     */
    public function test_kode_mobil_bengkel_diambil_dari_invoice(): void
    {
        $invoice = $this->makeBengkelInvoice();
        $invoice->update(['kode_mobil' => 'BRT']);

        $this->sign($invoice, 'pelanggan', 'Pak Bengkel');
        $this->sign($invoice, 'finance', 'Andi Finance');

        $this->actingAs($this->admin())
            ->get(route('admin.invoice.print-bengkel', $invoice->id))
            ->assertOk()
            ->assertSee('Kode Mobil')
            ->assertSee('BRT');
    }

    public function test_halaman_bengkel_menampilkan_tombol_tanda_tangan(): void
    {
        $invoice = $this->makeBengkelInvoice();

        $url = route('admin.armada.bengkel');

        // Belum ada tanda tangan: tombol TTD muncul.
        $this->actingAs($this->admin())
            ->get($url)
            ->assertOk()
            ->assertSee('Tanda Tangan')
            ->assertSee('JRBSignature.open')
            ->assertSee(route('admin.invoice.signature.store', $invoice->id), escape: false);

        $this->sign($invoice, 'pelanggan', 'Pak Bengkel');
        $this->sign($invoice, 'finance', 'Andi Finance');

        // Sudah lengkap: tombol unduh terbuka, tombol TTD hilang.
        $this->actingAs($this->admin())
            ->get($url)
            ->assertOk()
            ->assertSee('Unduh PDF')
            ->assertSee(route('admin.invoice.download', $invoice->id), escape: false);
    }

    /**
     * Regresi: atribut onclick harus dibungkus kutip GANDA, bukan tunggal.
     *
     * Js::from() membungkus payload-nya sebagai JSON.parse('...') memakai
     * kutip tunggal literal. Kalau atribut onclick diapit kutip tunggal,
     * parser HTML menutup atributnya di kutip milik JSON.parse, sehingga
     * onclick berisi JS yang tidak lengkap ("...JSON.parse('") dan kliknya
     * diam-diam tidak melakukan apa-apa.
     *
     * Assertion lama (assertSee('JRBSignature.open')) tetap lolos karena
     * string itu memang ada di HTML -- hanya HTML-nya yang rusak. Test ini
     * karena itu memeriksa hasil parse atributnya, bukan kehadirannya.
     */
    public function test_atribut_onclick_tombol_tanda_tangan_valid(): void
    {
        $invoice = $this->makeInvoice();
        $this->assertValidSignatureOnclicks(
            route('admin.bookings.show', $invoice->booking_id),
            $invoice
        );
    }

    public function test_atribut_onclick_tombol_tanda_tangan_bengkel_valid(): void
    {
        $invoice = $this->makeBengkelInvoice();
        $this->assertValidSignatureOnclicks(route('admin.armada.bengkel'), $invoice);
    }

    /**
     * Setiap onclick JRBSignature.open harus utuh dan payload-nya bisa didecode.
     */
    protected function assertValidSignatureOnclicks(string $url, Invoice $invoice): void
    {
        $html = $this->actingAs($this->admin())->get($url)->assertOk()->getContent();

        preg_match_all('/<button\b[^>]*\bonclick=("|\')(.*?)\1/s', $html, $matches, PREG_SET_ORDER);

        $onclicks = array_values(array_filter(
            array_map(fn ($m) => $m[2], $matches),
            fn ($attr) => str_contains($attr, 'JRBSignature.open')
        ));

        $this->assertNotEmpty($onclicks, "Tidak ada tombol TTD dengan onclick di {$url}");

        foreach ($onclicks as $onclick) {
            // Penutup panggilan harus utuh: JSON.parse('...') yang lengkap.
            $this->assertStringEndsWith('))', $onclick, "onclick terpotong di {$url}: {$onclick}");
            $this->assertStringStartsWith('JRBSignature.open(JSON.parse(', $onclick);

            // Kutip tunggal di dalam payload JSON.parse harus seimbang.
            $this->assertSame(
                2,
                substr_count($onclick, "'"),
                "Kutip tunggal tidak seimbang pada onclick di {$url}: {$onclick}"
            );

            // Payload-nya harus bisa di-decode dan punya kunci yang diharapkan.
            // Js::from() meng-encode dua kali: hasil akhirnya adalah string JS
            // berisi teks JSON, jadi perlu dua kali json_decode. Ekor yang
            // dibuang adalah penutup "')" dari JSON.parse + ")" dari open().
            $inner = substr($onclick, strlen("JRBSignature.open(JSON.parse('"), -3);
            $config = json_decode(json_decode('"' . $inner . '"', true) ?? '', true);

            $this->assertIsArray($config, "onclick bukan objek JS yang valid di {$url}");
            $this->assertSame(route('admin.invoice.signature.store', $invoice->id), $config['actionUrl'] ?? null);
        }
    }

    public function test_halaman_detail_pesanan_menampilkan_tombol_tanda_tangan(): void
    {
        $invoice = $this->makeInvoice();
        $url = route('admin.bookings.show', $invoice->booking_id);

        $this->actingAs($this->admin())
            ->get($url)
            ->assertOk()
            ->assertSee('Tanda Tangan')
            ->assertSee(route('admin.invoice.signature.store', $invoice->id), escape: false);

        $this->sign($invoice, 'pelanggan', 'Siti Aminah');
        $this->sign($invoice, 'finance', 'Andi Finance');

        $this->actingAs($this->admin())
            ->get($url)
            ->assertOk()
            ->assertSee('Unduh PDF')
            ->assertSee(route('admin.invoice.download', $invoice->id), escape: false);
    }
}
