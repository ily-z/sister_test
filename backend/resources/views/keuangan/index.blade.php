@extends('layouts.app')

@section('content')
<div id="laporanKeuanganPage" class="relative max-w-[1400px] mx-auto px-4 md:px-8">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-3 gap-2">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Laporan Performa Bisnis</h1>
            <p class="text-xs text-slate-500">Ringkasan keuangan untuk pengambilan keputusan.</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-xs text-slate-600 shadow-sm">
                <span class="material-symbols-outlined text-[14px] mr-1.5 text-slate-400">calendar_today</span>
                <span id="periodLabelBadge" class="font-medium">{{ $periodLabel }}</span>
            </div>
            @if ($expenseFilter !== 'all')
                <div class="flex items-center bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 text-xs text-amber-700 shadow-sm">
                    <span class="material-symbols-outlined text-[14px] mr-1.5 text-amber-400">filter_alt</span>
                    <span id="expenseFilterBadge" class="font-medium">{{ $expenseFilterLabel }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm mb-4 space-y-2">
        {{-- Row 1: Periode + Bulan --}}
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Periode</span>
            <div class="flex bg-slate-100 rounded-lg p-0.5 gap-0.5">
                @foreach (['1w' => '1 Minggu', '1m' => '1 Bulan', '6m' => '6 Bulan', 'all' => 'Semua'] as $val => $label)
                    <button type="button" data-period="{{ $val }}" class="period-btn px-3 py-1 text-xs rounded-md font-medium transition-all {{ $period === $val && !$month ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <span class="hidden sm:inline text-[11px] text-slate-300">|</span>

            <div class="flex items-center gap-1.5">
                <label for="monthInput" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Bulan</label>
                <input id="monthInput" type="month" value="{{ $month ?? '' }}"
                    class="px-2 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400">
                <button id="btnLihatBulan" type="button"
                    class="px-2.5 py-1 text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all active:scale-95">Lihat</button>
                <button id="btnResetBulan" type="button" class="px-2.5 py-1 text-xs font-medium text-slate-500 bg-slate-100 rounded-lg hover:text-slate-800 hover:bg-slate-200 transition-all {{ $month ? '' : 'hidden' }}">Reset</button>
            </div>
        </div>

        {{-- Divider --}}
        <div class="border-t border-slate-100"></div>

        {{-- Row 2: Expense Filter --}}
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pengeluaran</span>
            <div class="flex bg-slate-100 rounded-lg p-0.5 gap-0.5">
                @foreach (['all' => 'Semua', 'kantor' => 'Kantor', 'non-kantor' => 'Non-Kantor'] as $val => $label)
                    <button type="button" data-expense="{{ $val }}" class="expense-btn px-3 py-1 text-xs rounded-md font-medium transition-all {{ $expenseFilter === $val ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <span class="text-[10px] text-slate-400">Filter jenis pengeluaran yang masuk ke semua metrik & grafik</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 mb-4">
        <div class="lg:col-span-2 bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-center">
            <div class="text-center mb-2">
                <h3 class="text-sm font-bold text-slate-900 tracking-tight">Komposisi Keuangan</h3>
                <p class="text-[11px] text-slate-500">Proporsi pemasukan, pengeluaran & laba</p>
            </div>
            <canvas id="financialPieChart" height="140" class="max-w-[180px] mx-auto"></canvas>
        </div>
        <div class="lg:col-span-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 h-full">
                {{-- Total Pemasukan --}}
                <div class="bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Pemasukan</p>
                            <p id="totalPemasukanValue" class="text-sm font-bold text-emerald-600 tracking-tight mt-0.5">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</p>
                        </div>
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[16px]">trending_up</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-1">Total pemasukan periode ini</p>
                </div>

                {{-- Total Pengeluaran --}}
                <div class="bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Pengeluaran</p>
                            <p id="totalPengeluaranValue" class="text-sm font-bold text-rose-600 tracking-tight mt-0.5">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</p>
                        </div>
                        <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[16px]">trending_down</span>
                        </div>
                    </div>
                    <p id="pengeluaranDesc" class="text-[10px] text-slate-500 mt-1 leading-4">
                        @if ($expenseFilter === 'all')
                            Mencakup pengeluaran kantor & non-kantor dalam periode ini.
                        @elseif ($expenseFilter === 'kantor')
                            Hanya pengeluaran <strong>kantor</strong> dalam periode ini.
                        @else
                            Hanya pengeluaran <strong>non-kantor</strong> dalam periode ini.
                        @endif
                    </p>
                    {{-- Breakdown mini --}}
                    <div class="mt-2 space-y-1">
                        @if ($expenseFilter === 'all' || $expenseFilter === 'kantor')
                            <div id="breakdownKantorRow" class="flex items-center justify-between text-[10px]">
                                <span class="flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block"></span>
                                    <span class="text-slate-500">Kantor</span>
                                </span>
                                <span id="pengeluaranKantorValue" class="font-semibold text-slate-700">Rp {{ number_format($pengeluaranKantor, 0, ',', '.') }}</span>
                            </div>
                        @else
                            <div id="breakdownKantorRow" class="hidden"></div>
                        @endif
                        @if ($expenseFilter === 'all' || $expenseFilter === 'non-kantor')
                            <div id="breakdownNonKantorRow" class="flex items-center justify-between text-[10px]">
                                <span class="flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-400 inline-block"></span>
                                    <span class="text-slate-500">Non-kantor</span>
                                </span>
                                <span id="pengeluaranNonKantorValue" class="font-semibold text-slate-700">Rp {{ number_format($pengeluaranNonKantor, 0, ',', '.') }}</span>
                            </div>
                        @else
                            <div id="breakdownNonKantorRow" class="hidden"></div>
                        @endif
                    </div>
                    <div class="mt-1.5 flex flex-wrap gap-1">
                        @if ($expenseFilter === 'all' || $expenseFilter === 'kantor')
                            <span id="pillKantor" class="bg-rose-50 text-rose-600 border border-rose-100 rounded-full px-1.5 py-0.5 text-[9px] font-medium">Kantor</span>
                        @else
                            <span id="pillKantor" class="hidden"></span>
                        @endif
                        @if ($expenseFilter === 'all' || $expenseFilter === 'non-kantor')
                            <span id="pillNonKantor" class="bg-orange-50 text-orange-600 border border-orange-100 rounded-full px-1.5 py-0.5 text-[9px] font-medium">Non-kantor</span>
                        @else
                            <span id="pillNonKantor" class="hidden"></span>
                        @endif
                    </div>
                </div>

                {{-- Laba / Rugi --}}
                <div class="bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Laba / Rugi</p>
                            <p id="labaRugiValue" class="text-sm font-bold {{ $labaRugi >= 0 ? 'text-emerald-600' : 'text-rose-600' }} tracking-tight mt-0.5">
                                Rp {{ number_format(abs($labaRugi), 0, ',', '.') }}
                            </p>
                        </div>
                        <div id="labaRugiIconWrap" class="w-7 h-7 rounded-lg {{ $labaRugi >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center shrink-0">
                            <span id="labaRugiIcon" class="material-symbols-outlined text-[16px]">account_balance</span>
                        </div>
                    </div>
                    <div class="flex items-center text-[10px] mt-1">
                        @if ($labaRugi < 0)
                            <span id="labaRugiBadge" class="bg-rose-100 text-rose-700 font-bold px-1.5 py-0.5 rounded mr-1">Rugi</span>
                        @else
                            <span id="labaRugiBadge" class="bg-emerald-100 text-emerald-700 font-bold px-1.5 py-0.5 rounded mr-1">Laba</span>
                        @endif
                        <span class="text-slate-500">Selisih pemasukan & pengeluaran</span>
                    </div>
                    @if ($expenseFilter !== 'all')
                        <p id="labaRugiFilterNote" class="text-[9px] text-amber-600 mt-1 bg-amber-50 rounded px-1.5 py-0.5">
                            Dihitung dari pengeluaran {{ $expenseFilterLabel }} saja
                        </p>
                    @else
                        <p id="labaRugiFilterNote" class="hidden"></p>
                    @endif
                </div>

                {{-- Growth Rate --}}
                <div class="bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Growth Rate</p>
                            <p id="growthRateValue" class="text-sm font-bold {{ $growthRate >= 0 ? 'text-emerald-600' : 'text-rose-600' }} tracking-tight mt-0.5">
                                {{ $growthRate >= 0 ? '+' : '' }}{{ $growthRate }}%
                            </p>
                        </div>
                        <div id="growthRateIconWrap" class="w-7 h-7 rounded-lg {{ $growthRate >= 0 ? 'bg-brand-50 text-brand-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center shrink-0">
                            <span id="growthRateIcon" class="material-symbols-outlined text-[16px]">{{ $growthRate >= 0 ? 'arrow_upward' : 'arrow_downward' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center text-[10px] mt-1">
                        <span id="growthRateBadge" class="{{ $growthRate >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} font-bold px-1.5 py-0.5 rounded mr-1">
                            {{ $growthRate >= 0 ? 'Naik' : 'Turun' }}
                        </span>
                        <span class="text-slate-500">Dibanding bulan lalu</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
        <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <div>
                    <h3 class="text-xs font-bold text-slate-900 tracking-tight">Cash Flow</h3>
                    <p class="text-[10px] text-slate-500"><span id="cashFlowPeriodLabel">{{ $periodLabel }}</span>
                        @if ($expenseFilter !== 'all')
                            &middot; <span id="cashFlowFilterLabel" class="text-amber-600 font-medium">{{ $expenseFilterLabel }}</span>
                        @else
                            <span id="cashFlowFilterLabel" class="hidden"></span>
                        @endif
                    </p>
                </div>
            </div>
            <canvas id="cashFlowChart" height="80"></canvas>
        </div>
        <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <div>
                    <h3 class="text-xs font-bold text-slate-900 tracking-tight">Pengeluaran Kantor per Bulan</h3>
                    <p class="text-[10px] text-slate-500">Biaya operasional kantor bulanan</p>
                </div>
            </div>
            <canvas id="kantorPerMonthChart" height="80"></canvas>
        </div>
    </div>



    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Profit Margin</p>
                <p id="profitMarginValue" class="text-lg font-bold text-slate-900 tracking-tight mt-0.5">{{ $profitMargin }}%</p>
                <p class="text-[10px] text-slate-500 mt-0.5">Persentase laba terhadap pemasukan</p>
                @if ($expenseFilter !== 'all')
                    <p id="profitMarginFilterLabel" class="text-[9px] text-amber-600 mt-0.5">Dihitung dari {{ $expenseFilterLabel }} saja</p>
                @else
                    <p id="profitMarginFilterLabel" class="hidden"></p>
                @endif
            </div>
            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px]">pie_chart</span>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Laba Bersih</p>
                <p id="labaBersihValue" class="text-lg font-bold {{ $labaBersih >= 0 ? 'text-emerald-600' : 'text-rose-600' }} tracking-tight mt-0.5">Rp {{ number_format($labaBersih, 0, ',', '.') }}</p>
                <p id="labaBersihNote" class="text-[10px] text-slate-500 mt-0.5">Pemasukan dikurangi pengeluaran kantor</p>
                @if ($labaBersih < 0)
                    <p id="labaBersihWarning" class="text-[9px] text-rose-600 mt-0.5 bg-rose-50 rounded px-1.5 py-0.5">Rugi bersih setelah biaya kantor</p>
                @else
                    <p id="labaBersihWarning" class="hidden"></p>
                @endif
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px]">account_balance</span>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Cash Runway</p>
                <p id="cashRunwayValue" class="text-lg font-bold text-slate-900 tracking-tight mt-0.5">{{ $cashRunway }} hari</p>
                <p class="text-[10px] text-slate-500 mt-0.5">Estimasi dana bertahan</p>
                @if ($expenseFilter !== 'all')
                    <p id="cashRunwayFilterLabel" class="text-[9px] text-amber-600 mt-0.5">Dihitung dari {{ $expenseFilterLabel }} saja</p>
                @else
                    <p id="cashRunwayFilterLabel" class="hidden"></p>
                @endif
            </div>
            <div class="w-9 h-9 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px]">timer</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.__laporan = {!! json_encode([
        'data' => [
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'labaRugi' => $labaRugi,
            'growthRate' => $growthRate,
            'cashFlow' => $cashFlow,
            'expenseBreakdown' => $expenseBreakdown,
            'profitMargin' => $profitMargin,
            'burnRate' => $burnRate,
            'cashRunway' => $cashRunway,
            'pengeluaranKantor' => $pengeluaranKantor,
            'pengeluaranNonKantor' => $pengeluaranNonKantor,
            'pengeluaranKantorPerMonth' => $pengeluaranKantorPerMonth,
            'labaBersih' => $labaBersih,
            'periodLabel' => $periodLabel,
            'expenseFilterLabel' => $expenseFilterLabel,
            'period' => $period,
            'month' => $month,
            'expenseFilter' => $expenseFilter,
        ],
        'urls' => [
            'base' => route('admin.keuangan'),
            'data' => route('admin.keuangan.data'),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
</script>
@vite('resources/js/pages/keuangan.js')
@endpush