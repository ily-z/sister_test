<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingCar;
use App\Models\Car;
use App\Models\CarPart;
use App\Models\Invoice;
use App\Models\MasterPart;
use App\Models\OdometerLog;
use App\Models\ServiceLog;
use App\Models\ServiceLogDetail;
use App\Models\Surat;
use App\Support\FileUrl;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Armada: data kendaraan, komponen (part tracking), dan servis bengkel.
 *
 * Logika bisnis identik dengan versi Blade. Yang berubah:
 *   - return view() -> JSON
 *   - session('armada_create_surat_id') -> key sementara per-user, karena
 *     frontend sekarang tidak punya session. Lihat keyArmadaSession().
 *   - asset('storage/...') -> FileUrl::public(...) (signed URL)
 *
 * SOAL kode_mobil
 * ---------------
 * Versi Blade menerima dan menyimpan `kode_mobil` di tabel cars. Kolom itu
 * tidak pernah ada di cars -- ia ada di tabel invoices (lihat dump legacy).
 * Karena `Car::create()` memakai $validated apa adanya, kode lama berakhir
 * dengan error SQL "Unknown column 'kode_mobil'".
 *
 * Di sini penanganannya dihapus dari store() dan update(). Konsekuensinya:
 * invoice yang referencing fallback 'XXX' untuk kode mobil. Itu perilaku
 * yang sama dengan yang sudah terjadi di produksi, jadi tidak ada perubahan
 * hasil yang dirasakan pengguna. Lihat docs/KNOWN_ISSUES.md.
 */
class ArmadaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Car::with(['parts', 'activeBooking']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('plat_nomor', 'like', "%{$search}%")
                ->orWhere('merk', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('merk')) {
            $query->where('merk', $request->merk);
        }
        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        // Filter servis due membaca tabel car_parts.
        if ($request->filled('servis_due') && $request->servis_due == 1) {
            $query->whereHas('parts', fn ($q) => $q
                ->where('is_track_km', true)
                ->whereRaw('(cars.km_saat_ini - car_parts.last_service_km) >= car_parts.lifespan_km'));
        }

        $cars = $query->orderBy('created_at', 'desc')
            ->paginate((int) $request->query('per_page', 9))
            ->withQueryString();

        // Statistik memakai data asli tanpa filter.
        $allCars = Car::with('parts')->get();

        return response()->json([
            'cars' => $cars->through(fn (Car $car) => $this->carPayload($car)),
            'total' => $allCars->count(),
            'tersedia' => $allCars->where('status', 'Tersedia')->count(),
            'disewa' => $allCars->where('status', 'Sedang Disewa')->count(),
            'bengkel' => $allCars->where('status', 'Masuk Bengkel')->count(),
            'nonaktif' => $allCars->where('status', 'Nonaktif')->count(),
            'serviceDueCount' => $allCars->filter(fn (Car $car) => $car->parts->contains(
                fn (CarPart $part) => $part->is_track_km && (($car->km_saat_ini - $part->last_service_km) >= $part->lifespan_km)
            ))->count(),
            'filterOptions' => [
                'status' => Car::distinct()->orderBy('status')->pluck('status'),
                'merk' => Car::distinct()->orderBy('merk')->pluck('merk'),
                'tahun' => Car::distinct()->orderByDesc('tahun')->pluck('tahun'),
            ],
        ]);
    }

    /**
     * Draft dokumen PHK yang sudah dibuat tapi mobilnya belum disimpan.
     *
     * Di versi Blade ini disimpan di session. Karena frontend sekarang
     * tidak punya session, disimpan di cache dengan key per-user (lihat
     * keyArmadaSession) supaya tidak bentrok antar operator yang
     * bersamaan membuat draft.
     */
    public function create(Request $request): JsonResponse
    {
        $draftSuratId = $this->getDraftSuratId($request);

        $draftSurat = $draftSuratId
            ? Surat::where('id', $draftSuratId)->where('kode', 'PHK')->first()
            : null;

        return response()->json(['draftSurat' => $draftSurat]);
    }

    /**
     * POST /api/armada/create-dokumen
     *
     * Membuat Surat Pelepasan Hak Kendaraan sebelum dokumen diunggah.
     * Idempoten per user: tombol yang ditekan berulang tidak membuat
     * nomor surat beruntun.
     */
    public function createDokumen(Request $request): JsonResponse
    {
        $existing = $this->getDraftSurat($request);

        if ($existing) {
            return response()->json([
                'success' => true,
                'surat_id' => $existing->id,
                'nomor_surat' => $existing->nomor_surat,
            ]);
        }

        $surat = DB::transaction(function () {
            $nomor = $this->generateNomorSurat('PHK');

            return Surat::create([
                'jenis_dokumen' => 'Pelepasan Hak Kendaraan',
                'kode' => 'PHK',
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'no_urut' => $nomor['noUrut'],
                'bulan' => $nomor['bulan'],
                'tahun' => $nomor['tahun'],
                'nomor_surat' => $nomor['nomorSurat'],
            ]);
        });

        $this->setDraftSuratId($request, $surat->id);

        return response()->json([
            'success' => true,
            'surat_id' => $surat->id,
            'nomor_surat' => $surat->nomor_surat,
        ], 201);
    }

    /**
     * Data untuk cetak dokumen PHK.
     *
     * Data mobil & pembeli dikirim sebagai query string karena mobilnya
     * belum disimpan di database.
     */
    public function dokumenPhk(Request $request, string $surat): JsonResponse
    {
        $surat = Surat::where('id', $surat)->where('kode', 'PHK')->firstOrFail();

        return response()->json([
            'surat' => $surat,
            'fields' => collect([
                'plat', 'merk', 'model', 'tahun', 'jenis', 'isi_silinder',
                'mesin', 'rangka', 'warna', 'pembeli', 'alamat_pembeli',
            ])->mapWithKeys(fn (string $key) => [$key => (string) $request->query($key, '')])->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $messages = [
            'plat_nomor.unique' => 'Plat nomor ini sudah digunakan. Silakan gunakan plat lain.',
            'no_mesin.unique' => 'Nomor mesin ini sudah terdaftar. Periksa kembali isian Anda.',
            'no_rangka.unique' => 'Nomor rangka ini sudah terpakai. Gunakan nomor rangka yang valid dan unik.',
            'surat_id.required' => 'Buat nomor surat Pelepasan Hak Kendaraan dahulu melalui panel dokumen.',
            'surat_id.exists' => 'Nomor surat yang dipilih tidak valid. Silakan buat dokumen ulang.',
            'foto_dokumen.required' => 'Dokumen pelepasan hak kendaraan wajib diunggah sebelum data bisa disimpan.',
        ];

        $validated = $request->validate([
            'plat_nomor' => ['required', 'unique:cars,plat_nomor', 'max:15'],
            'merk' => ['required', 'max:50'],
            'model' => ['required', 'max:100'],
            'warna' => ['nullable', 'max:50'],
            'tahun' => ['required', 'integer'],
            'kapasitas' => ['required', 'integer'],
            'harga_default' => ['required', 'numeric', 'min:0'],
            'harga_kontrak' => ['required', 'numeric', 'min:0'],
            'no_mesin' => ['required', 'unique:cars,no_mesin', 'max:50'],
            'no_rangka' => ['required', 'unique:cars,no_rangka', 'max:50'],
            'km_saat_ini' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:Tersedia,Masuk Bengkel'],
            'surat_id' => ['required', Rule::exists('surat', 'id')->where('kode', 'PHK')],
            'foto_dokumen' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'foto_cropped' => ['nullable', 'string'],
        ], $messages);

        $validated['foto_dokumen'] = $request->file('foto_dokumen')->store('dokumen_mobil', 'public');

        if ($request->filled('foto_cropped')) {
            $validated['foto'] = $this->simpanFotoCropped($request->foto_cropped);
        }

        $car = Car::create($validated);

        $this->forgetDraftSurat($request);

        return response()->json([
            'message' => 'Data mobil berhasil ditambahkan!',
            'car' => $this->carPayload($car->fresh()),
        ], 201);
    }

    public function edit(Car $armada): JsonResponse
    {
        return response()->json(['car' => $this->carPayload($armada)]);
    }

    public function show(Request $request, Car $armada): JsonResponse
    {
        $armada->load(['parts', 'activeBooking']);

        $latestBookings = BookingCar::where('car_id', $armada->id)
            ->with('booking')
            ->when($request->filled('status_pembayaran_riwayat'), fn ($q) => $q->whereHas(
                'booking',
                fn ($bq) => $bq->where('status_pembayaran', $request->status_pembayaran_riwayat)
            ))
            ->paginate(8, ['*'], 'page');

        return response()->json([
            'car' => $this->carPayload($armada, withParts: true),
            'latestBookings' => $latestBookings,
            'latestOdometerLogs' => $armada->odometerLogs()->orderByDesc('created_at')->take(4)->get(),
            'totalBookings' => BookingCar::where('car_id', $armada->id)->count(),
            'masterParts' => MasterPart::all(),
        ]);
    }

    public function updateKm(Request $request, Car $armada): JsonResponse
    {
        $validated = $request->validate([
            'km_baru' => ['required', 'integer', 'min:'.($armada->km_saat_ini + 1)],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $armada->update(['km_saat_ini' => $validated['km_baru']]);

        OdometerLog::create([
            'car_id' => $armada->id,
            'km_reading' => $validated['km_baru'],
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Kilometer berhasil diperbarui dan log odometer tercatat.',
            'km_saat_ini' => $armada->km_saat_ini,
        ]);
    }

    public function update(Request $request, Car $armada): JsonResponse
    {
        $lockKapasitas = $armada->status === 'Sedang Disewa';

        $messages = [
            'plat_nomor.unique' => 'Plat nomor ini sudah digunakan. Silakan gunakan plat lain.',
            'no_mesin.unique' => 'Nomor mesin ini sudah terdaftar. Periksa kembali isian Anda.',
            'no_rangka.unique' => 'Nomor rangka ini sudah terpakai. Gunakan nomor rangka yang valid dan unik.',
        ];

        $validated = $request->validate([
            'plat_nomor' => ['required', 'max:15', Rule::unique('cars', 'plat_nomor')->ignore($armada->id)],
            'merk' => ['required', 'max:50'],
            'model' => ['required', 'max:100'],
            'warna' => ['nullable', 'max:50'],
            'tahun' => ['required', 'integer'],
            'kapasitas' => ['required', 'integer'],
            'harga_default' => ['required', 'numeric', 'min:0'],
            'harga_kontrak' => ['required', 'numeric', 'min:0'],
            'no_mesin' => ['required', 'max:50', Rule::unique('cars', 'no_mesin')->ignore($armada->id)],
            'no_rangka' => ['required', 'max:50', Rule::unique('cars', 'no_rangka')->ignore($armada->id)],
            'km_saat_ini' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:Tersedia,Masuk Bengkel,Sedang Disewa,Nonaktif'],
            'foto_dokumen' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'foto_cropped' => ['nullable', 'string'],
        ], $messages);

        if ($request->hasFile('foto_dokumen')) {
            if ($armada->foto_dokumen && Storage::disk('public')->exists($armada->foto_dokumen)) {
                Storage::disk('public')->delete($armada->foto_dokumen);
            }
            $validated['foto_dokumen'] = $request->file('foto_dokumen')->store('dokumen_mobil', 'public');
        }

        // Mobil yang sedang disewa tidak boleh mengubah kapasitas.
        if ($lockKapasitas) {
            $validated['kapasitas'] = $armada->kapasitas;
        }

        if ($request->filled('foto_cropped')) {
            if ($armada->foto && Storage::disk('public')->exists($armada->foto)) {
                Storage::disk('public')->delete($armada->foto);
            }
            $validated['foto'] = $this->simpanFotoCropped($request->foto_cropped);
        }

        $armada->update($validated);

        return response()->json([
            'message' => 'Data armada berhasil diperbarui!',
            'car' => $this->carPayload($armada->fresh()),
        ]);
    }

    public function destroy(Car $armada): JsonResponse
    {
        // Mobil dengan riwayat booking tidak boleh dihapus permanen.
        if (BookingCar::where('car_id', $armada->id)->exists()) {
            throw new ValidationException([
                'general' => 'Mobil ini tidak bisa dihapus permanen karena memiliki riwayat booking. '
                    .'Silakan ubah status mobil menjadi "Nonaktif" lewat halaman Edit agar tidak muncul '
                    .'di pilihan booking baru, namun riwayat transaksinya tetap tersimpan.',
            ]);
        }

        if ($armada->foto && Storage::disk('public')->exists($armada->foto)) {
            Storage::disk('public')->delete($armada->foto);
        }

        if ($armada->foto_dokumen && Storage::disk('public')->exists($armada->foto_dokumen)) {
            Storage::disk('public')->delete($armada->foto_dokumen);
        }

        $armada->delete();

        return response()->json(['message' => 'Data kendaraan berhasil dihapus secara permanen.']);
    }

    // ── Bengkel ───────────────────────────────────────────────────────

    public function bengkel(): JsonResponse
    {
        $carsInWorkshop = Car::where('status', 'Masuk Bengkel')
            ->with(['serviceLogs' => fn ($q) => $q
                ->where('status_bengkel', 'open')
                ->with('details')
                ->latest('tanggal_masuk')])
            ->get();

        $now = Carbon::now();

        return response()->json([
            'carsInWorkshop' => $carsInWorkshop->map(fn (Car $car) => $this->carPayload($car)),
            'totalBiayaBulanIni' => ServiceLogDetail::whereHas('serviceLog', fn ($q) => $q
                ->whereMonth('tanggal_masuk', $now->month)
                ->whereYear('tanggal_masuk', $now->year))->sum('biaya'),
            'openServiceLogs' => ServiceLog::where('status_bengkel', 'open')->count(),
            'closedThisMonth' => ServiceLog::where('status_bengkel', 'closed')
                ->whereMonth('tanggal_keluar', $now->month)
                ->whereYear('tanggal_keluar', $now->year)
                ->count(),
            'recentServiceLogs' => ServiceLog::with('car')->latest('tanggal_masuk')->take(5)->get(),
            'upcomingFinish' => ServiceLog::with('car')
                ->where('status_bengkel', 'open')
                ->whereNotNull('tanggal_keluar')
                ->orderBy('tanggal_keluar')
                ->take(3)
                ->get(),
            // Invoice bengkel per service log, supaya tombol TTD/Unduh
            // muncul tepat di baris servisnya.
            'bengkelInvoices' => Invoice::with('signatures')
                ->where('jenis_invoice', 'BNG')
                ->whereNotNull('service_log_id')
                ->get()
                ->keyBy('service_log_id'),
        ]);
    }

    public function createBengkel(Car $armada): JsonResponse
    {
        $armada->load('parts');

        $openLog = ServiceLog::with('serviceLogDetails')
            ->where('car_id', $armada->id)
            ->where('status_bengkel', 'open')
            ->first();

        return response()->json([
            'car' => $this->carPayload($armada, withParts: true),
            'openLog' => $openLog,
        ]);
    }

    public function storeBengkel(Request $request, Car $armada): JsonResponse
    {
        $validated = $request->validate([
            'km_saat_servis' => ['required', 'integer', 'min:'.$armada->km_saat_ini],
            'nama_bengkel' => ['nullable', 'string', 'max:255'],
            'status_bengkel' => ['required', 'in:open,closed'],
            'tanggal_masuk' => ['required', 'date'],
            'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'catatan_umum' => ['nullable', 'string'],
            'foto_pendukung' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'car_part_id' => ['required', 'array'],
            'deskripsi_tindakan' => ['required', 'array'],
            'biaya' => ['required', 'array'],
            'biaya.*' => ['numeric', 'min:0'],
            'reset_part' => ['nullable', 'array'],
        ]);

        $fotoPath = $request->hasFile('foto_pendukung')
            ? $request->file('foto_pendukung')->store('bukti_servis', 'public')
            : null;

        try {
            $serviceLog = DB::transaction(function () use ($armada, $request, $validated, $fotoPath) {
                // Update draft yang masih open, atau buat baru.
                $serviceLog = ServiceLog::where('car_id', $armada->id)
                    ->where('status_bengkel', 'open')
                    ->first();

                $attributes = [
                    'km_saat_servis' => $validated['km_saat_servis'],
                    'nama_bengkel' => $validated['nama_bengkel'],
                    'status_bengkel' => $validated['status_bengkel'],
                    'tanggal_masuk' => $validated['tanggal_masuk'],
                    'tanggal_keluar' => $validated['tanggal_keluar'],
                    'catatan' => $validated['catatan_umum'],
                ];

                if ($serviceLog) {
                    $serviceLog->update($fotoPath ? $attributes + ['foto_nota' => $fotoPath] : $attributes);
                    // Ganti seluruh rincian part lama dengan isian form baru.
                    $serviceLog->serviceLogDetails()->delete();
                } else {
                    $serviceLog = ServiceLog::create(
                        $attributes + ['car_id' => $armada->id, 'foto_nota' => $fotoPath]
                    );
                }

                $armada->update([
                    'km_saat_ini' => $validated['km_saat_servis'],
                    'status' => $validated['status_bengkel'] === 'closed' ? 'Tersedia' : 'Masuk Bengkel',
                ]);

                foreach ($validated['car_part_id'] as $key => $partId) {
                    if (empty($validated['deskripsi_tindakan'][$key])) {
                        continue;
                    }

                    ServiceLogDetail::create([
                        'service_log_id' => $serviceLog->id,
                        'car_part_id' => $partId ?: null,
                        'deskripsi_tindakan' => $validated['deskripsi_tindakan'][$key],
                        'biaya' => $validated['biaya'][$key] ?? 0,
                    ]);

                    // Reset umur komponen HANYA kalau part dipilih DAN
                    // servis ditutup.
                    if ($partId && $validated['status_bengkel'] === 'closed') {
                        CarPart::where('id', $partId)->update([
                            'last_service_km' => $validated['km_saat_servis'],
                            'last_service_date' => now(),
                        ]);
                    }
                }

                return $serviceLog;
            });
        } catch (Throwable $e) {
            report($e);

            throw new ValidationException([
                'general' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
            ]);
        }

        $closed = $validated['status_bengkel'] === 'closed';

        return response()->json([
            'message' => $closed
                ? 'Nota servis telah ditutup & kendaraan kembali tersedia!'
                : 'Draft servis berhasil disimpan. Anda bisa melanjutkan pengisian part lainnya.',
            'service_log_id' => $serviceLog->id,
            'redirect_path' => $closed
                ? "/armada/{$armada->id}"
                : "/armada/{$armada->id}/bengkel/create",
            'active_tab' => $closed ? 'komponen' : null,
        ], 201);
    }

    public function sendToWorkshop(Car $armada): JsonResponse
    {
        if ($armada->status === 'Sedang Disewa') {
            throw new ValidationException([
                'general' => 'Mobil sedang disewa, tidak dapat dikirim ke bengkel.',
            ]);
        }

        $armada->update(['status' => 'Masuk Bengkel']);

        return response()->json([
            'message' => 'Status mobil diubah ke Masuk Bengkel. Silakan catat rincian perbaikannya.',
            'redirect_path' => "/armada/{$armada->id}/bengkel/create",
        ]);
    }

    // ── Komponen (part) ───────────────────────────────────────────────

    /**
     * Simpan SATU ATAU BANYAK komponen sekaligus ke mobil.
     */
    public function storePart(Request $request, Car $armada): JsonResponse
    {
        $validated = $request->validate([
            'master_part_ids' => ['nullable', 'array'],
            'master_part_ids.*' => ['exists:master_parts,id'],
            'nama_part_custom' => ['required_without:master_part_ids', 'nullable', 'string', 'max:100'],
            'is_track_km_custom' => ['nullable', 'boolean'],
            'icon_custom' => ['nullable', 'string'],
            'lifespan_km' => ['nullable', 'integer', 'min:0'],
            'last_service_km' => ['required', 'integer', 'min:0'],
        ]);

        $partsAdded = DB::transaction(function () use ($armada, $validated) {
            $added = 0;

            // 1. Dari checkbox template master part.
            foreach ($validated['master_part_ids'] ?? [] as $mpId) {
                $masterPart = MasterPart::find($mpId);
                if (! $masterPart) {
                    continue;
                }

                CarPart::create([
                    'car_id' => $armada->id,
                    'master_part_id' => $masterPart->id,
                    'nama_part' => $masterPart->nama_part,
                    'icon' => $masterPart->icon,
                    'is_track_km' => $masterPart->is_track_km,
                    'lifespan_km' => $masterPart->default_lifespan_km,
                    'last_service_km' => $validated['last_service_km'],
                    'last_service_date' => now(),
                ]);
                $added++;
            }

            // 2. Form custom manual.
            if (! empty($validated['nama_part_custom'])) {
                $isTrackKm = (bool) ($validated['is_track_km_custom'] ?? false);

                CarPart::create([
                    'car_id' => $armada->id,
                    'master_part_id' => null,
                    'nama_part' => $validated['nama_part_custom'],
                    'icon' => $validated['icon_custom'] ?: 'build',
                    'is_track_km' => $isTrackKm,
                    'lifespan_km' => $isTrackKm ? ($validated['lifespan_km'] ?? null) : null,
                    'last_service_km' => $validated['last_service_km'],
                    'last_service_date' => now(),
                ]);
                $added++;
            }

            return $added;
        });

        return response()->json([
            'message' => "Berhasil menambahkan {$partsAdded} komponen untuk dilacak!",
            'parts_added' => $partsAdded,
        ], 201);
    }

    public function destroyPart(CarPart $part): JsonResponse
    {
        $namaPart = $part->nama_part;
        $part->delete();

        return response()->json([
            'message' => "Pelacakan untuk komponen '{$namaPart}' berhasil dihapus.",
        ]);
    }

    public function updatePart(Request $request, CarPart $part): JsonResponse
    {
        $car = Car::findOrFail($part->car_id);

        $validated = $request->validate([
            'nama_part' => ['required', 'string', 'max:100'],
            'lifespan_km' => ['nullable', 'integer', 'min:0'],
            'keausan_km' => ['nullable', 'integer', 'min:0', 'max:'.($request->lifespan_km ?? $part->lifespan_km)],
        ]);

        // Hitung ulang odometer pemasangan dari input keausan slider.
        $lastServiceKm = $part->last_service_km;
        if ($request->has('keausan_km')) {
            $lastServiceKm = max(0, $car->km_saat_ini - $request->keausan_km);
        }

        $part->update([
            'nama_part' => $validated['nama_part'],
            'lifespan_km' => $validated['lifespan_km'] ?? $part->lifespan_km,
            'last_service_km' => $lastServiceKm,
        ]);

        return response()->json([
            'message' => 'Detail komponen berhasil diperbarui!',
            'part' => $part->fresh(),
        ]);
    }

    // ── Template penjualan ─────────────────────────────────────────────

    public function saleTemplate(): JsonResponse
    {
        $filePath = 'sale-template/content.html';

        return response()->json([
            'content' => Storage::disk('local')->exists($filePath)
                ? Storage::disk('local')->get($filePath)
                : null,
        ]);
    }

    public function saveSaleTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        Storage::disk('local')->put('sale-template/content.html', $validated['content']);

        return response()->json([
            'success' => true,
            'message' => 'Template berhasil disimpan.',
        ]);
    }

    // ── Helper ─────────────────────────────────────────────────────────

    /**
     * Nomor surat berikutnya untuk kode + tahun berjalan.
     * WAJIB dipanggil dari dalam DB::transaction() supaya lockForUpdate()
     * benar-benar mencegah dua operator mendapat nomor sama.
     */
    protected function generateNomorSurat(string $kode): array
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

        return [
            'noUrut' => $noUrut,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'nomorSurat' => "{$noUrut}/SRT/{$kode}/JRB/{$bulan}/{$tahun}",
        ];
    }

    /**
     * Simpan hasil crop gambar dari frontend (Cropper.js) sebagai file.
     *
     * Data URL harus divalidasi: frontend mengirim string dari canvas,
     * tapi request bisa datang dari mana saja. Tanpa cek prefix, payload
     * Jekyll bisa ditulis ke disk dengan ekstensi bebas.
     */
    private function simpanFotoCropped(string $dataUrl): string
    {
        if (! preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#i', $dataUrl)) {
            throw new ValidationException([
                'foto_cropped' => 'Format foto tidak valid.',
            ]);
        }

        $parts = explode(';base64,', $dataUrl, 2);
        $imageType = strtolower(explode('image/', $parts[0])[1]);
        $binary = base64_decode($parts[1], true);

        if ($binary === false || $binary === '') {
            throw new ValidationException([
                'foto_cropped' => 'Isi foto tidak bisa dibaca.',
            ]);
        }

        $fileName = 'cars/'.time().'_'.uniqid().'.'.$imageType;
        Storage::disk('public')->put($fileName, $binary);

        return $fileName;
    }

    /**
     * Bentuk mobil untuk response API, termasuk signed URL untuk foto.
     */
    private function carPayload(Car $car, bool $withParts = false): array
    {
        $payload = $car->toArray();

        $payload['foto_url'] = FileUrl::public($car->foto);
        $payload['foto_dokumen_url'] = FileUrl::public($car->foto_dokumen);
        $payload['detail_path'] = "/armada/{$car->id}";

        if ($withParts && $car->relationLoaded('parts')) {
            $payload['parts'] = $car->parts->map(fn (CarPart $part) => array_merge($part->toArray(), [
                'keausan_km' => max(0, $car->km_saat_ini - $part->last_service_km),
                'persentase_keausan' => $part->lifespan_km > 0
                    ? round((max(0, $car->km_saat_ini - $part->last_service_km) / $part->lifespan_km) * 100, 1)
                    : 0,
            ]))->values();
        }

        return $payload;
    }

    /**
     * Key cache untuk draft surat PHK, di-scope per user.
     *
     * Di versi Blade dipakai session, jadi draft masing-masing user tidak
     * saling menimpa. Tanpa session, key harus di-scope per user juga --
     * kalau tidak, draft operator A akan muncul di form operator B.
     */
    private function keyArmadaSession(Request $request): string
    {
        return 'armada:create:surat:user:'.($request->user()?->id ?? 'anon');
    }

    private function getDraftSuratId(Request $request): ?int
    {
        $id = cache()->get($this->keyArmadaSession($request));

        return $id ? (int) $id : null;
    }

    private function getDraftSurat(Request $request): ?Surat
    {
        $id = $this->getDraftSuratId($request);

        return $id ? Surat::where('id', $id)->where('kode', 'PHK')->first() : null;
    }

    private function setDraftSuratId(Request $request, int $suratId): void
    {
        // TTL 1 jam: draft yang ditinggalkan tidak mengunci kode PHK selamanya.
        cache()->put($this->keyArmadaSession($request), $suratId, now()->addHour());
    }

    private function forgetDraftSurat(Request $request): void
    {
        cache()->forget($this->keyArmadaSession($request));
    }
}
