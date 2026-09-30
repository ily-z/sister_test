{{-- resources/views/armada/bengkel_form.blade.php --}}
@extends('layouts.app')

@section('title', 'Catat Servis Kendaraan')

@section('content')
    <div class="min-h-screen bg-slate-50/50 pb-12">

        {{-- Page Header --}}
        <div class="bg-white border-b border-slate-200 px-6 py-5 sticky top-16 z-20 shadow-sm">
            <div class="max-w-[1400px] mx-auto flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-4">
                    <a href="{{ url()->previous() }}"
                        class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-full flex items-center justify-center transition-colors text-slate-600 hover:text-slate-900 flex-shrink-0">
                        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                    </a>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Catat Servis Kendaraan</h1>
                        <p class="text-sm text-slate-500 mt-0.5 font-medium">Input rincian perawatan, penggantian part, dan
                            bukti servis</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-[1400px] mx-auto px-6 py-8">

            {{-- KOTAK PENAMPIL ERROR --}}
            @if (session('error'))
                <div
                    class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl font-bold flex items-center gap-2 animate-fade-in-up">
                    <span class="material-symbols-outlined">error</span>
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div
                    class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl font-medium animate-fade-in-up">
                    <div class="flex items-center gap-2 font-bold mb-2">
                        <span class="material-symbols-outlined">warning</span> Peringatan: Form tidak dapat disimpan karena
                        hal berikut:
                    </div>
                    <ul class="list-disc list-inside pl-6 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.armada.bengkel.store', $car->id) }}" method="POST" id="bengkel-form"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="vehicle_id" value="{{ $car->id }}">

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    {{-- ================= KOLOM KIRI (Lebar 2/3) ================= --}}
                    <div class="xl:col-span-2 space-y-6">

                        {{-- 1. Data Kendaraan & Bengkel --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px]">directions_car</span>
                                </div>
                                <h2 class="text-base font-bold text-slate-800">Target Kendaraan</h2>
                            </div>

                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

                                {{-- Identitas Mobil --}}
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Kendaraan Terpilih</label>
                                    <div class="flex items-center gap-4 bg-slate-50 border border-slate-200 rounded-xl p-4">
                                        @if ($car->foto)
                                            <img src="{{ asset('storage/' . $car->foto) }}" alt="{{ $car->model }}"
                                                class="w-12 h-12 object-cover rounded-lg border border-slate-200 shadow-sm flex-shrink-0">
                                        @else
                                            <div class="w-12 h-12 bg-white border border-slate-200 text-slate-400 rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
                                                <span class="material-symbols-outlined text-[24px]">directions_car</span>
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-0.5">
                                                {{ $car->merk }}</p>
                                            <h3 class="text-base font-extrabold text-slate-900 leading-tight mb-1.5">
                                                {{ $car->model }}</h3>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="inline-flex items-center bg-white border border-slate-200 px-2 py-0.5 rounded text-[10px] font-bold font-mono text-slate-700 shadow-sm">
                                                    {{ $car->plat_nomor }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded text-[10px] font-bold">
                                                    <span class="material-symbols-outlined text-[12px]">build</span>
                                                    {{ $car->status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Odometer --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Odometer Masuk Bengkel <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="number" name="km_saat_servis" id="km-servis-input"
                                            class="w-full pl-4 pr-12 py-3 text-sm font-bold text-slate-900 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white transition-all"
                                            value="{{ old('km_saat_servis', isset($openLog) ? $openLog->km_saat_servis : $car->km_saat_ini) }}"
                                            min="{{ $car->km_saat_ini }}" required>
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 uppercase tracking-wider">KM</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1.5 flex items-center gap-1">
                                        KM Terakhir tercatat:
                                        <strong>{{ number_format($car->km_saat_ini, 0, ',', '.') }}</strong>
                                    </p>
                                </div>

                                {{-- Nama Bengkel --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Bengkel / Mitra</label>
                                    <input type="text" name="nama_bengkel" placeholder="Contoh: Auto2000 Basuki Rahmat"
                                        value="{{ old('nama_bengkel', $openLog->nama_bengkel ?? '') }}"
                                        class="w-full px-4 py-3 text-sm font-medium border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white transition-all">
                                </div>
                            </div>
                        </div>

                        {{-- 2. Kondisi Komponen Saat Ini --}}
                        @if (isset($car->parts) && $car->parts->where('is_track_km', true)->count() > 0)
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                                <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                                    <div class="w-8 h-8 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[18px]">monitor_heart</span>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-bold text-slate-800">Kondisi Komponen Saat Ini</h2>
                                        <p class="text-xs text-slate-500 font-medium">Cek part yang mendekati batas umur sebelum input servis</p>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach ($car->parts->where('is_track_km', true) as $part)
                                            @php
                                                $km_berjalan = max(0, $car->km_saat_ini - $part->last_service_km);
                                                $persentase = $part->lifespan_km > 0 ? min(100, ($km_berjalan / $part->lifespan_km) * 100) : 0;
                                                $barColor = $persentase >= 90 ? 'bg-red-500' : ($persentase >= 70 ? 'bg-amber-400' : 'bg-emerald-500');
                                                $badge = $persentase >= 90 ? ['Segera Ganti', 'text-red-600 bg-red-50 border-red-200'] : ($persentase >= 70 ? ['Pantau', 'text-amber-600 bg-amber-50 border-amber-200'] : ['Aman', 'text-emerald-600 bg-emerald-50 border-emerald-200']);
                                            @endphp
                                            <div class="border border-slate-200 rounded-xl p-3.5 hover:border-purple-300 transition-colors">
                                                <div class="flex items-center justify-between gap-2 mb-2">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="material-symbols-outlined text-[16px] text-slate-400 flex-shrink-0">{{ $part->icon }}</span>
                                                        <h4 class="text-xs font-extrabold text-slate-800 truncate">{{ $part->nama_part }}</h4>
                                                        
                                                        {{-- BADGE: AKAN DIRESET (Muncul otomatis via JS) --}}
                                                        <span data-part-id="{{ $part->id }}" class="badge-akan-reset hidden px-1.5 py-0.5 rounded text-[8px] font-black bg-blue-100 text-blue-700 uppercase tracking-wider animate-pulse flex-shrink-0">
                                                            Akan Direset
                                                        </span>
                                                    </div>
                                                    <span class="badge-status-asli inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold border {{ $badge[1] }} flex-shrink-0">
                                                        {{ $badge[0] }}
                                                    </span>
                                                </div>
                                                <div class="w-full bg-slate-100 rounded-full h-1.5 mb-1.5 overflow-hidden">
                                                    <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ $persentase }}%"></div>
                                                </div>
                                                <p class="text-[10px] text-slate-500 font-medium">
                                                    {{ number_format($km_berjalan, 0, ',', '.') }} / {{ number_format($part->lifespan_km, 0, ',', '.') }} km
                                                    <span class="font-bold text-slate-700">({{ round($persentase) }}%)</span>
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    <div class="mt-4 p-3 bg-blue-50/50 border border-blue-100 rounded-lg flex gap-2 items-start">
                                        <span class="material-symbols-outlined text-[16px] text-blue-500 mt-0.5">info</span>
                                        <p class="text-[11px] text-blue-800 font-medium">
                                            Komponen yang Anda pilih di bawah akan ditandai <strong class="bg-blue-100 px-1 rounded text-blue-700">AKAN DIRESET</strong>. Usia komponen tersebut baru akan benar-benar di-reset ke 0 km <strong class="underline decoration-blue-300">setelah status diubah menjadi Selesai Servis (Closed).</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- 3. Rincian Komponen / Parts (TANPA CHECKBOX RESET) --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[18px]">build</span>
                                    </div>
                                    <h2 class="text-base font-bold text-slate-800">Rincian Perbaikan & Penggantian Part</h2>
                                </div>
                                <button type="button" onclick="addPartRow()"
                                    class="text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5 border border-blue-200 hover:border-blue-600 shadow-sm">
                                    <span class="material-symbols-outlined text-[16px]">add</span> Tambah Komponen
                                </button>
                            </div>

                            <div class="p-6">
                                <div class="hidden md:grid grid-cols-12 gap-4 mb-3 px-2">
                                    <div class="col-span-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Pilih Komponen</div>
                                    <div class="col-span-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tindakan / Detail</div>
                                    <div class="col-span-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right pr-12">Biaya (Rp)</div>
                                </div>

                                <div id="parts-container" class="space-y-4">
                                    @if (isset($openLog) && $openLog->serviceLogDetails->count() > 0)
                                        @foreach ($openLog->serviceLogDetails as $detail)
                                            <div class="part-row grid grid-cols-1 md:grid-cols-12 gap-4 bg-slate-50 p-4 md:p-2 md:bg-transparent rounded-xl border border-slate-100 md:border-none relative group items-start">
                                                <div class="col-span-1 md:col-span-5">
                                                    <label class="md:hidden block text-xs font-bold text-slate-500 mb-1">Pilih Komponen</label>
                                                    <select name="car_part_id[]" onchange="syncVisualBadges()" class="w-full px-3 py-2.5 text-sm font-medium border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white cursor-pointer">
                                                        <option value="">-- Servis Umum / Lainnya --</option>
                                                        @if (isset($car->parts) && $car->parts->count() > 0)
                                                            <optgroup label="🔧 Komponen Dilacak">
                                                                @foreach ($car->parts as $part)
                                                                    <option value="{{ $part->id }}"
                                                                        {{ $detail->car_part_id == $part->id ? 'selected' : '' }}>
                                                                        {{ $part->nama_part }}</option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endif
                                                    </select>
                                                </div>

                                                <div class="col-span-1 md:col-span-4">
                                                    <label class="md:hidden block text-xs font-bold text-slate-500 mb-1">Deskripsi / Tindakan <span class="text-red-500">*</span></label>
                                                    <input type="text" name="deskripsi_tindakan[]"
                                                        value="{{ $detail->deskripsi_tindakan }}"
                                                        placeholder="Contoh: Ganti oli..."
                                                        class="w-full px-3 py-2.5 text-sm font-medium border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white"
                                                        required>
                                                </div>

                                                <div class="col-span-1 md:col-span-3 flex items-start gap-2">
                                                    <div class="flex-1 relative">
                                                        <label class="md:hidden block text-xs font-bold text-slate-500 mb-1">Biaya (Rp) <span class="text-red-500">*</span></label>
                                                        <span class="absolute left-3 md:top-1/2 top-[70%] -translate-y-1/2 text-sm text-slate-400 font-bold">Rp</span>
                                                        <input type="number" name="biaya[]" value="{{ round($detail->biaya) }}"
                                                            placeholder="0" oninput="calculateTotal()"
                                                            class="part-cost-input w-full pl-9 pr-3 py-2.5 text-sm font-bold text-slate-900 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white text-right"
                                                            required min="0">
                                                    </div>
                                                    <button type="button" onclick="removePartRow(this)"
                                                        class="md:mt-0 mt-6 w-10 h-[42px] bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-lg flex items-center justify-center transition-colors flex-shrink-0"
                                                        title="Hapus Item">
                                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="part-row grid grid-cols-1 md:grid-cols-12 gap-4 bg-slate-50 p-4 md:p-2 md:bg-transparent rounded-xl border border-slate-100 md:border-none relative group items-start">
                                            <div class="col-span-1 md:col-span-5">
                                                <label class="md:hidden block text-xs font-bold text-slate-500 mb-1">Pilih Komponen</label>
                                                <select name="car_part_id[]" onchange="syncVisualBadges()" class="w-full px-3 py-2.5 text-sm font-medium border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white cursor-pointer">
                                                    <option value="">-- Servis Umum / Lainnya --</option>
                                                    @if (isset($car->parts) && $car->parts->count() > 0)
                                                        <optgroup label="🔧 Komponen Dilacak">
                                                            @foreach ($car->parts as $part)
                                                                <option value="{{ $part->id }}">{{ $part->nama_part }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-span-1 md:col-span-4">
                                                <label class="md:hidden block text-xs font-bold text-slate-500 mb-1">Deskripsi / Tindakan <span class="text-red-500">*</span></label>
                                                <input type="text" name="deskripsi_tindakan[]" placeholder="Contoh: Ganti oli..."
                                                    class="w-full px-3 py-2.5 text-sm font-medium border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white"
                                                    required>
                                            </div>
                                            <div class="col-span-1 md:col-span-3 flex items-start gap-2">
                                                <div class="flex-1 relative">
                                                    <label class="md:hidden block text-xs font-bold text-slate-500 mb-1">Biaya (Rp) <span class="text-red-500">*</span></label>
                                                    <span class="absolute left-3 md:top-1/2 top-[70%] -translate-y-1/2 text-sm text-slate-400 font-bold">Rp</span>
                                                    <input type="number" name="biaya[]" placeholder="0"
                                                        oninput="calculateTotal()"
                                                        class="part-cost-input w-full pl-9 pr-3 py-2.5 text-sm font-bold text-slate-900 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white text-right"
                                                        required min="0">
                                                </div>
                                                <button type="button" onclick="removePartRow(this)"
                                                    class="md:mt-0 mt-6 w-10 h-[42px] bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-lg flex items-center justify-center transition-colors flex-shrink-0"
                                                    title="Hapus Item">
                                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div id="empty-part-state" class="hidden text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-300 mt-4">
                                    <span class="material-symbols-outlined text-[40px] text-slate-300 mb-2">construction</span>
                                    <p class="text-sm font-medium text-slate-500">Belum ada komponen servis yang dicatat.</p>
                                    <button type="button" onclick="addPartRow()" class="mt-3 text-xs font-bold text-blue-600 hover:text-blue-800 underline">Tambah Baris Servis</button>
                                </div>
                            </div>
                        </div>

                        {{-- 4. Upload Bukti / Nota Servis --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                                <div class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                                </div>
                                <h2 class="text-base font-bold text-slate-800">Bukti / Nota Servis</h2>
                            </div>
                            <div class="p-6">
                                <label for="foto-pendukung-input"
                                    class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-slate-200 rounded-xl py-8 cursor-pointer hover:border-blue-400 hover:bg-blue-50/30 transition-colors text-center">
                                    <span class="material-symbols-outlined text-[32px] text-slate-300">cloud_upload</span>
                                    <span class="text-sm font-bold text-slate-600">Klik untuk upload foto nota / bukti servis</span>
                                    <span class="text-xs text-slate-400">PNG, JPG, atau PDF — maks. 5MB</span>
                                    <input type="file" id="foto-pendukung-input" name="foto_pendukung" accept="image/*,.pdf"
                                        class="hidden" onchange="previewFotoPendukung(this)">
                                </label>
                                <div id="foto-pendukung-preview"
                                    class="hidden mt-4 flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl p-3">
                                    <span class="material-symbols-outlined text-[24px] text-emerald-500">task_alt</span>
                                    <span id="foto-pendukung-filename" class="text-sm font-bold text-slate-700 truncate flex-1"></span>
                                    <button type="button" onclick="clearFotoPendukung()"
                                        class="text-xs font-bold text-red-500 hover:text-red-700">Hapus</button>
                                </div>
                            </div>
                        </div>

                    </div> {{-- AKHIR DARI KOLOM KIRI --}}

                    {{-- ================= KOLOM KANAN (Lebar 1/3) ================= --}}
                    <div class="space-y-6">

                        {{-- 5. Status & Jadwal --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                                <div class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px]">calendar_clock</span>
                                </div>
                                <h2 class="text-base font-bold text-slate-800">Status & Jadwal</h2>
                            </div>

                            <div class="p-6 space-y-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Status Pengerjaan</label>
                                    <div class="space-y-2">
                                        <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-amber-50 transition-colors has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/50 has-[:checked]:ring-1 has-[:checked]:ring-amber-500/50">
                                            <input type="radio" name="status_bengkel" value="open" checked
                                                class="mt-0.5 accent-amber-600 w-4 h-4">
                                            <div>
                                                <p class="text-sm font-bold text-slate-800">Sedang Diperbaiki (Open)</p>
                                                <p class="text-xs text-slate-500 mt-0.5 font-medium">Kendaraan berada di bengkel</p>
                                            </div>
                                        </label>
                                        <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-emerald-50 transition-colors has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 has-[:checked]:ring-1 has-[:checked]:ring-emerald-500/50">
                                            <input type="radio" name="status_bengkel" value="closed"
                                                class="mt-0.5 accent-emerald-600 w-4 h-4">
                                            <div>
                                                <p class="text-sm font-bold text-slate-800">Selesai Servis (Closed)</p>
                                                <p class="text-xs text-slate-500 mt-0.5 font-medium">Kendaraan siap disewakan</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tgl Masuk <span class="text-red-500">*</span></label>
                                        <input type="date" name="tanggal_masuk"
                                            value="{{ old('tanggal_masuk', isset($openLog) ? $openLog->tanggal_masuk : date('Y-m-d')) }}"
                                            class="w-full px-3 py-2.5 text-sm font-medium border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                                            required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2" id="label-tgl-keluar">Est. Selesai</label>
                                        <input type="date" name="tanggal_keluar" id="input-tgl-keluar"
                                            value="{{ old('tanggal_keluar', $openLog->tanggal_keluar ?? '') }}"
                                            class="w-full px-3 py-2.5 text-sm font-medium border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Keluhan / Catatan Montir</label>
                                    <textarea name="catatan_umum" rows="3" placeholder="Misal: Bunyi decit di rem depan, sudah dicek aman..."
                                        class="w-full px-4 py-3 text-sm font-medium border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white resize-none">{{ old('catatan_umum', $openLog->catatan ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- 6. Riwayat Servis Singkat --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                                <div class="w-8 h-8 bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px]">history</span>
                                </div>
                                <h2 class="text-base font-bold text-slate-800">Riwayat Servis Terakhir</h2>
                            </div>
                            <div class="p-6 space-y-3">
                                @forelse(($recentServiceLogs ?? collect())->take(3) as $log)
                                    <div class="border border-slate-100 rounded-xl p-3.5">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="text-xs font-bold text-slate-700">
                                                {{ \Carbon\Carbon::parse($log->tanggal_masuk)->translatedFormat('d M Y') }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                                {{ $log->status_bengkel === 'closed' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-amber-50 text-amber-600 border border-amber-200' }}">
                                                {{ $log->status_bengkel === 'closed' ? 'Selesai' : 'Berjalan' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 font-medium line-clamp-2">{{ $log->catatan ?: '—' }}</p>
                                        <p class="text-[11px] text-slate-400 mt-1.5 font-bold">
                                            KM {{ number_format($log->km_saat_servis, 0, ',', '.') }}
                                            • Rp {{ number_format($log->serviceLogDetails->sum('biaya') ?? 0, 0, ',', '.') }}
                                        </p>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 italic text-center py-4">Belum ada riwayat servis untuk kendaraan ini.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- 7. Ringkasan & Total --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-lg overflow-hidden relative">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-bl-full -z-0 opacity-50"></div>

                            <div class="p-6 relative z-10">
                                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4">Total Biaya Servis</h2>

                                <div class="flex items-end gap-2 mb-6">
                                    <span class="text-xl font-bold text-slate-400 mb-1">Rp</span>
                                    <span id="display-total-biaya" class="text-4xl font-black text-slate-900 tracking-tight">0</span>
                                </div>

                                <div class="space-y-3 mb-8 border-t border-slate-100 pt-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-slate-500">Jumlah Item Servis</span>
                                        <span id="display-total-item" class="text-sm font-bold text-slate-800 bg-slate-100 px-2 py-1 rounded-md">1 Baris</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-slate-500">Part Direset (Saat Selesai)</span>
                                        <span id="display-total-reset" class="text-sm font-bold text-blue-700 bg-blue-50 px-2 py-1 rounded-md">0 Part</span>
                                    </div>
                                </div>

                                {{-- Tombol Aksi --}}
                                <div class="flex flex-col gap-3">
                                    <button type="submit"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-3.5 rounded-xl transition-all shadow-md shadow-blue-200 active:scale-95 flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-[20px]">save</span> Simpan Data Servis
                                    </button>
                                    <a href="{{ route('admin.armada.show', $car->id) }}"
                                        class="w-full text-center text-sm font-bold text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 py-3.5 rounded-xl border border-slate-200 transition-colors">
                                        Batalkan
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div> {{-- AKHIR DARI KOLOM KANAN --}}
                </div> {{-- AKHIR DARI GRID --}}
            </form>
        </div>
    </div>

    {{-- TEMPLATE UNTUK JAVASCRIPT (Hidden) --}}
    <template id="part-row-template">
        <div class="part-row grid grid-cols-1 md:grid-cols-12 gap-4 bg-slate-50 p-4 md:p-2 md:bg-transparent rounded-xl border border-slate-100 md:border-none relative group items-start mt-4">

            <div class="col-span-1 md:col-span-5">
                <label class="md:hidden block text-xs font-bold text-slate-500 mb-1">Pilih Komponen</label>
                <select name="car_part_id[]" onchange="syncVisualBadges()"
                    class="w-full px-3 py-2.5 text-sm font-medium border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white cursor-pointer">
                    <option value="">-- Servis Umum / Lainnya --</option>
                    @if (isset($car->parts) && $car->parts->count() > 0)
                        <optgroup label="🔧 Komponen Dilacak">
                            @foreach ($car->parts as $part)
                                <option value="{{ $part->id }}">{{ $part->nama_part }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
            </div>

            <div class="col-span-1 md:col-span-4">
                <label class="md:hidden block text-xs font-bold text-slate-500 mb-1">Deskripsi / Tindakan <span
                        class="text-red-500">*</span></label>
                <input type="text" name="deskripsi_tindakan[]" placeholder="Contoh: Ganti oli..."
                    class="w-full px-3 py-2.5 text-sm font-medium border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white"
                    required>
            </div>

            <div class="col-span-1 md:col-span-3 flex items-start gap-2">
                <div class="flex-1 relative">
                    <label class="md:hidden block text-xs font-bold text-slate-500 mb-1">Biaya (Rp) <span
                            class="text-red-500">*</span></label>
                    <span class="absolute left-3 md:top-1/2 top-[70%] -translate-y-1/2 text-sm text-slate-400 font-bold">Rp</span>
                    <input type="number" name="biaya[]" placeholder="0" oninput="calculateTotal()"
                        class="part-cost-input w-full pl-9 pr-3 py-2.5 text-sm font-bold text-slate-900 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white text-right"
                        required min="0">
                </div>
                <button type="button" onclick="removePartRow(this)"
                    class="md:mt-0 mt-6 w-10 h-[42px] bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-lg flex items-center justify-center transition-colors flex-shrink-0"
                    title="Hapus Item">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                </button>
            </div>
        </div>
    </template>

    <script>
        function addPartRow() {
            const container = document.getElementById('parts-container');
            const template = document.getElementById('part-row-template').content.cloneNode(true);

            document.getElementById('empty-part-state').classList.add('hidden');
            container.appendChild(template);
            updateItemCount();
        }

        function removePartRow(btn) {
            const row = btn.closest('.part-row');
            row.remove();

            if (document.querySelectorAll('.part-row').length === 0) {
                document.getElementById('empty-part-state').classList.remove('hidden');
            }

            calculateTotal();
            updateItemCount();
            syncVisualBadges(); // Update badge jika baris dihapus
        }

        function calculateTotal() {
            const costInputs = document.querySelectorAll('.part-cost-input');
            let total = 0;

            costInputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });

            document.getElementById('display-total-biaya').textContent = total.toLocaleString('id-ID');
        }

        function updateItemCount() {
            const count = document.querySelectorAll('.part-row').length;
            document.getElementById('display-total-item').textContent = count + ' Baris';
        }

        // FUNGSI BARU: Sinkronisasi Badge Visual dan Angka Total Part Direset
        function syncVisualBadges() {
            const selectedParts = [];
            let count = 0;

            // Kumpulkan ID Part apa saja yang dipilih
            document.querySelectorAll('select[name="car_part_id[]"]').forEach(select => {
                if (select.value !== '') {
                    selectedParts.push(select.value);
                    count++;
                }
            });

            // Update Angka di Ringkasan
            document.getElementById('display-total-reset').textContent = count + ' Part';

            // Sembunyikan atau Munculkan Badge di tiap Card Komponen
            document.querySelectorAll('.badge-akan-reset').forEach(badge => {
                const partId = badge.getAttribute('data-part-id');
                const badgeStatusAsli = badge.nextElementSibling; // Mengambil badge Aman/Pantau/Ganti

                if (selectedParts.includes(partId)) {
                    badge.classList.remove('hidden');
                    if (badgeStatusAsli) badgeStatusAsli.classList.add('hidden'); // Sembunyikan status asli
                } else {
                    badge.classList.add('hidden');
                    if (badgeStatusAsli) badgeStatusAsli.classList.remove('hidden'); // Munculkan status asli
                }
            });
        }

        function previewFotoPendukung(input) {
            const preview = document.getElementById('foto-pendukung-preview');
            const filename = document.getElementById('foto-pendukung-filename');

            if (input.files && input.files.length > 0) {
                filename.textContent = input.files[0].name;
                preview.classList.remove('hidden');
                preview.classList.add('flex');
            }
        }

        function clearFotoPendukung() {
            const input = document.getElementById('foto-pendukung-input');
            const preview = document.getElementById('foto-pendukung-preview');
            input.value = '';
            preview.classList.add('hidden');
            preview.classList.remove('flex');
        }

        document.querySelectorAll('input[name="status_bengkel"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const labelOut = document.getElementById('label-tgl-keluar');
                const inputOut = document.getElementById('input-tgl-keluar');

                if (this.value === 'closed') {
                    labelOut.innerHTML = 'Tgl Selesai <span class="text-red-500">*</span>';
                    inputOut.setAttribute('required', 'required');
                    
                    // --- TAMBAHAN BARU: Otomatis isi tanggal hari ini ---
                    let d = new Date();
                    inputOut.value = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
                } else {
                    labelOut.innerHTML = 'Est. Selesai';
                    inputOut.removeAttribute('required');
                }
            });
        });

        // TRIGGERS AWAL KETIKA HALAMAN DIMUAT
        window.addEventListener('DOMContentLoaded', () => {
            calculateTotal();
            updateItemCount();
            syncVisualBadges(); // Panggil saat form load (berguna jika load dari draft)
            
            document.querySelectorAll('input[name="status_bengkel"]').forEach(radio => {
                if (radio.checked) radio.dispatchEvent(new Event('change'));
            });
        });
    </script>
@endsection