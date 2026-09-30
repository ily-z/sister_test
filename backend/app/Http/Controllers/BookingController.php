<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Car;
use App\Models\Booking;
use App\Models\BookingCar;
use App\Models\BookingAddon;
use App\Models\Transaction;
use App\Models\Activity; // <-- BARU: Import model Activity
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookingController extends Controller
{
    /**
     * Tampilkan halaman daftar pesanan (index).
     */
    public function index(Request $request)
    {
        $query = Booking::with(['bookingCars.car'])->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nomor_pesanan', 'like', "%{$request->search}%")
                    ->orWhere('nama_pelanggan', 'like', "%{$request->search}%")
                    ->orWhere('kontak_pelanggan', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status_pembayaran')) {
            $query->where('status_pembayaran', $request->status_pembayaran);
        }

        if ($request->filled('tipe_sewa')) {
            $query->where('tipe_sewa', $request->tipe_sewa);
        }

        $bookings = $query->paginate(10)->withQueryString();

        return view('bookings.index', compact('bookings'));
    }

    /**
     * Tampilkan form untuk membuat pesanan baru.
     */
    public function create()
    {
        // Ambil semua mobil yang statusnya sedang "Tersedia"
        $availableCars = Car::where('status', 'Tersedia')->get();

        return view('bookings.create', compact('availableCars'));
    }

    /**
     * Simpan pesanan baru ke database.
     */
    public function store(Request $request)
    {
        // ── Validasi ──────────────────────────────────────────────
        $request->validate([
            'nama_pelanggan' => 'required|string|max:100',
            'kontak_pelanggan' => 'required|string|max:50',
            'email' => 'nullable|email|max:150',
            'foto_ktp.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'foto_penyewa.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'catatan' => 'nullable|string',
            'tipe_sewa' => 'required|in:harian,kontrak',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status_pembayaran' => 'required|in:Belum Bayar,DP,Lunas',
            'dp_amount' => 'required_if:status_pembayaran,DP|numeric|min:0',
            'cars' => 'required|array|min:1',
            'cars.*' => 'required|exists:cars,id',
            'harga_per_hari' => 'required|array|min:1',
            'harga_per_hari.*' => 'required|numeric|min:0',
            'addon_nama' => 'nullable|array',
            'addon_nama.*' => 'nullable|string|max:100',
            'addon_nominal' => 'nullable|array',
            'addon_nominal.*' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // ── Generate Nomor Pesanan ─────────────
            $tanggalStr = now()->format('Ymd');
            $lastToday = Booking::where('nomor_pesanan', 'like', "{$tanggalStr}-%")->orderByDesc('nomor_pesanan')->first();
            $urut = $lastToday ? ((int) explode('-', $lastToday->nomor_pesanan)[1]) + 1 : 1;
            $nomorPesanan = $tanggalStr . '-' . str_pad($urut, 4, '0', STR_PAD_LEFT);

            // ── Hitung Total ───────────────────────────────────────
            $tanggalMulai = \Carbon\Carbon::parse($request->tanggal_mulai);
            $tanggalSelesai = \Carbon\Carbon::parse($request->tanggal_selesai);
            $durasi = max(1, $tanggalMulai->diffInDays($tanggalSelesai));

            $totalAddons = 0;
            if ($request->filled('addon_nama')) {
                foreach ($request->addon_nominal ?? [] as $nominal) {
                    $totalAddons += (float) $nominal;
                }
            }

            $totalKendaraan = 0;
            foreach ($request->harga_per_hari as $harga) {
                $totalKendaraan += (float) $harga * $durasi;
            }
            $totalHarga = $totalKendaraan + $totalAddons;

            // ── Proses Upload Foto KTP & Penyewa ────────
            $fotoKtpPaths = [];
            if ($request->hasFile('foto_ktp')) {
                foreach ($request->file('foto_ktp') as $file) {
                    $fotoKtpPaths[] = $file->store('bookings/ktp', 'public');
                }
            }

            $fotoPenyewaPaths = [];
            if ($request->hasFile('foto_penyewa')) {
                foreach ($request->file('foto_penyewa') as $file) {
                    $fotoPenyewaPaths[] = $file->store('bookings/penyewa', 'public');
                }
            }

            // ── Simpan Booking ─────────────────────────────────────
            $booking = Booking::create([
                'nomor_pesanan' => $nomorPesanan,
                'nama_pelanggan' => $request->nama_pelanggan,
                'kontak_pelanggan' => $request->kontak_pelanggan,
                'email' => $request->email,
                'foto_ktp' => $fotoKtpPaths,
                'foto_penyewa' => $fotoPenyewaPaths,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'lokasi_jemput' => $request->lokasi_jemput,
                'lokasi_antar' => $request->lokasi_antar,
                'tipe_sewa' => $request->tipe_sewa,
                'status_pembayaran' => $request->status_pembayaran,
                'status_rental' => 'Menunggu', // Default status operasional
                'total_harga' => $totalHarga,
                'total_addons' => $totalAddons,
                'dp_amount' => $request->status_pembayaran === 'DP' ? $request->dp_amount : ($request->status_pembayaran === 'Lunas' ? $totalHarga : 0),
                'catatan' => $request->catatan,
                'created_by' => auth()->id(),
            ]);

            // ── Simpan Booking Cars ────────────────────────────────
            foreach ($request->cars as $i => $carId) {
                $harga = (float) ($request->harga_per_hari[$i] ?? 0);

                $fotoKondisiPaths = [];
                // Cek apakah ada file foto_kondisi untuk car_id ini
                if ($request->hasFile("foto_kondisi.{$carId}")) {
                    foreach ($request->file("foto_kondisi.{$carId}") as $file) {
                        $fotoKondisiPaths[] = $file->store('bookings/kondisi', 'public');
                    }
                }

                BookingCar::create([
                    'booking_id' => $booking->id,
                    'car_id' => $carId,
                    'quantity' => 1,
                    'harga_dasar_per_hari' => $harga,
                    'foto_kondisi' => empty($fotoKondisiPaths) ? null : $fotoKondisiPaths, // <-- SIMPAN KE DB
                ]);

                // Update status mobil menjadi "Sedang Disewa" (Dipesan)
                Car::where('id', $carId)->update(['status' => 'Sedang Disewa']);
            }

            // ── Simpan Add-ons ─────────────────────────────────────
            if ($request->filled('addon_nama')) {
                $namaArr = $request->addon_nama ?? [];
                $nominalArr = $request->addon_nominal ?? [];

                foreach ($namaArr as $j => $nama) {
                    if (empty(trim($nama)))
                        continue;
                    BookingAddon::create([
                        'booking_id' => $booking->id,
                        'nama_biaya' => $nama,
                        'nominal' => (float) ($nominalArr[$j] ?? 0),
                    ]);
                }
            }

            // ── Catat Pemasukan Otomatis jika Lunas ────────────
            if ($request->status_pembayaran === 'Lunas' || $request->status_pembayaran === 'DP') {
                $jumlahBayar = $request->status_pembayaran === 'Lunas' ? $totalHarga : $request->dp_amount;
                $teksDeskripsi = $request->status_pembayaran === 'Lunas' ? 'Pembayaran Lunas' : 'Pembayaran DP';

                Transaction::create([
                    'tipe' => 'Pemasukan',
                    'kategori' => 'sewa',
                    'jumlah' => $jumlahBayar,
                    'tanggal' => now()->toDateString(),
                    'deskripsi' => "{$teksDeskripsi} {$nomorPesanan} - {$request->nama_pelanggan}",
                    'referensi_type' => 'booking',
                    'referensi_id' => $booking->id,
                ]);
            }

            // ── BARU: Catat Activity Log ────────────
            Activity::create([
                'user_id' => auth()->id(),
                'user' => auth()->user()->nama ?? 'System',
                'type' => 'booking',
                'icon' => 'add_circle',
                'color' => 'emerald',
                'title' => 'Pesanan Baru Dibuat',
                'description' => "Admin membuat pesanan #{$nomorPesanan} untuk {$request->nama_pelanggan}",
                'subject_type' => 'booking',
                'subject_id' => $booking->id,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.bookings')
                ->with('success', "Pesanan #{$nomorPesanan} berhasil dibuat!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage()]);
        }
    }

    /**
     * Tampilkan form untuk mengedit pesanan.
     */
    public function edit($id)
    {
        $booking = Booking::with(['bookingCars.car', 'addons'])->findOrFail($id);

        // ── BARU: Kunci Form Jika Status Aktif/Selesai/Batal ─────────
        if (in_array($booking->status_rental, ['Aktif', 'Selesai', 'Batal'])) {
            return redirect()->route('admin.bookings.show', $id)
                ->withErrors(['general' => 'Pesanan yang sedang berjalan, selesai, atau batal tidak dapat diubah datanya.']);
        }

        $bookedCarIds = $booking->bookingCars->pluck('car_id')->toArray();
        $availableCars = Car::where('status', 'Tersedia')
            ->orWhereIn('id', $bookedCarIds)
            ->get();

        return view('bookings.edit', compact('booking', 'availableCars'));
    }

    /**
     * Proses update pesanan ke database.
     */
    public function update(Request $request, $id)
    {
        $booking = Booking::with(['bookingCars', 'addons'])->findOrFail($id);

        // ── BARU: Guard Pengaman Ganda di Backend ─────────
        if (in_array($booking->status_rental, ['Aktif', 'Selesai', 'Batal'])) {
            return redirect()->route('admin.bookings.show', $id)
                ->withErrors(['general' => 'Aksi ditolak. Pesanan sudah dikunci dari perubahan data.']);
        }

        // ── Validasi (Status Pembayaran dihapus dari sini) ─────────
        $request->validate([
            'nama_pelanggan' => 'required|string|max:100',
            'kontak_pelanggan' => 'required|string|max:50',
            'catatan' => 'nullable|string',
            'tipe_sewa' => 'required|in:harian,kontrak',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'cars' => 'required|array|min:1',
            'cars.*' => 'required|exists:cars,id',
            'harga_per_hari' => 'required|array|min:1',
            'harga_per_hari.*' => 'required|numeric|min:0',
            'addon_nama' => 'nullable|array',
            'addon_nama.*' => 'nullable|string|max:100',
            'addon_nominal' => 'nullable|array',
            'addon_nominal.*' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // ── Hitung Total ───────────────────────────────────────
            $tanggalMulai = \Carbon\Carbon::parse($request->tanggal_mulai);
            $tanggalSelesai = \Carbon\Carbon::parse($request->tanggal_selesai);
            $durasi = max(1, $tanggalMulai->diffInDays($tanggalSelesai));

            $totalAddons = 0;
            if ($request->filled('addon_nama')) {
                foreach ($request->addon_nominal ?? [] as $nominal) {
                    $totalAddons += (float) $nominal;
                }
            }

            $totalKendaraan = 0;
            foreach ($request->harga_per_hari as $harga) {
                $totalKendaraan += (float) $harga * $durasi;
            }
            $totalHarga = $totalKendaraan + $totalAddons;

            // ── Proses Foto KTP ────────
            $fotoKtpPaths = is_array($booking->foto_ktp) ? $booking->foto_ktp : [];
            if ($request->has('hapus_foto_ktp')) {
                foreach ($request->hapus_foto_ktp as $pathHapus) {
                    $fotoKtpPaths = array_diff($fotoKtpPaths, [$pathHapus]);
                    if (Storage::disk('public')->exists($pathHapus))
                        Storage::disk('public')->delete($pathHapus);
                }
            }
            if ($request->hasFile('foto_ktp')) {
                foreach ($request->file('foto_ktp') as $file) {
                    $fotoKtpPaths[] = $file->store('bookings/ktp', 'public');
                }
            }
            $fotoKtpPaths = array_values($fotoKtpPaths);

            // ── Proses Foto Penyewa ────────
            $fotoPenyewaPaths = is_array($booking->foto_penyewa) ? $booking->foto_penyewa : [];
            if ($request->has('hapus_foto_penyewa')) {
                foreach ($request->hapus_foto_penyewa as $pathHapus) {
                    $fotoPenyewaPaths = array_diff($fotoPenyewaPaths, [$pathHapus]);
                    if (Storage::disk('public')->exists($pathHapus))
                        Storage::disk('public')->delete($pathHapus);
                }
            }
            if ($request->hasFile('foto_penyewa')) {
                foreach ($request->file('foto_penyewa') as $file) {
                    $fotoPenyewaPaths[] = $file->store('bookings/penyewa', 'public');
                }
            }
            $fotoPenyewaPaths = array_values($fotoPenyewaPaths);


            // ── Update Detail Booking ──────────────────────────────
            $booking->update([
                'nama_pelanggan' => $request->nama_pelanggan,
                'kontak_pelanggan' => $request->kontak_pelanggan,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'lokasi_jemput' => $request->lokasi_jemput,
                'lokasi_antar' => $request->lokasi_antar,
                'tipe_sewa' => $request->tipe_sewa,
                // status_pembayaran dihapus dari sini
                'total_harga' => $totalHarga,
                'total_addons' => $totalAddons,
                'catatan' => $request->catatan,
                'foto_ktp' => $fotoKtpPaths,
                'foto_penyewa' => $fotoPenyewaPaths,
            ]);

            // ── Kembalikan Status Mobil Lama ke "Tersedia" ─────────
            $oldCarIds = $booking->bookingCars->pluck('car_id')->toArray();
            Car::whereIn('id', $oldCarIds)->update(['status' => 'Tersedia']);

            // ── Hapus Daftar Mobil Lama & Simpan yang Baru ─────────
            $booking->bookingCars()->delete();
            // ── Simpan Booking Cars ────────────────────────────────
            foreach ($request->cars as $i => $carId) {
                $harga = (float) ($request->harga_per_hari[$i] ?? 0);

                // 👇 TAMBAHAN LOGIKA UPLOAD FOTO KONDISI PER MOBIL
                $fotoKondisiPaths = [];
                // Cek apakah ada file foto_kondisi untuk car_id ini
                if ($request->hasFile("foto_kondisi.{$carId}")) {
                    foreach ($request->file("foto_kondisi.{$carId}") as $file) {
                        $fotoKondisiPaths[] = $file->store('bookings/kondisi', 'public');
                    }
                }

                BookingCar::create([
                    'booking_id' => $booking->id,
                    'car_id' => $carId,
                    'quantity' => 1,
                    'harga_dasar_per_hari' => $harga,
                    'foto_kondisi' => empty($fotoKondisiPaths) ? null : $fotoKondisiPaths, // <-- SIMPAN KE DB
                ]);

                // Update status mobil menjadi "Sedang Disewa" (Dipesan)
                Car::where('id', $carId)->update(['status' => 'Sedang Disewa']);
            }

            // ── Update Add-ons ───────────
            $booking->addons()->delete();
            if ($request->filled('addon_nama')) {
                $namaArr = $request->addon_nama ?? [];
                $nominalArr = $request->addon_nominal ?? [];

                foreach ($namaArr as $j => $nama) {
                    if (empty(trim($nama)))
                        continue;
                    BookingAddon::create([
                        'booking_id' => $booking->id,
                        'nama_biaya' => $nama,
                        'nominal' => (float) ($nominalArr[$j] ?? 0),
                    ]);
                }
            }

            // ── Perbarui Nominal Pemasukan Otomatis (Jika sudah Lunas) ─────────────
            $existingTransaction = Transaction::where('referensi_type', 'booking')
                ->where('referensi_id', $booking->id)
                ->first();

            // Kita cek dari database lama, karena form edit tidak mengirim status pembayaran lagi
            if ($booking->status_pembayaran === 'Lunas' && $existingTransaction) {
                $existingTransaction->update(['jumlah' => $totalHarga]);
            }

            // ── BARU: Catat Activity Log ────────────
            Activity::create([
                'user_id' => auth()->id(),
                'user' => auth()->user()->nama ?? 'System',
                'type' => 'booking',
                'icon' => 'edit_document',
                'color' => 'amber',
                'title' => 'Data Pesanan Diedit',
                'description' => "Admin memperbarui data pelanggan atau unit kendaraan",
                'subject_type' => 'booking',
                'subject_id' => $booking->id,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.bookings.show', $booking->id)
                ->with('success', "Data Pesanan #{$booking->nomor_pesanan} berhasil diperbarui!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan saat mengupdate: ' . $e->getMessage()]);
        }
    }

    /**
     * ── BARU: METHOD KHUSUS UNTUK UPDATE STATUS DARI HALAMAN SHOW ──
     */
    public function updateStatus(Request $request, $id)
    {
        // 🟢 PERUBAHAN: Menambahkan status 'Dikembalikan'
        $request->validate([
            'status_pembayaran' => 'required|in:Belum Bayar,DP,Lunas',
            'status_rental' => 'required|in:Menunggu,Aktif,Dikembalikan,Selesai,Batal',
            'dp_amount' => 'required_if:status_pembayaran,DP|nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $booking = Booking::with('bookingCars')->findOrFail($id);
            $oldPembayaran = $booking->status_pembayaran;
            $oldRental = $booking->status_rental;

            if ($oldPembayaran === $request->status_pembayaran && $oldRental === $request->status_rental) {
                return back();
            }

            $booking->update([
                'status_pembayaran' => $request->status_pembayaran,
                'status_rental' => $request->status_rental,
                'dp_amount' => $request->status_pembayaran === 'DP' ? (float) $request->dp_amount : ($request->status_pembayaran === 'Lunas' ? $booking->total_harga : 0),
            ]);

            $carIds = $booking->bookingCars->pluck('car_id')->toArray();

            // 🟢 PERUBAHAN LOGIKA MOBIL: 'Dikembalikan' juga langsung membebaskan mobil
            if (in_array($request->status_rental, ['Dikembalikan', 'Selesai', 'Batal'])) {
                Car::whereIn('id', $carIds)->update(['status' => 'Tersedia']);
                if (in_array($request->status_rental, ['Dikembalikan', 'Selesai']) && is_null($booking->returned_at)) {
                    $booking->update(['returned_at' => now()]);
                }
            } elseif (in_array($request->status_rental, ['Aktif', 'Menunggu'])) {
                Car::whereIn('id', $carIds)->update(['status' => 'Sedang Disewa']);
                if ($request->status_rental === 'Aktif' && is_null($booking->picked_up_at)) {
                    $booking->update(['picked_up_at' => now()]);
                }
            }

            // ... (Kode sinkronisasi Buku Keuangan biarkan seperti aslinya) ...
            $existingTransaction = Transaction::where('referensi_type', 'booking')->where('referensi_id', $booking->id)->first();
            if ($request->status_pembayaran === 'Lunas') {
                if ($existingTransaction) {
                    $existingTransaction->update(['jumlah' => $booking->total_harga, 'deskripsi' => "Pembayaran Lunas {$booking->nomor_pesanan} - {$booking->nama_pelanggan}"]);
                } else {
                    Transaction::create(['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => $booking->total_harga, 'tanggal' => now()->toDateString(), 'deskripsi' => "Pembayaran Lunas {$booking->nomor_pesanan} - {$booking->nama_pelanggan}", 'referensi_type' => 'booking', 'referensi_id' => $booking->id]);
                }
            } elseif ($request->status_pembayaran === 'DP') {
                if ($existingTransaction) {
                    $existingTransaction->update(['jumlah' => (float) $request->dp_amount, 'deskripsi' => "Pembayaran DP {$booking->nomor_pesanan} - {$booking->nama_pelanggan}"]);
                } else {
                    Transaction::create(['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => (float) $request->dp_amount, 'tanggal' => now()->toDateString(), 'deskripsi' => "Pembayaran DP {$booking->nomor_pesanan} - {$booking->nama_pelanggan}", 'referensi_type' => 'booking', 'referensi_id' => $booking->id]);
                }
            } else {
                if ($existingTransaction)
                    $existingTransaction->delete();
            }

            // Catat Riwayat
            if ($oldPembayaran !== $request->status_pembayaran) {
                \App\Models\BookingPayment::create(['booking_id' => $booking->id, 'jumlah' => $request->status_pembayaran === 'DP' ? (float) $request->dp_amount : ($request->status_pembayaran === 'Lunas' ? $booking->total_harga : 0), 'status_sebelum' => $oldPembayaran, 'status_sesudah' => $request->status_pembayaran, 'metode_bayar' => $request->metode_bayar, 'created_by' => auth()->id(), 'created_at' => now()]);
            }

            Activity::create(['user_id' => auth()->id(), 'user' => auth()->user()->nama ?? 'System', 'type' => 'booking', 'icon' => 'sync_alt', 'color' => 'blue', 'title' => 'Status Pesanan Diubah', 'description' => "Status berubah ke: {$request->status_rental} / {$request->status_pembayaran}", 'subject_type' => 'booking', 'subject_id' => $booking->id]);

            DB::commit();
            return back()->with('success', 'Status pesanan berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal mengubah status: ' . $e->getMessage()]);
        }
    }

    // 🟢 3 FUNGSI BARU (Tambahkan di bagian paling bawah)
    public function updateOdometer(Request $request, $id)
    {
        $booking = Booking::with('bookingCars.car')->findOrFail($id);
        if (in_array($booking->status_rental, ['Selesai', 'Batal'])) {
            return back()->withErrors(['general' => 'Pesanan sudah dikunci.']);
        }
        $request->validate(['km_kembali' => 'required|array', 'km_kembali.*' => 'required|numeric|min:0']);

        DB::beginTransaction();
        try {
            foreach ($booking->bookingCars as $bc) {
                $car = $bc->car;
                $kmBaru = $request->km_kembali[$car->id] ?? $car->km_saat_ini;

                if ($kmBaru < $car->km_saat_ini)
                    throw new \Exception("KM {$car->plat_nomor} tidak boleh mundur.");

                if ($kmBaru > $car->km_saat_ini) {
                    $car->update(['km_saat_ini' => $kmBaru]);
                    \App\Models\OdometerLog::create(['car_id' => $car->id, 'km_reading' => $kmBaru, 'created_at' => now()]);
                }
            }
            DB::commit();
            return back()->with('success', 'Odometer armada berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    public function lockBooking($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status_rental' => 'Selesai']);

        $carIds = \App\Models\BookingCar::where('booking_id', $id)->pluck('car_id');
        Car::whereIn('id', $carIds)->update(['status' => 'Tersedia']);

        Activity::create(['user_id' => auth()->id(), 'user' => auth()->user()->nama ?? 'System', 'type' => 'booking', 'icon' => 'lock', 'color' => 'emerald', 'title' => 'Pesanan Dikunci (Selesai)', 'description' => "Seluruh tagihan dan data kendaraan telah dikunci.", 'subject_type' => 'booking', 'subject_id' => $booking->id]);
        return back()->with('success', 'Pesanan berhasil dikunci permanen!');
    }

    public function unlockBooking($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status_rental' => 'Dikembalikan']); // Kembali ke status Dikembalikan agar bisa diedit tapi mobil tetap Tersedia

        Activity::create(['user_id' => auth()->id(), 'user' => auth()->user()->nama ?? 'System', 'type' => 'booking', 'icon' => 'lock_open', 'color' => 'amber', 'title' => 'Kunci Pesanan Dibuka', 'description' => "Admin membuka kunci pesanan untuk revisi data.", 'subject_type' => 'booking', 'subject_id' => $booking->id]);
        return back()->with('success', 'Kunci pesanan berhasil dibuka.');
    }

    /**
     * Tampilkan detail pesanan beserta surat serah terima kendaraan.
     */
    public function show($id)
    {
        $booking = Booking::with([
            'bookingCars.car',
            'addons',
        ])->findOrFail($id);

        // Ambil metode bayar dari payment terakhir (untuk ditampilkan di modal jika sudah Lunas)
        $lastPayment = \App\Models\BookingPayment::where('booking_id', $booking->id)
            ->latest('created_at')
            ->first();

        // Invoice + tanda tangannya, supaya halaman ini bisa menandatangani
        // dan mengunduh PDF tanpa harus lewat halaman Arsip.
        $invoice = \App\Models\Invoice::with('signatures')
            ->where('booking_id', $booking->id)
            ->latest('id')
            ->first();

        return view('bookings.show', compact('booking', 'lastPayment', 'invoice'));
    }
    /**
     * Hapus pesanan dan kembalikan status mobil ke "Tersedia".
     */
    public function destroy($id)
    {
        $booking = Booking::with('bookingCars')->findOrFail($id);

        DB::beginTransaction();
        try {
            // Hapus transaksi pemasukan terkait
            Transaction::where('referensi_type', 'booking')
                ->where('referensi_id', $booking->id)
                ->delete();

            // Kembalikan status mobil
            foreach ($booking->bookingCars as $bc) {
                Car::where('id', $bc->car_id)->update(['status' => 'Tersedia']);
            }

            $booking->delete();

            DB::commit();
            return redirect()
                ->route('admin.bookings')
                ->with('success', 'Pesanan berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
    }

    public function storeAddon(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        if (in_array($booking->status_rental, ['Selesai', 'Batal'])) {
            return back()->withErrors(['general' => 'Tidak bisa menambah biaya pada pesanan yang sudah selesai atau dibatalkan.']);
        }

        $request->validate([
            'addon_nama' => 'required|string|max:100',
            'addon_nominal' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $addon = BookingAddon::create([
                'booking_id' => $booking->id,
                'nama_biaya' => $request->addon_nama,
                'nominal' => (float) $request->addon_nominal,
            ]);

            // Update total_addons & total_harga di booking
            $booking->total_addons += $addon->nominal;
            $booking->total_harga += $addon->nominal;
            $booking->save();

            // Update transaksi keuangan jika sudah Lunas
            if ($booking->status_pembayaran === 'Lunas') {
                Transaction::where('referensi_type', 'booking')
                    ->where('referensi_id', $booking->id)
                    ->update(['jumlah' => $booking->total_harga]);
            }

            Activity::create([
                'user_id' => auth()->id(),
                'user' => auth()->user()->nama ?? 'System',
                'type' => 'booking',
                'icon' => 'add_shopping_cart',
                'color' => 'blue',
                'title' => 'Biaya Tambahan Ditambahkan',
                'description' => "Ditambahkan: {$request->addon_nama} — Rp " . number_format($addon->nominal, 0, ',', '.'),
                'subject_type' => 'booking',
                'subject_id' => $booking->id,
            ]);

            DB::commit();
            return back()->with('success', "Biaya tambahan \"{$request->addon_nama}\" berhasil ditambahkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal menambah biaya: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus add-on dari halaman show.
     */
    public function destroyAddon(Request $request, $id, $addonId)
    {
        $booking = Booking::findOrFail($id);

        if (in_array($booking->status_rental, ['Aktif', 'Selesai', 'Batal'])) {
            return back()->withErrors(['general' => 'Tidak bisa menghapus biaya pada pesanan yang sudah aktif, selesai, atau dibatalkan.']);
        }

        DB::beginTransaction();
        try {
            $addon = BookingAddon::where('booking_id', $booking->id)->findOrFail($addonId);

            // Update total sebelum hapus
            $booking->total_addons -= $addon->nominal;
            $booking->total_harga -= $addon->nominal;
            $booking->save();

            $namaAddon = $addon->nama_biaya;
            $nominalAddon = $addon->nominal;
            $addon->delete();

            // Update transaksi keuangan jika sudah Lunas
            if ($booking->status_pembayaran === 'Lunas') {
                Transaction::where('referensi_type', 'booking')
                    ->where('referensi_id', $booking->id)
                    ->update(['jumlah' => $booking->total_harga]);
            }

            Activity::create([
                'user_id' => auth()->id(),
                'user' => auth()->user()->nama ?? 'System',
                'type' => 'booking',
                'icon' => 'remove_shopping_cart',
                'color' => 'red',
                'title' => 'Biaya Tambahan Dihapus',
                'description' => "Dihapus: {$namaAddon} — Rp " . number_format($nominalAddon, 0, ',', '.'),
                'subject_type' => 'booking',
                'subject_id' => $booking->id,
            ]);

            DB::commit();
            return back()->with('success', "Biaya tambahan \"{$namaAddon}\" berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal menghapus biaya: ' . $e->getMessage()]);
        }
    }

    /**
     * Tampilkan halaman kalender jadwal pesanan.
     */
    public function calendar()
    {
        // 1. Ambil semua armada
        $armada = Car::select('id', 'plat_nomor', 'merk', 'model', 'status')
            ->get()
            ->map(function ($car) {
                return [
                    'id' => $car->id,
                    'plat_nomor' => $car->plat_nomor,
                    'nama' => trim($car->merk . ' ' . $car->model),
                    'status' => $car->status,
                ];
            });

        // 2. Ambil semua data booking — expand per mobil (BookingCar)
        $bookings = collect();

        Booking::select('id', 'nomor_pesanan', 'nama_pelanggan', 'tanggal_mulai', 'tanggal_selesai', 'status_rental', 'status_pembayaran', 'total_harga', 'tipe_sewa', 'lokasi_jemput', 'lokasi_antar')
            ->with(['bookingCars.car'])
            ->get()
            ->each(function ($booking) use (&$bookings) {
                foreach ($booking->bookingCars as $bc) {
                    $car = $bc->car;
                    if (!$car)
                        continue;

                    $bookings->push([
                        'booking_id' => $booking->id,
                        'nomor_pesanan' => $booking->nomor_pesanan,
                        'nama_pelanggan' => $booking->nama_pelanggan,
                        'tanggal_mulai' => $booking->tanggal_mulai,
                        'tanggal_selesai' => $booking->tanggal_selesai,
                        'status' => strtolower(str_replace(' ', '_', $booking->status_pembayaran)),
                        'status_rental' => $booking->status_rental,
                        'total_harga' => $booking->total_harga,
                        'tipe_sewa' => $booking->tipe_sewa,
                        'plat_nomor' => $car->plat_nomor,
                        'car_id' => $car->id,
                        'nama_mobil' => trim($car->merk . ' ' . $car->model),

                        // Data ini sekarang akan terisi karena sudah di-select di atas
                        'lokasi_jemput' => $booking->lokasi_jemput,
                        'lokasi_antar' => $booking->lokasi_antar,
                    ]);
                }
            });

        return view('bookings.calendar', compact('armada', 'bookings'));
    }

    /**
     * ── FITUR BARU: PROSES REFUND DANA PESANAN BATAL ──
     */
    public function processRefund(Request $request, $id)
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:1',
            'refund_method' => 'required|string',
            'refund_notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $booking = Booking::findOrFail($id);

            // 1. Guard Pengaman: Hanya berlaku jika batal dan ada dana masuk
            if ($booking->status_rental !== 'Batal' || $booking->dp_amount <= 0) {
                return back()->withErrors(['general' => 'Pesanan ini tidak memenuhi syarat untuk refund.']);
            }

            $currentRefund = $booking->refund_amount ?? 0;
            $newRefund = (float) $request->refund_amount;

            // 2. Guard Pengaman: Pastikan tidak melebihi total uang yang sudah masuk
            if (($currentRefund + $newRefund) > $booking->dp_amount) {
                return back()->withErrors(['general' => 'Nominal refund melebihi total dana yang telah dibayarkan pelanggan.']);
            }

            // 3. Update nominal refund di tabel bookings
            $booking->update([
                'refund_amount' => $currentRefund + $newRefund
            ]);

            // 4. Catat pengeluaran di Buku Keuangan (Transactions)
            $catatanTambahan = $request->refund_notes ? " | Catatan: {$request->refund_notes}" : "";
            Transaction::create([
                'tipe' => 'Pengeluaran',
                'kategori' => 'sewa',
                'jumlah' => $newRefund,
                'tanggal' => now()->toDateString(),
                'deskripsi' => "Refund Batal {$booking->nomor_pesanan} - {$booking->nama_pelanggan} (" . ucfirst($request->refund_method) . "){$catatanTambahan}",
                'referensi_type' => 'booking',
                'referensi_id' => $booking->id,
            ]);

            // 5. Catat Riwayat Aktivitas (BARIS 'user' SUDAH DIHAPUS AGAR TIDAK ERROR SQL)
            Activity::create([
                'user_id' => auth()->id(),
                'type' => 'booking',
                'icon' => 'currency_exchange',
                'color' => 'purple',
                'title' => 'Dana Dikembalikan (Refund)',
                'description' => "Admin memproses refund sebesar Rp " . number_format($newRefund, 0, ',', '.') . " via " . ucfirst($request->refund_method),
                'subject_type' => 'booking',
                'subject_id' => $booking->id,
            ]);

            DB::commit();

            return back()->with('success', 'Refund sebesar Rp ' . number_format($newRefund, 0, ',', '.') . ' berhasil dicatat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Gagal memproses refund: ' . $e->getMessage()]);
        }
    }
}