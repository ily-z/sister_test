<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Car;
use Illuminate\Support\Facades\DB;

class KeuanganController extends Controller
{
    public function index(Request $request)
    {
        [$period, $month, $expenseFilter] = $this->resolveFilters($request);
        $report = $this->buildLaporanReport($period, $month, $expenseFilter);

        $report['cars'] = Car::all();

        return view('keuangan.index', $report);
    }

    public function data(Request $request)
    {
        [$period, $month, $expenseFilter] = $this->resolveFilters($request);

        return response()->json($this->buildLaporanReport($period, $month, $expenseFilter));
    }

    private function resolveFilters(Request $request): array
    {
        $period = $request->query('period', '6m');
        $month = $request->query('month');
        $expenseFilter = $request->query('expense_filter', 'all');

        return [$period, $month, $expenseFilter];
    }

    private function buildLaporanReport(string $period, ?string $month, string $expenseFilter): array
    {
        $applyExpenseFilter = function ($query) use ($expenseFilter) {
            if ($expenseFilter === 'kantor') {
                $query->where(function ($q) {
                    $q->where('tipe', '!=', 'Pengeluaran')
                      ->orWhere('kategori', 'kantor');
                });
            } elseif ($expenseFilter === 'non-kantor') {
                $query->where(function ($q) {
                    $q->where('tipe', '!=', 'Pengeluaran')
                      ->orWhere('kategori', '!=', 'kantor');
                });
            }

            return $query;
        };

        $base = Transaction::query();
        if ($month) {
            $base->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$month]);
        } elseif ($period !== 'all') {
            $base->where('tanggal', '>=', match ($period) {
                '1w'    => now()->subWeek(),
                '1m'    => now()->subMonth(),
                default => now()->subMonths(6),
            });
        }

        $applyExpenseFilter($base);

        $totalPemasukan   = (clone $base)->where('tipe', 'Pemasukan')->sum('jumlah');
        $totalPengeluaran = (clone $base)->where('tipe', 'Pengeluaran')->sum('jumlah');
        $labaRugi         = $totalPemasukan - $totalPengeluaran;

        $pengeluaranKantor    = (clone $base)->where('tipe', 'Pengeluaran')->where('kategori', 'kantor')->sum('jumlah');
        $pengeluaranNonKantor = (clone $base)->where('tipe', 'Pengeluaran')->where('kategori', '!=', 'kantor')->sum('jumlah');

        $bulanIni   = now()->format('Y-m');
        $bulanLalu  = now()->subMonth()->format('Y-m');

        $growthBase = fn() => Transaction::where('tipe', 'Pemasukan');

        $pemasukanBulanIni  = $growthBase()->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$bulanIni])->sum('jumlah');
        $pemasukanBulanLalu = $growthBase()->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$bulanLalu])->sum('jumlah');
        $growthRate = $pemasukanBulanLalu > 0
            ? round(($pemasukanBulanIni - $pemasukanBulanLalu) / $pemasukanBulanLalu * 100, 1)
            : 0;

        $dateGroup = 'DATE_FORMAT(tanggal, "%Y-%m") as bulan';
        if ($month) {
            $dateGroup = 'DATE_FORMAT(tanggal, "%Y-%m-%d") as bulan';
        } else {
            $uniqueMonths = (clone $base)
                ->selectRaw('DATE_FORMAT(tanggal, "%Y-%m") as month')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('month')
                ->toArray();

            if (count($uniqueMonths) <= 1) {
                $dateGroup = 'DATE_FORMAT(tanggal, "%Y-%m-%d") as bulan';
            }
        }

        $cashFlow = (clone $base)
            ->selectRaw("$dateGroup, tipe, SUM(jumlah) as total")
            ->groupBy('bulan', 'tipe')
            ->orderBy('bulan')
            ->get()
            ->groupBy('bulan');

        $expenseBreakdown = (clone $base)
            ->where('tipe', 'Pengeluaran')
            ->selectRaw('kategori, SUM(jumlah) as total')
            ->groupBy('kategori')
            ->get();

        $pengeluaranKantorPerMonth = (clone $base)
            ->where('tipe', 'Pengeluaran')
            ->where('kategori', 'kantor')
            ->selectRaw("$dateGroup, SUM(jumlah) as total")
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        $profitMargin = $totalPemasukan > 0
            ? round($labaRugi / $totalPemasukan * 100, 1)
            : 0;

        $burnRateQuery = Transaction::where('tipe', 'Pengeluaran')
            ->where('tanggal', '>=', now()->subMonth());
        $applyExpenseFilter($burnRateQuery);
        $burnRate = $burnRateQuery->sum('jumlah');

        $cashRunway = $burnRate > 0 ? round($totalPemasukan / $burnRate * 30, 0) : 0;
        $labaBersih = $totalPemasukan - $pengeluaranKantor;

        $periodLabel = match (true) {
            $month !== null => \Carbon\Carbon::createFromFormat('!Y-m', $month)->translatedFormat('F Y'),
            $period === '1w'  => '1 Minggu Terakhir',
            $period === '1m'  => '1 Bulan Terakhir',
            $period === 'all' => 'Semua Waktu',
            default           => '6 Bulan Terakhir',
        };

        $expenseFilterLabel = match ($expenseFilter) {
            'kantor'      => 'Kantor',
            'non-kantor'  => 'Non-Kantor',
            default       => 'Semua Pengeluaran',
        };

        return compact(
            'totalPemasukan',
            'totalPengeluaran',
            'labaRugi',
            'growthRate',
            'cashFlow',
            'expenseBreakdown',
            'profitMargin',
            'burnRate',
            'cashRunway',
            'periodLabel',
            'expenseFilterLabel',
            'pengeluaranKantor',
            'pengeluaranNonKantor',
            'pengeluaranKantorPerMonth',
            'labaBersih',
            'period',
            'month',
            'expenseFilter'
        );
    }

    public function perMobil(Request $request)
    {
        $month       = $request->query('month', now()->format('Y-m'));
        $currentMonth = now()->format('Y-m');

        $data = $this->buildPerMobilReport($month);

        return view('keuangan.per-mobil', array_merge($data, [
            'currentMonth' => $currentMonth,
        ]));
    }

    public function perMobilData(Request $request)
    {
        $month       = $request->query('month', now()->format('Y-m'));
        $currentMonth = now()->format('Y-m');

        $data = $this->buildPerMobilReport($month);

        return response()->json(array_merge($data, [
            'currentMonth' => $currentMonth,
        ]));
    }

    private function buildPerMobilReport(string $month): array
    {
        $currentMonth = now()->format('Y-m');

        $prevMonth = \Carbon\Carbon::createFromFormat('!Y-m', $month)->subMonth()->format('Y-m');
        $nextMonth = \Carbon\Carbon::createFromFormat('!Y-m', $month)->addMonth()->format('Y-m');
        $canGoNext = $month < $currentMonth;

        $transactions = Transaction::selectRaw('car_id, tipe, SUM(jumlah) as total')
            ->whereNotNull('car_id')
            ->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$month])
            ->groupBy('car_id', 'tipe')
            ->get()
            ->groupBy('car_id');

        $carIds = $transactions->keys();

        $cars = Car::whereIn('id', $carIds)->get()->keyBy('id');

        $report = [];
        foreach ($carIds as $carId) {
            $car = $cars->get($carId);
            if (!$car) continue;

            $carTx      = $transactions->get($carId, collect());
            $pemasukan  = $carTx->firstWhere('tipe', 'Pemasukan')?->total ?? 0;
            $pengeluaran = $carTx->firstWhere('tipe', 'Pengeluaran')?->total ?? 0;
            $report[] = [
                'car_id'      => $car->id,
                'plat_nomor'  => $car->plat_nomor,
                'nama_mobil'  => $car->merk . ' ' . $car->model,
                'pemasukan'   => (float) $pemasukan,
                'pengeluaran' => (float) $pengeluaran,
                'laba'        => (float) $pemasukan - $pengeluaran,
                'detail_url'  => route('admin.armada.show', $car->id),
            ];
        }

        $monthLabel = \Carbon\Carbon::createFromFormat('!Y-m', $month)->translatedFormat('F Y');

        return compact('report', 'month', 'prevMonth', 'nextMonth', 'canGoNext', 'monthLabel');
    }

    public function pengeluaranKantor(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $currentMonth = now()->format('Y-m');

        $data = $this->buildLaporanKantorPerBulan($month);

        return view('keuangan.pengeluaran-kantor', array_merge($data, [
            'currentMonth' => $currentMonth,
        ]));
    }

    public function pengeluaranKantorData(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $currentMonth = now()->format('Y-m');

        $data = $this->buildLaporanKantorPerBulan($month);

        return response()->json(array_merge($data, [
            'currentMonth' => $currentMonth,
        ]));
    }

    private function buildLaporanKantorPerBulan(string $month): array
    {
        $currentMonth = now()->format('Y-m');

        $prevMonth = \Carbon\Carbon::createFromFormat('!Y-m', $month)->subMonth()->format('Y-m');
        $nextMonth = \Carbon\Carbon::createFromFormat('!Y-m', $month)->addMonth()->format('Y-m');
        $canGoNext = $month < $currentMonth;

        $pengeluaran = Transaction::where('tipe', 'Pengeluaran')
            ->where('kategori', 'kantor')
            ->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$month])
            ->orderByDesc('tanggal')
            ->get();

        $total = $pengeluaran->sum('jumlah');

        $monthLabel = \Carbon\Carbon::createFromFormat('!Y-m', $month)->translatedFormat('F Y');

        return compact('pengeluaran', 'total', 'month', 'prevMonth', 'nextMonth', 'canGoNext', 'monthLabel');
    }

    public function createPengeluaranKantor()
    {
        return view('keuangan.create');
    }

    public function storePengeluaranKantor(Request $request)
    {
        // Support bulk insert: accept arrays tanggal[], jumlah[], deskripsi[]
        if ($request->has('jumlah') && is_array($request->input('jumlah'))) {
            $validated = $request->validate([
                'tanggal.*' => 'required|date',
                'jumlah.*'  => 'required|numeric|min:0.01',
                'deskripsi.*' => 'nullable|string|max:255',
            ]);

            $count   = count($request->input('jumlah'));
            $created = 0;

            DB::beginTransaction();
            try {
                for ($i = 0; $i < $count; $i++) {
                    $tanggal  = $request->input('tanggal')[$i] ?? null;
                    $jumlah   = $request->input('jumlah')[$i] ?? null;
                    $deskripsi = $request->input('deskripsi')[$i] ?? null;
                    if (!$tanggal || !$jumlah) continue;

                    Transaction::create([
                        'tipe'     => 'Pengeluaran',
                        'kategori' => 'kantor',
                        'jumlah'   => $jumlah,
                        'tanggal'  => $tanggal,
                        'deskripsi' => $deskripsi,
                        'user_id'  => auth()->id(),
                    ]);

                    $created++;
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->withErrors('Gagal menyimpan pengeluaran: ' . $e->getMessage());
            }

            return redirect()
                ->route('admin.keuangan.pengeluaran-kantor')
                ->with('success', $created . ' pengeluaran kantor berhasil ditambahkan.');
        }

        // Single insert (backward compatible)
        $data = $request->validate([
            'tanggal'   => 'required|date',
            'jumlah'    => 'required|numeric|min:0.01',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        Transaction::create([
            'tipe'      => 'Pengeluaran',
            'kategori'  => 'kantor',
            'jumlah'    => $data['jumlah'],
            'tanggal'   => $data['tanggal'],
            'deskripsi' => $data['deskripsi'],
            'user_id'   => auth()->id(),
        ]);

        return redirect()
            ->route('admin.keuangan.pengeluaran-kantor')
            ->with('success', 'Pengeluaran kantor berhasil ditambahkan.');
    }

    public function editPengeluaranKantor($id)
    {
        $pengeluaran = Transaction::where('tipe', 'Pengeluaran')
            ->where('kategori', 'kantor')
            ->findOrFail($id);

        return view('keuangan.edit', compact('pengeluaran'));
    }

    public function updatePengeluaranKantor(Request $request, $id)
    {
        $pengeluaran = Transaction::where('tipe', 'Pengeluaran')
            ->where('kategori', 'kantor')
            ->findOrFail($id);

        $data = $request->validate([
            'tanggal'   => 'required|date',
            'jumlah'    => 'required|numeric|min:0.01',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $pengeluaran->update([
            'tanggal'   => $data['tanggal'],
            'jumlah'    => $data['jumlah'],
            'deskripsi' => $data['deskripsi'],
        ]);

        return redirect()->route('admin.keuangan.pengeluaran-kantor')
            ->with('success', 'Pengeluaran kantor berhasil diperbarui.');
    }

    public function destroyPengeluaranKantor($id)
    {
        $pengeluaran = Transaction::where('tipe', 'Pengeluaran')
            ->where('kategori', 'kantor')
            ->findOrFail($id);

        $pengeluaran->delete();

        return redirect()->route('admin.keuangan.pengeluaran-kantor')
            ->with('success', 'Pengeluaran kantor berhasil dihapus.');
    }
}