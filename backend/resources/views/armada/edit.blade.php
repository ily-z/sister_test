@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gray-50">

        {{-- Page Header --}}
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">

                {{-- Bagian Kiri: Tombol Back & Judul --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.armada') }}"
                        class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Edit Data Kendaraan</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Perbarui informasi untuk armada: <span
                                class="font-bold uppercase">{{ $car->plat_nomor }}</span></p>
                    </div>
                </div>

                {{-- Bagian Kanan: Tombol Hapus --}}
                <form action="{{ route('admin.armada.destroy', $car->id) }}" method="POST" class="m-0"
                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus mobil ini? Data yang dihapus tidak dapat dikembalikan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-100 rounded-xl transition-all shadow-sm active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                        Hapus Mobil
                    </button>
                </form>

            </div>
        </div>

        <div class="px-6 py-6">
            @if ($errors->any())
                <div class="px-6 pt-6">
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm">
                        <div class="flex items-center mb-2">
                            <span class="material-symbols-outlined text-red-500 mr-2">error</span>
                            <h3 class="text-sm font-bold text-red-800">Gagal menyimpan data!</h3>
                        </div>
                        <ul class="list-disc list-inside text-xs text-red-700 ml-1 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            <form action="{{ route('admin.armada.update', $car->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- LEFT: Main Form --}}
                    <div class="lg:col-span-2 space-y-5">

                        {{-- Identitas Kendaraan --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-5">
                                <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[16px] text-blue-600">directions_car</span>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Identitas Kendaraan</h2>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Plat Nomor <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="plat_nomor" placeholder="Contoh: B 1234 XY"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 uppercase font-mono tracking-widest"
                                        value="{{ old('plat_nomor', $car->plat_nomor) }}" required>
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
                                        @foreach (['Toyota', 'Honda', 'Mitsubishi', 'Daihatsu', 'Suzuki', 'Nissan', 'Lainnya'] as $m)
                                            <option value="{{ $m }}"
                                                {{ old('merk', $car->merk) == $m ? 'selected' : '' }}>
                                                {{ $m }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Model/Tipe <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="model" placeholder="Contoh: Innova Reborn 2.0 G"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                                        value="{{ old('model', $car->model ?? '') }}" required>
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
                                                {{ old('tahun', $car->tahun) == $y ? 'selected' : '' }}>
                                                {{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kapasitas (kursi) <span
                                            class="text-red-500">*</span></label>
                                    @if ($car->status === 'Sedang Disewa')
                                        <select disabled
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-100 text-gray-500 cursor-not-allowed">
                                            @foreach ([2, 4, 5, 7, 8] as $k)
                                                <option value="{{ $k }}"
                                                    {{ $car->kapasitas == $k ? 'selected' : '' }}>
                                                    {{ $k }} Kursi</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="kapasitas" value="{{ $car->kapasitas }}">
                                        <p class="text-xs text-orange-600 mt-2">Karena mobil sedang disewa, kapasitas tidak
                                            dapat diubah saat ini.</p>
                                    @else
                                        <select name="kapasitas"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                                            required>
                                            <option value="">Pilih Kapasitas</option>
                                            @foreach ([2, 4, 5, 7, 8] as $k)
                                                <option value="{{ $k }}"
                                                    {{ old('kapasitas', $car->kapasitas) == $k ? 'selected' : '' }}>
                                                    {{ $k }} Kursi</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Warna</label>
                                    <input type="text" name="warna" placeholder="Contoh: Putih Pearl"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                                        value="{{ old('warna', $car->warna ?? '') }}">
                                </div>

                            </div>
                        </div>

                        {{-- Nomor Kendaraan --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-5">
                                <div class="w-7 h-7 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[16px] text-purple-600">badge</span>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Nomor Identifikasi</h2>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nomor Mesin <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="no_mesin" placeholder="Contoh: 1TR1234567"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 font-mono uppercase"
                                        value="{{ old('no_mesin', $car->no_mesin) }}" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nomor Rangka <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="no_rangka" placeholder="Contoh: MHFAN3GK5L0123456"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 font-mono uppercase"
                                        value="{{ old('no_rangka', $car->no_rangka) }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Odometer & Servis --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-5">
                                <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[16px] text-emerald-600">speed</span>
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
                                            value="{{ old('km_saat_ini') ? number_format(old('km_saat_ini'), 0, ',', '.') : number_format($car->km_saat_ini ?? 0, 0, ',', '.') }}"
                                            oninput="formatRibuan(this, 'km_saat_ini')" required>

                                        <input type="hidden" name="km_saat_ini" id="km_saat_ini"
                                            value="{{ old('km_saat_ini', $car->km_saat_ini ?? 0) }}">
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
                                            value="{{ old('km_servis_terakhir') ? number_format(old('km_servis_terakhir'), 0, ',', '.') : number_format($car->km_servis_terakhir ?? 0, 0, ',', '.') }}"
                                            oninput="formatRibuan(this, 'km_servis_terakhir')">

                                        <input type="hidden" name="km_servis_terakhir" id="km_servis_terakhir"
                                            value="{{ old('km_servis_terakhir', $car->km_servis_terakhir ?? 0) }}">
                                        <span
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">km</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Threshold Servis <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" id="display_threshold_servis_km" placeholder="10.000"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 pr-10"
                                            value="{{ old('threshold_servis_km') ? number_format(old('threshold_servis_km'), 0, ',', '.') : number_format($car->threshold_servis_km ?? 10000, 0, ',', '.') }}"
                                            oninput="formatRibuan(this, 'threshold_servis_km')" required>

                                        <input type="hidden" name="threshold_servis_km" id="threshold_servis_km"
                                            value="{{ old('threshold_servis_km', $car->threshold_servis_km ?? 10000) }}">
                                        <span
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">km</span>
                                    </div>
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
                                        Harga Sewa Harian <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium">Rp</span>
                                        <input type="text" id="display_harga_default" placeholder="350.000"
                                            class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                                            value="{{ old('harga_default') ? number_format(old('harga_default'), 0, ',', '.') : number_format($car->harga_default ?? 0, 0, ',', '.') }}"
                                            oninput="formatRibuan(this, 'harga_default')" required>
                                        <input type="hidden" name="harga_default" id="harga_default"
                                            value="{{ old('harga_default', $car->harga_default ?? 0) }}">
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Digunakan untuk tipe sewa harian</p>
                                    @error('harga_default')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Harga Kontrak --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                        Harga Kontrak <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium">Rp</span>
                                        <input type="text" id="display_harga_kontrak" placeholder="300.000"
                                            class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                                            value="{{ old('harga_kontrak') ? number_format(old('harga_kontrak'), 0, ',', '.') : number_format($car->harga_kontrak ?? 0, 0, ',', '.') }}"
                                            oninput="formatRibuan(this, 'harga_kontrak')" required>
                                        <input type="hidden" name="harga_kontrak" id="harga_kontrak"
                                            value="{{ old('harga_kontrak', $car->harga_kontrak ?? 0) }}">
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Digunakan untuk tipe sewa kontrak</p>
                                    @error('harga_kontrak')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                {{-- Harga Dasar per Hari (harga_default) --}}
                                <!-- <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Harga Dasar per Hari
                                            <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium">Rp</span>

                                            {{-- Input yang tampil dengan titik ribuan --}}
                                            <input type="text" id="display_harga_default" placeholder="350.000"
                                                class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                                                value="{{ old('harga_default') ? number_format(old('harga_default'), 0, ',', '.') : number_format($car->harga_default ?? 0, 0, ',', '.') }}"
                                                oninput="formatRibuan(this, 'harga_default')" required>

                                            {{-- Input tersembunyi yang dikirim ke database (angka murni) --}}
                                            <input type="hidden" name="harga_default" id="harga_default"
                                                value="{{ old('harga_default', $car->harga_default ?? 0) }}">
                                        </div>
                                        @error('harga_default')
        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
    @enderror
                                    </div> -->
                            </div>
                        </div>

                    </div>

                    {{-- RIGHT: Foto & Status --}}
                    <div class="space-y-5">

                        {{-- Foto Dokumen Serah Terima --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[16px] text-blue-600">description</span>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Foto Dokumen Serah Terima</h2>
                            </div>

                            @if ($car->foto_dokumen)
                                <a href="{{ asset('storage/' . $car->foto_dokumen) }}" target="_blank"
                                    class="flex items-center gap-2 mb-3 text-xs font-semibold text-blue-600 hover:underline">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    Lihat dokumen saat ini
                                </a>
                            @endif

                            <div id="dokumen-drop-area"
                                class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition-all duration-150 cursor-pointer"
                                onclick="document.getElementById('foto-dokumen-input').click()">
                                <div id="dokumen-preview-container" class="hidden">
                                    <p id="dokumen-file-name" class="text-xs font-semibold text-gray-700 truncate mb-1"></p>
                                    <p class="text-xs text-blue-600 font-medium">Klik untuk ganti dokumen</p>
                                </div>
                                <div id="dokumen-upload-placeholder">
                                    <div
                                        class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <span class="material-symbols-outlined text-gray-400">upload_file</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-600">Klik untuk ganti dokumen</p>
                                    <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak ingin mengubah dokumen. JPG,
                                        PNG, PDF, max 5MB</p>
                                </div>
                                <input type="file" name="foto_dokumen" id="foto-dokumen-input" accept="image/*,.pdf"
                                    class="hidden">
                            </div>
                            @error('foto_dokumen')
                                <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Upload Foto --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 bg-pink-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[16px] text-pink-600">image</span>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Foto Kendaraan</h2>
                            </div>

                            {{-- Upload Area --}}
                            <div id="drop-area"
                                class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition-all duration-150 cursor-pointer"
                                onclick="document.getElementById('foto-input').click()">

                                <div id="preview-container" class="{{ $car->foto ? '' : 'hidden' }}">
                                    <img id="preview-img" src="{{ $car->foto ? asset('storage/' . $car->foto) : '' }}"
                                        alt="Preview" class="w-full h-40 object-cover rounded-lg mb-3">
                                    <p class="text-xs text-blue-600 font-semibold">Klik untuk mengganti foto</p>
                                </div>

                                <div id="upload-placeholder" class="{{ $car->foto ? 'hidden' : '' }}">
                                    <div
                                        class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <span class="material-symbols-outlined text-gray-400">upload_file</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-600">Klik untuk upload foto baru</p>
                                    <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak ingin mengubah foto</p>
                                </div>

                                <input type="file" id="foto-input" name="foto" accept="image/*" class="hidden"
                                    onchange="previewFoto(this)">
                            </div>
                        </div>

                        {{-- Input Hidden untuk hasil crop --}}
                        <input type="hidden" name="foto_cropped" id="foto-cropped">

                        {{-- Modal Cropper --}}
                        <div id="cropper-modal"
                            class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4">
                            <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl">
                                <div class="p-4 border-b flex justify-between items-center">
                                    <h3 class="font-bold text-gray-800">Sesuaikan Posisi Foto</h3>
                                    <button type="button" onclick="closeCropper()"
                                        class="text-gray-400 hover:text-red-500">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="p-4">
                                    <div class="max-h-[50vh] overflow-hidden bg-gray-50 rounded-lg">
                                        <img id="image-to-crop" class="max-w-full block">
                                    </div>
                                </div>
                                <div class="p-4 bg-gray-50 flex gap-3">
                                    <button type="button" onclick="closeCropper()"
                                        class="flex-1 px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-100 transition-all">Batal</button>
                                    <button type="button" onclick="applyCrop()"
                                        class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 shadow-md transition-all">Terapkan
                                        Potongan</button>
                                </div>
                            </div>
                        </div>

                        {{-- Status Awal --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[16px] text-emerald-600">toggle_on</span>
                                </div>
                                <h2 class="text-sm font-bold text-gray-800">Status Kendaraan</h2>
                            </div>

                            <div class="space-y-2">
                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50">
                                    <input type="radio" name="status" value="Tersedia"
                                        {{ old('status', $car->status) == 'Tersedia' ? 'checked' : '' }}
                                        class="accent-emerald-500">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Tersedia</p>
                                        <p class="text-xs text-gray-400">Siap untuk disewakan</p>
                                    </div>
                                </label>

                                {{-- <label
                                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                                    <input type="radio" name="status" value="Masuk Bengkel"
                                        {{ old('status', $car->status) == 'Masuk Bengkel' ? 'checked' : '' }}
                                        class="accent-amber-500">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Masuk Bengkel</p>
                                        <p class="text-xs text-gray-400">Sedang dalam perawatan</p>
                                    </div>
                                </label> --}}

                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors has-[:checked]:border-red-400 has-[:checked]:bg-red-50">
                                    <input type="radio" name="status" value="Nonaktif" class="accent-red-500"
                                        {{ old('status', $car->status) == 'Nonaktif' ? 'checked' : '' }}>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Nonaktif</p>
                                        <p class="text-xs text-gray-400">Mobil pensiun atau tidak dioperasikan</p>
                                    </div>
                                </label>

                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                                    <input type="radio" name="status" value="Sedang Disewa"
                                        {{ old('status', $car->status) == 'Sedang Disewa' ? 'checked' : '' }}
                                        class="accent-blue-500">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Sedang Disewa</p>
                                        <p class="text-xs text-gray-400">Mobil sedang dibawa customer</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex flex-col gap-2">
                            <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-3 rounded-xl transition-colors shadow-sm">
                                Simpan Perubahan
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
        // Preview nama file dokumen serah terima saat diganti
        const dokumenInput = document.getElementById('foto-dokumen-input');
        if (dokumenInput) {
            dokumenInput.addEventListener('change', function(e) {
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                document.getElementById('dokumen-file-name').textContent = file.name;
                document.getElementById('dokumen-upload-placeholder').classList.add('hidden');
                document.getElementById('dokumen-preview-container').classList.remove('hidden');
            });
        }

        // Script untuk memunculkan preview gambar saat file dipilih
        let cropper = null;
        const modal = document.getElementById('cropper-modal');
        const imageToCrop = document.getElementById('image-to-crop');
        const fotoFinalInput = document.getElementById('foto-cropped');

        function previewFoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    // 1. Masukkan gambar ke dalam modal cropper
                    imageToCrop.src = e.target.result;

                    // 2. Tampilkan Modal
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');

                    // 3. Hancurkan cropper lama jika ada (antisipasi ganti foto berkali-kali)
                    if (cropper) {
                        cropper.destroy();
                    }

                    // 4. Inisialisasi Cropper.js
                    cropper = new Cropper(imageToCrop, {
                        aspectRatio: 16 / 9, // Bisa diganti 1 atau null (bebas)
                        viewMode: 2,
                        dragMode: 'move', // Agar bisa digeser-geser
                        autoCropArea: 1,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function applyCrop() {
            if (!cropper) return;

            // Ambil hasil crop dalam bentuk Base64
            // Bisa atur resolusi output di sini (width/height)
            const canvas = cropper.getCroppedCanvas({
                width: 1280,
                height: 720,
            });

            const croppedImage = canvas.toDataURL('image/jpeg', 0.9); // Quality 90%

            // Tampilkan di preview UI (elemen id preview-img dari kodemu sebelumnya)
            const previewImg = document.getElementById('preview-img');
            const previewContainer = document.getElementById('preview-container');
            const uploadPlaceholder = document.getElementById('upload-placeholder');

            previewImg.src = croppedImage;
            previewContainer.classList.remove('hidden');
            uploadPlaceholder.classList.add('hidden');

            // Masukkan string Base64 ke hidden input untuk dikirim ke Laravel
            fotoFinalInput.value = croppedImage;

            closeCropper();
        }

        function closeCropper() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            // Reset input file agar bisa upload file yang sama jika diinginkan
            document.getElementById('foto-input').value = '';
        }

        function formatRibuan(input, hiddenId) {
            // Hapus semua karakter kecuali angka
            let rawValue = input.value.replace(/[^0-9]/g, '');

            // Update nilai di input hidden (yang dikirim ke database)
            document.getElementById(hiddenId).value = rawValue;

            // Ubah tampilan input teks menjadi format ribuan Indonesia
            if (rawValue) {
                input.value = parseInt(rawValue, 10).toLocaleString('id-ID');
            } else {
                input.value = '';
            }
        }
    </script>
@endsection