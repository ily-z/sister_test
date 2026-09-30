<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Surat;
use App\Support\FileUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Surat menyurat: penomoran surat, arsip invoice, balik nama, serah terima.
 *
 * Logika bisnis identik dengan versi Blade, hanya return-nya JSON.
 */
class SuratMenyuratController extends Controller
{
    public function index(): JsonResponse
    {
        $surat = Surat::orderBy('jenis_dokumen')->orderBy('subkategori')->orderBy('created_at')->get();

        $grouped = [];
        foreach ($surat as $s) {
            $jenis = $s->jenis_dokumen;
            if (! isset($grouped[$jenis])) {
                $grouped[$jenis] = ['main' => null, 'children' => collect()];
            }
            if (empty($s->subkategori)) {
                if (! $grouped[$jenis]['main']) {
                    $grouped[$jenis]['main'] = $s;
                }
            } else {
                $grouped[$jenis]['children']->push($s);
            }
        }

        return response()->json([
            'grouped' => $grouped,
            'subkategorisByJenis' => $this->subkategorisByJenis(),
        ]);
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'jenisDokumen' => Surat::select('jenis_dokumen')
                ->distinct()
                ->orderBy('jenis_dokumen')
                ->pluck('jenis_dokumen'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'jenis_dokumen' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:255'],
            'subkategori' => ['nullable', 'string', 'max:255'],
        ]);

        $surat = Surat::create($this->suratPayload($request));

        return response()->json([
            'message' => 'Nomor surat berhasil dibuat.',
            'surat' => $surat,
        ], 201);
    }

    public function storeSub(Request $request): JsonResponse
    {
        $request->validate([
            'jenis_dokumen' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:255'],
            'subkategori' => ['required', 'string', 'max:255'],
        ]);

        $exists = Surat::where('jenis_dokumen', $request->jenis_dokumen)
            ->where('subkategori', $request->subkategori)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'subkategori' => 'Subkategori "'.$request->subkategori.'" sudah ada untuk jenis dokumen ini.',
            ]);
        }

        $surat = Surat::create($this->suratPayload($request));

        return response()->json([
            'message' => 'Subkategori "'.$request->subkategori.'" berhasil ditambahkan.',
            'surat' => $surat,
        ], 201);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:surat,id'],
        ]);

        $count = Surat::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => $count.' nomor surat berhasil dihapus.',
        ]);
    }

    /**
     * Arsip dokumen: semua invoice yang sudah jadi surat.
     */
    public function arsip(Request $request): JsonResponse
    {
        $query = Invoice::with(['surat', 'signatures', 'booking.bookingCars.car', 'serviceLog.car']);

        if ($request->filled('jenis_dokumen')) {
            $query->whereHas('surat', fn ($q) => $q->where('jenis_dokumen', $request->jenis_dokumen));
        }

        if ($request->filled('subkategori')) {
            $query->whereHas('surat', fn ($q) => $q->where('subkategori', $request->subkategori));
        }

        if ($request->filled('bulan')) {
            $query->whereRaw('DATE_FORMAT(issued_at, "%Y-%m") = ?', [$request->bulan]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_seri', 'like', "%{$search}%")
                    ->orWhere('kode_mobil', 'like', "%{$search}%")
                    ->orWhereHas('surat', fn ($sq) => $sq
                        ->where('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('jenis_dokumen', 'like', "%{$search}%")
                        ->orWhere('subkategori', 'like', "%{$search}%"))
                    ->orWhereHas('booking', fn ($bq) => $bq->where('nomor_pesanan', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->orderByDesc('issued_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return response()->json([
            'invoices' => $invoices,
            'jenisDokumenList' => Surat::select('jenis_dokumen')->distinct()->orderBy('jenis_dokumen')->pluck('jenis_dokumen'),
            'subkategorisByJenis' => $this->subkategorisByJenis(),
        ]);
    }

    /**
     * Surat Balik Nama Kendaraan.
     */
    public function balikNama(Car $car): JsonResponse
    {
        $surat = $this->buatSuratOtomatis('BN', 'Balik Nama');

        return response()->json([
            'surat' => $surat,
            'car' => $car,
            'foto_url' => FileUrl::public($car->foto),
            'dokumen_url' => FileUrl::public($car->foto_dokumen),
        ]);
    }

    /**
     * Surat Serah Terima Kendaraan.
     */
    public function serahTerima(Booking $booking): JsonResponse
    {
        $booking->load(['bookingCars.car']);

        $surat = $this->buatSuratOtomatis('ST', 'Serah Terima');

        // Foto serah terima: foto kondisi tiap kendaraan (disimpan di
        // booking_car.foto_kondisi) + foto pendukung di level booking.
        $fotoKondisi = collect($booking->bookingCars)
            ->flatMap(fn ($bc) => collect(
                $bc->foto_kondisi ?? []
            )->map(fn ($path) => [
                'kendaraan' => $bc->car?->plat_nomor,
                'url' => FileUrl::public($path),
            ]))
            ->filter(fn ($item) => $item['url'] !== null)
            ->values();

        $fotoPendukung = collect($booking->foto_pendukung ?? [])
            ->map(fn ($path) => FileUrl::public($path))
            ->filter()
            ->values();

        return response()->json([
            'booking' => $booking,
            'surat' => $surat,
            'foto_kondisi' => $fotoKondisi,
            'foto_pendukung' => $fotoPendukung,
        ]);
    }

    /**
     * Ambil nomor surat terbaru untuk kode tertentu, atau buat baru
     * kalau belum pernah ada.
     */
    private function buatSuratOtomatis(string $kode, string $jenisDokumen): Surat
    {
        $surat = Surat::where('kode', $kode)->latest()->first();

        if ($surat) {
            return $surat;
        }

        $number = $this->generateNomorSurat($kode);

        return Surat::create([
            'jenis_dokumen' => $jenisDokumen,
            'kode' => $kode,
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => $number['noUrut'],
            'bulan' => $number['bulan'],
            'tahun' => $number['tahun'],
            'nomor_surat' => $number['nomorSurat'],
        ]);
    }

    /**
     * Nomor surat berikutnya untuk kode + tahun berjalan.
     *
     * Bentuk: {urut}/SRT/{kode}/JRB/{bulan romawi}/{tahun}
     *
     * WAJIB dipanggil dari dalam DB::transaction(). lockForUpdate() hanya
     * berarti apa-apa di dalam transaksi: tanpa itu, dua operator yang
     * membuat surat bersamaan bisa membaca no_urut yang sama dan
     * menghasilkan nomor surat duplikat.
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
     * Payload create Surat dari request (dipakai store & storeSub).
     *
     * Bungkus dalam transaksi supaya lockForUpdate() di
     * generateNomorSurat() benar-benar mengunci baris terakhir: tanpa
     * transaksi, dua operator yang klik bersamaan bisa membuat surat
     * dengan nomor yang sama.
     */
    private function suratPayload(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $number = $this->generateNomorSurat($request->kode);

            return [
                'jenis_dokumen' => $request->jenis_dokumen,
                'kode' => $request->kode,
                'subkategori' => $request->subkategori,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'no_urut' => $number['noUrut'],
                'bulan' => $number['bulan'],
                'tahun' => $number['tahun'],
                'nomor_surat' => $number['nomorSurat'],
            ];
        });
    }

    private function subkategorisByJenis()
    {
        return Surat::whereNotNull('subkategori')
            ->select('jenis_dokumen', 'subkategori')
            ->distinct()
            ->orderBy('jenis_dokumen')
            ->orderBy('subkategori')
            ->get()
            ->groupBy('jenis_dokumen')
            ->map(fn ($items) => $items->pluck('subkategori'));
    }
}
