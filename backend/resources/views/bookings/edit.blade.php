@extends('layouts.app')

@section('content')
    <div class="px-4 pb-48 lg:px-8 lg:pb-24 xl:pb-8 max-w-7xl mx-auto w-full">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 text-sm text-slate-400 mb-3">
                <a href="{{ route('admin.bookings') }}" class="hover:text-blue-600 transition-colors">Pesanan</a>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                <span class="text-slate-600 font-medium">Edit Pesanan</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Pesanan #{{ $booking->id }}</h1>
            <p class="text-sm text-slate-500 mt-1">Perbarui detail pelanggan dan informasi sewa kendaraan.</p>
        </div>

        {{-- Alert Error Validasi --}}
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3">
                <span class="material-symbols-outlined text-red-500 mt-0.5 shrink-0">error</span>
                <div>
                    <p class="font-semibold text-red-700 text-sm mb-1">Terdapat kesalahan input:</p>
                    <ul class="list-disc list-inside text-sm text-red-600 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST" id="bookingForm" data-mode="edit"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Data semua mobil untuk JS modal picker --}}
            <script id="all-cars-json" type="application/json">
                [
                    @foreach ($availableCars as $car)
                    {
                        "id":            {{ $car->id }},
                        "merk":          "{{ addslashes($car->merk) }}",
                        "model":         "{{ addslashes($car->model) }}",
                        "plat":          "{{ addslashes($car->plat_nomor) }}",
                        "harga":         {{ $car->harga_default }},
                        "harga_kontrak": {{ $car->harga_kontrak ?? $car->harga_default }},
                        "foto":          "{{ $car->foto ? asset('storage/' . $car->foto) : '' }}",
                        "status":        "{{ addslashes($car->status) }}",
                        "tahun":         "{{ $car->tahun }}",
                        "servis_due":    "{{ $car->servis_due ?? '' }}"
                    }{{ !$loop->last ? ',' : '' }}
                    @endforeach
                ]
            </script>

            {{-- Data prefill pesanan ini untuk JS (kendaraan & addon tersimpan) --}}
            <script id="booking-prefill" type="application/json">
                {
                    "cars": [
                        @foreach($booking->bookingCars as $bc)
                        {
                            "car_id": {{ $bc->car_id }},
                            "harga":  {{ $bc->harga_dasar_per_hari }},
                            "foto_kondisi": {!! json_encode($bc->foto_kondisi ?? []) !!}
                        }{{ !$loop->last ? ',' : '' }}
                        @endforeach
                    ],
                    "addons": [
                        @foreach($booking->addons as $addon)
                        {
                            "nama":    "{{ addslashes($addon->nama_biaya) }}",
                            "nominal": {{ $addon->nominal }}
                        }{{ !$loop->last ? ',' : '' }}
                        @endforeach
                    ]
                }
            </script>

            {{-- Hidden fields untuk data terkalkulasi --}}
            <input type="hidden" name="total_harga" id="input_total_harga" value="{{ $booking->total_harga }}">
            <input type="hidden" name="total_addons" id="input_total_addons" value="0">

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

                {{-- ===================== KOLOM KIRI ===================== --}}
                <div class="xl:col-span-8 flex flex-col gap-6">

                    {{-- Section 1: Data Pelanggan --}}
                    <section class="bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                        <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
                            <span class="material-symbols-outlined text-blue-600">person</span>
                            <h3 class="text-lg font-semibold text-slate-800">Data Pelanggan</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            {{-- Nama Pelanggan --}}
                            <div class="col-span-1 md:col-span-2">
                                <label class="block font-medium text-sm text-slate-600 mb-1">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input name="nama_pelanggan" value="{{ old('nama_pelanggan', $booking->nama_pelanggan) }}"
                                    class="w-full bg-slate-50 border @error('nama_pelanggan') border-red-400 @else border-slate-200 @enderror rounded-lg px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                    placeholder="Masukkan nama pelanggan" type="text" required />
                                @error('nama_pelanggan')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- No HP --}}
                            <div>
                                <label class="block font-medium text-sm text-slate-600 mb-1">
                                    No. Telepon / WhatsApp <span class="text-red-500">*</span>
                                </label>
                                <input name="kontak_pelanggan"
                                    value="{{ old('kontak_pelanggan', $booking->kontak_pelanggan) }}"
                                    class="w-full bg-slate-50 border @error('kontak_pelanggan') border-red-400 @else border-slate-200 @enderror rounded-lg px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                    placeholder="Contoh: 081234567890" type="tel" required />
                                @error('kontak_pelanggan')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="block font-medium text-sm text-slate-600 mb-1">
                                    Email <span class="text-slate-400 font-normal text-xs">(Opsional)</span>
                                </label>
                                <input name="email" value="{{ old('email', $booking->email) }}"
                                    class="w-full bg-slate-50 border @error('email') border-red-400 @else border-slate-200 @enderror rounded-lg px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                    placeholder="contoh@email.com" type="email" />
                                @error('email')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- Upload KTP --}}
                            <div class="col-span-1 md:col-span-2 pt-3 border-t border-slate-100">
                                <label class="block font-medium text-sm text-slate-600 mb-2">
                                    Foto KTP Penyewa <span class="text-slate-400 font-normal text-xs">(Opsional)</span>
                                </label>

                                {{-- CONTAINER UTAMA (GRID 4 KOLOM) UNTUK FOTO LAMA & BARU --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">

                                    {{-- Card Preview Foto Lama --}}
                                    @if (is_array($booking->foto_ktp) && count($booking->foto_ktp) > 0)
                                        @foreach ($booking->foto_ktp as $path)
                                            <div
                                                class="existing-file-card relative flex items-center gap-3 p-2 border border-slate-200 rounded-lg bg-white shadow-sm w-full transition-all">

                                                {{-- Ikon Merah --}}
                                                <a href="{{ asset('storage/' . $path) }}" target="_blank"
                                                    class="shrink-0 w-8 h-8 bg-red-50 text-red-500 rounded-md border border-red-100 flex items-center justify-center hover:bg-red-100 transition-colors"
                                                    title="Buka Gambar">
                                                    <span class="material-symbols-outlined text-[18px]">image</span>
                                                </a>

                                                {{-- Info Teks --}}
                                                <div class="flex-1 overflow-hidden">
                                                    <a href="{{ asset('storage/' . $path) }}" target="_blank"
                                                        class="block text-xs font-semibold text-slate-700 truncate hover:text-blue-600 hover:underline"
                                                        title="Buka Gambar: {{ basename($path) }}">
                                                        {{ basename($path) }}
                                                    </a>
                                                    {{-- Tambahan teks agar tingginya sejajar dengan foto baru --}}
                                                    <span
                                                        class="block text-[10px] text-slate-400 font-medium mt-0.5">Tersimpan</span>
                                                </div>

                                                {{-- Tombol Silang --}}
                                                <button type="button"
                                                    onclick="hapusExistingFoto(this, 'ktp', '{{ $path }}')"
                                                    class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                                                    title="Hapus foto ini">
                                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                                </button>
                                            </div>
                                        @endforeach
                                    @endif

                                    {{-- Tempat Preview File Baru (Pakai class 'contents' agar nyatu dengan Grid bapaknya) --}}
                                    <div id="new-preview-ktp" class="contents"></div>

                                </div>

                                <input id="input-foto-ktp" name="foto_ktp[]" type="file" multiple accept="image/*"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all bg-slate-50 border border-slate-200 rounded-lg" />
                                @error('foto_ktp.*')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-[11px] text-slate-400 mt-1">Upload foto baru akan otomatis ditambahkan ke
                                    daftar foto di atas.</p>
                            </div>

                            {{-- Upload Foto Penyewa --}}
                            <div class="col-span-1 md:col-span-2 pb-2 mt-2">
                                <label class="block font-medium text-sm text-slate-600 mb-2">
                                    Foto Diri Penyewa <span class="text-slate-400 font-normal text-xs">(Opsional)</span>
                                </label>

                                {{-- CONTAINER UTAMA (GRID 4 KOLOM) UNTUK FOTO LAMA & BARU --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">

                                    {{-- Card Preview Foto Lama --}}
                                    @if (is_array($booking->foto_penyewa) && count($booking->foto_penyewa) > 0)
                                        @foreach ($booking->foto_penyewa as $path)
                                            <div
                                                class="existing-file-card relative flex items-center gap-3 p-2 border border-slate-200 rounded-lg bg-white shadow-sm w-full transition-all">

                                                {{-- Ikon Merah --}}
                                                <a href="{{ asset('storage/' . $path) }}" target="_blank"
                                                    class="shrink-0 w-8 h-8 bg-red-50 text-red-500 rounded-md border border-red-100 flex items-center justify-center hover:bg-red-100 transition-colors"
                                                    title="Buka Gambar">
                                                    <span class="material-symbols-outlined text-[18px]">image</span>
                                                </a>

                                                {{-- Info Teks --}}
                                                <div class="flex-1 overflow-hidden">
                                                    <a href="{{ asset('storage/' . $path) }}" target="_blank"
                                                        class="block text-xs font-semibold text-slate-700 truncate hover:text-blue-600 hover:underline"
                                                        title="Buka Gambar: {{ basename($path) }}">
                                                        {{ basename($path) }}
                                                    </a>
                                                    {{-- Tambahan teks agar tingginya sejajar dengan foto baru --}}
                                                    <span
                                                        class="block text-[10px] text-slate-400 font-medium mt-0.5">Tersimpan</span>
                                                </div>

                                                {{-- Tombol Silang --}}
                                                <button type="button"
                                                    onclick="hapusExistingFoto(this, 'penyewa', '{{ $path }}')"
                                                    class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                                                    title="Hapus foto ini">
                                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                                </button>
                                            </div>
                                        @endforeach
                                    @endif

                                    {{-- Tempat Preview File Baru (Pakai class 'contents' agar nyatu dengan Grid bapaknya) --}}
                                    <div id="new-preview-penyewa" class="contents"></div>

                                </div>

                                <input id="input-foto-penyewa" name="foto_penyewa[]" type="file" multiple
                                    accept="image/*"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all bg-slate-50 border border-slate-200 rounded-lg" />
                                @error('foto_penyewa.*')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Catatans --}}
                            <div class="col-span-1 md:col-span-2 border-t border-slate-100 pt-3">
                                <label class="block font-medium text-sm text-slate-600 mb-1">Catatan (Opsional)</label>
                                <input name="catatan" value="{{ old('catatan', $booking->catatan) }}"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                    placeholder="Catatan khusus untuk pesanan ini..." type="text" />
                            </div>
                        </div>
                    </section>

                    {{-- Section 2: Data Sewa --}}
                    <section class="bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                        <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
                            <span class="material-symbols-outlined text-blue-600">car_rental</span>
                            <h3 class="text-lg font-semibold text-slate-800">Data Sewa</h3>
                        </div>

                        {{-- Jenis Sewa Toggle --}}
                        <div class="mb-5">
                            <label class="block font-medium text-sm text-slate-600 mb-2">Jenis Sewa</label>
                            <div class="flex p-1 bg-slate-100 rounded-lg w-fit" id="tipe-toggle">
                                <button type="button" data-tipe="harian"
                                    class="tipe-btn px-6 py-2 rounded-md font-medium text-sm text-slate-500 transition-all">
                                    Harian
                                </button>
                                <button type="button" data-tipe="kontrak"
                                    class="tipe-btn px-6 py-2 rounded-md font-medium text-sm text-slate-500 transition-all">
                                    Kontrak
                                </button>
                            </div>
                            <input type="hidden" name="tipe_sewa" id="input_tipe_sewa"
                                value="{{ old('tipe_sewa', $booking->tipe_sewa) }}" />
                        </div>

                        {{-- Tanggal & Durasi --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5 items-start">
                            <div>
                                <label class="block font-medium text-sm text-slate-600 mb-1">
                                    Tanggal Mulai <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input id="tanggal_mulai" name="tanggal_mulai"
                                        value="{{ old('tanggal_mulai', \Carbon\Carbon::parse($booking->tanggal_mulai)->format('Y-m-d')) }}"
                                        class="w-full bg-slate-50 border @error('tanggal_mulai') border-red-400 @else border-slate-200 @enderror rounded-lg pl-10 pr-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                        type="date" required />
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[20px]">calendar_month</span>
                                </div>
                                @error('tanggal_mulai')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block font-medium text-sm text-slate-600 mb-1">
                                    Durasi (Hari) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input id="input_durasi_hari" type="number" min="1" disabled
                                        class="w-full bg-white border border-slate-200 disabled:bg-slate-100 disabled:text-slate-400 rounded-lg px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                        placeholder="Ketik hari..." />
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">Dihitung dari tanggal mulai & selesai</p>
                            </div>

                            <div>
                                <label class="block font-medium text-sm text-slate-600 mb-1">
                                    Tanggal Selesai <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input id="tanggal_selesai" name="tanggal_selesai"
                                        value="{{ old('tanggal_selesai', \Carbon\Carbon::parse($booking->tanggal_selesai)->format('Y-m-d')) }}"
                                        class="w-full bg-slate-50 border @error('tanggal_selesai') border-red-400 @else border-slate-200 @enderror rounded-lg pl-10 pr-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                        type="date" required />
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[20px]">calendar_month</span>
                                </div>
                                @error('tanggal_selesai')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Info durasi --}}
                        <div id="info-durasi"
                            class="hidden mb-5 p-3 bg-blue-50 rounded-lg border border-blue-100 flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-500 text-[18px]">schedule</span>
                            <span id="teks-durasi" class="text-sm font-medium text-blue-700"></span>
                        </div>

                        {{-- Lokasi Jemput & Antar --}}
                        <div id="section-lokasi" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-sm text-slate-600 mb-1">Lokasi Jemput
                                    (Opsional)</label>
                                <div class="relative">
                                    <input name="lokasi_jemput"
                                        value="{{ old('lokasi_jemput', $booking->lokasi_jemput) }}"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                        placeholder="Alamat penjemputan..." type="text" />
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[20px]">location_on</span>
                                </div>
                            </div>
                            <div>
                                <label class="block font-medium text-sm text-slate-600 mb-1">Lokasi Antar
                                    (Opsional)</label>
                                <div class="relative">
                                    <input name="lokasi_antar" value="{{ old('lokasi_antar', $booking->lokasi_antar) }}"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                        placeholder="Alamat pengantaran..." type="text" />
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[20px]">flag</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Section 3: Pilih Kendaraan --}}
                    <section class="bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                        <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-blue-600">directions_car</span>
                                <h3 class="text-lg font-semibold text-slate-800">Pilih Kendaraan</h3>
                            </div>
                            <button type="button" id="btn-tambah-kendaraan"
                                class="flex items-center gap-1 font-medium text-sm text-blue-600 hover:text-blue-700 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                Tambah Kendaraan
                            </button>
                        </div>

                        {{-- Grid card kendaraan yang sudah dipilih --}}
                        <div id="container-kendaraan" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>

                        {{-- State kosong --}}
                        <div id="empty-kendaraan" class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                                <span class="material-symbols-outlined text-slate-400 text-3xl">directions_car</span>
                            </div>
                            <p class="text-sm font-medium text-slate-500">Belum ada kendaraan dipilih</p>
                            <p class="text-xs text-slate-400 mt-0.5">Klik "Tambah Kendaraan" untuk memilih dari daftar
                                armada</p>
                        </div>
                    </section>

                    {{-- Section 4: Add-ons --}}
                    <section class="bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-blue-600">add_shopping_cart</span>
                                <h3 class="text-lg font-semibold text-slate-800">Biaya Tambahan (Add-ons)</h3>
                            </div>
                            <button type="button" id="btn-tambah-addon"
                                class="flex items-center gap-1 font-medium text-sm text-blue-600 hover:text-blue-700 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                Tambah Manual
                            </button>
                        </div>

                        {{-- Quick Select Buttons --}}
                        <div class="flex flex-wrap gap-2 mb-5">
                            <button type="button" data-nama="Jasa Supir"
                                class="btn-quick-addon px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors border border-slate-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">person</span> Supir
                            </button>
                            <button type="button" data-nama="Biaya Tol"
                                class="btn-quick-addon px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors border border-slate-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">add_road</span> Tol
                            </button>
                            <button type="button" data-nama="Biaya Parkir"
                                class="btn-quick-addon px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors border border-slate-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">local_parking</span> Parkir
                            </button>
                            <button type="button" data-nama="Tiket Masuk Wisata"
                                class="btn-quick-addon px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors border border-slate-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">confirmation_number</span> Tiket
                            </button>
                            <button type="button" data-nama="Biaya Service/Cuci"
                                class="btn-quick-addon px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors border border-slate-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">local_car_wash</span> Service
                            </button>
                        </div>

                        <div id="container-addons" class="space-y-3"></div>
                        <p id="empty-addon" class="text-sm text-slate-400 text-center py-4 hidden">
                            Belum ada biaya tambahan.
                        </p>
                    </section>

                </div>{{-- end kolom kiri --}}

                {{-- ===================== KOLOM KANAN (Ringkasan) ===================== --}}
                <div class="xl:col-span-4">
                    <div class="xl:sticky xl:top-24 bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4 pb-3 border-b border-slate-100">Ringkasan
                            Pesanan</h3>

                        {{-- Pelanggan --}}
                        <div class="mb-4">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Pelanggan</p>
                            <div class="text-sm">
                                <p class="font-semibold text-slate-800" id="summary-nama">-</p>
                                <p class="text-slate-500 mt-0.5" id="summary-telp">-</p>
                                <p class="text-slate-500 mt-1 italic text-[11px] hidden bg-yellow-50 p-2 rounded border border-yellow-100"
                                    id="summary-catatan"></p>
                            </div>
                        </div>

                        {{-- Durasi --}}
                        <div id="summary-durasi" class="hidden mb-4 p-3 bg-slate-50 rounded-lg border border-slate-100">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Durasi Sewa</p>
                            <p class="font-semibold text-slate-800" id="summary-teks-durasi">-</p>
                            <p class="text-xs text-slate-500" id="summary-teks-tanggal">-</p>
                        </div>

                        {{-- Kendaraan --}}
                        <div id="summary-kendaraan-list" class="space-y-3 mb-4">
                            <p class="text-sm text-slate-400 italic" id="summary-kendaraan-empty">Belum ada kendaraan
                                dipilih.</p>
                        </div>

                        {{-- Rincian Biaya --}}
                        <div class="border-t border-slate-100 pt-4 mb-4">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Rincian Biaya</p>
                            <div id="summary-rincian" class="space-y-2">
                                <p class="text-sm text-slate-400 italic">Belum ada rincian.</p>
                            </div>
                        </div>

                        {{-- Addons Summary --}}
                        <div id="summary-addons-wrap" class="border-t border-slate-100 pt-4 mb-4 hidden">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Biaya Tambahan
                            </p>
                            <div id="summary-addons" class="space-y-2"></div>
                        </div>

                        {{-- Total --}}
                        <div class="border-t border-slate-100 pt-4 mb-6">
                            <div class="flex justify-between items-end">
                                <span class="font-medium text-slate-600">TOTAL</span>
                                <span class="text-2xl font-bold text-blue-600" id="summary-total">Rp 0</span>
                            </div>
                        </div>

                        <button type="submit"
                            class="hidden xl:flex w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-[20px]">update</span>
                            Update Pesanan
                        </button>
                    </div>

                    {{-- Mobile fixed action bar --}}
                    <div
                        class="xl:hidden fixed inset-x-0 bottom-16 md:bottom-0 z-[60] bg-white border-t border-slate-200 p-4 shadow-[0_-10px_25px_rgba(15,23,42,0.08)]">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs text-slate-400 uppercase tracking-wider">TOTAL</p>
                                <p class="text-xl font-bold text-blue-600" id="summary-total-mobile">Rp 0</p>
                            </div>
                            <button type="submit"
                                class="flex-1 min-w-[130px] bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors shadow-sm">
                                Update Pesanan
                            </button>
                        </div>
                    </div>
                </div>

            </div>{{-- end grid --}}
        </form>
    </div>

    {{-- ======================== TEMPLATE KENDARAAN ======================== --}}
    {{-- Identik dengan create.blade.php --}}
    <template id="tpl-kendaraan">
        <div class="kendaraan-item group relative bg-slate-50 border border-slate-200 rounded-xl overflow-hidden transition-all hover:border-blue-200 hover:shadow-sm"
            data-idx="" data-car-id="">

            <input type="hidden" name="cars[]" class="input-car-id" />
            <input type="hidden" name="harga_per_hari[]" class="harga-input" />

            <div class="p-3 pb-0">
                <div class="car-foto-wrap h-40 bg-slate-200 relative overflow-hidden rounded-lg">
                    <img class="car-foto w-full h-full object-cover hidden" src="" alt="">
                    <div
                        class="car-foto-placeholder absolute inset-0 flex flex-col items-center justify-center bg-slate-100">
                        <span class="material-symbols-outlined text-slate-300 text-[48px] mb-1">directions_car</span>
                    </div>
                    <button type="button"
                        class="btn-hapus-kendaraan absolute top-2 right-2 z-10 w-8 h-8 flex items-center justify-center rounded-lg bg-white/95 border border-slate-100 backdrop-blur-md text-slate-700 hover:text-white hover:bg-red-500 hover:border-red-500 shadow-md transition-all">
                        <span class="material-symbols-outlined text-[18px] font-bold">close</span>
                    </button>
                </div>
            </div>

            <div class="p-4">
                <p class="font-semibold text-sm text-slate-800 car-nama truncate">—</p>
                <p class="text-xs text-slate-500 mt-0.5 car-plat">—</p>

                <div class="mt-4 pt-3 border-t border-slate-200">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Harga /
                        Hari</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-medium">Rp</span>
                        <input type="number"
                            class="harga-display w-full bg-white border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                            placeholder="0" min="0" />
                    </div>
                </div>

                <div class="section-foto-kontrak hidden mt-4 pt-3 border-t border-slate-200">
                    <label class="block font-bold text-[10px] uppercase tracking-wider text-slate-400 mb-2">Foto Kondisi
                        Awal</label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            class="w-16 h-16 border-2 border-dashed border-slate-300 rounded-lg bg-white flex flex-col items-center justify-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all group/foto">
                            <span
                                class="material-symbols-outlined text-slate-400 group-hover/foto:text-blue-500 text-[20px]">add_a_photo</span>
                            <input type="file" name="foto_kondisi[]" class="hidden input-foto" accept="image/*"
                                multiple>
                        </label>
                        <div class="preview-foto-container flex flex-wrap gap-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- ======================== TEMPLATE ADD-ON ======================== --}}
    <template id="tpl-addon">
        <div class="addon-item flex items-center gap-3 p-3 border border-slate-200 rounded-lg bg-slate-50">
            <input type="text" name="addon_nama[]"
                class="flex-1 bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                placeholder="Nama biaya (misal: Supir, Antar-Jemput...)" required />
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">Rp</span>
                <input type="number" name="addon_nominal[]" min="0"
                    class="addon-nominal w-36 bg-white border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                    placeholder="0" required />
            </div>
            <button type="button" class="btn-hapus-addon text-slate-400 hover:text-red-500 transition-colors p-1">
                <span class="material-symbols-outlined text-[20px]">delete</span>
            </button>
        </div>
    </template>

    {{-- ======================== MODAL PICKER ======================== --}}
    <div id="modal-picker" class="fixed inset-0 z-[9999] hidden items-end md:items-center justify-center p-0 md:p-4"
        role="dialog" aria-modal="true" aria-labelledby="modal-title">

        <div id="modal-overlay" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        {{-- Panel --}}
        <div
            class="relative w-full max-w-full md:max-w-2xl h-full md:h-auto bg-white rounded-t-3xl md:rounded-2xl shadow-2xl flex flex-col overflow-hidden max-h-[100vh] md:max-h-[90vh]">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <h2 id="modal-title" class="text-lg font-bold text-slate-900">Pilih Kendaraan</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Pilih satu atau lebih kendaraan untuk pesanan ini</p>
                </div>
                <button type="button" id="btn-modal-close"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <div class="px-6 py-4 border-b border-slate-100 flex flex-col md:flex-row gap-3">
                {{-- Search --}}
                <div class="relative flex-1">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">search</span>
                    <input id="modal-search" type="text"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                        placeholder="Cari merek, model, atau plat nomor..." />
                </div>

                {{-- Filter Container --}}
                <div class="relative" id="modalFilterContainer">
                    {{-- Tombol Buka Dropdown --}}
                    <button type="button" id="btn-toggle-modal-filter"
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all">
                        <span class="material-symbols-outlined text-[20px]">filter_list</span>
                        Filter
                    </button>

                    {{-- Filter Dropdown --}}
                    <div id="modalFilterDropdown"
                        class="hidden absolute right-0 md:left-auto left-0 mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 z-[60] p-6 overflow-y-auto max-h-[80vh]">
                        <div class="flex items-center justify-between mb-5">
                            <h4 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Multiple Filters
                            </h4>
                            <span class="material-symbols-outlined text-slate-400 text-sm">tune</span>
                        </div>

                        <div class="space-y-3">
                            {{-- Filter Status --}}
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Status
                                    Armada</label>
                                <select id="modal-filter-status"
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors">
                                    <option value="">Semua Status</option>
                                    <option value="Tersedia">Tersedia</option>
                                    <option value="Sedang Disewa">Sedang Disewa</option>
                                    <option value="Masuk Bengkel">Masuk Bengkel</option>
                                </select>
                            </div>

                            {{-- Filter Servis --}}
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Kondisi
                                    Perawatan</label>
                                <select id="modal-filter-servis"
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors">
                                    <option value="">Semua Kondisi</option>
                                    <option value="1">Perlu Servis</option>
                                </select>
                            </div>

                            {{-- Filter Merk --}}
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Merk
                                    Kendaraan</label>
                                <select id="modal-filter-merk"
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors">
                                    <option value="">Semua Merk</option>
                                    <option value="Toyota">Toyota</option>
                                    <option value="Honda">Honda</option>
                                    <option value="Mitsubishi">Mitsubishi</option>
                                    <option value="Daihatsu">Daihatsu</option>
                                    <option value="Suzuki">Suzuki</option>
                                    <option value="Nissan">Nissan</option>
                                </select>
                            </div>

                            {{-- Filter Tahun --}}
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Tahun</label>
                                <select id="modal-filter-tahun"
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors">
                                    <option value="">Semua Tahun</option>
                                    @for ($y = date('Y'); $y >= 2010; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        {{-- Footer Dropdown --}}
                        <div class="flex items-center gap-2 mt-4 pt-4 border-t border-slate-100">
                            <button type="button" id="btn-reset-modal-filter"
                                class="flex-1 text-center py-2.5 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors">
                                Reset
                            </button>
                            <button type="button" id="btn-apply-modal-filter"
                                class="flex-1 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md shadow-blue-200">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="modal-car-list" class="overflow-y-auto flex-1 px-6 py-4 space-y-2"></div>

            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50">
                <p class="text-sm text-slate-500">
                    <span id="modal-selected-count" class="font-bold text-blue-600">0</span> kendaraan dipilih
                </p>
                <div class="flex gap-2">
                    <button type="button" id="btn-modal-cancel"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" id="btn-modal-confirm"
                        class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                        Tambahkan ke Pesanan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/booking-form.js')
@endpush
