@extends('layouts.app')

@section('title', 'Kalender Jadwal')

@section('content')
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">

        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Kalender Jadwal</h1>
                <p class="text-sm text-slate-500 mt-1">Visualisasi booking dan ketersediaan semua armada</p>
            </div>

            <a href="{{ route('admin.bookings.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-md shadow-blue-100 active:scale-95">
                <span class="material-symbols-outlined text-[20px]">add</span>
                Tambah Pesanan
            </a>
        </div>

        {{-- Cek Ketersediaan Card --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5 flex flex-wrap items-center gap-3">
            <span class="material-symbols-outlined text-blue-500 text-[20px]">search</span>
            <span class="text-sm font-medium text-blue-800">Cek Ketersediaan Armada:</span>
            <div class="flex flex-wrap items-center gap-2 flex-1">

                {{-- Input Dari --}}
                <div class="flex items-center gap-1.5 bg-white border border-blue-200 rounded-lg px-3 py-1.5">
                    <span class="text-xs text-slate-500">Dari</span>
                    <input type="date" id="cek_dari" min="{{ date('Y-m-d') }}"
                        onchange="document.getElementById('cek_sampai').min = this.value"
                        class="text-sm text-slate-700 bg-transparent border-none outline-none font-medium" />
                </div>

                <span class="text-slate-400 text-sm">—</span>

                {{-- Input Sampai --}}
                <div class="flex items-center gap-1.5 bg-white border border-blue-200 rounded-lg px-3 py-1.5">
                    <span class="text-xs text-slate-500">Sampai</span>
                    <input type="date" id="cek_sampai" min="{{ date('Y-m-d') }}"
                        class="text-sm text-slate-700 bg-transparent border-none outline-none font-medium" />
                </div>

                <button onclick="cekKetersediaan()"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors">
                    Cek Sekarang
                </button>
            </div>
            <button onclick="tutupHasilCek()" id="btn_tutup_cek" class="hidden text-slate-400 hover:text-slate-600 ml-auto">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        {{-- Hasil Cek Ketersediaan --}}
        <div id="hasil_cek" class="hidden mb-5 bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Hasil Cek Ketersediaan</h3>
                <span id="hasil_cek_range" class="text-xs text-slate-500 font-medium"></span>
            </div>
            <div id="hasil_cek_list" class="divide-y divide-slate-50 max-h-60 overflow-y-auto"></div>
        </div>

        {{-- Toolbar: Navigasi Bulan + Filter Status --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">

            {{-- Navigasi Bulan --}}
            <div class="flex items-center gap-2">
                <button onclick="gantibulan(-1)"
                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </button>
                <h2 id="label_bulan" class="text-base font-semibold text-slate-900 min-w-[100px] text-center"></h2>
                <button onclick="gantibulan(1)"
                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
                <button onclick="keBulanIni()"
                    class="ml-1 text-xs font-medium text-brand-600 hover:text-brand-700 border border-brand-200 hover:border-brand-300 px-3 py-1.5 rounded-lg transition-colors">
                    Bulan ini
                </button>
            </div>

            {{-- Filter Status --}}
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs text-slate-500 font-medium">Filter:</span>
                <button onclick="toggleFilter(this, 'lunas')" data-status="lunas" data-active="true"
                    class="filter-btn active flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border transition-all
                       bg-emerald-50 border-emerald-300 text-emerald-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Lunas
                </button>
                <button onclick="toggleFilter(this, 'dp')" data-status="dp" data-active="true"
                    class="filter-btn active flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border transition-all
                       bg-blue-50 border-blue-300 text-blue-700">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span> DP
                </button>
                <button onclick="toggleFilter(this, 'belum_bayar')" data-status="belum_bayar" data-active="true"
                    class="filter-btn active flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border transition-all
                       bg-amber-50 border-amber-300 text-amber-700">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Belum Bayar
                </button>
                <button onclick="toggleFilter(this, 'belum_lunas')" data-status="belum_lunas" data-active="true"
                    class="filter-btn active flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border transition-all
                       bg-orange-50 border-orange-300 text-orange-700">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span> Belum Lunas
                </button>

                {{-- Toggle Sembunyikan Mobil Kosong --}}
                <label class="flex items-center gap-2 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" id="toggle_mobil_kosong" class="sr-only" onchange="renderKalender()" checked>
                        <div class="block bg-slate-200 w-8 h-5 rounded-full transition-colors duration-200"></div>
                        <div
                            class="dot absolute left-1 top-1 bg-white w-3 h-3 rounded-full transition-transform duration-200">
                        </div>
                    </div>
                    <span class="text-xs text-slate-500 font-medium select-none">Hanya tampilkan yang disewa</span>
                </label>

                <style>
                    input:checked~.block {
                        background-color: #2563eb;
                    }

                    /* blue-600 */
                    input:checked~.dot {
                        transform: translateX(100%);
                    }
                </style>

            </div>
        </div>

        {{-- Kalender Grid --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto overflow-y-auto max-h-[65vh] relative">
                {{-- 🟢 PERUBAHAN: Gunakan table-fixed dan min-w-max --}}
                <table class="border-collapse min-w-max table-fixed" id="tabel_kalender">
                    <thead id="thead_kalender" class="sticky top-0 z-20 bg-white shadow-sm"></thead>
                    <tbody id="tbody_kalender"></tbody>
                </table>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">

            {{-- Total Booking --}}
            <div class="bg-white border border-gray-200 rounded-2xl px-5 py-4 shadow-sm">
                <div class="flex items-center gap-2 mb-1">
                    <span class="material-symbols-outlined text-[18px] text-slate-500">book_online</span>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Booking</p>
                </div>
                <p id="stat_total" class="text-2xl font-bold text-slate-800">—</p>
                <p class="text-xs text-slate-400 mt-0.5">bulan ini</p>
            </div>

            {{-- Lunas --}}
            <div class="bg-white border border-gray-200 rounded-2xl px-5 py-4 shadow-sm">
                <div class="flex items-center gap-2 mb-1">
                    <span class="material-symbols-outlined text-[18px] text-emerald-500">task_alt</span>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Lunas</p>
                </div>
                <p id="stat_lunas" class="text-2xl font-bold text-emerald-600">—</p>
                <p id="stat_lunas_sub" class="text-xs text-slate-400 mt-0.5">—</p>
            </div>

            {{-- DP / Proses --}}
            <div class="bg-white border border-gray-200 rounded-2xl px-5 py-4 shadow-sm">
                <div class="flex items-center gap-2 mb-1">
                    <span class="material-symbols-outlined text-[18px] text-blue-500">pending_actions</span>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">DP / Proses</p>
                </div>
                <p id="stat_dp" class="text-2xl font-bold text-blue-600">—</p>
                <p class="text-xs text-slate-400 mt-0.5">perlu tindak lanjut</p>
            </div>

        </div>

    </div>

    {{-- Modal Detail Booking --}}
    <div id="modal_overlay" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)tutupModal()">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
            <div id="modal_header" class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
                <div>
                    <h3 id="modal_nama" class="text-base font-bold text-slate-900"></h3>
                    <p id="modal_nomor" class="text-xs text-slate-400 mt-0.5"></p>
                </div>
                <span id="modal_badge" class="text-xs font-semibold px-2.5 py-1 rounded-full flex-shrink-0"></span>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-slate-400 text-[18px]">directions_car</span>
                    <span class="text-slate-500">Kendaraan:</span>
                    <span id="modal_kendaraan" class="font-medium text-slate-800 ml-auto text-right"></span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-slate-400 text-[18px]">calendar_month</span>
                    <span class="text-slate-500">Tanggal:</span>
                    <span id="modal_tanggal" class="font-medium text-slate-800 ml-auto"></span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-slate-400 text-[18px]">receipt</span>
                    <span class="text-slate-500">Tipe Sewa:</span>
                    <span id="modal_tipe" class="font-medium text-slate-800 ml-auto"></span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-slate-400 text-[18px]">payments</span>
                    <span class="text-slate-500">Nominal:</span>
                    <span id="modal_nominal" class="font-bold text-slate-900 ml-auto"></span>
                </div>
            </div>
            <div class="px-5 pb-5 flex gap-2">
                <a id="modal_link_detail" href="#"
                    class="flex-1 text-center bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                    Lihat Detail
                </a>
                <button onclick="tutupModal()"
                    class="flex-1 text-center border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium py-2 rounded-lg transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.CalendarData = {
            armada: @json($armada),
            bookings: @json($bookings),
            baseUrl: '{{ url('/') }}'
        };
    </script>

    @vite('resources/js/calendar.js')
@endpush
