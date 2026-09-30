<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Laporan keuangan: arus kas, laba rugi, pengeluaran per mobil, dan
 * pengeluaran kantor.
 *
 * Logika query identik dengan versi Blade -- hanya bentuk return-nya yang
 * berubah dari view ke JSON.
 */
class KeuanganController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        [$period, $month, $expenseFilter] = $this->resolveFilters($request);

        $report = $this->buildLaporanReport($period, $month, $expenseFilter);
        $report['cars'] = Car::all();

        return response()->json($report);
    }

    /**
     * Endpoint yang dipanggil axios saat user ganti filter periode/bulan.
     * Sengaja dipisah dari index() supaya tidak perlu reload halaman.
     */
    public function data(Request $request): JsonResponse
    {
        [$period, $month, $expenseFilter] = $this->resolveFilters($request);

        return response()->json($this->buildLaporanReport($period, $month, $expenseFilter));
    }

    public function perMobil(Request $request): JsonResponse
    {
        return $this->perMobilData($request);
    }

    public function perMobilData(Request $request): JsonResponse
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        // Guard: month harus format Y-m, kalau tidak createFromFormat()
        // akan melempar exception dan menghasilkan 500.
        $month = $this->validMonth($month);

        return response()->json(array_merge(
            $this->buildPerMobilReport($month),
            ['currentMonth' => now()->format('Y-m')]
        ));
    }

    public function pengeluaranKantor(Request $request): JsonResponse
    {
        return $this->pengeluaranKantorData($request);
    }

    public function pengeluaranKantorData(Request $request): JsonResponse
    {
        $month = $this->validMonth((string) $request->query('month', now()->format('Y-m')));

        return response()->json(array_merge(
            $this->buildLaporanKantorPerBulan($month),
            ['currentMonth' => now()->format('Y-m')]
        ));
    }

    public function createPengeluaranKantor(): JsonResponse
    {
        return response()->json([
            'currentMonth' => now()->format('Y-m'),
        ]);
    }

    /**
     * Simpan pengeluaran kantor. Menerima dua bentuk input:
     *   - Single : {tanggal, jumlah, deskripsi}
     *   - Bulk   : {tanggal: [], jumlah: [], deskripsi: []}
     */
    public function storePengeluaranKantor(Request $request): JsonResponse
    {
        $isBulk = $request->has('jumlah') && is_array($request->input('jumlah'));

        if ($isBulk) {
            $request->validate([
                'tanggal.*' => ['required', 'date'],
                'jumlah.*' => ['required', 'numeric', 'min:0.01'],
                'deskripsi.*' => ['nullable', 'string', 'max:255'],
            ]);

            $count = count($request->input('jumlah'));
            $created = 0;

            try {
                DB::transaction(function () use ($request, $count, &$created) {
                    for ($i = 0; $i < $count; $i++) {
                        $tanggal = $request->input('tanggal')[$i] ?? null;
                        $jumlah = $request->input('jumlah')[$i] ?? null;
                        $deskripsi = $request->input('deskripsi')[$i] ?? null;

                        if (! $tanggal || ! $jumlah) {
                            continue;
                        }

                        Transaction::create([
                            'tipe' => 'Pengeluaran',
                            'kategori' => 'kantor',
                            'jumlah' => $jumlah,
                            'tanggal' => $tanggal,
                            'deskripsi' => $deskripsi,
                            'user_id' => request()->user()->id,
                        ]);

                        $created++;
                    }
                });
            } catch (Throwable $e) {
                report($e);

                throw new ValidationException([
                    'jumlah' => 'Gagal menyimpan pengeluaran: '.$e->getMessage(),
                ]);
            }

            return response()->json([
                'message' => $created.' pengeluaran kantor berhasil ditambahkan.',
                'created' => $created,
            ], 201);
        }

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ]);

        $transaction = Transaction::create([
            'tipe' => 'Pengeluaran',
            'kategori' => 'kantor',
            'jumlah' => $data['jumlah'],
            'tanggal' => $data['tanggal'],
            'deskripsi' => $data['deskripsi'],
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Pengeluaran kantor berhasil ditambahkan.',
            'pengeluaran' => $transaction,
        ], 201);
    }

    public function editPengeluaranKantor(Transaction $transaction): JsonResponse
    {
        $this->abortIfNotKantorExpense($transaction);

        return response()->json(['pengeluaran' => $transaction]);
    }

    public function updatePengeluaranKantor(Request $request, Transaction $transaction): JsonResponse
    {
        $this->abortIfNotKantorExpense($transaction);

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ]);

        $transaction->update($data);

        return response()->json([
            'message' => 'Pengeluaran kantor berhasil diperbarui.',
            'pengeluaran' => $transaction->fresh(),
        ]);
    }

    public function destroyPengeluaranKantor(Transaction $transaction): JsonResponse
    {
        $this->abortIfNotKantorExpense($transaction);

        $transaction->delete();

        return response()->json(['message' => 'Pengeluaran kantor berhasil dihapus.']);
    }

    // ── Helper ─────────────────────────────────────────────────────────

    /**
     * Pastikan record benar-benar pengeluaran kantor. Tanpa ini, user yang
     * menebak ID transaksi bisa mengedit atau menghapus income lewat route
     * "pengeluaran kantor".
     */
    private function abortIfNotKantorExpense(Transaction $transaction): void
    {
        abort_unless(
            $transaction->tipe === 'Pengeluaran' && $transaction->kategori === 'kantor',
            404,
            'Data pengeluaran kantor tidak ditemukan.'
        );
    }

    /**
     * Validasi format bulan (Y-m). Nilai dari query string tidak boleh
     * dipercaya, dan Carbon::createFromFormat() akan meledak kalau formatnya
     * salah -- jadi gagal cepat dengan 422 lebih baik daripada 500.
     */
    private function validMonth(string $month): string
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new ValidationException([
                'month' => 'Format bulan harus YYYY-MM.',
            ]);
        }

        return $month;
    }

    private function resolveFilters(Request $request): array
    {
        return [
            (string) $request->query('period', '6m'),
            $request->query('month') ? $this->validMonth((string) $request->query('month')) : null,
            (string) $request->query('expense_filter', 'all'),
        ];
    }

    private function buildLaporanReport(string $period, ?string $month, string $expenseFilter): array
    {
        $applyExpenseFilter = function ($query) use ($expenseFilter) {
            if ($expenseFilter === 'kantor') {
                $query->where(fn ($q) => $q->where('tipe', '!=', 'Pengeluaran')->orWhere('kategori', 'kantor'));
            } elseif ($expenseFilter === 'non-kantor') {
                $query->where(fn ($q) => $q->where('tipe', '!=', 'Pengeluaran')->orWhere('kategori', '!=', 'kantor'));
            }

            return $query;
        };

        $base = Transaction::query();

        if ($month) {
            $base->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$month]);
        } elseif ($period !== 'all') {
            $base->where('tanggal', '>=', match ($period) {
                '1w' => now()->subWeek(),
                '1m' => now()->subMonth(),
                default => now()->subMonths(6),
            });
        }

        $applyExpenseFilter($base);

        $totalPemasukan = (clone $base)->where('tipe', 'Pemasukan')->sum('jumlah');
        $totalPengeluaran = (clone $base)->where('tipe', 'Pengeluaran')->sum('jumlah');
        $labaRugi = $totalPemasukan - $totalPengeluaran;

        $pengeluaranKantor = (clone $base)->where('tipe', 'Pengeluaran')->where('kategori', 'kantor')->sum('jumlah');
        $pengeluaranNonKantor = (clone $base)->where('tipe', 'Pengeluaran')->where('kategori', '!=', 'kantor')->sum('jumlah');

        $bulanIni = now()->format('Y-m');
        $bulanLalu = now()->subMonth()->format('Y-m');

        $growthBase = fn () => Transaction::where('tipe', 'Pemasukan');

        $pemasukanBulanIni = $growthBase()->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$bulanIni])->sum('jumlah');
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

        $profitMargin = $totalPemasukan > 0 ? round($labaRugi / $totalPemasukan * 100, 1) : 0;

        $burnRateQuery = Transaction::where('tipe', 'Pengeluaran')->where('tanggal', '>=', now()->subMonth());
        $applyExpenseFilter($burnRateQuery);
        $burnRate = $burnRateQuery->sum('jumlah');

        $cashRunway = $burnRate > 0 ? round($totalPemasukan / $burnRate * 30, 0) : 0;
        $labaBersih = $totalPemasukan - $pengeluaranKantor;

        $periodLabel = match (true) {
            $month !== null => Carbon::createFromFormat('!Y-m', $month)->translatedFormat('F Y'),
            $period === '1w' => '1 Minggu Terakhir',
            $period === '1m' => '1 Bulan Terakhir',
            $period === 'all' => 'Semua Waktu',
            default => '6 Bulan Terakhir',
        };

        $expenseFilterLabel = match ($expenseFilter) {
            'kantor' => 'Kantor',
            'non-kantor' => 'Non-Kantor',
            default => 'Semua Pengeluaran',
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

    private function buildPerMobilReport(string $month): array
    {
        $currentMonth = now()->format('Y-m');

        $prevMonth = Carbon::createFromFormat('!Y-m', $month)->subMonth()->format('Y-m');
        $nextMonth = Carbon::createFromFormat('!Y-m', $month)->addMonth()->format('Y-m');
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
            if (! $car) {
                continue;
            }

            $carTx = $transactions->get($carId, collect());
            $pemasukan = $carTx->firstWhere('tipe', 'Pemasukan')?->total ?? 0;
            $pengeluaran = $carTx->firstWhere('tipe', 'Pengeluaran')?->total ?? 0;

            $report[] = [
                'car_id' => $car->id,
                'plat_nomor' => $car->plat_nomor,
                'nama_mobil' => $car->merk.' '.$car->model,
                'pemasukan' => (float) $pemasukan,
                'pengeluaran' => (float) $pengeluaran,
                'laba' => (float) $pemasukan - $pengeluaran,
                /*
                 * Path frontend, bukan URL backend. Dulu ini route()
                 * yang menghasilkan URL server Blade; di SPA, router
                 * Vue yang harus menangani navigasi, jadi cukup kirim path.
                 */
                'detail_path' => "/armada/{$car->id}",
            ];
        }

        return [
            'report' => $report,
            'month' => $month,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'canGoNext' => $canGoNext,
            'monthLabel' => Carbon::createFromFormat('!Y-m', $month)->translatedFormat('F Y'),
        ];
    }

    private function buildLaporanKantorPerBulan(string $month): array
    {
        $currentMonth = now()->format('Y-m');

        $prevMonth = Carbon::createFromFormat('!Y-m', $month)->subMonth()->format('Y-m');
        $nextMonth = Carbon::createFromFormat('!Y-m', $month)->addMonth()->format('Y-m');
        $canGoNext = $month < $currentMonth;

        $pengeluaran = Transaction::where('tipe', 'Pengeluaran')
            ->where('kategori', 'kantor')
            ->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$month])
            ->orderByDesc('tanggal')
            ->get();

        return [
            'pengeluaran' => $pengeluaran,
            'total' => $pengeluaran->sum('jumlah'),
            'month' => $month,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'canGoNext' => $canGoNext,
            'monthLabel' => Carbon::createFromFormat('!Y-m', $month)->translatedFormat('F Y'),
        ];
    }
}
