{{-- resources/views/armada/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Tambah Mobil')

@section('content')
    <div class="min-h-screen bg-gray-50">

        {{-- Page Header --}}
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.armada') }}"
                    class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Tambah Mobil Baru</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Isi data kendaraan dengan lengkap</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-6">
            <form action="{{ route('admin.armada.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- LEFT: Main Form --}}
                    <div class="lg:col-span-2">
                        <fieldset id="main-fieldset" class="space-y-5">

                        {{-- Identitas Kendaraan --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-5">
                                <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Identitas Kendaraan</h2>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Plat Nomor <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="plat_nomor" placeholder="Contoh: B 1234 XY"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 uppercase font-mono tracking-widest"
                                        value="{{ old('plat_nomor') }}" required>
                                    @error('plat_nomor')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Merk <span
                                            class="text-red-500">*</span></label>
                                    <select name="merk"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                                        required>
                                        <option value="">Pilih Merk</option>
                                        <option value="Toyota" {{ old('merk') == 'Toyota' ? 'selected' : '' }}>Toyota
                                        </option>
                                        <option value="Honda" {{ old('merk') == 'Honda' ? 'selected' : '' }}>Honda</option>
                                        <option value="Mitsubishi" {{ old('merk') == 'Mitsubishi' ? 'selected' : '' }}>
                                            Mitsubishi</option>
                                        <option value="Daihatsu" {{ old('merk') == 'Daihatsu' ? 'selected' : '' }}>Daihatsu
                                        </option>
                                        <option value="Suzuki" {{ old('merk') == 'Suzuki' ? 'selected' : '' }}>Suzuki
                                        </option>
                                        <option value="Nissan" {{ old('merk') == 'Nissan' ? 'selected' : '' }}>Nissan
                                        </option>
                                        <option value="Lainnya" {{ old('merk') == 'Lainnya' ? 'selected' : '' }}>Lainnya
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Model/Tipe <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="model" placeholder="Contoh: Innova Reborn 2.0 G"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                                        value="{{ old('model') }}" required>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tahun <span
                                            class="text-red-500">*</span></label>
                                    <select name="tahun"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                                        required>
                                        <option value="">Pilih Tahun</option>
                                        @for ($y = date('Y'); $y >= 2010; $y--)
                                            <option value="{{ $y }}"
                                                {{ old('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kapasitas (kursi) <span
                                            class="text-red-500">*</span></label>
                                    <select name="kapasitas"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                                        required>
                                        <option value="">Pilih Kapasitas</option>
                                        <option value="4" {{ old('kapasitas') == 2 ? 'selected' : '' }}>2 Kursi
                                        </option>
                                        <option value="4" {{ old('kapasitas') == 4 ? 'selected' : '' }}>4 Kursi
                                        </option>
                                        <option value="5" {{ old('kapasitas') == 5 ? 'selected' : '' }}>5 Kursi
                                        </option>
                                        <option value="7" {{ old('kapasitas') == 7 ? 'selected' : '' }}>7 Kursi
                                        </option>
                                        <option value="8" {{ old('kapasitas') == 8 ? 'selected' : '' }}>8 Kursi
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Warna</label>
                                    <input type="text" name="warna" placeholder="Contoh: Putih Pearl"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                                        value="{{ old('warna') }}">
                                </div>

                            </div>
                        </div>

                        {{-- Nomor Kendaraan --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-5">
                                <div class="w-7 h-7 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Nomor Identifikasi</h2>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nomor Mesin <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="no_mesin" placeholder="Contoh: 1TR1234567"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 font-mono uppercase"
                                        value="{{ old('no_mesin') }}" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nomor Rangka <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="no_rangka" placeholder="Contoh: MHFAN3GK5L0123456"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 font-mono uppercase"
                                        value="{{ old('no_rangka') }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Odometer & Servis --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-5">
                                <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Odometer & Konfigurasi Servis</h2>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">KM Saat Ini <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" id="display_km_saat_ini" placeholder="0"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 pr-10"
                                            value="{{ old('km_saat_ini') ? number_format(old('km_saat_ini'), 0, ',', '.') : '0' }}"
                                            oninput="formatRibuan(this, 'km_saat_ini')" required>

                                        <input type="hidden" name="km_saat_ini" id="km_saat_ini"
                                            value="{{ old('km_saat_ini', 0) }}">
                                        <span
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">km</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">KM Servis
                                        Terakhir</label>
                                    <div class="relative">
                                        <input type="text" id="display_km_servis_terakhir" placeholder="0"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 pr-10"
                                            value="{{ old('km_servis_terakhir') ? number_format(old('km_servis_terakhir'), 0, ',', '.') : '0' }}"
                                            oninput="formatRibuan(this, 'km_servis_terakhir')">

                                        <input type="hidden" name="km_servis_terakhir" id="km_servis_terakhir"
                                            value="{{ old('km_servis_terakhir', 0) }}">
                                        <span
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">km</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Threshold Servis <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="relative">
                                            <input type="text" id="display_threshold_servis_km" placeholder="10.000"
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 pr-10"
                                                value="{{ old('threshold_servis_km') ? number_format(old('threshold_servis_km'), 0, ',', '.') : '10.000' }}"
                                                oninput="formatRibuan(this, 'threshold_servis_km')" required>

                                            <input type="hidden" name="threshold_servis_km" id="threshold_servis_km"
                                                value="{{ old('threshold_servis_km', 10000) }}">
                                        </div>
                                        <span
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">km</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Notifikasi servis setelah jarak ini</p>
                                </div>
                            </div>
                        </div>

                        {{-- Harga Sewa --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-5">
                                <div class="w-7 h-7 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Tarif Harga Mobil</h2>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {{-- Harga Harian --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                        Harga Harian (per hari) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium">Rp</span>
                                        <input type="text" id="display_harga_default" placeholder="350.000"
                                            class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                                            value="{{ old('harga_default') ? number_format(old('harga_default'), 0, ',', '.') : '' }}"
                                            oninput="formatRibuan(this, 'harga_default')" required>
                                        <input type="hidden" name="harga_default" id="harga_default"
                                            value="{{ old('harga_default') }}">
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Digunakan untuk tipe sewa harian</p>
                                    @error('harga_default')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Harga Kontrak --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                        Harga Kontrak (per hari) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium">Rp</span>
                                        <input type="text" id="display_harga_kontrak" placeholder="300.000"
                                            class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                                            value="{{ old('harga_kontrak') ? number_format(old('harga_kontrak'), 0, ',', '.') : '' }}"
                                            oninput="formatRibuan(this, 'harga_kontrak')" required>
                                        <input type="hidden" name="harga_kontrak" id="harga_kontrak"
                                            value="{{ old('harga_kontrak') }}">
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Digunakan untuk tipe sewa kontrak</p>
                                    @error('harga_kontrak')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        </fieldset>
                    </div>

                    {{-- RIGHT: Foto & Preview --}}
                    <div class="space-y-5">

                        {{-- Dokumen Pelepasan Hak Kendaraan (WAJIB, dibuat sebelum data disimpan) --}}
                        <div class="bg-white rounded-2xl border-2 border-blue-200 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Dokumen Pelepasan Hak <span
                                        class="text-red-500">*</span></h2>
                            </div>

                            {{-- STEP 1: Dokumen belum dibuat --}}
                            <div id="dokumen-empty-state" class="{{ $draftSurat ? 'hidden' : '' }}">
                                <p class="text-xs text-gray-400 mb-3">Buat nomor surat secara otomatis (mengikuti skema
                                    surat-menyurat seperti invoice). Isi data identitas kendaraan terlebih dahulu agar
                                    muncul di dokumen.</p>
                                <button type="button" id="btn-buat-dokumen" onclick="buatDokumen()"
                                    class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-3 rounded-xl transition-colors shadow-sm">
                                    <svg id="btn-buat-dokumen-icon" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span id="btn-buat-dokumen-label">Buat Nomor Surat</span>
                                </button>
                                <p class="text-xs text-gray-400 mt-3 text-center">Otomatis seperti invoice &nbsp;·&nbsp;
                                    Contoh: <span class="font-mono text-blue-600">001/SRT/PHK/JRB/VIII/2026</span></p>
                            </div>

                            {{-- STEP 2: Dokumen sudah dibuat --}}
                            <div id="dokumen-created-state" class="{{ $draftSurat ? '' : 'hidden' }}">
                                <input type="hidden" name="surat_id" id="surat-id-input"
                                    value="{{ $draftSurat->id ?? '' }}">

                                {{-- Nomor Surat --}}
                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 mb-3">
                                    <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Nomor
                                        Surat</p>
                                    <p id="nomor-surat-display"
                                        class="text-sm font-bold text-blue-900 font-mono break-all">{{ $draftSurat->nomor_surat ?? '...' }}</p>
                                </div>

                                {{-- Data tambahan untuk dokumen (tidak disimpan ke mobil) --}}
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-500 mb-1">Jenis
                                            Kendaraan</label>
                                        <input type="text" name="jenis_kendaraan" placeholder="Mobil Barang / Double Cabin"
                                            class="w-full px-2.5 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                                            value="{{ old('jenis_kendaraan') }}">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-500 mb-1">Isi Silinder
                                            (CC)</label>
                                        <input type="text" name="isi_silinder" placeholder="2442"
                                            class="w-full px-2.5 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                                            value="{{ old('isi_silinder') }}">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-500 mb-1">Pembeli /
                                            Pihak Kedua</label>
                                        <input type="text" name="pembeli" placeholder="Nama pembeli"
                                            class="w-full px-2.5 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                                            value="{{ old('pembeli') }}">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-500 mb-1">Alamat
                                            Pembeli</label>
                                        <input type="text" name="alamat_pembeli" placeholder="Alamat pembeli"
                                            class="w-full px-2.5 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                                            value="{{ old('alamat_pembeli') }}">
                                    </div>
                                </div>

                                {{-- Preview & Download Dokumen --}}
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    <button type="button" onclick="previewDokumen()"
                                        class="flex flex-col items-center gap-1 border-2 border-blue-200 bg-blue-50 rounded-xl py-3 text-blue-700 hover:bg-blue-100 transition-all cursor-pointer">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                        <span class="text-xs font-semibold">Preview Dokumen</span>
                                    </button>
                                    <button type="button" onclick="downloadDokumen()"
                                        class="flex flex-col items-center gap-1 border-2 border-gray-200 rounded-xl py-3 text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition-all cursor-pointer">
                                        <span class="material-symbols-outlined text-[20px]">download</span>
                                        <span class="text-xs font-semibold">Download</span>
                                    </button>
                                </div>

                                <p class="text-xs text-gray-400 mb-3">Unggah hasil cetak yang sudah ditandatangani. JPG,
                                    PNG, atau PDF, maks 5MB.</p>

                                {{-- Upload Dokumen --}}
                                <div id="dokumen-drop-area"
                                    class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition-all duration-150 cursor-pointer"
                                    onclick="document.getElementById('foto-dokumen-input').click()">
                                    <div id="dokumen-preview-container" class="hidden">
                                        <img id="dokumen-preview-img" src="" alt="Preview"
                                            class="w-full h-32 object-cover rounded-lg mb-3 hidden">
                                        <p id="dokumen-file-name"
                                            class="text-xs font-semibold text-gray-700 truncate mb-1"></p>
                                        <p class="text-xs text-blue-600 font-medium">Klik untuk ganti dokumen</p>
                                    </div>
                                    <div id="dokumen-upload-placeholder">
                                        <div
                                            class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-600">Klik atau drag dokumen di sini</p>
                                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF, max 5MB</p>
                                    </div>
                                    <input type="file" name="foto_dokumen" id="foto-dokumen-input"
                                        accept="image/*,.pdf" class="hidden" required>
                                </div>
                                @error('foto_dokumen')
                                    <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Upload Foto --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 bg-pink-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-pink-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Foto Kendaraan</h2>
                            </div>

                            {{-- Upload Area --}}
                            <div id="drop-area"
                                class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition-all duration-150 cursor-pointer"
                                onclick="document.getElementById('foto-input').click()">
                                <div id="preview-container" class="hidden">
                                    <img id="preview-img" src="" alt="Preview"
                                        class="w-full h-40 object-cover rounded-lg mb-3">
                                    <p class="text-xs text-blue-600 font-medium">Klik untuk ganti foto</p>
                                </div>
                                <div id="upload-placeholder">
                                    <div
                                        class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-600">Klik atau drag foto di sini</p>
                                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, max 2MB</p>
                                </div>
                                <input type="file" id="foto-input" accept="image/*" class="hidden">
                                {{-- Hidden input untuk menyimpan data gambar yang sudah di-crop (base64) --}}
                                <input type="hidden" name="foto_cropped" id="foto-cropped">
                            </div>
                        </div>

                        {{-- Modal Cropper --}}
                        <div id="cropper-modal"
                            class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4">
                            <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden">
                                <div class="p-4 border-b flex justify-between items-center">
                                    <h3 class="font-bold">Sesuaikan Foto</h3>
                                    <button type="button" onclick="closeCropper()"
                                        class="text-gray-400 hover:text-gray-600">&times;</button>
                                </div>
                                <div class="p-4">
                                    <div class="max-h-[60vh] overflow-hidden bg-gray-100">
                                        <img id="image-to-crop" class="max-w-full block">
                                    </div>
                                </div>
                                <div class="p-4 border-t flex gap-2">
                                    <button type="button" onclick="closeCropper()"
                                        class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-xl font-medium">Batal</button>
                                    <button type="button" onclick="cropImage()"
                                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl font-medium">Terapkan</button>
                                </div>
                            </div>
                        </div>

                        {{-- Three Letter Code Preview --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Kode Invoice (3 Huruf)</h2>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <div id="kode-preview"
                                    class="text-3xl font-black text-blue-600 font-mono tracking-widest mb-1">---</div>
                                <p class="text-xs text-gray-400">Auto-generate dari nama model</p>
                            </div>

                            <div class="mt-3">
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Override Kode
                                    (opsional)</label>
                                <input type="text" name="kode_mobil" placeholder="Contoh: INN" maxlength="3"
                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white uppercase font-mono tracking-widest text-center"
                                    oninput="this.value=this.value.toUpperCase(); updateKodePreview(this.value)"
                                    value="{{ old('kode_mobil') }}">
                            </div>
                        </div>

                        {{-- Status Awal --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Status Awal</h2>
                            </div>

                            <div class="space-y-2">
                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50">
                                    <input type="radio" name="status" value="Tersedia" checked
                                        class="accent-emerald-500">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Tersedia</p>
                                        <p class="text-xs text-gray-400">Siap untuk disewakan</p>
                                    </div>
                                </label>
                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                                    <input type="radio" name="status" value="Masuk Bengkel" class="accent-amber-500">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Masuk Bengkel</p>
                                        <p class="text-xs text-gray-400">Sedang dalam perawatan</p>
                                    </div>
                                </label>
                                {{-- TAMBAHAN: Opsi Nonaktif --}}
                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors has-[:checked]:border-red-400 has-[:checked]:bg-red-50">
                                    <input type="radio" name="status" value="Nonaktif" class="accent-red-500">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Nonaktif</p>
                                        <p class="text-xs text-gray-400">Mobil pensiun atau tidak dioperasikan</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex flex-col gap-2">
                            <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-3 rounded-xl transition-colors shadow-sm">
                                Simpan Mobil
                            </button>
                            <a href="{{ route('admin.armada') }}"
                                class="w-full text-center text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 py-3 rounded-xl transition-colors">
                                Batal
                            </a>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let cropper;
        const fotoInput = document.getElementById('foto-input');
        const imageToCrop = document.getElementById('image-to-crop');
        const cropperModal = document.getElementById('cropper-modal');
        const fotoCroppedInput = document.getElementById('foto-cropped');
        const previewImg = document.getElementById('preview-img');
        const previewContainer = document.getElementById('preview-container');
        const uploadPlaceholder = document.getElementById('upload-placeholder');

        // --- 1. LOGIKA KODE INVOICE OTOMATIS ---
        function updateKodePreview(val) {
            const preview = document.getElementById('kode-preview');
            // Ambil 3 huruf pertama, hilangkan spasi, jadikan uppercase
            const code = val.replace(/\s/g, '').substring(0, 3).toUpperCase();
            preview.innerText = code || '---';
        }

        // Hubungkan input Model ke Preview Kode
        document.querySelector('input[name="model"]').addEventListener('input', function() {
            // Hanya update jika input "Override Kode" kosong
            const overrideInput = document.querySelector('input[name="kode_mobil"]');
            if (!overrideInput.value) {
                updateKodePreview(this.value);
            }
        });

        // --- 2. LOGIKA CROPPER ---
        fotoInput.addEventListener('change', function(e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    imageToCrop.src = event.target.result;
                    cropperModal.classList.remove('hidden');
                    cropperModal.classList.add('flex');

                    // Hancurkan cropper lama jika ada
                    if (cropper) cropper.destroy();

                    // Inisialisasi Cropper baru (Rasio 16:9 sesuai desain card)
                    cropper = new Cropper(imageToCrop, {
                        aspectRatio: 16 / 9,
                        viewMode: 2,
                        autoCropArea: 1,
                    });
                };
                reader.readAsDataURL(files[0]);
            }
        });

        function closeCropper() {
            cropperModal.classList.add('hidden');
            cropperModal.classList.remove('flex');
            fotoInput.value = ""; // Reset input file
        }

        function cropImage() {
            // Ambil hasil crop dalam format Base64 (JPG kualitas 0.8)
            const canvas = cropper.getCroppedCanvas({
                width: 800,
                height: 450,
            });

            const dataUrl = canvas.toDataURL('image/jpeg', 0.8);

            // Simpan hasil ke hidden input untuk dikirim ke Controller
            fotoCroppedInput.value = dataUrl;

            // Update Tampilan Preview di Form
            previewImg.src = dataUrl;
            previewContainer.classList.remove('hidden');
            uploadPlaceholder.classList.add('hidden');

            closeCropper();
        }

        // Auto generate kode dari input model
        document.querySelector('input[name="model"]')?.addEventListener('input', function() {
            const overrideInput = document.querySelector('input[name="kode_mobil"]');
            if (!overrideInput.value) {
                const words = this.value.trim().split(/\s+/);
                let kode = '';
                if (words.length >= 3) {
                    kode = words.slice(0, 3).map(w => w[0]).join('').toUpperCase();
                } else if (words.length === 2) {
                    kode = (words[0].substring(0, 2) + words[1][0]).toUpperCase();
                } else if (words[0]) {
                    kode = words[0].substring(0, 3).toUpperCase();
                }
                document.getElementById('kode-preview').textContent = kode.padEnd(3, '-') || '---';
            }
        });

        function formatRibuan(input, hiddenId) {
            // Hapus semua karakter kecuali angka
            let rawValue = input.value.replace(/[^0-9]/g, '');

            // Update nilai asli ke input hidden (yang akan dikirim ke Laravel)
            document.getElementById(hiddenId).value = rawValue;

            // Tampilkan format titik ke user
            if (rawValue) {
                input.value = parseInt(rawValue, 10).toLocaleString('id-ID');
            } else {
                input.value = '';
            }
        }
        // --- 3. LOGIKA DOKUMEN PELEPASAN HAK (Buat Nomor Surat → Preview → Download → Upload) ---
        function buatDokumen() {
            const btn = document.getElementById('btn-buat-dokumen');
            const btnIcon = document.getElementById('btn-buat-dokumen-icon');
            const btnLabel = document.getElementById('btn-buat-dokumen-label');

            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btnIcon.classList.add('animate-spin');
            btnLabel.textContent = 'Membuat Nomor Surat...';

            fetch('{{ route('admin.armada.create-dokumen') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.success) {
                        throw new Error('Gagal membuat dokumen');
                    }

                    document.getElementById('surat-id-input').value = data.surat_id;
                    document.getElementById('nomor-surat-display').textContent = data.nomor_surat;
                    document.getElementById('dokumen-empty-state').classList.add('hidden');
                    document.getElementById('dokumen-created-state').classList.remove('hidden');
                })
                ['catch'](function() {
                    alert('Gagal membuat dokumen. Silakan coba lagi.');
                    btn.disabled = false;
                    btn.classList.remove('opacity-70', 'cursor-not-allowed');
                    btnIcon.classList.remove('animate-spin');
                    btnLabel.textContent = 'Buat Nomor Surat';
                });
        }

        // Ambil data form & susun URL dokumen Pelepasan Hak
        function buildDokumenUrl() {
            const val = function(selector) {
                const el = document.querySelector(selector);
                return el ? el.value.trim() : '';
            };

            const params = new URLSearchParams({
                plat: val('input[name="plat_nomor"]'),
                merk: val('select[name="merk"]'),
                model: val('input[name="model"]'),
                tahun: val('select[name="tahun"]'),
                warna: val('input[name="warna"]'),
                mesin: val('input[name="no_mesin"]'),
                rangka: val('input[name="no_rangka"]'),
                jenis: val('input[name="jenis_kendaraan"]'),
                isi_silinder: val('input[name="isi_silinder"]'),
                pembeli: val('input[name="pembeli"]'),
                alamat_pembeli: val('input[name="alamat_pembeli"]'),
            });

            const suratId = document.getElementById('surat-id-input').value;
            const base = '{{ route('admin.armada.dokumen-phk', ['surat' => '__ID__']) }}';
            return base.replace('__ID__', suratId) + '?' + params.toString();
        }

        function previewDokumen() {
            if (!document.getElementById('surat-id-input').value) return;
            window.open(buildDokumenUrl(), '_blank');
        }

        function downloadDokumen() {
            if (!document.getElementById('surat-id-input').value) return;
            window.open(buildDokumenUrl() + '&download=1', '_blank');
        }

        const dokumenInput = document.getElementById('foto-dokumen-input');
        const dokumenPreviewContainer = document.getElementById('dokumen-preview-container');
        const dokumenUploadPlaceholder = document.getElementById('dokumen-upload-placeholder');
        const dokumenPreviewImg = document.getElementById('dokumen-preview-img');
        const dokumenFileName = document.getElementById('dokumen-file-name');

        dokumenInput.addEventListener('change', function(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;

            dokumenFileName.textContent = file.name;
            dokumenUploadPlaceholder.classList.add('hidden');
            dokumenPreviewContainer.classList.remove('hidden');

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    dokumenPreviewImg.src = event.target.result;
                    dokumenPreviewImg.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                dokumenPreviewImg.classList.add('hidden');
            }
        });
    </script>
@endsection