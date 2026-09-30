@extends('layouts.app')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 md:px-8">
    <div class="flex flex-col md:flex-row md:justify-between md:items-end mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Pengeluaran Kantor</h1>
            <p class="text-sm text-slate-500 mt-1">Biaya operasional dan administrasi kantor per bulan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.keuangan.pengeluaran-kantor.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-md active:scale-95">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Pengeluaran
            </a>
            <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-xl px-4 py-2 shadow-sm">
            <button type="button" id="btn-prev-month"
                data-month="{{ $prevMonth }}"
                class="flex items-center justify-center w-9 h-9 rounded-lg transition-colors text-slate-700 hover:bg-slate-100">
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </button>

            <span id="month-label" class="text-sm font-semibold text-slate-900 min-w-[160px] text-center">
                {{ $monthLabel }}
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
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Deskripsi</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Jumlah</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="report-body" class="divide-y divide-slate-100">
                    @forelse ($pengeluaran as $item)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-sm text-slate-700 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-700">{{ $item->deskripsi }}</td>
                        <td class="px-6 py-4 text-sm text-red-600 font-semibold text-right whitespace-nowrap">
                            Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.keuangan.pengeluaran-kantor.edit', $item->id) }}"
                                    class="px-3 py-2 bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold hover:bg-slate-200">Edit</a>
                                <form action="{{ route('admin.keuangan.pengeluaran-kantor.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus pengeluaran ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-lg text-xs font-semibold hover:bg-red-700">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="empty-row">
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                            Tidak ada pengeluaran kantor pada bulan ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="kpi-row" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Bulan Ini</p>
                <p id="total-bulan" class="text-lg font-bold text-red-600 tracking-tight mt-0.5">Rp {{ number_format($total, 0, ',', '.') }}</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Jumlah Transaksi</p>
                <p id="jumlah-transaksi" class="text-lg font-bold text-slate-900 tracking-tight mt-0.5">{{ $pengeluaran->count() }} entri</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px]">fact_check</span>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Rata-rata per Transaksi</p>
                <p id="rata-rata" class="text-lg font-bold text-slate-900 tracking-tight mt-0.5">Rp {{ $pengeluaran->count() > 0 ? number_format($total / $pengeluaran->count(), 0, ',', '.') : 0 }}</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px]">calculate</span>
            </div>
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
        const totalEl   = document.getElementById('total-bulan');
        const countEl   = document.getElementById('jumlah-transaksi');
        const avgEl     = document.getElementById('rata-rata');
        const dataUrl   = '{{ route('admin.keuangan.pengeluaran-kantor.data') }}';
        const editUrlTemplate = '{{ route('admin.keuangan.pengeluaran-kantor.edit', '__ID__') }}';
        const destroyUrlTemplate = '{{ route('admin.keuangan.pengeluaran-kantor.destroy', '__ID__') }}';

        function formatRp(amount) {
            return 'Rp ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function renderTable(data) {
            if (data.pengeluaran.length === 0) {
                reportBody.innerHTML =
                    '<tr id="empty-row"><td colspan="4" class="px-6 py-12 text-center text-slate-500">Tidak ada pengeluaran kantor pada bulan ini.</td></tr>';
                return;
            }

            let html = '';
            data.pengeluaran.forEach(function (item) {
                const date = new Date(item.tanggal + 'T00:00:00');
                const dateStr = date.toLocaleDateString('id', { day: '2-digit', month: '2-digit', year: 'numeric' });
                const editUrl = editUrlTemplate.replace('__ID__', item.id);
                const destroyUrl = destroyUrlTemplate.replace('__ID__', item.id);

                html += '<tr class="hover:bg-slate-50 transition-colors">' +
                    '<td class="px-6 py-4 text-sm text-slate-700 whitespace-nowrap">' + dateStr + '</td>' +
                    '<td class="px-6 py-4 text-sm text-slate-700">' + escapeHtml(item.deskripsi || '') + '</td>' +
                    '<td class="px-6 py-4 text-sm text-red-600 font-semibold text-right whitespace-nowrap">' + formatRp(parseFloat(item.jumlah)) + '</td>' +
                    '<td class="px-6 py-4 text-right">' +
                        '<div class="inline-flex items-center gap-2">' +
                            '<a href="' + editUrl + '" class="px-3 py-2 bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold hover:bg-slate-200">Edit</a>' +
                            '<form action="' + destroyUrl + '" method="POST" onsubmit="return confirm(\'Hapus pengeluaran ini?\');" style="display:inline">' +
                                '<input type="hidden" name="_token" value="' + '{{ csrf_token() }}' + '">' +
                                '<input type="hidden" name="_method" value="DELETE">' +
                                '<button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-lg text-xs font-semibold hover:bg-red-700">Hapus</button>' +
                            '</form>' +
                        '</div>' +
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
            reportBody.innerHTML = '<tr><td colspan="4" class="px-6 py-12 text-center text-slate-500">Memuat...</td></tr>';

            axios.get(dataUrl, { params: { month: month } })
                .then(function (res) {
                    const data = res.data;
                    monthLabel.textContent = data.monthLabel;

                    const canGoPrev = month > data.prevMonth; // always allow going back
                    btnPrev.dataset.month = data.prevMonth;
                    btnPrev.disabled = false;
                    btnPrev.className = 'flex items-center justify-center w-9 h-9 rounded-lg transition-colors text-slate-700 hover:bg-slate-100';

                    const canGoNext = data.canGoNext;
                    btnNext.dataset.month = data.nextMonth;
                    btnNext.disabled = !canGoNext;
                    btnNext.className = 'flex items-center justify-center w-9 h-9 rounded-lg transition-colors ' +
                        (canGoNext ? 'text-slate-700 hover:bg-slate-100' : 'text-slate-300 cursor-not-allowed');

                    renderTable(data);

                    const total = data.pengeluaran.reduce(function (sum, item) {
                        return sum + parseFloat(item.jumlah);
                    }, 0);
                    totalEl.textContent = formatRp(total);
                    countEl.textContent = data.pengeluaran.length + ' entri';
                    const avg = data.pengeluaran.length > 0 ? total / data.pengeluaran.length : 0;
                    avgEl.textContent = formatRp(Math.round(avg));
                })
                .catch(function () {
                    reportBody.innerHTML = '<tr><td colspan="4" class="px-6 py-12 text-center text-red-500">Gagal memuat data.</td></tr>';
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
