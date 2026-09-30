{{-- resources/views/armada/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Manajemen Armada')

@section('content')
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">

        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Manajemen Armada</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola seluruh armada kendaraan dan pantau kondisi unit.</p>
            </div>

            <a href="{{ route('admin.armada.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-md shadow-blue-100 active:scale-95">
                <span class="material-symbols-outlined text-[20px]">add</span>
                Tambah Mobil
            </a>
        </div>

        <div class="py-2 space-y-5">

            {{-- 🟢 PERUBAHAN: Stats Cards (Grid disesuaikan jadi 5 kolom untuk layar besar) --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total</span>
                        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">{{ $total }}</p>
                    <p class="text-xs text-gray-400 mt-1">Armada</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tersedia</span>
                        <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-emerald-600">{{ $tersedia }}</p>
                    <p class="text-xs text-gray-400 mt-1">Siap disewa</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Disewa</span>
                        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-blue-600">{{ $disewa }}</p>
                    <p class="text-xs text-gray-400 mt-1">Sedang berjalan</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Bengkel</span>
                        <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-amber-600">{{ $bengkel }}</p>
                    <p class="text-xs text-gray-400 mt-1">Dalam perawatan</p>
                </div>

                {{-- 🟢 TAMBAHAN STATS UNTUK NONAKTIF --}}
                <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nonaktif</span>
                        <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-red-600">{{ $nonaktif ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">Pensiun/Ditarik</p>
                </div>
            </div>

            {{-- Service Due Alert --}}
            @if ($serviceDueCount > 0)
                <a href="{{ route('admin.armada', ['servis_due' => 1]) }}"
                    class="flex items-center gap-4 p-4 rounded-2xl border transition-all group {{ request('servis_due') ? 'bg-orange-100 border-orange-300 shadow-inner' : 'bg-orange-50 border-orange-200 hover:bg-orange-100 shadow-sm' }}">

                    <div
                        class="w-10 h-10 bg-orange-100/80 border border-orange-200/50 rounded-xl flex-shrink-0 flex items-center justify-center group-hover:bg-white transition-colors">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-orange-800">
                            {{ $serviceDueCount }} Mobil Perlu Servis Komponen
                        </p>
                        <p class="text-xs font-medium text-orange-600 mt-0.5">
                            Ada kendaraan yang komponennya melampaui batas keausan. Klik untuk melihat daftar.
                        </p>
                    </div>
                    <span
                        class="material-symbols-outlined text-orange-400 group-hover:text-orange-600 group-hover:translate-x-1 transition-all">
                        chevron_right
                    </span>
                </a>
            @endif

            {{-- Filter & Search --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                <form action="{{ route('admin.armada') }}" method="GET" id="filterForm">
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="relative flex-1">
                            <span
                                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari plat nomor, merk, atau model..."
                                class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-slate-50/50 transition-all">
                        </div>

                        <div class="relative" id="filterContainer">
                            @php
                                $hasFilter =
                                    request('status') || request('merk') || request('tahun') || request('servis_due');
                            @endphp

                            <div
                                class="w-full md:w-auto flex items-stretch border rounded-xl shadow-sm transition-all {{ $hasFilter ? 'bg-blue-50 border-blue-200' : 'bg-white border-slate-200 hover:bg-slate-50' }}">
                                <button type="button" onclick="toggleFilter()"
                                    class="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold transition-transform active:scale-95 {{ $hasFilter ? 'text-blue-700 rounded-l-xl' : 'text-slate-600 rounded-xl' }}">
                                    <span class="material-symbols-outlined text-[20px]">
                                        {{ $hasFilter ? 'filter_list_alt' : 'filter_list' }}
                                    </span>
                                    Filter
                                </button>
                                @if ($hasFilter)
                                    <div class="w-px bg-blue-200 my-2"></div>
                                    <a href="{{ route('admin.armada') }}"
                                        class="flex items-center justify-center px-3 text-blue-400 hover:text-red-600 hover:bg-blue-100 rounded-r-xl transition-colors"
                                        title="Hapus Filter">
                                        <span class="material-symbols-outlined text-[18px]">close</span>
                                    </a>
                                @endif
                            </div>

                            <div id="filterDropdown"
                                class="hidden absolute right-0 mt-3 w-[min(320px,calc(100vw-2rem))] bg-white rounded-2xl shadow-2xl border border-slate-100 z-[60] p-6">
                                <div class="flex items-center justify-between mb-5">
                                    <h4 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Multiple Filters</h4>
                                    <span class="material-symbols-outlined text-slate-400 text-sm">tune</span>
                                </div>

                                <div class="space-y-5">
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Status
                                            Armada</label>
                                        <select name="status"
                                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors">
                                            <option value="">Semua Status</option>
                                            <option value="Tersedia" {{ request('status') == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                                            <option value="Sedang Disewa" {{ request('status') == 'Sedang Disewa' ? 'selected' : '' }}>Sedang Disewa</option>
                                            <option value="Masuk Bengkel" {{ request('status') == 'Masuk Bengkel' ? 'selected' : '' }}>Masuk Bengkel</option>
                                            {{-- 🟢 TAMBAHAN OPSI FILTER NONAKTIF --}}
                                            <option value="Nonaktif" {{ request('status') == 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Kondisi
                                            Perawatan</label>
                                        <select name="servis_due"
                                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors">
                                            <option value="">Semua Kondisi</option>
                                            <option value="1" {{ request('servis_due') == '1' ? 'selected' : '' }}>
                                                Perlu Servis Komponen</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Merk
                                            Kendaraan</label>
                                        <select name="merk"
                                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors">
                                            <option value="">Semua Merk</option>
                                            @foreach (['Toyota', 'Honda', 'Mitsubishi', 'Daihatsu', 'Suzuki', 'Nissan'] as $merk)
                                                <option value="{{ $merk }}"
                                                    {{ request('merk') == $merk ? 'selected' : '' }}>{{ $merk }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Tahun</label>
                                        <select name="tahun"
                                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors">
                                            <option value="">Semua Tahun</option>
                                            @for ($y = date('Y'); $y >= 2010; $y--)
                                                <option value="{{ $y }}"
                                                    {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 mt-8 pt-5 border-t border-slate-100">
                                    <a href="{{ route('admin.armada') }}"
                                        class="flex-1 text-center py-2.5 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors">
                                        Reset
                                    </a>
                                    <button type="submit"
                                        class="flex-1 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md shadow-blue-200">
                                        Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                            class="hidden md:block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-blue-100 active:scale-95">
                            Cari
                        </button>
                    </div>
                </form>
            </div>

            {{-- Vehicle Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
                @foreach ($cars as $car)
                    @php
                        // Logika Kalkulasi Keausan Komponen BARU
                        $is_service_due = false;
                        $max_percentage = 0;
                        $critical_parts = [];

                        if ($car->parts && $car->parts->count() > 0) {
                            foreach ($car->parts as $part) {
                                if ($part->is_track_km && $part->lifespan_km > 0) {
                                    $km_berjalan = max(0, $car->km_saat_ini - $part->last_service_km);
                                    $persentase = ($km_berjalan / $part->lifespan_km) * 100;

                                    if ($persentase > $max_percentage) {
                                        $max_percentage = $persentase;
                                    }

                                    // Jika aus 100% atau lebih, masukkan ke daftar komponen kritis
                                    if ($persentase >= 100) {
                                        $is_service_due = true;
                                        $critical_parts[] = $part->nama_part;
                                    }
                                }
                            }
                        }

                        // 🟢 PERUBAHAN: Konfigurasi Tema Berdasarkan Status termasuk Nonaktif
                        if ($car->status == 'Masuk Bengkel') {
                            $theme = ['bg' => 'bg-slate-500', 'icon' => 'build', 'border' => 'border-slate-200'];
                            $imgClass = 'grayscale opacity-80';
                        } elseif ($car->status == 'Sedang Disewa') {
                            $theme = ['bg' => 'bg-blue-500', 'icon' => 'car_rental', 'border' => 'border-blue-200'];
                            $imgClass = '';
                        } elseif ($car->status == 'Nonaktif') {
                            $theme = ['bg' => 'bg-red-500', 'icon' => 'block', 'border' => 'border-red-200'];
                            $imgClass = 'grayscale opacity-50'; // Bikin fotonya pudar dan hitam putih
                        } else {
                            $theme = [
                                'bg' => 'bg-emerald-500',
                                'icon' => 'check_circle',
                                'border' => $is_service_due
                                    ? 'border-orange-300 shadow-orange-100'
                                    : 'border-slate-200',
                            ];
                            $imgClass = '';
                        }
                    @endphp

                    <a href="{{ route('admin.armada.show', $car->id) }}"
                        class="bg-white rounded-2xl border {{ $theme['border'] }} shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group flex flex-col cursor-pointer">

                        {{-- Header Foto & Status --}}
                        <div class="p-3 pb-0">
                            <div class="h-40 bg-slate-100 relative overflow-hidden rounded-lg flex-shrink-0">
                                @if ($car->foto)
                                    <img src="{{ asset('storage/' . $car->foto) }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 {{ $imgClass }}">
                                @else
                                    <div
                                        class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 bg-slate-100">
                                        <span
                                            class="material-symbols-outlined text-[48px] opacity-40 mb-2">no_photography</span>
                                        <span class="text-[10px] font-bold uppercase tracking-widest">Belum Ada Foto</span>
                                    </div>
                                @endif

                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>

                                {{-- Badge Status --}}
                                <div
                                    class="absolute top-2 right-2 {{ $theme['bg'] }} text-white text-[10px] font-extrabold px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-md uppercase tracking-wide">
                                    <span class="material-symbols-outlined text-[14px]">{{ $theme['icon'] }}</span>
                                    {{ $car->status }}
                                </div>

                                {{-- Peringatan Servis (Sembunyikan kalau Nonaktif atau Di Bengkel) --}}
                                @if ($is_service_due && !in_array($car->status, ['Masuk Bengkel', 'Nonaktif']))
                                    <span
                                        class="absolute top-2 left-2 bg-orange-500 text-white text-[10px] font-extrabold px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-md uppercase tracking-wide animate-pulse">
                                        <span class="material-symbols-outlined text-[14px]">warning</span> Servis!
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Body Konten --}}
                        <div class="p-5 flex-1 flex flex-col">
                            {{-- Merk & Model --}}
                            <div class="mb-4">
                                <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-0.5">
                                    {{ $car->merk }}
                                </p>
                                <h3 class="font-extrabold text-slate-900 text-lg leading-tight line-clamp-1"
                                    title="{{ $car->merk }} {{ $car->model }}">
                                    {{ $car->model ?? 'Model Belum Diatur' }}
                                </h3>
                            </div>

                            {{-- Atribut Kendaraan --}}
                            <div class="flex flex-wrap gap-2 mb-5">
                                <div
                                    class="flex items-center gap-1 bg-slate-50 border border-slate-200 px-2 py-1 rounded-lg text-xs font-bold text-slate-600">
                                    <span class="material-symbols-outlined text-[16px] text-slate-400">pin</span>
                                    {{ strtoupper($car->plat_nomor) }}
                                </div>
                                <div
                                    class="flex items-center gap-1 bg-slate-50 border border-slate-200 px-2 py-1 rounded-lg text-xs font-bold text-slate-600">
                                    <span
                                        class="material-symbols-outlined text-[16px] text-slate-400">calendar_today</span>
                                    {{ $car->tahun }}
                                </div>
                                <div
                                    class="flex items-center gap-1 bg-slate-50 border border-slate-200 px-2 py-1 rounded-lg text-xs font-bold text-slate-600">
                                    <span
                                        class="material-symbols-outlined text-[16px] text-slate-400">airline_seat_recline_normal</span>
                                    {{ $car->kapasitas }} Seat
                                </div>
                                @if ($car->warna)
                                    <div
                                        class="flex items-center gap-1 bg-slate-50 border border-slate-200 px-2 py-1 rounded-lg text-xs font-bold text-slate-600">
                                        <span class="material-symbols-outlined text-[16px] text-slate-400">palette</span>
                                        {{ $car->warna }}
                                    </div>
                                @endif
                            </div>

                            {{-- Area Bawah (Berubah sesuai kondisi) --}}
                            <div class="mt-auto">
                                @if ($car->status == 'Sedang Disewa')
                                    <div class="bg-blue-50 rounded-xl p-3 mb-4 border border-blue-100">
                                        <div class="flex justify-between items-center">
                                            <span
                                                class="text-[10px] font-bold text-blue-500 uppercase tracking-wider flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">info</span> Status
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-blue-700 mt-1">Kendaraan Sedang Diluar</p>
                                        @if ($car->activeBooking)
                                            <span class="mt-1.5 flex items-center gap-1 text-[10px] font-bold text-blue-600">
                                                <span class="material-symbols-outlined text-[13px]">receipt_long</span>
                                                Pesanan #{{ $car->activeBooking->nomor_pesanan }}
                                            </span>
                                        @else
                                            <p class="text-[10px] text-blue-500 mt-0.5">Mobil sedang digunakan oleh
                                                pelanggan.</p>
                                        @endif
                                    </div>
                                @elseif ($car->status == 'Masuk Bengkel')
                                    <div class="bg-slate-50 rounded-xl p-3 mb-4 border border-slate-200">
                                        <div class="flex justify-between items-center">
                                            <span
                                                class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">build_circle</span>
                                                Status
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-700 mt-1">Dalam Perawatan Bengkel</p>
                                        <p class="text-[10px] text-slate-500 mt-0.5">Mobil tidak dapat disewa sementara.
                                        </p>
                                    </div>
                                {{-- 🟢 TAMBAHAN: INFO BOX UNTUK MOBIL NONAKTIF --}}
                                @elseif ($car->status == 'Nonaktif')
                                    <div class="bg-red-50 rounded-xl p-3 mb-4 border border-red-200">
                                        <div class="flex justify-between items-center">
                                            <span
                                                class="text-[10px] font-bold text-red-500 uppercase tracking-wider flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">block</span>
                                                Status
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-red-700 mt-1">Kendaraan Nonaktif</p>
                                        <p class="text-[10px] text-red-500 mt-0.5">Mobil dipensiunkan atau tidak beroperasi.
                                        </p>
                                    </div>
                                @else
                                    {{-- Tampilan Odometer & Info Komponen --}}
                                    @php
                                        $barColor =
                                            $max_percentage >= 100
                                                ? 'bg-orange-500'
                                                : ($max_percentage >= 80
                                                    ? 'bg-amber-400'
                                                    : 'bg-emerald-500');
                                        $bgBox =
                                            $max_percentage >= 100
                                                ? 'bg-orange-50 border-orange-200'
                                                : 'bg-slate-50 border-slate-100';
                                    @endphp
                                    <div class="{{ $bgBox }} rounded-xl p-3 mb-4 border transition-colors">
                                        <div class="flex justify-between items-center mb-2">
                                            <span
                                                class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">speed</span> Odometer
                                            </span>
                                            <span
                                                class="text-xs font-extrabold text-slate-800">{{ number_format($car->km_saat_ini, 0, ',', '.') }}
                                                km</span>
                                        </div>

                                        {{-- Tampilkan Progress Bar dari part yang paling aus --}}
                                        @if ($car->parts && $car->parts->where('is_track_km', true)->count() > 0)
                                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                <div class="{{ $barColor }} h-1.5 rounded-full"
                                                    style="width: {{ min(100, $max_percentage) }}%"></div>
                                            </div>
                                            <div class="flex justify-between mt-2">
                                                <span class="text-[10px] font-medium text-slate-400">
                                                    Keausan tertinggi: {{ round(min(100, $max_percentage)) }}%
                                                </span>
                                                @if ($is_service_due)
                                                    <span
                                                        class="text-[10px] font-bold text-orange-600 animate-pulse truncate max-w-[120px]"
                                                        title="{{ implode(', ', $critical_parts) }}">
                                                        ⚠ {{ $critical_parts[0] }}
                                                        @if (count($critical_parts) > 1)
                                                            +{{ count($critical_parts) - 1 }}
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="mt-2 text-[10px] text-slate-400 italic">Belum ada komponen yang
                                                dilacak.</div>
                                        @endif
                                    </div>
                                @endif

                            </div>
                        </div>
                    </a>
                @endforeach

                {{-- Card Statis: Tambah Mobil Baru (Hanya muncul di halaman terakhir) --}}
                @if ($cars->currentPage() == $cars->lastPage())
                    <a href="{{ route('admin.armada.create') }}"
                        class="hidden xl:flex bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 items-center justify-center min-h-[320px] hover:border-blue-300 hover:bg-blue-50 transition-colors duration-200 cursor-pointer group">
                        <div class="text-center">
                            <div
                                class="w-12 h-12 bg-gray-200 group-hover:bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2 transition-colors">
                                <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-500 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-400 group-hover:text-blue-500 transition-colors">Tambah
                                Mobil Baru</p>
                        </div>
                    </a>
                @endif
            </div>

            {{-- Footer / Pagination --}}
            @if ($cars->hasPages())
                <div
                    class="px-6 py-4 flex flex-col sm:flex-row gap-4 items-center justify-between border-t border-slate-200 bg-white rounded-b-2xl mt-auto">
                    <div class="text-sm text-slate-500">
                        Menampilkan <span class="font-medium text-slate-900">{{ $cars->firstItem() ?? 0 }}</span> sampai
                        <span class="font-medium text-slate-900">{{ $cars->lastItem() ?? 0 }}</span> dari <span
                            class="font-medium text-slate-900">{{ $cars->total() }}</span> entri
                    </div>
                    <div class="flex items-center gap-1">
                        @foreach ($cars->linkCollection() as $link)
                            @if ($link['url'] == null)
                                @if (str_contains($link['label'], '...'))
                                    <span class="px-2 text-slate-400">...</span>
                                @else
                                    <span class="p-1 text-slate-300 cursor-not-allowed flex items-center justify-center">
                                        @if (str_contains($link['label'], 'Previous'))
                                            <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                                        @else
                                            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                                        @endif
                                    </span>
                                @endif
                            @elseif ($link['active'])
                                <span
                                    class="w-8 h-8 rounded-md bg-blue-600 text-white font-medium text-sm flex items-center justify-center">
                                    {{ $link['label'] }}
                                </span>
                            @else
                                @if (str_contains($link['label'], 'Previous'))
                                    <a href="{{ $link['url'] }}"
                                        class="p-1 text-slate-400 hover:text-blue-600 transition-colors flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                                    </a>
                                @elseif (str_contains($link['label'], 'Next'))
                                    <a href="{{ $link['url'] }}"
                                        class="p-1 text-slate-400 hover:text-blue-600 transition-colors flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                                    </a>
                                @else
                                    <a href="{{ $link['url'] }}"
                                        class="w-8 h-8 rounded-md hover:bg-slate-100 text-slate-600 font-medium text-sm flex items-center justify-center transition-colors">
                                        {{ $link['label'] }}
                                    </a>
                                @endif
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                <div
                    class="px-6 py-4 flex items-center justify-between border-t border-slate-200 bg-white rounded-b-2xl mt-auto">
                    <span class="text-sm text-slate-500">
                        Menampilkan <span class="font-medium text-slate-900">{{ $cars->count() }}</span> entri
                    </span>
                </div>
            @endif
        </div>

    </div>

    <script>
        function toggleFilter() {
            const dropdown = document.getElementById('filterDropdown');
            dropdown.classList.toggle('hidden');
        }

        window.addEventListener('click', function(e) {
            const container = document.getElementById('filterContainer');
            const dropdown = document.getElementById('filterDropdown');

            if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
@endsection