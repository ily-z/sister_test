<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Car;
use App\Models\Surat;
use App\Models\BookingCar;
use App\Models\OdometerLog;
use App\Models\MasterPart; // <-- Tambahan untuk template part
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // <-- Tambahan untuk query DB raw
use Illuminate\Validation\Rule;

class ArmadaController extends Controller
{
    public function index(Request $request)
    {
        // Gunakan eager loading 'parts' agar ringan saat mengecek status servis komponen
        // + eager load 'activeBooking' untuk link pesanan pada mobil yang Sedang Disewa
        $query = Car::with(['parts', 'activeBooking']);

        // Search Plat Nomor atau Merk
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('plat_nomor', 'like', "%{$request->search}%")
                    ->orWhere('merk', 'like', "%{$request->search}%")
                    ->orWhere('model', 'like', "%{$request->search}%");
            });
        }

        // Filter Status, Merk, Tahun
        if ($request->filled('status'))
            $query->where('status', $request->status);
        if ($request->filled('merk'))
            $query->where('merk', $request->merk);
        if ($request->filled('tahun'))
            $query->where('tahun', $request->tahun);

        // UPDATE: Logika filter Servis Due sekarang membaca ke tabel car_parts
        if ($request->filled('servis_due') && $request->servis_due == 1) {
            $query->whereHas('parts', function ($q) {
                $q->where('is_track_km', true)
                    ->whereRaw('(cars.km_saat_ini - car_parts.last_service_km) >= car_parts.lifespan_km');
            });
        }

        $cars = $query->orderBy('created_at', 'desc')->paginate(9)->withQueryString();

        // Statistik (Gunakan data asli tanpa filter, dengan eager load parts)
        $allCars = Car::with('parts')->get();
        $total = $allCars->count();
        $tersedia = $allCars->where('status', 'Tersedia')->count();
        $disewa = $allCars->where('status', 'Sedang Disewa')->count();
        $bengkel = $allCars->where('status', 'Masuk Bengkel')->count();

        $nonaktif = $allCars->where('status', 'Nonaktif')->count();

        // UPDATE: Logika alert servis mengecek apakah ada komponen mobil yang sudah lewat batas
        $serviceDueCount = $allCars->filter(function ($car) {
            return $car->parts->contains(function ($part) use ($car) {
                return $part->is_track_km && (($car->km_saat_ini - $part->last_service_km) >= $part->lifespan_km);
            });
        })->count();

        return view('armada.index', compact('cars', 'total', 'tersedia', 'disewa', 'bengkel', 'nonaktif', 'serviceDueCount'));
    }

    public function create()
    {
        // Draft dokumen yang sudah dibuat di sesi ini (tetap tampil bila validasi form gagal)
        $draftSurat = null;
        if (session('armada_create_surat_id')) {
            $draftSurat = Surat::where('id', session('armada_create_surat_id'))
                ->where('kode', 'PHK')
                ->first();
        }

        return view('armada.create', compact('draftSurat'));
    }

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

    /**
     * Membuat dokumen (Surat Pelepasan Hak Kendaraan) sebelum dokumen diunggah.
     */
    public function createDokumen(Request $request)
    {
        // Hindari membuat ganda bila tombol ditekan berulang kali di sesi yang sama
        if (session('armada_create_surat_id')) {
            $existing = Surat::where('id', session('armada_create_surat_id'))
                ->where('kode', 'PHK')
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'surat_id' => $existing->id,
                    'nomor_surat' => $existing->nomor_surat,
                ]);
            }
        }

        $number = $this->generateNomorSurat('PHK');

        $surat = Surat::create([
            'jenis_dokumen' => 'Pelepasan Hak Kendaraan',
            'kode' => 'PHK',
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => $number['noUrut'],
            'bulan' => $number['bulan'],
            'tahun' => $number['tahun'],
            'nomor_surat' => $number['nomorSurat'],
        ]);

        session(['armada_create_surat_id' => $surat->id]);

        return response()->json([
            'success' => true,
            'surat_id' => $surat->id,
            'nomor_surat' => $surat->nomor_surat,
        ]);
    }

    /**
     * Menampilkan dokumen Surat Pelepasan Hak Kendaraan (cetak A4).
     * Data mobil & pembeli dikirim lewat query string karena data belum disimpan.
     */
    public function dokumenPhk($suratId)
    {
        $surat = Surat::where('id', $suratId)->where('kode', 'PHK')->firstOrFail();

        $fields = [
            'plat' => request('plat', ''),
            'merk' => request('merk', ''),
            'model' => request('model', ''),
            'tahun' => request('tahun', ''),
            'jenis' => request('jenis', ''),
            'isi_silinder' => request('isi_silinder', ''),
            'mesin' => request('mesin', ''),
            'rangka' => request('rangka', ''),
            'warna' => request('warna', ''),
            'pembeli' => request('pembeli', ''),
            'alamat_pembeli' => request('alamat_pembeli', ''),
        ];

        return view('surat.phk-print', compact('surat', 'fields'));
    }

    public function store(Request $request)
    {
        $messages = [
            'plat_nomor.unique' => 'Plat nomor ini sudah digunakan. Silakan gunakan plat lain.',
            'no_mesin.unique' => 'Nomor mesin ini sudah terdaftar. Periksa kembali isian Anda.',
            'no_rangka.unique' => 'Nomor rangka ini sudah terpakai. Gunakan nomor rangka yang valid dan unik.',
            'surat_id.required' => 'Buat nomor surat Pelepasan Hak Kendaraan dahulu melalui panel dokumen.',
            'surat_id.exists' => 'Nomor surat yang dipilih tidak valid. Silakan buat dokumen ulang.',
            'foto_dokumen.required' => 'Dokumen pelepasan hak kendaraan wajib diunggah sebelum data bisa disimpan.',
        ];

        $validatedData = $request->validate([
            'plat_nomor' => 'required|unique:cars,plat_nomor|max:15',
            'merk' => 'required|max:50',
            'model' => 'required|max:100',
            'warna' => 'nullable|max:50',
            'tahun' => 'required|integer',
            'kapasitas' => 'required|integer',
            'harga_default' => 'required|numeric|min:0',
            'harga_kontrak' => 'required|numeric|min:0',
            'no_mesin' => 'required|unique:cars,no_mesin|max:50',
            'no_rangka' => 'required|unique:cars,no_rangka|max:50',
            'km_saat_ini' => 'required|integer|min:0',

            // KOLOM LAMA TELAH DIHAPUS DARI VALIDASI DI SINI

            'status' => 'required|in:Tersedia,Masuk Bengkel',
            'kode_mobil' => 'nullable|max:3',
            'surat_id' => ['required', Rule::exists('surat', 'id')->where('kode', 'PHK')],
            'foto_dokumen' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ], $messages);

        // Simpan foto dokumen serah terima (wajib)
        $validatedData['foto_dokumen'] = $request->file('foto_dokumen')->store('dokumen_mobil', 'public');

        if (!$request->filled('kode_mobil')) {
            $validatedData['kode_mobil'] = strtoupper(substr(str_replace(' ', '', $request->model), 0, 3));
        }

        if ($request->filled('foto_cropped')) {
            $imageData = $request->foto_cropped;
            $imageInfo = explode(";base64,", $imageData);
            $imageType = explode("image/", $imageInfo[0])[1];
            $imageBinary = base64_decode($imageInfo[1]);

            $fileName = 'cars/' . time() . '_' . uniqid() . '.' . $imageType;
            Storage::disk('public')->put($fileName, $imageBinary);

            $validatedData['foto'] = $fileName;
        }

        Car::create($validatedData);

        session()->forget('armada_create_surat_id');

        return redirect()->route('admin.armada')->with('success', 'Data mobil berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $car = Car::findOrFail($id);
        return view('armada.edit', compact('car'));
    }

    public function show($id)
    {
        // UPDATE: Tarik relasi parts + activeBooking (untuk link pesanan yang sedang berjalan)
        $car = Car::with(['parts', 'activeBooking'])->findOrFail($id);

        $latestBookings = BookingCar::where('car_id', $car->id)
            ->with('booking')
            ->whereHas('booking', function ($q) {
                if (request('status_pembayaran_riwayat')) {
                    $q->where('status_pembayaran', request('status_pembayaran_riwayat'));
                }
            })
            ->paginate(8, ['*'], 'page');

        $latestOdometerLogs = $car->odometerLogs()
            ->orderByDesc('created_at')
            ->take(4)
            ->get();

        $totalBookings = BookingCar::where('car_id', $car->id)->count();

        // UPDATE: Tarik data Master Parts untuk ditampilkan di modal "Mulai Lacak"
        $masterParts = MasterPart::all();

        // Compact ditambahkan $masterParts
        return view('armada.show', compact('car', 'latestBookings', 'latestOdometerLogs', 'totalBookings', 'masterParts'));
    }

    public function updateKm(Request $request, $id)
    {
        $car = Car::findOrFail($id);

        $validatedData = $request->validate([
            'km_baru' => 'required|integer|min:' . ($car->km_saat_ini + 1),
            'catatan' => 'nullable|string|max:255',
        ]);

        $car->update(['km_saat_ini' => $validatedData['km_baru']]);

        OdometerLog::create([
            'car_id' => $car->id,
            'km_reading' => $validatedData['km_baru'],
            'created_at' => now(),
        ]);

        return redirect()->route('admin.armada.show', $car->id)
            ->with('success', 'Kilometer berhasil diperbarui dan log odometer tercatat.');
    }

    public function update(Request $request, $id)
    {
        $car = Car::findOrFail($id);
        $lockKapasitas = $car->status === 'Sedang Disewa';

        $messages = [
            'plat_nomor.unique' => 'Plat nomor ini sudah digunakan. Silakan gunakan plat lain.',
            'no_mesin.unique' => 'Nomor mesin ini sudah terdaftar. Periksa kembali isian Anda.',
            'no_rangka.unique' => 'Nomor rangka ini sudah terpakai. Gunakan nomor rangka yang valid dan unik.',
        ];

        $validatedData = $request->validate([
            'plat_nomor' => 'required|max:15|unique:cars,plat_nomor,' . $car->id,
            'merk' => 'required|max:50',
            'model' => 'required|max:100',
            'warna' => 'nullable|max:50',
            'tahun' => 'required|integer',
            'kapasitas' => 'required|integer',
            'harga_default' => 'required|numeric|min:0',
            'harga_kontrak' => 'required|numeric|min:0',
            'no_mesin' => 'required|max:50|unique:cars,no_mesin,' . $car->id,
            'no_rangka' => 'required|max:50|unique:cars,no_rangka,' . $car->id,
            'km_saat_ini' => 'required|integer|min:0',
            'status' => 'required|in:Tersedia,Masuk Bengkel,Sedang Disewa,Nonaktif',
            'kode_mobil' => 'nullable|max:3',
            'foto_dokumen' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ], $messages);

        if ($request->hasFile('foto_dokumen')) {
            if ($car->foto_dokumen && Storage::disk('public')->exists($car->foto_dokumen)) {
                Storage::disk('public')->delete($car->foto_dokumen);
            }
            $validatedData['foto_dokumen'] = $request->file('foto_dokumen')->store('dokumen_mobil', 'public');
        }

        if ($lockKapasitas) {
            $validatedData['kapasitas'] = $car->kapasitas;
        }

        if ($request->filled('kode_mobil')) {
            $validatedData['kode_mobil'] = strtoupper($request->kode_mobil);
        }

        if ($request->filled('foto_cropped')) {
            if ($car->foto && Storage::disk('public')->exists($car->foto)) {
                Storage::disk('public')->delete($car->foto);
            }

            $imageData = $request->foto_cropped;
            $imageInfo = explode(";base64,", $imageData);
            $imageType = explode("image/", $imageInfo[0])[1];
            $imageBinary = base64_decode($imageInfo[1]);

            $fileName = 'cars/' . time() . '_' . uniqid() . '.' . $imageType;
            Storage::disk('public')->put($fileName, $imageBinary);

            $validatedData['foto'] = $fileName;
        }

        $car->update($validatedData);

        return redirect()->route('admin.armada')->with('success', 'Data armada berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $car = Car::findOrFail($id);

        // Cek apakah mobil ini pernah dipakai di booking manapun (aktif maupun histori lama)
        $punyaRiwayatBooking = BookingCar::where('car_id', $car->id)->exists();

        if ($punyaRiwayatBooking) {
            return redirect()->route('admin.armada')->withErrors([
                'general' => 'Mobil ini tidak bisa dihapus permanen karena memiliki riwayat booking. '
                    . 'Silakan ubah status mobil menjadi "Nonaktif" lewat halaman Edit agar tidak muncul '
                    . 'di pilihan booking baru, namun riwayat transaksinya tetap tersimpan.'
            ]);
        }

        if ($car->foto && Storage::disk('public')->exists($car->foto)) {
            Storage::disk('public')->delete($car->foto);
        }

        $car->delete();

        return redirect()->route('admin.armada')->with('success', 'Data kendaraan berhasil dihapus secara permanen.');
    }

    public function bengkel()
    {
        // 1. Mobil yang sedang di bengkel (dengan eager loading log & detail)
        $carsInWorkshop = Car::where('status', 'Masuk Bengkel')
            ->with(['serviceLogs' => function($q) {
                $q->where('status_bengkel', 'open')
                  ->with('details') // <-- Memanggil relasi baru di bawah
                  ->latest('tanggal_masuk');
            }])->get();

        // 2. Statistik: Total Biaya Servis Bulan Ini
        $totalBiayaBulanIni = \App\Models\ServiceLogDetail::whereHas('serviceLog', function($q) {
            $q->whereMonth('tanggal_masuk', \Carbon\Carbon::now()->month)
              ->whereYear('tanggal_masuk', \Carbon\Carbon::now()->year);
        })->sum('biaya');

        // 3. Statistik: Log Aktif (Masih Open)
        $openServiceLogs = \App\Models\ServiceLog::where('status_bengkel', 'open')->count();

        // 4. Statistik: Servis Selesai Bulan Ini
        $closedThisMonth = \App\Models\ServiceLog::where('status_bengkel', 'closed')
            ->whereMonth('tanggal_keluar', \Carbon\Carbon::now()->month)
            ->whereYear('tanggal_keluar', \Carbon\Carbon::now()->year)
            ->count();

        // 5. Riwayat Servis (5 Terakhir untuk panel kanan)
        $recentServiceLogs = \App\Models\ServiceLog::with('car')
            ->latest('tanggal_masuk')
            ->take(5)
            ->get();

        // 6. Estimasi Selesai Mendatang (3 Paling dekat)
        $upcomingFinish = \App\Models\ServiceLog::with('car')
            ->where('status_bengkel', 'open')
            ->whereNotNull('tanggal_keluar')
            ->orderBy('tanggal_keluar', 'asc')
            ->take(3)
            ->get();

        // 7. Invoice bengkel per service log (lengkap dengan tanda tangannya),
        //    supaya tombol TTD/Unduh bisa muncul tepat di baris servisnya.
        $bengkelInvoices = \App\Models\Invoice::with('signatures')
            ->where('jenis_invoice', 'BNG')
            ->whereNotNull('service_log_id')
            ->get()
            ->keyBy('service_log_id');

        return view('armada.bengkel', compact(
            'carsInWorkshop',
            'totalBiayaBulanIni',
            'openServiceLogs',
            'closedThisMonth',
            'recentServiceLogs',
            'upcomingFinish',
            'bengkelInvoices'
        ));
    }

    public function createBengkel($id)
    {
        $car = Car::with('parts')->findOrFail($id);

        // Cek apakah ada nota servis yang masih 'Open' hari ini/sebelumnya
        $openLog = \App\Models\ServiceLog::with('serviceLogDetails')
            ->where('car_id', $id)
            ->where('status_bengkel', 'open')
            ->first();

        return view('armada.bengkel_form', compact('car', 'openLog'));
    }

    /**
     * Menyimpan komponen baru yang akan di-track pada mobil.
     */
    /**
     * Menyimpan SATU ATAU BANYAK komponen baru sekaligus ke mobil.
     */
    public function storePart(Request $request, $id)
    {
        $car = Car::findOrFail($id);

        $request->validate([
            'master_part_ids' => 'nullable|array',
            'master_part_ids.*' => 'exists:master_parts,id',
            'nama_part_custom' => 'required_without:master_part_ids|nullable|string|max:100',
            'is_track_km_custom' => 'nullable|boolean',
            'icon_custom' => 'nullable|string',
            'lifespan_km' => 'nullable|integer|min:0',
            'last_service_km' => 'required|integer|min:0',
        ]);

        $partsAdded = 0;

        // 1. JIKA MEMILIH DARI CHECKBOX TEMPLATE
        if ($request->has('master_part_ids') && count($request->master_part_ids) > 0) {
            foreach ($request->master_part_ids as $mp_id) {
                $masterPart = MasterPart::find($mp_id);
                \App\Models\CarPart::create([
                    'car_id' => $car->id,
                    'master_part_id' => $masterPart->id,
                    'nama_part' => $masterPart->nama_part,
                    'icon' => $masterPart->icon,
                    'is_track_km' => $masterPart->is_track_km,
                    'lifespan_km' => $masterPart->default_lifespan_km,
                    'last_service_km' => $request->last_service_km,
                    'last_service_date' => now(),
                ]);
                $partsAdded++;
            }
        }

        // 2. JIKA MENGISI FORM CUSTOM MANUAL
        if ($request->filled('nama_part_custom')) {
            $isTrackKm = $request->is_track_km_custom == '1';
            \App\Models\CarPart::create([
                'car_id' => $car->id,
                'master_part_id' => null,
                'nama_part' => $request->nama_part_custom,
                'icon' => $request->filled('icon_custom') ? $request->icon_custom : 'build',
                'is_track_km' => $isTrackKm,
                'lifespan_km' => $isTrackKm ? $request->lifespan_km : null,
                'last_service_km' => $request->last_service_km,
                'last_service_date' => now(),
            ]);
            $partsAdded++;
        }

        return redirect()->route('admin.armada.show', $car->id)
            ->with('success', "Berhasil menambahkan $partsAdded komponen untuk dilacak!");
    }

    /**
     * Ubah status mobil menjadi Masuk Bengkel dan arahkan ke form servis.
     */
    public function sendToWorkshop($id)
    {
        $car = Car::findOrFail($id);

        // Jangan ubah jika mobil sedang disewa
        if ($car->status === 'Sedang Disewa') {
            return back()->withErrors(['general' => 'Mobil sedang disewa, tidak dapat dikirim ke bengkel.']);
        }

        // Ubah status mobil
        $car->update(['status' => 'Masuk Bengkel']);

        // Arahkan ke halaman form checklist bengkel
        return redirect()->route('admin.armada.bengkel.create', $car->id)
            ->with('success', 'Status mobil diubah ke Masuk Bengkel. Silakan catat rincian perbaikannya.');
    }

    /**
     * Menghapus pelacakan komponen dari mobil.
     */
    public function destroyPart($id)
    {
        $part = \App\Models\CarPart::findOrFail($id);
        $namaPart = $part->nama_part;

        // Hapus part dari pelacakan mobil
        $part->delete();

        return back()->with('success', "Pelacakan untuk komponen '{$namaPart}' berhasil dihapus.");
    }

    /**
     * Rename & Update Target KM / Keausan Komponen
     */
    public function updatePart(Request $request, $id)
    {
        $part = \App\Models\CarPart::findOrFail($id);
        $car = \App\Models\Car::findOrFail($part->car_id);

        $request->validate([
            'nama_part' => 'required|string|max:100',
            'lifespan_km' => 'nullable|integer|min:0',
            'keausan_km' => 'nullable|integer|min:0|max:' . ($request->lifespan_km ?? $part->lifespan_km),
        ]);

        // Kalkulasi ulang Odometer Pemasangan berdasarkan Input Keausan dari Slider
        $lastServiceKm = $part->last_service_km;
        if ($request->has('keausan_km')) {
            $lastServiceKm = max(0, $car->km_saat_ini - $request->keausan_km);
        }

        $part->update([
            'nama_part' => $request->nama_part,
            'lifespan_km' => $request->lifespan_km ?? $part->lifespan_km,
            'last_service_km' => $lastServiceKm,
        ]);

        return back()->with('success', 'Detail komponen berhasil diperbarui!');
    }

    /**
     * Menyimpan data servis bengkel beserta detail part yang diganti
     */
    public function storeBengkel(Request $request, $id)
    {
        $car = Car::findOrFail($id);

        $request->validate([
            'km_saat_servis' => 'required|integer|min:' . $car->km_saat_ini,
            'nama_bengkel' => 'nullable|string|max:255',
            'status_bengkel' => 'required|in:open,closed',
            'tanggal_masuk' => 'required|date',
            'tanggal_keluar' => 'nullable|date|after_or_equal:tanggal_masuk',
            'catatan_umum' => 'nullable|string',
            'foto_pendukung' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'car_part_id' => 'required|array',
            'deskripsi_tindakan' => 'required|array',
            'biaya' => 'required|array',
            'biaya.*' => 'numeric|min:0',
            'reset_part' => 'nullable|array',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $fotoPath = null;
            if ($request->hasFile('foto_pendukung')) {
                $fotoPath = $request->file('foto_pendukung')->store('bukti_servis', 'public');
            }

            // CEK: Apakah ini Buat Baru atau Update Simpanan Sementara?
            $serviceLog = \App\Models\ServiceLog::where('car_id', $car->id)->where('status_bengkel', 'open')->first();

            if ($serviceLog) {
                // UPDATE DATA YANG ADA
                $serviceLog->update([
                    'km_saat_servis' => $request->km_saat_servis,
                    'nama_bengkel' => $request->nama_bengkel,
                    'status_bengkel' => $request->status_bengkel,
                    'tanggal_masuk' => $request->tanggal_masuk,
                    'tanggal_keluar' => $request->tanggal_keluar,
                    'catatan' => $request->catatan_umum,
                ]);
                if ($fotoPath)
                    $serviceLog->update(['foto_nota' => $fotoPath]);

                // Hapus semua rincian part lama untuk diganti dengan yang baru dari form
                $serviceLog->serviceLogDetails()->delete();
            } else {
                // BUAT DATA BARU
                $serviceLog = \App\Models\ServiceLog::create([
                    'car_id' => $car->id,
                    'km_saat_servis' => $request->km_saat_servis,
                    'nama_bengkel' => $request->nama_bengkel,
                    'status_bengkel' => $request->status_bengkel,
                    'tanggal_masuk' => $request->tanggal_masuk,
                    'tanggal_keluar' => $request->tanggal_keluar,
                    'catatan' => $request->catatan_umum,
                    'foto_nota' => $fotoPath,
                ]);
            }

            // Update Status & Odometer Mobil
            $car->update([
                'km_saat_ini' => $request->km_saat_servis,
                'status' => $request->status_bengkel === 'closed' ? 'Tersedia' : 'Masuk Bengkel',
            ]);

            foreach ($request->car_part_id as $key => $partId) {
                if (!empty($request->deskripsi_tindakan[$key])) {

                    \App\Models\ServiceLogDetail::create([
                        'service_log_id' => $serviceLog->id,
                        'car_part_id' => $partId ?: null,
                        'deskripsi_tindakan' => $request->deskripsi_tindakan[$key],
                        'biaya' => $request->biaya[$key] ?? 0,
                    ]);

                    // LOGIKA SAKTI DIPERBARUI: 
                    // Otomatis Reset Umur HANYA JIKA part dipilih DAN statusnya Selesai (Closed)!
                    if ($partId && $request->status_bengkel === 'closed') {
                        \App\Models\CarPart::where('id', $partId)->update([
                            'last_service_km' => $request->km_saat_servis,
                            'last_service_date' => now(),
                        ]);
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            // LOGIKA REDIRECT PINTAR
            if ($request->status_bengkel === 'open') {
                // Jika masih Open, kembali ke form ini dengan pesan sukses
                return redirect()->route('admin.armada.bengkel.create', $car->id)
                    ->with('success', 'Draft servis berhasil disimpan. Anda bisa melanjutkan pengisian part lainnya.');
            } else {
                // Jika sudah Closed, kembali ke detail mobil
                session()->flash('activeMainTab', 'komponen');
                return redirect()->route('admin.armada.show', $car->id)
                    ->with('success', 'Nota servis telah ditutup & kendaraan kembali tersedia!');
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage())->withInput();
        }
    }

    public function saleTemplate()
    {
        $filePath = 'sale-template/content.html';
        $content = null;
        if (Storage::disk('local')->exists($filePath)) {
            $content = Storage::disk('local')->get($filePath);
        }
        return view('armada.sale-template', compact('content'));
    }

    public function saveSaleTemplate(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        Storage::disk('local')->put('sale-template/content.html', $request->content);

        return response()->json([
            'success' => true,
            'message' => 'Template berhasil disimpan.',
        ]);
    }
}