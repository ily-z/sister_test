<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\BookingCar;
use App\Models\BookingPayment;
use App\Models\Car;
use App\Models\OdometerLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alur tulis endpoint /api/bookings/*.
 *
 * Ini modul yang paling banyak berubah saat pemisahan 3 server, jadi
 * diuji dari ujung ke ujung: buat pesanan -> ubah -> biaya tambahan ->
 * status pembayaran -> odometer -> kunci -> hapus.
 *
 * Yang diuji bukan hanya "balas 200", tapi efek sampingnya:
 *   - nomor pesanan urut dan tidak bentrok,
 *   - status kendaraan ikut berubah,
 *   - buku keuangan (transactions) selalu sinkron dengan status pembayaran,
 *   - data terkunci tidak bisa diedit.
 *
 * CATATAN: satu test hanya boleh memakai token milik SATU user, karena
 * Illuminate\Auth\RequestGuard meng-cache user selama satu test berjalan.
 */
class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    protected function makeCar(array $attributes = []): Car
    {
        return Car::create(array_merge([
            'plat_nomor' => 'B '.fake()->unique()->numerify('####').' XYZ',
            'merk' => 'Toyota',
            'model' => 'Avanza',
            'warna' => 'Putih',
            'tahun' => 2022,
            'kapasitas' => 7,
            'harga_default' => 400000,
            'harga_kontrak' => 3000000,
            'km_saat_ini' => 10000,
            'status' => 'Tersedia',
        ], $attributes));
    }

    /**
     * Payload minimum yang lolos validasi BookingController::validateBookingPayload().
     */
    protected function payload(Car $car, array $overrides = []): array
    {
        return array_merge([
            'nama_pelanggan' => 'Budi Santoso',
            'kontak_pelanggan' => '08123456789',
            'email' => 'budi@example.test',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-04',
            'tipe_sewa' => 'harian',
            'status_pembayaran' => 'Belum Bayar',
            'cars' => [$car->id],
            'harga_per_hari' => [400000],
        ], $overrides);
    }

    protected function auth(): self
    {
        return $this->withToken($this->token);
    }

    // ── Create ─────────────────────────────────────────────────────────

    public function test_bisa_membuat_pesanan_serta_menghitung_total_harga(): void
    {
        $car = $this->makeCar();

        $response = $this->auth()->postJson('/api/bookings', $this->payload($car));

        $response->assertCreated()
            ->assertJsonPath('redirect_path', '/bookings/1');

        $booking = Booking::first();

        $this->assertNotNull($booking);
        // 3 hari (1 s.d. 4 Oktober) x 400.000 = 1.200.000
        $this->assertSame('2026-10-01', $booking->tanggal_mulai->toDateString());
        $this->assertSame(1200000.0, (float) $booking->total_harga);
        $this->assertSame(0.0, (float) $booking->total_addons);
        $this->assertSame('Menunggu', $booking->status_rental);
        $this->assertSame($this->admin->id, $booking->created_by);

        // Mobil langsung ditandai sedang disewa supaya tidak bisa
        // dipesan ganda oleh operator lain.
        $this->assertSame('Sedang Disewa', $car->fresh()->status);
        $this->assertSame(1, BookingCar::where('booking_id', $booking->id)->count());
    }

    public function test_nomor_pesanan_urut_dan_direset_ditiap_hari(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(9, 0));

        $this->auth()->postJson('/api/bookings', $this->payload($this->makeCar()))->assertCreated();
        $this->auth()->postJson('/api/bookings', $this->payload($this->makeCar()))->assertCreated();

        $this->travelTo(now()->setDate(2026, 10, 2)->setTime(9, 0));
        $this->auth()->postJson('/api/bookings', $this->payload($this->makeCar()))->assertCreated();

        $nomor = Booking::orderBy('id')->pluck('nomor_pesanan')->all();

        $this->assertSame(['20261001-0001', '20261001-0002', '20261002-0001'], $nomor);
    }

    public function test_dp_mencatat_pemasukan_di_buku_keuangan(): void
    {
        $car = $this->makeCar();

        $this->auth()->postJson('/api/bookings', $this->payload($car, [
            'status_pembayaran' => 'DP',
            'dp_amount' => 500000,
        ]))->assertCreated();

        $booking = Booking::first();
        $this->assertSame(500000.0, (float) $booking->dp_amount);

        $transaction = Transaction::where('referensi_type', 'booking')->first();
        $this->assertNotNull($transaction);
        $this->assertSame('Pemasukan', $transaction->tipe);
        $this->assertSame(500000.0, (float) $transaction->jumlah);
    }

    public function test_belum_bayar_tidak_mencatat_pemasukan(): void
    {
        $this->auth()->postJson('/api/bookings', $this->payload($this->makeCar()))->assertCreated();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_total_harga_menjumlahkan_biaya_tambahan(): void
    {
        $car = $this->makeCar();

        $this->auth()->postJson('/api/bookings', $this->payload($car, [
            'addon_nama' => ['BBM', 'Parkir Tol'],
            'addon_nominal' => [100000, 50000],
        ]))->assertCreated();

        $booking = Booking::first();

        $this->assertSame(150000.0, (float) $booking->total_addons);
        $this->assertSame(1350000.0, (float) $booking->total_harga);
        $this->assertSame(2, BookingAddon::where('booking_id', $booking->id)->count());
    }

    public function test_pesanan_menolak_tanggal_selesai_sebelum_mulai(): void
    {
        $response = $this->auth()->postJson('/api/bookings', $this->payload($this->makeCar(), [
            'tanggal_selesai' => '2026-09-01',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('tanggal_selesai');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_pesanan_wajib_punya_minimal_satu_kendaraan(): void
    {
        $this->auth()->postJson('/api/bookings', $this->payload($this->makeCar(), [
            'cars' => [],
            'harga_per_hari' => [],
        ]))->assertStatus(422)->assertJsonValidationErrors('cars');
    }

    public function test_foto_ktp_dan_penyewa_tersimpan_sebagai_path_yang_aman(): void
    {
        Storage::fake('public');

        $this->auth()->postJson('/api/bookings', $this->payload($this->makeCar(), [
            'foto_ktp' => [UploadedFile::fake()->image('ktp.jpg')],
            'foto_penyewa' => [UploadedFile::fake()->image('penyewa.jpg')],
        ]))->assertCreated();

        $booking = Booking::first();

        $this->assertCount(1, $booking->foto_ktp);
        $this->assertStringStartsWith('bookings/ktp/', $booking->foto_ktp[0]);
        Storage::disk('public')->assertExists($booking->foto_ktp[0]);
    }

    // ── Lock / edit ────────────────────────────────────────────────────

    public function test_pesanan_yang_sudah_aktif_tidak_bisa_dit_edit(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car))->assertCreated();

        $booking = Booking::first();
        $booking->update(['status_rental' => 'Aktif']);

        $this->auth()->getJson("/api/bookings/{$booking->id}/edit")
            ->assertStatus(423)
            ->assertJsonPath('locked', true)
            ->assertJsonPath('redirect_path', "/bookings/{$booking->id}");

        $this->auth()->putJson("/api/bookings/{$booking->id}", $this->payload($car))
            ->assertStatus(423);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'nama_pelanggan' => 'Budi Santoso']);
    }

    public function test_edit_pesanan_melepas_kendaraan_lama_dan_memasang_baru(): void
    {
        $mobilA = $this->makeCar();
        $mobilB = $this->makeCar();

        $this->auth()->postJson('/api/bookings', $this->payload($mobilA))->assertCreated();
        $booking = Booking::first();

        $this->auth()->putJson("/api/bookings/{$booking->id}", $this->payload($mobilB, [
            'nama_pelanggan' => 'Siti Aminah',
            'cars' => [$mobilB->id],
            'harga_per_hari' => [500000],
        ]))->assertOk();

        $this->assertSame('Siti Aminah', $booking->fresh()->nama_pelanggan);
        $this->assertSame([$mobilB->id], BookingCar::where('booking_id', $booking->id)->pluck('car_id')->all());
        $this->assertSame('Tersedia', $mobilA->fresh()->status);
    }

    // ── Biaya tambahan ─────────────────────────────────────────────────

    public function test_bisa_menambah_dan_menghapus_biaya_tambahan(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car))->assertCreated();
        $booking = Booking::first();
        $totalAwal = (float) $booking->total_harga;

        $response = $this->auth()->postJson("/api/bookings/{$booking->id}/addon", [
            'addon_nama' => 'Driver Tambahan',
            'addon_nominal' => 150000,
        ]);

        $response->assertCreated();
        $this->assertSame($totalAwal + 150000, (float) $booking->fresh()->total_harga);

        $addon = BookingAddon::where('booking_id', $booking->id)->firstOrFail();

        $this->auth()->deleteJson("/api/bookings/{$booking->id}/addon/{$addon->id}")->assertOk();

        $this->assertSame($totalAwal, (float) $booking->fresh()->total_harga);
        $this->assertDatabaseCount('booking_addons', 0);
    }

    public function test_biaya_tambahan_tertutup_setelah_pesanan_selesai(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car))->assertCreated();
        $booking = Booking::first();
        $booking->update(['status_rental' => 'Selesai']);

        $this->auth()->postJson("/api/bookings/{$booking->id}/addon", [
            'addon_nama' => 'Telat Bayar',
            'addon_nominal' => 50000,
        ])->assertStatus(423);

        $this->assertDatabaseCount('booking_addons', 0);
    }

    // ── Status pembayaran ──────────────────────────────────────────────

    public function test_ubah_status_memperbarui_buku_keuangan_dan_mencatat_riwayat(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car))->assertCreated();
        $booking = Booking::first();

        $this->auth()->postJson("/api/bookings/{$booking->id}/update-status", [
            'status_pembayaran' => 'Lunas',
            'status_rental' => 'Aktif',
        ])->assertOk();

        $booking->refresh();

        $this->assertSame('Lunas', $booking->status_pembayaran);
        $this->assertSame('Aktif', $booking->status_rental);
        $this->assertSame(1200000.0, (float) $booking->dp_amount);
        $this->assertNotNull($booking->picked_up_at);
        $this->assertSame('Sedang Disewa', $car->fresh()->status);

        $this->assertDatabaseHas('transactions', [
            'referensi_type' => 'booking',
            'referensi_id' => $booking->id,
            'jumlah' => 1200000.0,
        ]);

        $this->assertDatabaseHas('booking_payments', [
            'booking_id' => $booking->id,
            'status_sebelum' => 'Belum Bayar',
            'status_sesudah' => 'Lunas',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_status_belum_bayar_menghapus_baris_pemasukan(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car, [
            'status_pembayaran' => 'Lunas',
        ]))->assertCreated();

        $booking = Booking::first();
        $this->assertDatabaseCount('transactions', 1);

        $this->auth()->postJson("/api/bookings/{$booking->id}/update-status", [
            'status_pembayaran' => 'Belum Bayar',
            'status_rental' => 'Menunggu',
        ])->assertOk();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_status_yang_tidak_berubah_tidak_menulis_riwayat_baru(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car))->assertCreated();
        $booking = Booking::first();

        $this->auth()->postJson("/api/bookings/{$booking->id}/update-status", [
            'status_pembayaran' => 'Belum Bayar',
            'status_rental' => 'Menunggu',
        ])->assertOk()->assertJsonPath('unchanged', true);

        $this->assertDatabaseCount('booking_payments', 0);
    }

    public function test_pengembalian_membebaskan_kendaraan(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car))->assertCreated();
        $booking = Booking::first();

        $this->auth()->postJson("/api/bookings/{$booking->id}/update-status", [
            'status_pembayaran' => 'DP',
            'status_rental' => 'Aktif',
            'dp_amount' => 500000,
        ])->assertOk();

        $this->auth()->postJson("/api/bookings/{$booking->id}/update-status", [
            'status_pembayaran' => 'DP',
            'status_rental' => 'Dikembalikan',
            'dp_amount' => 500000,
        ])->assertOk();

        $this->assertSame('Tersedia', $car->fresh()->status);
        $this->assertNotNull($booking->fresh()->returned_at);
    }

    // ── Odometer ───────────────────────────────────────────────────────

    public function test_odometer_naik_mencatat_log(): void
    {
        $car = $this->makeCar(['km_saat_ini' => 10000]);
        $this->auth()->postJson('/api/bookings', $this->payload($car))->assertCreated();
        $booking = Booking::first();

        $this->auth()->postJson("/api/bookings/{$booking->id}/update-odometer", [
            'km_kembali' => [$car->id => 10500],
        ])->assertOk();

        $this->assertSame(10500.0, (float) $car->fresh()->km_saat_ini);
        $this->assertDatabaseHas('odometer_logs', ['car_id' => $car->id, 'km_reading' => 10500]);
    }

    public function test_odometer_yang_mundur_ditolak(): void
    {
        $car = $this->makeCar(['km_saat_ini' => 10000]);
        $this->auth()->postJson('/api/bookings', $this->payload($car))->assertCreated();
        $booking = Booking::first();

        $this->auth()->postJson("/api/bookings/{$booking->id}/update-odometer", [
            'km_kembali' => [$car->id => 9000],
        ])->assertStatus(422)->assertJsonValidationErrors("km_kembali.{$car->id}");

        $this->assertSame(10000.0, (float) $car->fresh()->km_saat_ini);
        $this->assertDatabaseCount('odometer_logs', 0);
    }

    // ── Lock / unlock ──────────────────────────────────────────────────

    public function test_kunci_dan_buka_kunci_pesanan(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car))->assertCreated();
        $booking = Booking::first();

        $this->auth()->postJson("/api/bookings/{$booking->id}/lock")->assertOk();
        $this->assertSame('Selesai', $booking->fresh()->status_rental);
        $this->assertSame('Tersedia', $car->fresh()->status);

        $this->auth()->postJson("/api/bookings/{$booking->id}/unlock")->assertOk();
        $this->assertSame('Dikembalikan', $booking->fresh()->status_rental);

        // Setelah dibuka, data boleh diedit lagi.
        $this->auth()->putJson("/api/bookings/{$booking->id}", $this->payload($car, [
            'nama_pelanggan' => 'Siti Aminah',
        ]))->assertOk();

        $this->assertSame('Siti Aminah', $booking->fresh()->nama_pelanggan);
    }

    // ── Hapus ──────────────────────────────────────────────────────────

    public function test_menghapus_pesanan_membersihkan_kendaraan_dan_keuangan(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car, [
            'status_pembayaran' => 'Lunas',
        ]))->assertCreated();
        $booking = Booking::first();

        $this->auth()->deleteJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('redirect_path', '/bookings');

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseCount('booking_car', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('Tersedia', $car->fresh()->status);
    }

    // ── Refund ─────────────────────────────────────────────────────────

    public function test_refund_hanya_boleh_untuk_pesanan_batal_yang_sudah_dp(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car, [
            'status_pembayaran' => 'DP',
            'dp_amount' => 500000,
        ]))->assertCreated();
        $booking = Booking::first();

        // Belum batal -> ditolak.
        $this->auth()->postJson("/api/bookings/{$booking->id}/refund", [
            'refund_amount' => 100000,
            'refund_method' => 'Transfer',
        ])->assertStatus(422);

        $booking->update(['status_rental' => 'Batal']);

        $this->auth()->postJson("/api/bookings/{$booking->id}/refund", [
            'refund_amount' => 200000,
            'refund_method' => 'Transfer',
            'refund_notes' => 'Sisa uang sewa',
        ])->assertCreated()->assertJsonPath('refund_amount', 200000);

        $this->assertSame(200000.0, (float) $booking->fresh()->refund_amount);
        $this->assertDatabaseHas('transactions', [
            'referensi_id' => $booking->id,
            'tipe' => 'Pengeluaran',
            'jumlah' => 200000.0,
        ]);
    }

    public function test_refund_melebihi_dana_yang_dibayar_ditolak(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car, [
            'status_pembayaran' => 'DP',
            'dp_amount' => 500000,
        ]))->assertCreated();
        $booking = Booking::first();
        $booking->update(['status_rental' => 'Batal']);

        $this->auth()->postJson("/api/bookings/{$booking->id}/refund", [
            'refund_amount' => 600000,
            'refund_method' => 'Tunai',
        ])->assertStatus(422);

        $this->assertSame(0.0, (float) $booking->fresh()->refund_amount);
    }

    // ── Daftar & kalender ──────────────────────────────────────────────

    public function test_daftar_pesanan_bisa_difilter(): void
    {
        $this->auth()->postJson('/api/bookings', $this->payload($this->makeCar(), [
            'nama_pelanggan' => 'Budi Santoso',
        ]))->assertCreated();

        $this->auth()->postJson('/api/bookings', $this->payload($this->makeCar(), [
            'nama_pelanggan' => 'Siti Aminah',
            'status_pembayaran' => 'Lunas',
        ]))->assertCreated();

        $response = $this->auth()->getJson('/api/bookings?search=Siti')->assertOk();
        $this->assertCount(1, $response->json('bookings.data'));

        $response = $this->auth()->getJson('/api/bookings?status_pembayaran=Lunas')->assertOk();
        $this->assertCount(1, $response->json('bookings.data'));

        $response = $this->auth()->getJson('/api/bookings')->assertOk();
        $this->assertCount(2, $response->json('bookings.data'));
        $this->assertSame(2, $response->json('bookings.total'));
    }

    public function test_endpoint_create_menampilkan_kendaraan_yang_tersedia(): void
    {
        $tersedia = $this->makeCar();
        $terpakai = $this->makeCar(['status' => 'Sedang Disewa']);

        $response = $this->auth()->getJson('/api/bookings/create')->assertOk();

        $ids = collect($response->json('availableCars'))->pluck('id')->all();

        $this->assertContains($tersedia->id, $ids);
        $this->assertNotContains($terpakai->id, $ids);
    }

    public function test_kalender_menghasilkan_satu_event_per_kendaraan(): void
    {
        $mobilA = $this->makeCar();
        $mobilB = $this->makeCar();

        $this->auth()->postJson('/api/bookings', $this->payload($mobilA, [
            'cars' => [$mobilA->id, $mobilB->id],
            'harga_per_hari' => [400000, 400000],
        ]))->assertCreated();

        $response = $this->auth()->getJson('/api/bookings/calendar')->assertOk();

        // Satu booking dengan 2 mobil harus muncul sebagai 2 baris kalender.
        $this->assertCount(2, $response->json('bookings'));
        $this->assertCount(2, $response->json('armada'));
    }

    // ── Jejak aktivitas ────────────────────────────────────────────────

    public function test_aktivitas_tercatat_dengan_user_yang_login(): void
    {
        $car = $this->makeCar();
        $this->auth()->postJson('/api/bookings', $this->payload($car))->assertCreated();
        $booking = Booking::first();

        $this->assertDatabaseHas('activities', [
            'user_id' => $this->admin->id,
            'type' => 'booking',
            'title' => 'Pesanan Baru Dibuat',
            'subject_type' => 'booking',
            'subject_id' => $booking->id,
        ]);

        $activity = Activity::where('title', 'Pesanan Baru Dibuat')->first();
        $this->assertSame($this->admin->nama, $activity->user);
    }
}
