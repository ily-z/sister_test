<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\Car;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuratMenyuratController extends Controller
{
    public function index()
    {
        $surat = Surat::orderBy('jenis_dokumen')->orderBy('subkategori')->orderBy('created_at')->get();
        $grouped = [];
        foreach ($surat as $s) {
            $jenis = $s->jenis_dokumen;
            if (!isset($grouped[$jenis])) {
                $grouped[$jenis] = ['main' => null, 'children' => collect()];
            }
            if (empty($s->subkategori)) {
                if (!$grouped[$jenis]['main']) {
                    $grouped[$jenis]['main'] = $s;
                }
            } else {
                $grouped[$jenis]['children']->push($s);
            }
        }

        $subkategorisByJenis = Surat::whereNotNull('subkategori')
            ->select('jenis_dokumen', 'subkategori')
            ->distinct()
            ->orderBy('jenis_dokumen')
            ->orderBy('subkategori')
            ->get()
            ->groupBy('jenis_dokumen')
            ->map(function ($items) {
                return $items->pluck('subkategori');
            });

        return view('surat-menyurat.index', compact('grouped', 'subkategorisByJenis'));
    }

    public function create()
    {
        $jenisDokumen = Surat::select('jenis_dokumen')->distinct()->orderBy('jenis_dokumen')->pluck('jenis_dokumen');
        return view('surat-menyurat.create', compact('jenisDokumen'));
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

    public function store(Request $request)
    {
        $request->validate([
            'jenis_dokumen' => 'required|string|max:255',
            'kode' => 'required|string|max:255',
            'subkategori' => 'nullable|string|max:255',
        ]);

        $number = $this->generateNomorSurat($request->kode);

        Surat::create([
            'jenis_dokumen' => $request->jenis_dokumen,
            'kode' => $request->kode,
            'subkategori' => $request->subkategori,
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => $number['noUrut'],
            'bulan' => $number['bulan'],
            'tahun' => $number['tahun'],
            'nomor_surat' => $number['nomorSurat'],
        ]);

        return redirect()->route('admin.surat-menyurat')->with('success', 'Nomor surat berhasil dibuat.');
    }

    public function storeSub(Request $request)
    {
        $request->validate([
            'jenis_dokumen' => 'required|string|max:255',
            'kode' => 'required|string|max:255',
            'subkategori' => 'required|string|max:255',
        ]);

        $exists = Surat::where('jenis_dokumen', $request->jenis_dokumen)
            ->where('subkategori', $request->subkategori)
            ->exists();

        if ($exists) {
            return redirect()->route('admin.surat-menyurat')
                ->with('error', 'Subkategori "' . $request->subkategori . '" sudah ada untuk jenis dokumen ini.');
        }

        $number = $this->generateNomorSurat($request->kode);

        Surat::create([
            'jenis_dokumen' => $request->jenis_dokumen,
            'kode' => $request->kode,
            'subkategori' => $request->subkategori,
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => $number['noUrut'],
            'bulan' => $number['bulan'],
            'tahun' => $number['tahun'],
            'nomor_surat' => $number['nomorSurat'],
        ]);

        return redirect()->route('admin.surat-menyurat')
            ->with('success', 'Subkategori "' . $request->subkategori . '" berhasil ditambahkan.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:surat,id',
        ]);

        $count = Surat::whereIn('id', $request->ids)->delete();

        return redirect()->route('admin.surat-menyurat')
            ->with('success', $count . ' nomor surat berhasil dihapus.');
    }

    public function arsip(Request $request)
    {
        $query = Invoice::with(['surat', 'signatures', 'booking.bookingCars.car', 'serviceLog.car']);

        if ($request->filled('jenis_dokumen')) {
            $query->whereHas('surat', function ($q) use ($request) {
                $q->where('jenis_dokumen', $request->jenis_dokumen);
            });
        }

        if ($request->filled('subkategori')) {
            $query->whereHas('surat', function ($q) use ($request) {
                $q->where('subkategori', $request->subkategori);
            });
        }

        if ($request->filled('bulan')) {
            $query->whereRaw('DATE_FORMAT(issued_at, "%Y-%m") = ?', [$request->bulan]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_seri', 'like', "%{$search}%")
                  ->orWhere('kode_mobil', 'like', "%{$search}%")
                  ->orWhereHas('surat', function ($sq) use ($search) {
                      $sq->where('nomor_surat', 'like', "%{$search}%")
                         ->orWhere('jenis_dokumen', 'like', "%{$search}%")
                         ->orWhere('subkategori', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('nomor_pesanan', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderByDesc('issued_at')->orderByDesc('id')->paginate(15)->withQueryString();

        $jenisDokumenList = Surat::select('jenis_dokumen')->distinct()->orderBy('jenis_dokumen')->pluck('jenis_dokumen');

        $subkategorisByJenis = Surat::whereNotNull('subkategori')
            ->select('jenis_dokumen', 'subkategori')
            ->distinct()
            ->orderBy('jenis_dokumen')
            ->orderBy('subkategori')
            ->get()
            ->groupBy('jenis_dokumen')
            ->map(function ($items) {
                return $items->pluck('subkategori');
            });

        return view('surat-menyurat.arsip', compact('invoices', 'jenisDokumenList', 'subkategorisByJenis'));
    }

    public function balikNama($carId)
    {
        $car = Car::findOrFail($carId);

        $surat = Surat::where('kode', 'BN')->latest()->first();
        if (!$surat) {
            $number = $this->generateNomorSurat('BN');
            $surat = Surat::create([
                'jenis_dokumen' => 'Balik Nama',
                'kode' => 'BN',
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'no_urut' => $number['noUrut'],
                'bulan' => $number['bulan'],
                'tahun' => $number['tahun'],
                'nomor_surat' => $number['nomorSurat'],
            ]);
        }

        return view('surat-menyurat.balik-nama', compact('surat', 'car'));
    }

    public function serahTerima($bookingId)
    {
        $booking = Booking::with(['bookingCars.car'])->findOrFail($bookingId);

        $surat = Surat::where('kode', 'ST')->latest()->first();
        if (!$surat) {
            $number = $this->generateNomorSurat('ST');
            $surat = Surat::create([
                'jenis_dokumen' => 'Serah Terima',
                'kode' => 'ST',
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'no_urut' => $number['noUrut'],
                'bulan' => $number['bulan'],
                'tahun' => $number['tahun'],
                'nomor_surat' => $number['nomorSurat'],
            ]);
        }

        return view('bookings.show-serah-terima', compact('booking', 'surat'));
    }
}
