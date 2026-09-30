@extends('layouts.app')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 md:px-8">
    <div class="flex flex-col md:flex-row md:justify-between md:items-end mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Laporan Keuangan Permobil</h1>
            <p class="text-sm text-slate-500 mt-1">Pemasukan, pengeluaran, dan laba per kendaraan.</p>
        </div>
        <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-xl px-4 py-2 shadow-sm">
            @php
                $sixMonthsAgo = \Carbon\Carbon::now()->subMonths(6)->format('Y-m');
            @endphp
            <button type="button" id="btn-prev-month"
                data-month="{{ $prevMonth }}"
                class="flex items-center justify-center w-9 h-9 rounded-lg transition-colors
                {{ $month <= $sixMonthsAgo ? 'text-slate-300 cursor-not-allowed' : 'text-slate-700 hover:bg-slate-100' }}"
                {{ $month <= $sixMonthsAgo ? 'disabled' : '' }}>
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </button>

            <span id="month-label" class="text-sm font-semibold text-slate-900 min-w-[160px] text-center">
                {{ \Carbon\Carbon::createFromFormat('!Y-m', $month)->translatedFormat('F Y') }}
            </span>

            <button type="button" id="btn-next-month"
                data-month="{{ $nextMonth }}"
                class="flex items-center justify-center w-9 h-9 rounded-lg transition-colors
                {{ !$canGoNext ? 'text-slate-300 cursor-not-allowed' : 'text-slate-700 hover:bg-slate-100' }}"
                {{ !$canGoNext ? 'disabled' : '' }}>
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </button>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">No. Polisi</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Mobil</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Pemasukan</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Pengeluaran</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Laba</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="report-body" class="divide-y divide-slate-100">
                    @forelse ($report as $item)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-medium text-slate-900 whitespace-nowrap">
                            {{ $item['plat_nomor'] }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-700">
                            {{ $item['nama_mobil'] }}
                        </td>
                        <td class="px-6 py-4 text-sm text-green-600 font-semibold text-right whitespace-nowrap">
                            Rp {{ number_format($item['pemasukan'], 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-red-600 font-semibold text-right whitespace-nowrap">
                            Rp {{ number_format($item['pengeluaran'], 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-right whitespace-nowrap {{ $item['laba'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            Rp {{ number_format(abs($item['laba']), 0, ',', '.') }}
                            @if ($item['laba'] < 0)
                                <span class="text-xs text-red-500">(Rugi)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ $item['detail_url'] }}"
                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-brand-600 bg-brand-50 rounded-lg hover:bg-brand-100 transition-colors">
                                <span class="material-symbols-outlined text-[16px] mr-1">visibility</span>
                                Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr id="empty-row">
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            Tidak ada transaksi pada bulan ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="loading-indicator" class="hidden mt-4 flex items-center justify-center gap-2 text-sm text-slate-500">
        <span class="material-symbols-outlined text-[18px] animate-spin">sync</span>
        Memuat data...
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnPrev   = document.getElementById('btn-prev-month');
        const btnNext   = document.getElementById('btn-next-month');
        const monthLabel = document.getElementById('month-label');
        const reportBody = document.getElementById('report-body');
        const loading   = document.getElementById('loading-indicator');
        const dataUrl   = '{{ route('admin.keuangan.per-mobil.data') }}';
        const detailUrlTemplate = '{{ route('admin.armada.show', '__ID__') }}';
        const sixMonthsAgo = '{{ \Carbon\Carbon::now()->subMonths(6)->format('Y-m') }}';

        function formatRp(amount) {
            return 'Rp ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function renderTable(data) {
            if (data.report.length === 0) {
                reportBody.innerHTML =
                    '<tr id="empty-row"><td colspan="6" class="px-6 py-12 text-center text-slate-500">Tidak ada transaksi pada bulan ini.</td></tr>';
                return;
            }

            let html = '';
            data.report.forEach(function (item) {
                const labaClass = item.laba >= 0 ? 'text-green-600' : 'text-red-600';
                const rugiTag = item.laba < 0 ? '<span class="text-xs text-red-500">(Rugi)</span>' : '';
                const detailUrl = detailUrlTemplate.replace('__ID__', item.car_id);

                html += '<tr class="hover:bg-slate-50 transition-colors">' +
                    '<td class="px-6 py-4 text-sm font-medium text-slate-900 whitespace-nowrap">' + escapeHtml(item.plat_nomor) + '</td>' +
                    '<td class="px-6 py-4 text-sm text-slate-700">' + escapeHtml(item.nama_mobil) + '</td>' +
                    '<td class="px-6 py-4 text-sm text-green-600 font-semibold text-right whitespace-nowrap">' + formatRp(item.pemasukan) + '</td>' +
                    '<td class="px-6 py-4 text-sm text-red-600 font-semibold text-right whitespace-nowrap">' + formatRp(item.pengeluaran) + '</td>' +
                    '<td class="px-6 py-4 text-sm font-semibold text-right whitespace-nowrap ' + labaClass + '">' + formatRp(Math.abs(item.laba)) + ' ' + rugiTag + '</td>' +
                    '<td class="px-6 py-4 text-center">' +
                        '<a href="' + detailUrl + '" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-brand-600 bg-brand-50 rounded-lg hover:bg-brand-100 transition-colors">' +
                            '<span class="material-symbols-outlined text-[16px] mr-1">visibility</span> Details' +
                        '</a>' +
                    '</td>' +
                    '</tr>';
            });
            reportBody.innerHTML = html;
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function fetchMonth(month) {
            loading.classList.remove('hidden');
            reportBody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Memuat...</td></tr>';

            axios.get(dataUrl, { params: { month: month } })
                .then(function (res) {
                    const data = res.data;
                    monthLabel.textContent = data.monthLabel;

                    // Update prev button
                    const canGoPrev = month > sixMonthsAgo;
                    btnPrev.dataset.month = data.prevMonth;
                    btnPrev.disabled = !canGoPrev;
                    btnPrev.className = 'flex items-center justify-center w-9 h-9 rounded-lg transition-colors ' +
                        (canGoPrev ? 'text-slate-700 hover:bg-slate-100' : 'text-slate-300 cursor-not-allowed');

                    // Update next button
                    const canGoNext = data.canGoNext;
                    btnNext.dataset.month = data.nextMonth;
                    btnNext.disabled = !canGoNext;
                    btnNext.className = 'flex items-center justify-center w-9 h-9 rounded-lg transition-colors ' +
                        (canGoNext ? 'text-slate-700 hover:bg-slate-100' : 'text-slate-300 cursor-not-allowed');

                    renderTable(data);
                })
                .catch(function () {
                    reportBody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-red-500">Gagal memuat data.</td></tr>';
                })
                .finally(function () {
                    loading.classList.add('hidden');
                });
        }

        btnPrev.addEventListener('click', function () {
            if (btnPrev.disabled) return;
            fetchMonth(btnPrev.dataset.month);
        });

        btnNext.addEventListener('click', function () {
            if (btnNext.disabled) return;
            fetchMonth(btnNext.dataset.month);
        });
    });
</script>
@endpush
