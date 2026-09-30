<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\BookingCar;
use App\Models\BookingPayment;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\OdometerLog;
use App\Models\Transaction;
use App\Support\FileUrl;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Booking / pesanan penyewaan.
 *
 * Logika bisnis identik dengan versi Blade. Yang berubah:
 *   - return view()/redirect()/back() -> JSON + status code yang jelas
 *   - auth() -> request()->user()  (token Sanctum, bukan session)
 *   - file foto -> signed URL lewat App\Support\FileUrl
 *
 * CATATAN PENTING SOAL TRANSAKSI
 * -----------------------------
 * Versi Blade memakai DB::beginTransaction() + try/catch manual. Pola
 * itu dipertahankan di sini, tapi lewat DB::transaction() supaya
 * rollback terjadi otomatis pada exception -- termasuk pada error validasi
 * di dalam callback, yang pada pola manual bisa lolos tanpa rollback.
 */
class BookingController extends Controller
{
    /** Status rental yang membuat data pesanan terkunci (tidak bisa diedit). */
    private const LOCKED = ['Aktif', 'Selesai', 'Batal'];

    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['bookingCars.car'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('nomor_pesanan', 'like', "%{$search}%")
                ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                ->orWhere('kontak_pelanggan', 'like', "%{$search}%"));
        }

        if ($request->filled('status_pembayaran')) {
            $query->where('status_pembayaran', $request->status_pembayaran);
        }

        if ($request->filled('tipe_sewa')) {
            $query->where('tipe_sewa', $request->tipe_sewa);
        }

        $bookings = $query->paginate((int) $request->query('per_page', 10))->withQueryString();

        return response()->json([
            'bookings' => $bookings->through(fn (Booking $booking) => $this->bookingPayload($booking)),
        ]);
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'availableCars' => Car::where('status', 'Tersedia')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->validateBookingPayload($request);

        $booking = DB::transaction(function () use ($request) {
            $nomorPesanan = $this->generateNomorPesanan();

            $durasi = $this->durasiHari($request);
            $totalAddons = $this->totalAddons($request);
            $totalHarga = $this->totalHarga($request, $durasi, $totalAddons);

            $booking = Booking::create([
                'nomor_pesanan' => $nomorPesanan,
                'nama_pelanggan' => $request->nama_pelanggan,
                'kontak_pelanggan' => $request->kontak_pelanggan,
                'email' => $request->email,
                'foto_ktp' => $this->simpanFoto($request, 'foto_ktp', 'bookings/ktp'),
                'foto_penyewa' => $this->simpanFoto($request, 'foto_penyewa', 'bookings/penyewa'),
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'lokasi_jemput' => $request->lokasi_jemput,
                'lokasi_antar' => $request->lokasi_antar,
                'tipe_sewa' => $request->tipe_sewa,
                'status_pembayaran' => $request->status_pembayaran,
                'status_rental' => 'Menunggu',
                'total_harga' => $totalHarga,
                'total_addons' => $totalAddons,
                'dp_amount' => $this->dpAmount($request, $totalHarga),
                'catatan' => $request->catatan,
                'created_by' => $request->user()->id,
            ]);

            $this->syncBookingCars($request, $booking);
            $this->syncAddons($request, $booking);

            // Pemasukan dicatat otomatis begitu ada uang masuk.
            if (in_array($request->status_pembayaran, ['Lunas', 'DP'], true)) {
                $jumlahBayar = $request->status_pembayaran === 'Lunas' ? $totalHarga : $request->dp_amount;
                $teks = $request->status_pembayaran === 'Lunas' ? 'Pembayaran Lunas' : 'Pembayaran DP';

                Transaction::create([
                    'tipe' => 'Pemasukan',
                    'kategori' => 'sewa',
                    'jumlah' => $jumlahBayar,
                    'tanggal' => now()->toDateString(),
                    'deskripsi' => "{$teks} {$nomorPesanan} - {$request->nama_pelanggan}",
                    'referensi_type' => 'booking',
                    'referensi_id' => $booking->id,
                ]);
            }

            $this->logActivity(
                $request,
                'add_circle',
                'emerald',
                'Pesanan Baru Dibuat',
                "{$request->user()->nama} membuat pesanan #{$nomorPesanan} untuk {$request->nama_pelanggan}",
                $booking->id
            );

            return $booking;
        });

        return response()->json([
            'message' => "Pesanan #{$booking->nomor_pesanan} berhasil dibuat!",
            'booking_id' => $booking->id,
            'redirect_path' => "/bookings/{$booking->id}",
        ], 201);
    }

    /**
     * GET /api/bookings/{booking}/edit
     *
     * Kalau pesanan terkunci, backend TIDAK diam-diam mengembalikan form
     * kosong. Frontend menerima 423 + redirect_path supaya bisa
     * mengarahkan user ke halaman detail dengan pesan yang jelas.
     */
    public function edit(Booking $booking): JsonResponse
    {
        $booking->load(['bookingCars.car', 'addons']);

        if ($this->isLocked($booking)) {
            return response()->json([
                'message' => 'Pesanan yang sedang berjalan, selesai, atau batal tidak dapat diubah datanya.',
                'locked' => true,
                'redirect_path' => "/bookings/{$booking->id}",
            ], 423);
        }

        $bookedCarIds = $booking->bookingCars->pluck('car_id')->toArray();

        return response()->json([
            'booking' => $this->bookingPayload($booking, withFiles: true),
            'availableCars' => Car::where('status', 'Tersedia')
                ->orWhereIn('id', $bookedCarIds)
                ->get(),
        ]);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $booking->load(['bookingCars', 'addons']);

        if ($this->isLocked($booking)) {
            return response()->json([
                'message' => 'Aksi ditolak. Pesanan sudah dikunci dari perubahan data.',
                'locked' => true,
                'redirect_path' => "/bookings/{$booking->id}",
            ], 423);
        }

        // status_pembayaran sengaja TIDAK ada di sini: form edit tidak
        // mengirimnya, dan diubah lewat endpoint status terpisah.
        $this->validateBookingPayload($request, requirePaymentStatus: false);

        DB::transaction(function () use ($request, $booking) {
            $durasi = $this->durasiHari($request);
            $totalAddons = $this->totalAddons($request);
            $totalHarga = $this->totalHarga($request, $durasi, $totalAddons);

            $booking->update([
                'nama_pelanggan' => $request->nama_pelanggan,
                'kontak_pelanggan' => $request->kontak_pelanggan,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'lokasi_jemput' => $request->lokasi_jemput,
                'lokasi_antar' => $request->lokasi_antar,
                'tipe_sewa' => $request->tipe_sewa,
                'total_harga' => $totalHarga,
                'total_addons' => $totalAddons,
                'catatan' => $request->catatan,
                'foto_ktp' => $this->kelolaFoto($request, $booking, 'foto_ktp', 'hapus_foto_ktp', 'bookings/ktp'),
                'foto_penyewa' => $this->kelolaFoto($request, $booking, 'foto_penyewa', 'hapus_foto_penyewa', 'bookings/penyewa'),
            ]);

            // Kendaraan lama dibebaskan dulu, lalu daftar baru dipasang.
            Car::whereIn('id', $booking->bookingCars->pluck('car_id'))->update(['status' => 'Tersedia']);
            $booking->bookingCars()->delete();

            $this->syncBookingCars($request, $booking);
            $this->syncAddons($request, $booking);

            // Kalau sudah Lunas, nominal pemasukan ikut mengikuti total baru.
            if ($booking->status_pembayaran === 'Lunas') {
                Transaction::where('referensi_type', 'booking')
                    ->where('referensi_id', $booking->id)
                    ->update(['jumlah' => $totalHarga]);
            }

            $this->logActivity(
                $request,
                'edit_document',
                'amber',
                'Data Pesanan Diedit',
                "{$request->user()->nama} memperbarui data pelanggan atau unit kendaraan",
                $booking->id
            );
        });

        return response()->json([
            'message' => "Data Pesanan #{$booking->nomor_pesanan} berhasil diperbarui!",
            'redirect_path' => "/bookings/{$booking->id}",
        ]);
    }

    /**
     * POST /api/bookings/{booking}/status
     *
     * Endpoint ini yang menjalankan perubahan status pembayaran & rental dari
     * halaman detail, sekaligus menyinkronkan buku keuangan dan status
     * kendaraan.
     */
    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'status_pembayaran' => ['required', 'in:Belum Bayar,DP,Lunas'],
            'status_rental' => ['required', 'in:Menunggu,Aktif,Dikembalikan,Selesai,Batal'],
            'dp_amount' => ['required_if:status_pembayaran,DP', 'nullable', 'numeric', 'min:0'],
            'metode_bayar' => ['nullable', 'string', 'max:50'],
        ]);

        $booking->load('bookingCars');

        $oldPembayaran = $booking->status_pembayaran;
        $oldRental = $booking->status_rental;

        if ($oldPembayaran === $request->status_pembayaran && $oldRental === $request->status_rental) {
            return response()->json([
                'message' => 'Status pesanan tidak berubah.',
                'unchanged' => true,
            ]);
        }

        DB::transaction(function () use ($request, $booking, $oldPembayaran) {
            $booking->update([
                'status_pembayaran' => $request->status_pembayaran,
                'status_rental' => $request->status_rental,
                'dp_amount' => match ($request->status_pembayaran) {
                    'DP' => (float) $request->dp_amount,
                    'Lunas' => $booking->total_harga,
                    default => 0,
                },
            ]);

            $carIds = $booking->bookingCars->pluck('car_id')->toArray();

            // Status rental menentukan status kendaraan. 'Dikembalikan'
            // juga langsung membebaskan mobil -- sama seperti Selesai.
            if (in_array($request->status_rental, ['Dikembalikan', 'Selesai', 'Batal'], true)) {
                Car::whereIn('id', $carIds)->update(['status' => 'Tersedia']);

                if (in_array($request->status_rental, ['Dikembalikan', 'Selesai'], true) && $booking->returned_at === null) {
                    $booking->update(['returned_at' => now()]);
                }
            } elseif (in_array($request->status_rental, ['Aktif', 'Menunggu'], true)) {
                Car::whereIn('id', $carIds)->update(['status' => 'Sedang Disewa']);

                if ($request->status_rental === 'Aktif' && $booking->picked_up_at === null) {
                    $booking->update(['picked_up_at' => now()]);
                }
            }

            $this->syncTransaction($request, $booking);

            if ($oldPembayaran !== $request->status_pembayaran) {
                BookingPayment::create([
                    'booking_id' => $booking->id,
                    'jumlah' => $booking->dp_amount,
                    'status_sebelum' => $oldPembayaran,
                    'status_sesudah' => $request->status_pembayaran,
                    'metode_bayar' => $request->metode_bayar,
                    'created_by' => $request->user()->id,
                    'created_at' => now(),
                ]);
            }

            $this->logActivity(
                $request,
                'sync_alt',
                'blue',
                'Status Pesanan Diubah',
                "Status berubah ke: {$request->status_rental} / {$request->status_pembayaran}",
                $booking->id
            );
        });

        return response()->json([
            'message' => 'Status pesanan berhasil diperbarui!',
            'booking' => $this->bookingPayload($booking->fresh(['bookingCars.car'])),
        ]);
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load(['bookingCars.car', 'addons']);

        $lastPayment = BookingPayment::where('booking_id', $booking->id)
            ->latest('created_at')
            ->first();

        $invoice = Invoice::with('signatures')
            ->where('booking_id', $booking->id)
            ->latest('id')
            ->first();

        return response()->json([
            'booking' => $this->bookingPayload($booking, withFiles: true),
            'lastPayment' => $lastPayment,
            'invoice' => $invoice,
            'locked' => $this->isLocked($booking),
        ]);
    }

    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        $booking->load('bookingCars');

        DB::transaction(function () use ($booking) {
            Transaction::where('referensi_type', 'booking')
                ->where('referensi_id', $booking->id)
                ->delete();

            Car::whereIn('id', $booking->bookingCars->pluck('car_id'))
                ->update(['status' => 'Tersedia']);

            $booking->delete();
        });

        return response()->json([
            'message' => 'Pesanan berhasil dihapus.',
            'redirect_path' => '/bookings',
        ]);
    }

    /**
     * POST /api/bookings/{booking}/addon
     */
    public function storeAddon(Request $request, Booking $booking): JsonResponse
    {
        if (in_array($booking->status_rental, ['Selesai', 'Batal'], true)) {
            return response()->json([
                'message' => 'Tidak bisa menambah biaya pada pesanan yang sudah selesai atau dibatalkan.',
                'locked' => true,
            ], 423);
        }

        $request->validate([
            'addon_nama' => ['required', 'string', 'max:100'],
            'addon_nominal' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $booking) {
            $addon = BookingAddon::create([
                'booking_id' => $booking->id,
                'nama_biaya' => $request->addon_nama,
                'nominal' => (float) $request->addon_nominal,
            ]);

            $booking->total_addons += $addon->nominal;
            $booking->total_harga += $addon->nominal;
            $booking->save();

            if ($booking->status_pembayaran === 'Lunas') {
                Transaction::where('referensi_type', 'booking')
                    ->where('referensi_id', $booking->id)
                    ->update(['jumlah' => $booking->total_harga]);
            }

            $this->logActivity(
                $request,
                'add_shopping_cart',
                'blue',
                'Biaya Tambahan Ditambahkan',
                'Ditambahkan: '.$request->addon_nama.' — Rp '.number_format($addon->nominal, 0, ',', '.'),
                $booking->id
            );
        });

        return response()->json([
            'message' => 'Biaya tambahan "'.$request->addon_nama.'" berhasil ditambahkan.',
            'booking' => $this->bookingPayload($booking->fresh(['bookingCars.car', 'addons'])),
        ], 201);
    }

    public function destroyAddon(Request $request, Booking $booking, int $addonId): JsonResponse
    {
        if (in_array($booking->status_rental, self::LOCKED, true)) {
            return response()->json([
                'message' => 'Tidak bisa menghapus biaya pada pesanan yang sudah aktif, selesai, atau dibatalkan.',
                'locked' => true,
            ], 423);
        }

        DB::transaction(function () use ($booking, $addonId, $request) {
            $addon = BookingAddon::where('booking_id', $booking->id)->findOrFail($addonId);

            $booking->total_addons -= $addon->nominal;
            $booking->total_harga -= $addon->nominal;
            $booking->save();

            $namaAddon = $addon->nama_biaya;
            $nominalAddon = $addon->nominal;
            $addon->delete();

            if ($booking->status_pembayaran === 'Lunas') {
                Transaction::where('referensi_type', 'booking')
                    ->where('referensi_id', $booking->id)
                    ->update(['jumlah' => $booking->total_harga]);
            }

            $this->logActivity(
                $request,
                'remove_shopping_cart',
                'red',
                'Biaya Tambahan Dihapus',
                'Dihapus: '.$namaAddon.' — Rp '.number_format($nominalAddon, 0, ',', '.'),
                $booking->id
            );
        });

        return response()->json([
            'message' => 'Biaya tambahan berhasil dihapus.',
            'booking' => $this->bookingPayload($booking->fresh(['bookingCars.car', 'addons'])),
        ]);
    }

    /**
     * POST /api/bookings/{booking}/odometer
     */
    public function updateOdometer(Request $request, Booking $booking): JsonResponse
    {
        if (in_array($booking->status_rental, ['Selesai', 'Batal'], true)) {
            return response()->json([
                'message' => 'Pesanan sudah dikunci.',
                'locked' => true,
            ], 423);
        }

        $booking->load('bookingCars.car');

        $request->validate([
            'km_kembali' => ['required', 'array'],
            'km_kembali.*' => ['required', 'numeric', 'min:0'],
        ]);

        $updated = DB::transaction(function () use ($request, $booking) {
            $updated = [];

            foreach ($booking->bookingCars as $bc) {
                $car = $bc->car;

                if (! $car) {
                    continue;
                }

                $kmBaru = $request->km_kembali[$car->id] ?? $car->km_saat_ini;

                // Odometer tidak boleh mundur: kalau iya, berarti input salah
                // atau ada yang mengisi form(Id) dari layar lain.
                if ($kmBaru < $car->km_saat_ini) {
                    throw ValidationException::withMessages([
                        "km_kembali.{$car->id}" => "KM {$car->plat_nomor} tidak boleh mundur.",
                    ]);
                }

                if ($kmBaru > $car->km_saat_ini) {
                    $car->update(['km_saat_ini' => $kmBaru]);

                    OdometerLog::create([
                        'car_id' => $car->id,
                        'km_reading' => $kmBaru,
                        'created_at' => now(),
                    ]);

                    $updated[] = [
                        'car_id' => $car->id,
                        'plat_nomor' => $car->plat_nomor,
                        'km_saat_ini' => (int) $kmBaru,
                    ];
                }
            }

            return $updated;
        });

        return response()->json([
            'message' => 'Odometer armada berhasil diperbarui!',
            'updated' => $updated,
        ]);
    }

    public function lockBooking(Request $request, Booking $booking): JsonResponse
    {
        DB::transaction(function () use ($request, $booking) {
            $booking->update(['status_rental' => 'Selesai']);

            Car::whereIn('id', BookingCar::where('booking_id', $booking->id)->pluck('car_id'))
                ->update(['status' => 'Tersedia']);

            $this->logActivity(
                $request,
                'lock',
                'emerald',
                'Pesanan Dikunci (Selesai)',
                'Seluruh tagihan dan data kendaraan telah dikunci.',
                $booking->id
            );
        });

        return response()->json([
            'message' => 'Pesanan berhasil dikunci permanen!',
            'booking' => $this->bookingPayload($booking->fresh(['bookingCars.car'])),
        ]);
    }

    public function unlockBooking(Request $request, Booking $booking): JsonResponse
    {
        // Dikembalikan, bukan Menunggu: mobil tetap Tersedia tapi data
        // pesanan bisa direvisi -- sama seperti perilaku versi Blade.
        $booking->update(['status_rental' => 'Dikembalikan']);

        $this->logActivity(
            $request,
            'lock_open',
            'amber',
            'Kunci Pesanan Dibuka',
            "{$request->user()->nama} membuka kunci pesanan untuk revisi data.",
            $booking->id
        );

        return response()->json([
            'message' => 'Kunci pesanan berhasil dibuka.',
            'booking' => $this->bookingPayload($booking->fresh(['bookingCars.car'])),
        ]);
    }

    /**
     * GET /api/bookings/calendar
     */
    public function calendar(): JsonResponse
    {
        $armada = Car::select('id', 'plat_nomor', 'merk', 'model', 'status')
            ->get()
            ->map(fn (Car $car) => [
                'id' => $car->id,
                'plat_nomor' => $car->plat_nomor,
                'nama' => trim($car->merk.' '.$car->model),
                'status' => $car->status,
            ]);

        // Event dikalikan per kendaraan: satu booking dengan 3 mobil
        // menghasilkan 3 baris kalender.
        $bookings = Booking::select(
            'id', 'nomor_pesanan', 'nama_pelanggan', 'tanggal_mulai', 'tanggal_selesai',
            'status_rental', 'status_pembayaran', 'total_harga', 'tipe_sewa',
            'lokasi_jemput', 'lokasi_antar'
        )
            ->with(['bookingCars.car'])
            ->get()
            ->flatMap(fn (Booking $booking) => $booking->bookingCars->map(fn (BookingCar $bc) => [
                'booking_id' => $booking->id,
                'nomor_pesanan' => $booking->nomor_pesanan,
                'nama_pelanggan' => $booking->nama_pelanggan,
                'tanggal_mulai' => $booking->tanggal_mulai,
                'tanggal_selesai' => $booking->tanggal_selesai,
                'status' => strtolower(str_replace(' ', '_', $booking->status_pembayaran)),
                'status_rental' => $booking->status_rental,
                'total_harga' => $booking->total_harga,
                'tipe_sewa' => $booking->tipe_sewa,
                'plat_nomor' => $bc->car?->plat_nomor,
                'car_id' => $bc->car_id,
                'nama_mobil' => $bc->car ? trim($bc->car->merk.' '.$bc->car->model) : '',
                'lokasi_jemput' => $booking->lokasi_jemput,
                'lokasi_antar' => $booking->lokasi_antar,
            ]))
            ->values();

        return response()->json([
            'armada' => $armada,
            'bookings' => $bookings,
        ]);
    }

    /**
     * POST /api/bookings/{booking}/refund
     */
    public function processRefund(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'refund_amount' => ['required', 'numeric', 'min:1'],
            'refund_method' => ['required', 'string', 'max:50'],
            'refund_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $currentRefund = (float) ($booking->refund_amount ?? 0);
        $newRefund = (float) $request->refund_amount;

        // Guard di luar transaksi: menolak lebih awal, tanpa lock.
        if ($booking->status_rental !== 'Batal' || $booking->dp_amount <= 0) {
            return response()->json([
                'message' => 'Pesanan ini tidak memenuhi syarat untuk refund.',
            ], 422);
        }

        if (($currentRefund + $newRefund) > $booking->dp_amount) {
            return response()->json([
                'message' => 'Nominal refund melebihi total dana yang telah dibayarkan pelanggan.',
            ], 422);
        }

        DB::transaction(function () use ($request, $booking, $currentRefund, $newRefund) {
            $booking->update(['refund_amount' => $currentRefund + $newRefund]);

            $catatan = $request->refund_notes ? " | Catatan: {$request->refund_notes}" : '';

            Transaction::create([
                'tipe' => 'Pengeluaran',
                'kategori' => 'sewa',
                'jumlah' => $newRefund,
                'tanggal' => now()->toDateString(),
                'deskripsi' => "Refund Batal {$booking->nomor_pesanan} - {$booking->nama_pelanggan} (".ucfirst($request->refund_method)."){$catatan}",
                'referensi_type' => 'booking',
                'referensi_id' => $booking->id,
            ]);

            $this->logActivity(
                $request,
                'currency_exchange',
                'purple',
                'Dana Dikembalikan (Refund)',
                $request->user()->nama.' memproses refund sebesar Rp '.number_format($newRefund, 0, ',', '.').' via '.ucfirst($request->refund_method),
                $booking->id
            );
        });

        return response()->json([
            'message' => 'Refund sebesar Rp '.number_format($newRefund, 0, ',', '.').' berhasil dicatat.',
            'refund_amount' => $currentRefund + $newRefund,
        ], 201);
    }

    // ── Helper ─────────────────────────────────────────────────────────

    private function isLocked(Booking $booking): bool
    {
        return in_array($booking->status_rental, self::LOCKED, true);
    }

    private function validateBookingPayload(Request $request, bool $requirePaymentStatus = true): void
    {
        $rules = [
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'kontak_pelanggan' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'foto_ktp.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'foto_penyewa.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'catatan' => ['nullable', 'string'],
            'tipe_sewa' => ['required', 'in:harian,kontrak'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'cars' => ['required', 'array', 'min:1'],
            'cars.*' => ['required', 'exists:cars,id'],
            'harga_per_hari' => ['required', 'array', 'min:1'],
            'harga_per_hari.*' => ['required', 'numeric', 'min:0'],
            'addon_nama' => ['nullable', 'array'],
            'addon_nama.*' => ['nullable', 'string', 'max:100'],
            'addon_nominal' => ['nullable', 'array'],
            'addon_nominal.*' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($requirePaymentStatus) {
            $rules['status_pembayaran'] = ['required', 'in:Belum Bayar,DP,Lunas'];
            $rules['dp_amount'] = ['required_if:status_pembayaran,DP', 'numeric', 'min:0'];
        }

        $request->validate($rules);
    }

    /**
     * Nomor pesanan berikutnya: {Ymd}-{urut 4 digit}, direset tiap hari.
     * WAJIB dipanggil di dalam transaksi supaya dua operator yang klik
     * bersamaan tidak mendapat nomor sama.
     */
    private function generateNomorPesanan(): string
    {
        $tanggalStr = now()->format('Ymd');

        $lastToday = Booking::where('nomor_pesanan', 'like', "{$tanggalStr}-%")
            ->orderByDesc('nomor_pesanan')
            ->lockForUpdate()
            ->first();

        $urut = $lastToday ? ((int) explode('-', $lastToday->nomor_pesanan)[1]) + 1 : 1;

        return $tanggalStr.'-'.str_pad($urut, 4, '0', STR_PAD_LEFT);
    }

    private function durasiHari(Request $request): int
    {
        return max(1, Carbon::parse($request->tanggal_mulai)->diffInDays(Carbon::parse($request->tanggal_selesai)));
    }

    private function totalAddons(Request $request): float
    {
        if (! $request->filled('addon_nama')) {
            return 0.0;
        }

        return (float) collect($request->addon_nominal ?? [])->sum(fn ($nominal) => (float) $nominal);
    }

    private function totalHarga(Request $request, int $durasi, float $totalAddons): float
    {
        $totalKendaraan = (float) collect($request->harga_per_hari)
            ->sum(fn ($harga) => (float) $harga * $durasi);

        return $totalKendaraan + $totalAddons;
    }

    private function dpAmount(Request $request, float $totalHarga): float
    {
        return match ($request->status_pembayaran) {
            'DP' => (float) $request->dp_amount,
            'Lunas' => $totalHarga,
            default => 0.0,
        };
    }

    /**
     * Simpan banyak file dari satu field multipart (foto_ktp, foto_penyewa).
     */
    private function simpanFoto(Request $request, string $field, string $folder): array
    {
        if (! $request->hasFile($field)) {
            return [];
        }

        return collect($request->file($field))
            ->map(fn ($file) => $file->store($folder, 'public'))
            ->all();
    }

    /**
     * Gabungkan foto lama + baru, dan hapus file yang dicentang untuk dihapus.
     */
    private function kelolaFoto(Request $request, Booking $booking, string $field, string $hapusField, string $folder): array
    {
        $paths = is_array($booking->{$field}) ? $booking->{$field} : [];

        foreach ((array) $request->input($hapusField, []) as $pathHapus) {
            $paths = array_diff($paths, [$pathHapus]);

            if (Storage::disk('public')->exists($pathHapus)) {
                Storage::disk('public')->delete($pathHapus);
            }
        }

        return array_values(array_merge($paths, $this->simpanFoto($request, $field, $folder)));
    }

    /**
     * Pasang daftar kendaraan + harga harian, lalu tandai mobil sebagai
     * sedang disewa.
     */
    private function syncBookingCars(Request $request, Booking $booking): void
    {
        foreach ($request->cars as $i => $carId) {
            $harga = (float) ($request->harga_per_hari[$i] ?? 0);

            $fotoKondisi = $this->simpanFotoPerMobil($request, $carId);

            BookingCar::create([
                'booking_id' => $booking->id,
                'car_id' => $carId,
                'quantity' => 1,
                'harga_dasar_per_hari' => $harga,
                'foto_kondisi' => $fotoKondisi ?: null,
            ]);

            Car::where('id', $carId)->update(['status' => 'Sedang Disewa']);
        }
    }

    /**
     * Foto kondisi dikirim sebagai field dinamis: foto_kondisi[carId][].
     */
    private function simpanFotoPerMobil(Request $request, int $carId): array
    {
        if (! $request->hasFile("foto_kondisi.{$carId}")) {
            return [];
        }

        return collect($request->file("foto_kondisi.{$carId}"))
            ->map(fn ($file) => $file->store('bookings/kondisi', 'public'))
            ->all();
    }

    private function syncAddons(Request $request, Booking $booking): void
    {
        if (! $request->filled('addon_nama')) {
            return;
        }

        $namaArr = $request->addon_nama ?? [];
        $nominalArr = $request->addon_nominal ?? [];

        foreach ($namaArr as $j => $nama) {
            if (blank($nama)) {
                continue;
            }

            BookingAddon::create([
                'booking_id' => $booking->id,
                'nama_biaya' => $nama,
                'nominal' => (float) ($nominalArr[$j] ?? 0),
            ]);
        }
    }

    /**
     * Sinkronkan satu baris pemasukan yang mewakili pembayaran booking ini.
     * Kalau statusnya bukan Lunas/DP, baris pemasukan dihapus.
     */
    private function syncTransaction(Request $request, Booking $booking): void
    {
        $existing = Transaction::where('referensi_type', 'booking')
            ->where('referensi_id', $booking->id)
            ->first();

        $pembayaran = $request->status_pembayaran;

        if ($pembayaran === 'Lunas' || $pembayaran === 'DP') {
            $jumlah = $pembayaran === 'Lunas' ? $booking->total_harga : (float) $request->dp_amount;
            $teks = $pembayaran === 'Lunas' ? 'Pembayaran Lunas' : 'Pembayaran DP';
            $deskripsi = "{$teks} {$booking->nomor_pesanan} - {$booking->nama_pelanggan}";

            if ($existing) {
                $existing->update(['jumlah' => $jumlah, 'deskripsi' => $deskripsi]);
            } else {
                Transaction::create([
                    'tipe' => 'Pemasukan',
                    'kategori' => 'sewa',
                    'jumlah' => $jumlah,
                    'tanggal' => now()->toDateString(),
                    'deskripsi' => $deskripsi,
                    'referensi_type' => 'booking',
                    'referensi_id' => $booking->id,
                ]);
            }

            return;
        }

        $existing?->delete();
    }

    private function logActivity(
        Request $request,
        string $icon,
        string $color,
        string $title,
        string $description,
        ?int $bookingId
    ): void {
        Activity::create([
            'user_id' => $request->user()?->id,
            'user' => $request->user()?->nama ?? 'System',
            'type' => 'booking',
            'icon' => $icon,
            'color' => $color,
            'title' => $title,
            'description' => $description,
            'subject_type' => 'booking',
            'subject_id' => $bookingId,
        ]);
    }

    /**
     * Bentuk booking untuk response API. Foto dikirim sebagai signed URL,
     * bukan path mentah, karena frontend tidak punya akses disk backend.
     */
    private function bookingPayload(Booking $booking, bool $withFiles = false): array
    {
        $payload = $booking->toArray();

        $payload['foto_ktp_urls'] = collect($booking->foto_ktp ?? [])
            ->map(fn ($path) => FileUrl::public($path))
            ->filter()
            ->values();

        $payload['foto_penyewa_urls'] = collect($booking->foto_penyewa ?? [])
            ->map(fn ($path) => FileUrl::public($path))
            ->filter()
            ->values();

        if ($booking->relationLoaded('bookingCars')) {
            $payload['bookingCars'] = $booking->bookingCars->map(fn (BookingCar $bc) => array_merge(
                $bc->toArray(),
                [
                    'foto_kondisi_urls' => collect($bc->foto_kondisi ?? [])
                        ->map(fn ($path) => FileUrl::public($path))
                        ->filter()
                        ->values(),
                    'car_foto_url' => FileUrl::public($bc->car?->foto),
                ]
            ))->values();
        }

        if ($withFiles) {
            $payload['locked'] = $this->isLocked($booking);
        }

        $payload['detail_path'] = "/bookings/{$booking->id}";

        return $payload;
    }
}