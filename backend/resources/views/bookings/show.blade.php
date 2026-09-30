@extends('layouts.app')

@section('content')
    <div class="px-4 pb-4 lg:px-8 lg:pb-8 max-w-5xl mx-auto w-full">

        {{-- ═══════════ AREA YANG TERLIHAT DI LAYAR ═══════════ --}}
        <div class="no-print">

            {{-- ── Header & Action Buttons ── --}}
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-8">
                <div>
                    <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-3">
                        <a href="{{ route('admin.bookings') }}" class="hover:text-blue-600 transition-colors">Pesanan</a>
                        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        <span class="text-slate-800">Detail #{{ $booking->nomor_pesanan }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-bold text-slate-900">{{ $booking->nomor_pesanan }}</h1>

                        {{-- Badge Status Pembayaran --}}
                        @if ($booking->status_pembayaran == 'Lunas')
                            <span
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                <span class="material-symbols-outlined text-[14px]">check_circle</span> LUNAS
                            </span>
                        @elseif ($booking->status_pembayaran == 'DP')
                            <span
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                <span class="material-symbols-outlined text-[14px]">payments</span> DP SEBAGIAN
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                                <span class="material-symbols-outlined text-[14px]">error</span> BELUM BAYAR
                            </span>
                        @endif

                        {{-- Badge Status Rental --}}
                        @if ($booking->status_rental == 'Selesai')
                            <span
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                <span class="material-symbols-outlined text-[14px]">done_all</span> RENTAL SELESAI
                            </span>
                        @elseif ($booking->status_rental == 'Aktif')
                            <span
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                <span class="material-symbols-outlined text-[14px]">local_taxi</span> SEDANG DISEWA
                            </span>
                        @elseif ($booking->status_rental == 'Batal')
                            <span
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                                <span class="material-symbols-outlined text-[14px]">cancel</span> DIBATALKAN
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200">
                                <span class="material-symbols-outlined text-[14px]">hourglass_empty</span> MENUNGGU DIAMBIL
                            </span>
                        @endif
                    </div>

                    <p class="text-sm text-slate-500 mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">history</span>
                        Dibuat pada {{ $booking->created_at->format('d F Y, H:i') }}
                    </p>
                </div>

                {{-- Tombol Aksi --}}
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-stretch sm:items-center gap-2 sm:gap-3 shrink-0 mt-2 md:mt-0 w-full md:w-auto">

                    {{-- 🟢 FITUR BARU: Tombol Proses Refund (Hanya Muncul Jika Batal & Ada Dana Masuk) --}}
                    @php
                        // Asumsi field refund_amount akan kamu tambahkan di DB, jika null dianggap 0
                        $refundAmount = $booking->refund_amount ?? 0;
                        $isBatalRefundable = $booking->status_rental === 'Batal' && $booking->dp_amount > 0;
                    @endphp

                    @if ($isBatalRefundable && $refundAmount < $booking->dp_amount)
                        <button type="button"
                            onclick="document.getElementById('modal-proses-refund').classList.remove('hidden')"
                            class="col-span-2 sm:col-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-bold transition-all shadow-md active:scale-95">
                            <span class="material-symbols-outlined text-[18px]">currency_exchange</span> Refund Dana
                        </button>
                    @endif

                    @if ($booking->status_rental !== 'Batal')
                        <button type="button"
                            onclick="document.getElementById('modal-update-status').classList.remove('hidden')"
                            class="col-span-2 sm:col-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-sm font-bold transition-all shadow-md active:scale-95">
                            <span class="material-symbols-outlined text-[18px]">sync_alt</span> Update Status
                        </button>
                    @endif

                    @if (!in_array($booking->status_rental, ['Selesai', 'Batal']))
                        <a href="{{ route('admin.bookings.edit', $booking->id) }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-slate-200 bg-white rounded-xl text-sm font-semibold text-slate-700 hover:text-blue-600 hover:bg-blue-50 transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">edit</span> Edit Data
                        </a>
                    @endif

                    @if ($booking->tipe_sewa === 'harian')
                        <form method="POST" action="{{ route('admin.invoice.generate', [$booking->id, 'SH']) }}" target="_blank" class="inline-flex">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold transition-all shadow-md active:scale-95">
                                <span class="material-symbols-outlined text-[18px]">receipt</span> Invoice
                            </button>
                        </form>
                    @elseif ($booking->tipe_sewa === 'kontrak')
                        <form method="POST" action="{{ route('admin.invoice.generate', [$booking->id, 'SK']) }}" target="_blank" class="inline-flex">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold transition-all shadow-md active:scale-95">
                                <span class="material-symbols-outlined text-[18px]">receipt</span> Invoice
                            </button>
                        </form>
                        @if (in_array($booking->status_rental, ['Aktif', 'Selesai', 'Menunggu']))
                            <form method="POST" action="{{ route('admin.surat-menyurat.serah-terima', $booking->id) }}" target="_blank" class="inline-flex">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold transition-all shadow-md active:scale-95">
                                    <span class="material-symbols-outlined text-[18px]">swap_horiz</span> Serah Terima
                                </button>
                            </form>
                        @endif
                    @endif
                    @if ($invoice)
                        @if ($invoice->isFullySigned())
                            <a href="{{ route('admin.invoice.download', $invoice->id) }}"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold transition-all shadow-md active:scale-95"
                                title="Unduh PDF invoice yang sudah ditandatangani">
                                <span class="material-symbols-outlined text-[18px]">download</span> Unduh PDF
                            </a>
                        @else
                            @include('components.invoice-sign-button', [
                                'invoice' => $invoice,
                                'label' => 'Tanda Tangan & Unduh',
                            ])
                        @endif
                    @endif
                    <button onclick="window.print()"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-bold transition-all shadow-sm active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">print</span> Cetak
                    </button>
                </div>
            </div>

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex gap-3 items-start">
                    <span class="material-symbols-outlined text-emerald-500 shrink-0 mt-0.5">check_circle</span>
                    <p class="text-sm text-emerald-700 font-medium">{{ session('success') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3 items-start">
                    <span class="material-symbols-outlined text-red-500 shrink-0 mt-0.5">error</span>
                    <ul class="text-sm text-red-700 font-medium list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 🟢 PANEL KUNCI & UPDATE ODOMETER --}}
            @if (
                !in_array($booking->status_rental, ['Selesai', 'Batal']) &&
                    in_array($booking->status_rental, ['Aktif', 'Dikembalikan']))
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {{-- Form Update Odometer --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 shadow-sm">
                        <div class="flex items-start gap-3 mb-4">
                            <div
                                class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[20px]">speed</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-blue-900">Update Odometer</h3>
                                <p class="text-xs text-blue-700 mt-0.5">Catat KM terakhir saat mobil kembali agar sistem
                                    melacak keausan servis komponen secara otomatis.</p>
                            </div>
                        </div>

                        <form action="{{ route('admin.bookings.update_odometer', $booking->id) }}" method="POST">
                            @csrf
                            <div class="space-y-3 mb-4">
                                @foreach ($booking->bookingCars as $bc)
                                    <div class="bg-white p-3 rounded-xl border border-blue-100 flex items-center gap-3">
                                        <div class="flex-1">
                                            <p class="text-sm font-bold text-slate-800">{{ $bc->car->merk }}
                                                {{ $bc->car->model }}</p>
                                            <p class="text-[10px] text-slate-500 font-mono">KM Awal:
                                                {{ number_format($bc->car->km_saat_ini, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="w-24 sm:w-32">
                                            <label class="text-[9px] font-bold text-slate-500 uppercase block mb-0.5">KM
                                                Kembali</label>
                                            <input type="number" name="km_kembali[{{ $bc->car_id }}]"
                                                min="{{ $bc->car->km_saat_ini }}" value="{{ $bc->car->km_saat_ini }}"
                                                class="w-full px-2 py-1.5 text-sm font-bold text-blue-700 bg-blue-50/50 border border-blue-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                required>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-xl text-sm transition-colors shadow-sm">Simpan
                                Odometer</button>
                        </form>
                    </div>

                    {{-- Form Slide to Lock --}}
                    <div
                        class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6 shadow-sm flex flex-col items-center justify-center relative overflow-hidden">
                        <div class="absolute -right-8 -top-8 text-emerald-100/50">
                            <span class="material-symbols-outlined text-[150px]">verified_user</span>
                        </div>
                        <h3 class="text-lg font-bold text-emerald-900 mb-1 z-10">Selesaikan Pesanan</h3>
                        <p class="text-sm text-emerald-700 mb-6 text-center z-10">Pastikan odometer dan semua biaya tambahan
                            tagihan sudah tercatat sebelum mengunci pesanan permanen.</p>

                        {{-- Slide Component --}}
                        <div
                            class="relative w-full max-w-xs h-14 bg-emerald-200/60 rounded-full flex items-center p-1.5 overflow-hidden shadow-inner mx-auto border border-emerald-300 z-10">
                            <div
                                class="absolute inset-0 flex items-center justify-center text-emerald-800 font-extrabold text-sm tracking-widest pointer-events-none z-0">
                                GESER UNTUK KUNCI
                            </div>
                            <div id="lock-track"
                                class="absolute left-0 top-0 bottom-0 bg-emerald-500 z-0 transition-all w-14 rounded-full pointer-events-none">
                            </div>
                            <input type="range" min="0" max="100" value="0" id="lock-slider"
                                class="absolute inset-0 w-full h-full z-20 opacity-0 cursor-grab active:cursor-grabbing">
                            <div id="lock-thumb"
                                class="absolute left-1.5 h-11 w-11 bg-white rounded-full z-10 flex items-center justify-center text-emerald-600 transition-all shadow-md pointer-events-none">
                                <span class="material-symbols-outlined text-[24px]">double_arrow</span>
                            </div>
                        </div>

                        <form id="form-lock" action="{{ route('admin.bookings.lock', $booking->id) }}" method="POST"
                            class="hidden">@csrf</form>
                    </div>
                </div>
            @elseif ($booking->status_rental === 'Selesai')
                {{-- Banner Kunci Ganda Terkunci --}}
                <div
                    class="mb-6 bg-slate-50 border border-slate-200 rounded-2xl p-8 text-center shadow-sm relative overflow-hidden">
                    <div class="absolute inset-x-0 -top-10 opacity-5 flex justify-center"><span
                            class="material-symbols-outlined text-[150px]">lock</span></div>
                    <div class="relative z-10 inline-flex items-center justify-center w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full mb-4 cursor-pointer hover:bg-emerald-200 hover:scale-105 transition-all shadow-sm ring-4 ring-emerald-50"
                        ondblclick="document.getElementById('form-unlock').submit()"
                        title="Klik 2x untuk membuka kunci data pesanan">
                        <span class="material-symbols-outlined text-[40px]">lock</span>
                    </div>
                    <h3 class="text-xl font-black text-slate-800 relative z-10">Pesanan Telah Selesai & Terkunci</h3>
                    <p class="text-sm font-medium text-slate-500 mt-2 relative z-10 max-w-md mx-auto">Seluruh biaya,
                        Add-on, dan Operasional tidak dapat diubah lagi. <span
                            class="text-emerald-600 font-bold block mt-1">(Klik 2x ikon gembok di atas untuk membuka kunci
                            jika perlu revisi).</span></p>

                    <form id="form-unlock" action="{{ route('admin.bookings.unlock', $booking->id) }}" method="POST"
                        class="hidden">@csrf</form>
                </div>
            @endif

            <div class="space-y-6">

                {{-- ── ROW 1: Pelanggan & Jadwal ── --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Card Pelanggan --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
                            <span class="material-symbols-outlined text-blue-600">account_circle</span>
                            <h3 class="text-lg font-bold text-slate-800">Informasi Pelanggan</h3>
                        </div>
                        <div class="flex items-center gap-4">
                            <div
                                class="w-14 h-14 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xl font-black shrink-0">
                                {{ substr($booking->nama_pelanggan, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Pemesan</p>
                                <p class="font-bold text-slate-800 text-base">{{ $booking->nama_pelanggan }}</p>
                                <p class="text-sm text-slate-500 flex items-center gap-1 mt-1">
                                    <span class="material-symbols-outlined text-[16px]">call</span>
                                    {{ $booking->kontak_pelanggan }}
                                </p>
                                @if ($booking->email)
                                    <p class="text-sm text-slate-500 flex items-center gap-1 mt-0.5">
                                        <span class="material-symbols-outlined text-[16px]">mail</span>
                                        {{ $booking->email }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Card Jadwal --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                            <span class="material-symbols-outlined text-[100px]">calendar_month</span>
                        </div>
                        <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100 relative z-10">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-blue-600">event</span>
                                <h3 class="text-lg font-bold text-slate-800">Jadwal Sewa</h3>
                            </div>
                            <span
                                class="bg-slate-100 text-slate-700 px-3 py-1 rounded text-xs font-bold uppercase tracking-wider">
                                {{ $booking->tipe_sewa }}
                            </span>
                        </div>
                        @php
                            $durasi = \Carbon\Carbon::parse($booking->tanggal_mulai)->diffInDays(
                                \Carbon\Carbon::parse($booking->tanggal_selesai),
                            );
                        @endphp
                        <div class="flex justify-between items-center relative z-10">
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mulai</p>
                                <p class="font-bold text-slate-800">
                                    {{ \Carbon\Carbon::parse($booking->tanggal_mulai)->format('d M Y') }}</p>
                            </div>
                            <div class="flex flex-col items-center px-2">
                                <div
                                    class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap">
                                    {{ $durasi }} Hari
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Selesai</p>
                                <p class="font-bold text-slate-800">
                                    {{ \Carbon\Carbon::parse($booking->tanggal_selesai)->format('d M Y') }}</p>
                            </div>
                        </div>

                        @if ($booking->picked_up_at || $booking->returned_at)
                            <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3 text-xs">
                                <div class="text-left">
                                    @if ($booking->picked_up_at)
                                        <p class="text-slate-400 font-semibold uppercase tracking-wider mb-0.5">Diambil</p>
                                        <p class="text-slate-700 font-medium">
                                            {{ \Carbon\Carbon::parse($booking->picked_up_at)->format('d M Y, H:i') }}
                                        </p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    @if ($booking->returned_at)
                                        <p class="text-slate-400 font-semibold uppercase tracking-wider mb-0.5">
                                            Dikembalikan
                                        </p>
                                        <p class="text-slate-700 font-medium">
                                            {{ \Carbon\Carbon::parse($booking->returned_at)->format('d M Y, H:i') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── ROW 2: Rincian Kendaraan & Biaya ── --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">receipt_long</span>
                        <h3 class="text-lg font-bold text-slate-800">Rincian Kendaraan & Biaya</h3>
                    </div>
                    <div class="p-6">

                        {{-- Daftar Kendaraan --}}
                        <div class="space-y-4">
                            @foreach ($booking->bookingCars as $bc)
                                <div
                                    class="flex flex-col md:flex-row md:justify-between md:items-center p-4 border border-slate-100 rounded-xl bg-white shadow-sm gap-4">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-slate-50 text-slate-500 flex items-center justify-center border border-slate-200 shrink-0 overflow-hidden">
                                            @if ($bc->car->foto)
                                                <img src="{{ asset('storage/' . $bc->car->foto) }}"
                                                    alt="{{ $bc->car->merk }}" class="w-full h-full object-cover">
                                            @else
                                                <span class="material-symbols-outlined">directions_car</span>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 text-base">{{ $bc->car->merk }}
                                                {{ $bc->car->model }}</p>
                                            <p class="text-xs text-slate-500 mt-1">
                                                <span
                                                    class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-slate-700 font-bold">{{ $bc->car->plat_nomor }}</span>
                                                · {{ $bc->car->warna }} · {{ $bc->car->tahun }}
                                            </p>
                                        </div>
                                    </div>
                                    @if ($booking->tipe_sewa === 'harian')
                                        <div
                                            class="md:text-right pt-3 md:pt-0 border-t md:border-0 border-slate-100 flex justify-between md:block">
                                            <p class="text-xs text-slate-400 font-medium mb-0.5">
                                                Rp {{ number_format($bc->harga_dasar_per_hari, 0, ',', '.') }} ×
                                                {{ $durasi }} hari
                                            </p>
                                            <p class="font-bold text-slate-800">
                                                Rp {{ number_format($bc->harga_dasar_per_hari * $durasi, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    @elseif ($booking->tipe_sewa === 'kontrak')
                                        <div
                                            class="md:text-right pt-3 md:pt-0 border-t md:border-0 border-slate-100 flex justify-between md:block">
                                            <p class="text-xs text-slate-400 font-medium mb-0.5">
                                                Harga Kontrak
                                            </p>
                                            <p class="font-bold text-slate-800">
                                                Rp {{ number_format($bc->harga_dasar_per_hari ?? 0, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            @if ($booking->tipe_sewa === 'kontrak')
                                <div
                                    class="flex justify-between items-center p-4 border border-slate-100 rounded-xl bg-slate-50">
                                    <span class="font-bold text-slate-700">Total Harga Kontrak (Kendaraan)</span>
                                    <span class="font-bold text-slate-800">Rp
                                        {{ number_format($booking->total_harga - $booking->total_addons, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Biaya Tambahan (Add-ons) --}}
                        @if ($booking->addons->count() > 0)
                            <div class="mt-6 pt-6 border-t border-dashed border-slate-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-sm font-bold text-slate-800">Biaya Tambahan</h4>
                                </div>
                                <div class="space-y-3" id="addon-list">
                                    @foreach ($booking->addons as $addon)
                                        <div class="flex justify-between items-center text-slate-600 px-2 text-sm group"
                                            id="addon-row-{{ $addon->id }}">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="material-symbols-outlined text-[16px] text-slate-400">add_circle</span>
                                                {{ $addon->nama_biaya }}
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="font-medium text-slate-800">Rp
                                                    {{ number_format($addon->nominal, 0, ',', '.') }}</span>
                                                @if (!in_array($booking->status_rental, ['Aktif', 'Selesai', 'Batal']))
                                                    <form
                                                        action="{{ route('admin.bookings.addon.destroy', [$booking->id, $addon->id]) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Hapus biaya tambahan ini?')"
                                                        class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="opacity-0 group-hover:opacity-100 transition-opacity text-slate-400 hover:text-red-500 p-0.5 rounded">
                                                            <span
                                                                class="material-symbols-outlined text-[16px]">delete</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Form Tambah Add-on (hanya muncul jika belum terkunci) --}}
                        @if (!in_array($booking->status_rental, ['Selesai', 'Batal']))
                            <div class="mt-5 pt-5 border-t border-dashed border-slate-200">
                                <button type="button" id="btn-toggle-addon-form"
                                    class="flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors group">
                                    <span
                                        class="material-symbols-outlined text-[18px] transition-transform group-hover:scale-110">add_circle</span>
                                    Tambah Biaya Tambahan
                                </button>

                                <form action="{{ route('admin.bookings.addon.store', $booking->id) }}" method="POST"
                                    id="form-tambah-addon"
                                    class="hidden mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                                    @csrf
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Biaya Baru
                                    </p>
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <input type="text" name="addon_nama" required
                                            placeholder="Nama biaya (misal: Supir, Tol...)"
                                            class="flex-1 bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all" />
                                        <div class="relative w-full sm:w-44">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">Rp</span>
                                            <input type="number" name="addon_nominal" min="0" required
                                                placeholder="0"
                                                class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all" />
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="submit"
                                                class="flex-1 sm:flex-none px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                                                Simpan
                                            </button>
                                            <button type="button" id="btn-cancel-addon-form"
                                                class="flex-1 sm:flex-none px-4 py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-semibold rounded-lg transition-colors">
                                                Batal
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif

                        {{-- Grand Total --}}
                        <div
                            class="mt-8 pt-6 border-t-[3px] border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-2 px-2">
                            <span class="text-sm md:text-lg font-black text-slate-800 tracking-wider">TOTAL TAGIHAN</span>
                            <span class="text-3xl font-black text-blue-700">Rp
                                {{ number_format($booking->total_harga, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- ── ROW 3: Ringkasan Pembayaran & Refund ── --}}
                @php
                    $sisaTagihan = $booking->total_harga - $booking->dp_amount;
                @endphp

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-600">account_balance_wallet</span>
                            <h3 class="text-lg font-bold text-slate-800">Ringkasan Pembayaran</h3>
                        </div>
                    </div>

                    <div class="p-6">
                        {{-- 🟢 FITUR BARU: Layout Grid Dinamis jika ada Card Refund --}}
                        <div class="grid grid-cols-1 sm:grid-cols-{{ $isBatalRefundable ? '4' : '3' }} gap-4 mb-6">

                            {{-- Total Tagihan --}}
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total
                                    Tagihan</p>
                                <p class="text-xl font-black text-slate-800">Rp
                                    {{ number_format($booking->total_harga, 0, ',', '.') }}</p>
                            </div>

                            {{-- Sudah Dibayar --}}
                            <div
                                class="p-4 rounded-xl border text-center
                                {{ $booking->dp_amount > 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200' }}">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-widest mb-1
                                    {{ $booking->dp_amount > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                                    DP yang Dibayar
                                    @if ($booking->status_pembayaran === 'DP')
                                        <span
                                            class="ml-1 px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded text-[9px]">DP</span>
                                    @endif
                                </p>
                                <p
                                    class="text-xl font-black {{ $booking->dp_amount > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                                    Rp {{ number_format($booking->dp_amount, 0, ',', '.') }}
                                </p>
                            </div>

                            {{-- Sisa Tagihan --}}
                            <div
                                class="p-4 rounded-xl border text-center
                                {{ $booking->status_rental === 'Batal' ? 'bg-slate-50 border-slate-200 opacity-60' : ($booking->status_pembayaran === 'Lunas' ? 'bg-emerald-50 border-emerald-200' : ($sisaTagihan > 0 ? 'bg-red-50 border-red-200' : 'bg-slate-50 border-slate-200')) }}">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-widest mb-1
                                    {{ $booking->status_rental === 'Batal' ? 'text-slate-500' : ($booking->status_pembayaran === 'Lunas' ? 'text-emerald-600' : ($sisaTagihan > 0 ? 'text-red-500' : 'text-slate-400')) }}">
                                    Sisa Tagihan
                                </p>
                                @if ($booking->status_rental === 'Batal')
                                    <p class="text-xl font-black text-slate-500 line-through">DIBATALKAN</p>
                                @elseif ($booking->status_pembayaran === 'Lunas')
                                    <p class="text-xl font-black text-emerald-700">LUNAS ✓</p>
                                @else
                                    <p
                                        class="text-xl font-black {{ $sisaTagihan > 0 ? 'text-red-600' : 'text-slate-400' }}">
                                        Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                                    </p>
                                @endif
                            </div>

                            {{-- 🟢 FITUR BARU: Card Refund --}}
                            @if ($isBatalRefundable)
                                <div
                                    class="p-4 rounded-xl border text-center {{ $refundAmount >= $booking->dp_amount ? 'bg-purple-50 border-purple-200' : 'bg-red-50 border-red-200' }}">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-widest mb-1 {{ $refundAmount >= $booking->dp_amount ? 'text-purple-600' : 'text-red-500' }}">
                                        Status Refund
                                    </p>
                                    @if ($refundAmount > 0)
                                        <p class="text-xl font-black text-purple-700">Rp
                                            {{ number_format($refundAmount, 0, ',', '.') }}</p>
                                        @if ($refundAmount < $booking->dp_amount)
                                            <p class="text-[10px] text-red-500 mt-1">Sisa: Rp
                                                {{ number_format($booking->dp_amount - $refundAmount, 0, ',', '.') }}</p>
                                        @endif
                                    @else
                                        <p class="text-lg font-black text-red-600">MENUNGGU</p>
                                    @endif
                                </div>
                            @endif

                        </div>

                        {{-- Riwayat Bayar dari booking_payments --}}
                        @php
                            $payments = \App\Models\BookingPayment::where('booking_id', $booking->id)
                                ->with('createdBy')
                                ->latest('created_at')
                                ->get();
                        @endphp

                        @if ($payments->count() > 0)
                            <div class="mt-2 mb-6">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Riwayat Transaksi
                                    Bayar</p>
                                <div class="space-y-2">
                                    @foreach ($payments as $pay)
                                        <div
                                            class="flex items-center justify-between text-sm px-3 py-2.5 rounded-lg
                                            {{ $pay->status_sesudah === 'Lunas' ? 'bg-emerald-50 border border-emerald-100' : 'bg-amber-50 border border-amber-100' }}">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="material-symbols-outlined text-[16px]
                                                    {{ $pay->status_sesudah === 'Lunas' ? 'text-emerald-500' : 'text-amber-500' }}">
                                                    {{ $pay->status_sesudah === 'Lunas' ? 'check_circle' : 'payments' }}
                                                </span>
                                                <div>
                                                    <p class="font-semibold text-slate-800">
                                                        {{ $pay->status_sesudah === 'Lunas' ? 'Pelunasan' : 'Pembayaran DP' }}
                                                        @if ($pay->metode_bayar)
                                                            <span class="text-xs text-slate-400 font-normal">·
                                                                {{ ucfirst($pay->metode_bayar) }}</span>
                                                        @endif
                                                    </p>
                                                    <p class="text-xs text-slate-400">
                                                        {{ \Carbon\Carbon::parse($pay->created_at)->format('d M Y, H:i') }}
                                                        @if ($pay->createdBy)
                                                            · {{ $pay->createdBy->nama ?? $pay->createdBy->name }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                            <p
                                                class="font-bold {{ $pay->status_sesudah === 'Lunas' ? 'text-emerald-700' : 'text-amber-700' }}">
                                                + Rp {{ number_format($pay->jumlah, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Aksi Cepat Pelunasan (hanya muncul jika masih DP & belum Batal/Selesai) --}}
                        @if ($booking->status_pembayaran === 'DP' && !in_array($booking->status_rental, ['Batal', 'Selesai']))
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                                <p class="text-sm font-bold text-amber-800 mb-1 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[18px]">info</span>
                                    Sisa Pelunasan
                                </p>
                                <p class="text-xs text-amber-700 mb-4">
                                    Pelanggan masih memiliki sisa tagihan sebesar
                                    <strong>Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</strong>.
                                    Catat pelunasan di bawah ini.
                                </p>
                                <form action="{{ route('admin.bookings.update_status', $booking->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status_pembayaran" value="Lunas">
                                    <input type="hidden" name="status_rental" value="{{ $booking->status_rental }}">
                                    <input type="hidden" name="dp_amount" value="{{ $booking->total_harga }}">
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <select name="metode_bayar"
                                            class="flex-1 bg-white border border-amber-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-all">
                                            <option value="">Pilih metode bayar...</option>
                                            <option value="tunai">Tunai</option>
                                            <option value="transfer">Transfer Bank</option>
                                            <option value="qris">QRIS</option>
                                            <option value="lainnya">Lainnya</option>
                                        </select>
                                        <button type="submit"
                                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-colors shadow-sm whitespace-nowrap">
                                            ✓ Tandai Lunas
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        {{-- Aksi Catat DP pertama kali (hanya muncul jika Belum Bayar & belum Batal) --}}
                        @if ($booking->status_pembayaran === 'Belum Bayar' && !in_array($booking->status_rental, ['Batal', 'Selesai']))
                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
                                <p class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[18px] text-slate-500">payments</span>
                                    Catat Pembayaran
                                </p>
                                <form action="{{ route('admin.bookings.update_status', $booking->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status_rental" value="{{ $booking->status_rental }}">
                                    <div class="space-y-3">
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <select name="status_pembayaran" id="quick-pay-status"
                                                class="flex-1 bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-all">
                                                <option value="DP">DP (Dibayar Sebagian)</option>
                                                <option value="Lunas">Lunas</option>
                                            </select>
                                            <select name="metode_bayar"
                                                class="flex-1 bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-all">
                                                <option value="">Pilih metode bayar...</option>
                                                <option value="tunai">Tunai</option>
                                                <option value="transfer">Transfer Bank</option>
                                                <option value="qris">QRIS</option>
                                                <option value="lainnya">Lainnya</option>
                                            </select>
                                        </div>
                                        <div>
                                            <div id="quick-dp-wrap" class="relative">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">Rp</span>
                                                <input type="number" name="dp_amount" id="quick-dp-amount"
                                                    min="0" max="{{ $booking->total_harga }}"
                                                    placeholder="Nominal DP..."
                                                    class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all" />
                                            </div>
                                            <p class="text-xs text-slate-400 mt-1">Maks: Rp
                                                {{ number_format($booking->total_harga, 0, ',', '.') }}</p>
                                        </div>
                                        <button type="submit"
                                            class="w-full px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg transition-colors shadow-sm">
                                            Simpan Pembayaran
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── ROW 4: Riwayat Aktivitas & Catatan ── --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">

                    {{-- Activity Log --}}
                    <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-6">
                            <span class="material-symbols-outlined text-slate-400">format_list_bulleted</span>
                            <h3 class="text-lg font-bold text-slate-800">Riwayat Perubahan Status</h3>
                        </div>

                        @php
                            $activities = \App\Models\Activity::where('subject_type', 'booking')
                                ->where('subject_id', $booking->id)
                                ->latest()
                                ->get();
                        @endphp

                        <div class="relative border-l-2 border-slate-100 ml-3 space-y-6">
                            @forelse ($activities as $log)
                                <div class="relative pl-6">
                                    <div
                                        class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-{{ $log->color ?? 'blue' }}-100 border-2 border-white flex items-center justify-center shrink-0">
                                        <div class="w-2 h-2 rounded-full bg-{{ $log->color ?? 'blue' }}-500"></div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">{{ $log->title }}</p>
                                        <p class="text-sm text-slate-600 mt-0.5">{{ $log->description }}</p>
                                        <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">schedule</span>
                                            {{ $log->created_at->format('d M Y, H:i') }} • Oleh
                                            {{ optional($log->userRelation)->nama ?? ($log->user ?? 'Sistem') }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <div class="pl-6 text-sm text-slate-500 italic">Belum ada riwayat aktivitas.</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Catatan & Hapus --}}
                    <div class="flex flex-col gap-4">
                        @if ($booking->catatan)
                            <div class="p-5 bg-yellow-50 border border-yellow-200 rounded-2xl shadow-sm">
                                <p
                                    class="text-[11px] font-bold text-yellow-700 uppercase tracking-wider mb-2 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">speaker_notes</span> Catatan Khusus
                                </p>
                                <p class="text-sm text-yellow-900 italic leading-relaxed">"{{ $booking->catatan }}"</p>
                            </div>
                        @endif

                        {{-- ── Dokumen & Foto Penyewa ── --}}
                        @php
                            $hasFotoKtp = !empty($booking->foto_ktp) && count((array) $booking->foto_ktp) > 0;
                            $hasFotoPenyewa =
                                !empty($booking->foto_penyewa) && count((array) $booking->foto_penyewa) > 0;

                            $hasFotoKondisi = false;
                            foreach ($booking->bookingCars as $bc) {
                                if (!empty($bc->foto_kondisi) && count((array) $bc->foto_kondisi) > 0) {
                                    $hasFotoKondisi = true;
                                    break;
                                }
                            }
                        @endphp

                        @if ($hasFotoKtp || $hasFotoPenyewa || $hasFotoKondisi)
                            <div class="p-4 bg-white border border-slate-200 rounded-2xl shadow-sm space-y-4">
                                <p
                                    class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">folder_shared</span> Dokumen &amp;
                                    Foto
                                </p>

                                {{-- Foto KTP --}}
                                @if ($hasFotoKtp)
                                    <div>
                                        <p class="text-xs font-semibold text-slate-400 mb-2 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">badge</span> Foto KTP
                                        </p>
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach ((array) $booking->foto_ktp as $foto)
                                                <a href="{{ asset('storage/' . $foto) }}" target="_blank">
                                                    <img src="{{ asset('storage/' . $foto) }}"
                                                        class="w-full h-24 object-cover rounded-lg border border-slate-200 hover:opacity-80 transition-opacity"
                                                        alt="Foto KTP">
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Foto Diri Penyewa --}}
                                @if ($hasFotoPenyewa)
                                    <div>
                                        <p class="text-xs font-semibold text-slate-400 mb-2 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">person</span> Foto Diri
                                            Penyewa
                                        </p>
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach ((array) $booking->foto_penyewa as $foto)
                                                <a href="{{ asset('storage/' . $foto) }}" target="_blank">
                                                    <img src="{{ asset('storage/' . $foto) }}"
                                                        class="w-full h-24 object-cover rounded-lg border border-slate-200 hover:opacity-80 transition-opacity"
                                                        alt="Foto Diri">
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Foto Kondisi Awal (khusus kontrak) --}}
                                @if ($hasFotoKondisi)
                                    <div>
                                        <p class="text-xs font-semibold text-slate-400 mb-2 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">photo_camera</span> Foto
                                            Kondisi Awal Kendaraan
                                        </p>
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach ($booking->bookingCars as $bc)
                                                @if (!empty($bc->foto_kondisi))
                                                    @foreach ((array) $bc->foto_kondisi as $foto)
                                                        <a href="{{ asset('storage/' . $foto) }}" target="_blank"
                                                            class="relative group">
                                                            <img src="{{ asset('storage/' . $foto) }}"
                                                                class="w-full h-24 object-cover rounded-lg border border-slate-200 group-hover:opacity-80 transition-opacity"
                                                                alt="Kondisi Awal">
                                                            <span
                                                                class="absolute bottom-1 left-1 bg-slate-900/70 text-white text-[10px] px-1.5 py-0.5 rounded">
                                                                {{ $bc->car->plat_nomor }}
                                                            </span>
                                                        </a>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST" class="w-full"
                            onsubmit="return confirm('Peringatan Keras: Yakin ingin menghapus pesanan ini secara permanen?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 text-slate-500 bg-white hover:text-red-600 hover:bg-red-50 rounded-xl text-sm font-semibold transition-colors border border-slate-200 hover:border-red-200 shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">delete</span> Hapus Permanen
                            </button>
                        </form>
                    </div>
                </div>

            </div>{{-- end space-y-6 --}}
        </div>{{-- end no-print --}}

        {{-- Komponen cetak --}}
        <x-serah-terima :booking="$booking" />
    </div>

    {{-- ═══════════ MODAL UPDATE STATUS ═══════════ --}}
    <div id="modal-update-status"
        class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 transition-all">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600">sync_alt</span> Update Status Pesanan
                </h3>
                <button type="button" onclick="document.getElementById('modal-update-status').classList.add('hidden')"
                    class="text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <form action="{{ route('admin.bookings.update_status', $booking->id) }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-5">

                    {{-- ── Status Pembayaran ── --}}
                    <div>
                        <label class="block font-semibold text-sm text-slate-700 mb-2">Status Pembayaran</label>

                        @if ($booking->status_pembayaran === 'Lunas')
                            <input type="hidden" name="status_pembayaran" value="Lunas">
                            <div
                                class="flex items-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                                <span class="material-symbols-outlined text-emerald-600 text-[18px]">lock</span>
                                <span class="text-sm font-bold text-emerald-700">Lunas</span>
                                <span
                                    class="ml-auto text-xs text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full font-semibold">Terkunci</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5">Status pembayaran tidak dapat diubah setelah
                                Lunas.</p>
                        @else
                            <select name="status_pembayaran" id="modal-select-pembayaran"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                <option value="Belum Bayar"
                                    {{ $booking->status_pembayaran == 'Belum Bayar' ? 'selected' : '' }}>Belum Bayar
                                </option>
                                <option value="DP" {{ $booking->status_pembayaran == 'DP' ? 'selected' : '' }}>DP
                                    (Dibayar
                                    Sebagian)</option>
                                <option value="Lunas">Lunas</option>
                            </select>
                        @endif
                    </div>

                    {{-- ── Input Nominal DP ── --}}
                    @if ($booking->status_pembayaran !== 'Lunas')
                        <div id="modal-wrap-dp" class="{{ $booking->status_pembayaran === 'DP' ? '' : 'hidden' }}">
                            <label class="block font-semibold text-sm text-slate-700 mb-2">
                                Nominal DP Dibayarkan <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">Rp</span>
                                <input type="number" name="dp_amount" id="modal-input-dp" min="0"
                                    max="{{ $booking->total_harga }}"
                                    value="{{ old('dp_amount', $booking->dp_amount ?? 0) }}"
                                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all" />
                            </div>
                            <p class="text-xs text-slate-400 mt-1">
                                Maks: Rp {{ number_format($booking->total_harga, 0, ',', '.') }}
                            </p>
                        </div>
                    @endif

                    {{-- ── Metode Bayar ── --}}
                    <div id="modal-wrap-metode"
                        class="{{ in_array($booking->status_pembayaran, ['DP', 'Lunas']) ? '' : 'hidden' }}">
                        <label class="block font-semibold text-sm text-slate-700 mb-2">Metode Bayar</label>

                        @if ($booking->status_pembayaran === 'Lunas')
                            @php
                                $metodeTampil = $lastPayment?->metode_bayar ?? null;
                                $metodeLabel = match ($metodeTampil) {
                                    'tunai' => 'Tunai',
                                    'transfer' => 'Transfer Bank',
                                    'qris' => 'QRIS',
                                    'lainnya' => 'Lainnya',
                                    default => 'Tidak dicatat',
                                };
                                $metodeIcon = match ($metodeTampil) {
                                    'tunai' => 'payments',
                                    'transfer' => 'account_balance',
                                    'qris' => 'qr_code_2',
                                    default => 'help_outline',
                                };
                            @endphp
                            <input type="hidden" name="metode_bayar" value="{{ $metodeTampil }}">
                            <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl">
                                <span
                                    class="material-symbols-outlined text-slate-400 text-[18px]">{{ $metodeIcon }}</span>
                                <span class="text-sm font-semibold text-slate-700">{{ $metodeLabel }}</span>
                                <span
                                    class="ml-auto text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full font-semibold">Terkunci</span>
                            </div>
                        @else
                            <select name="metode_bayar" id="modal-select-metode"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                <option value="">Pilih metode bayar...</option>
                                <option value="tunai" {{ $lastPayment?->metode_bayar === 'tunai' ? 'selected' : '' }}>
                                    Tunai</option>
                                <option value="transfer"
                                    {{ $lastPayment?->metode_bayar === 'transfer' ? 'selected' : '' }}>Transfer Bank
                                </option>
                                <option value="qris" {{ $lastPayment?->metode_bayar === 'qris' ? 'selected' : '' }}>
                                    QRIS</option>
                                <option value="lainnya"
                                    {{ $lastPayment?->metode_bayar === 'lainnya' ? 'selected' : '' }}>
                                    Lainnya</option>
                            </select>
                        @endif
                    </div>

                    {{-- ── Status Rental ── --}}
                    <div>
                        <label class="block font-semibold text-sm text-slate-700 mb-2">Status Operasional (Rental)</label>

                        @if (in_array($booking->status_rental, ['Selesai', 'Batal']))
                            <input type="hidden" name="status_rental" value="{{ $booking->status_rental }}">
                            <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl">
                                <span class="material-symbols-outlined text-slate-600 text-[18px]">lock</span>
                                <span class="text-sm font-bold text-slate-700">{{ $booking->status_rental }}</span>
                                <span
                                    class="ml-auto text-xs text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full font-semibold">Terkunci</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5">Status operasional tidak dapat diubah karena
                                pesanan telah dikunci.</p>
                        @else
                            <select name="status_rental"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                <option value="Menunggu" {{ $booking->status_rental == 'Menunggu' ? 'selected' : '' }}>
                                    Menunggu Diambil (Standby)</option>
                                <option value="Aktif" {{ $booking->status_rental == 'Aktif' ? 'selected' : '' }}>Aktif
                                    (Mobil Sedang Disewa)</option>
                                <option value="Dikembalikan"
                                    {{ $booking->status_rental == 'Dikembalikan' ? 'selected' : '' }}>Dikembalikan (Mobil
                                    di Garasi)</option>
                                <option value="Batal" {{ $booking->status_rental == 'Batal' ? 'selected' : '' }}>
                                    Dibatalkan</option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                                <strong class="text-amber-600">Perhatian:</strong> Mengubah ke
                                <strong>Aktif</strong> akan mengunci edit data. Mengubah ke <strong>Batal</strong> akan
                                membatalkan pesanan secara permanen.
                            </p>
                        @endif
                    </div>

                </div>

                <div class="mt-8 pt-4 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button"
                        onclick="document.getElementById('modal-update-status').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-100 transition-all active:scale-95">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 🟢 FITUR BARU: MODAL PROSES REFUND ═══════════ --}}
    @if ($isBatalRefundable)
        <div id="modal-proses-refund"
            class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 transition-all">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-purple-100 flex justify-between items-center bg-purple-50">
                    <h3 class="text-lg font-bold text-purple-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-purple-600">currency_exchange</span> Proses Refund
                        Dana
                    </h3>
                    <button type="button"
                        onclick="document.getElementById('modal-proses-refund').classList.add('hidden')"
                        class="text-purple-400 hover:text-purple-600 bg-white hover:bg-purple-100 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                {{-- Asumsi routing baru: admin.bookings.process_refund --}}
                <form action="{{ route('admin.bookings.process_refund', $booking->id) }}" method="POST"
                    class="p-6">
                    @csrf
                    <div class="space-y-5">

                        <div class="p-3 bg-purple-50 border border-purple-100 rounded-xl">
                            <p class="text-xs text-purple-600 font-semibold mb-1">Total Dana Masuk Pelanggan:</p>
                            <p class="text-xl font-black text-purple-800">Rp
                                {{ number_format($booking->dp_amount, 0, ',', '.') }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-sm text-slate-700 mb-2">
                                Nominal Refund (Dikembalikan) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">Rp</span>

                                {{-- Input text dengan class "format-ribuan" untuk dimanipulasi oleh JS --}}
                                <input type="text" id="formatted-refund-amount" required
                                    data-max="{{ (int) ($booking->dp_amount - $refundAmount) }}"
                                    value="{{ number_format((int) ($booking->dp_amount - $refundAmount), 0, '', '.') }}"
                                    class="format-ribuan w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all" />

                                {{-- Hidden input yang akan dikirim ke server (diisi otomatis oleh JS) --}}
                                <input type="hidden" name="refund_amount" id="real-refund-amount"
                                    value="{{ (int) ($booking->dp_amount - $refundAmount) }}" />
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Sisa yang bisa direfund: Rp
                                {{ number_format($booking->dp_amount - $refundAmount, 0, ',', '.') }}</p>
                            <p id="refund-error-msg" class="text-[11px] text-red-500 mt-1 hidden">Nominal melebihi batas
                                maksimal refund!</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-sm text-slate-700 mb-2">Metode Pengembalian</label>
                            <select name="refund_method" required
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                                <option value="">Pilih metode transfer...</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="tunai">Tunai Kasir</option>
                                <option value="qris">E-Wallet / QRIS</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-sm text-slate-700 mb-2">Catatan (Opsional)</label>
                            <textarea name="refund_notes" rows="2" placeholder="Misal: Dipotong biaya admin 50rb"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500"></textarea>
                        </div>

                    </div>

                    <div class="mt-8 pt-4 border-t border-slate-100 flex justify-end gap-3">
                        <button type="button"
                            onclick="document.getElementById('modal-proses-refund').classList.add('hidden')"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow-md shadow-purple-100 transition-all active:scale-95">
                            Simpan Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal tanda tangan (dibutuhkan oleh tombol Tanda Tangan & Unduh) --}}
    @if ($invoice)
        @include('components.signature-modal', ['invoice' => $invoice])
    @endif
@endsection

@push('scripts')
    <script>
        // Logika Slider Lock (Geser untuk Kunci Pesanan)
        const lockSlider = document.getElementById('lock-slider');
        const lockThumb = document.getElementById('lock-thumb');
        const lockTrack = document.getElementById('lock-track');

        if (lockSlider) {
            lockSlider.addEventListener('input', function() {
                const maxMove = this.clientWidth - 56; // 44px thumb + 12px padding
                const move = (this.value / 100) * maxMove;
                lockThumb.style.transform = `translateX(${move}px)`;
                lockTrack.style.width = `calc(${move}px + 56px)`;

                if (this.value > 85) {
                    lockThumb.innerHTML = '<span class="material-symbols-outlined text-[24px]">lock</span>';
                    lockThumb.classList.replace('text-emerald-600', 'text-red-500');
                    lockTrack.classList.replace('bg-emerald-500', 'bg-red-500');
                } else {
                    lockThumb.innerHTML = '<span class="material-symbols-outlined text-[24px]">double_arrow</span>';
                    lockThumb.classList.replace('text-red-500', 'text-emerald-600');
                    lockTrack.classList.replace('bg-red-500', 'bg-emerald-500');
                }
            });

            lockSlider.addEventListener('change', function() {
                if (this.value < 100) {
                    this.value = 0;
                    lockThumb.style.transform = `translateX(0px)`;
                    lockTrack.style.width = '56px';
                    lockThumb.innerHTML = '<span class="material-symbols-outlined text-[24px]">double_arrow</span>';
                    lockThumb.classList.replace('text-red-500', 'text-emerald-600');
                    lockTrack.classList.replace('bg-red-500', 'bg-emerald-500');
                    lockThumb.style.transition = 'all 0.3s ease';
                    lockTrack.style.transition = 'all 0.3s ease';
                } else {
                    document.getElementById('form-lock').submit();
                    lockSlider.disabled = true;
                }
            });

            lockSlider.addEventListener('mousedown', () => {
                lockThumb.style.transition = 'none';
                lockTrack.style.transition = 'none';
            });
            lockSlider.addEventListener('touchstart', () => {
                lockThumb.style.transition = 'none';
                lockTrack.style.transition = 'none';
            });
        }
    </script>
    @vite('resources/js/booking-show.js')
    @if ($invoice)
        @vite('resources/js/invoice-signature.js')
    @endif
@endpush