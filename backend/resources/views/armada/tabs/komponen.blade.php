{{-- resources/views/armada/tabs/komponen.blade.php --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- KIRI: Pelacakan Part --}}
    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">build_circle</span>
                    </div>
                    <h2 class="text-base font-bold text-slate-800">Pelacakan Usia Komponen</h2>
                </div>
                <button onclick="document.getElementById('modal-add-part').classList.remove('hidden')"
                    class="text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5 border border-blue-200 shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">add</span> Mulai Lacak Part
                </button>
            </div>

            {{-- TABS FILTER KOMPONEN INTERNAL (Desain Toggle Segmented Control) --}}
            @if (isset($car->parts) && $car->parts->count() > 0)
                <div class="px-6 py-4 border-b border-slate-100 bg-white">
                    <div
                        class="inline-flex bg-slate-100 border border-slate-200 rounded-xl p-1 gap-1 text-xs font-bold w-full sm:w-auto overflow-x-auto">
                        <label class="cursor-pointer flex-1 sm:flex-none">
                            <input type="radio" name="filter_komponen" value="all" class="sr-only peer"
                                onchange="filterGroup('all')" checked>
                            <span
                                class="flex items-center justify-center px-5 py-2 rounded-lg transition-all peer-checked:bg-white peer-checked:text-blue-700 peer-checked:shadow-sm text-slate-500 hover:text-slate-700 whitespace-nowrap">
                                Semua Komponen
                            </span>
                        </label>
                        <label class="cursor-pointer flex-1 sm:flex-none">
                            <input type="radio" name="filter_komponen" value="rutin" class="sr-only peer"
                                onchange="filterGroup('rutin')">
                            <span
                                class="flex items-center justify-center px-5 py-2 rounded-lg transition-all peer-checked:bg-white peer-checked:text-blue-700 peer-checked:shadow-sm text-slate-500 hover:text-slate-700 whitespace-nowrap">
                                Servis Rutin (KM)
                            </span>
                        </label>
                        <label class="cursor-pointer flex-1 sm:flex-none">
                            <input type="radio" name="filter_komponen" value="aksesoris" class="sr-only peer"
                                onchange="filterGroup('aksesoris')">
                            <span
                                class="flex items-center justify-center px-5 py-2 rounded-lg transition-all peer-checked:bg-white peer-checked:text-blue-700 peer-checked:shadow-sm text-slate-500 hover:text-slate-700 whitespace-nowrap">
                                Aksesoris & Interior
                            </span>
                        </label>
                    </div>
                </div>
            @endif

            <div class="p-6 flex flex-col gap-8">
                @if (isset($car->parts) && $car->parts->count() > 0)

                    @php
                        $rutinParts = $car->parts->where('is_track_km', true);
                        $aksesorisParts = $car->parts->where('is_track_km', false);
                    @endphp

                    {{-- GRUP 1: SERVIS RUTIN (KM) --}}
                    @if ($rutinParts->count() > 0)
                        <div id="group-rutin" class="part-group">
                            <div class="flex items-center gap-2 border-b border-slate-100 pb-2 mb-4">
                                <span class="material-symbols-outlined text-[18px] text-blue-500">settings</span>
                                <h3 class="text-sm font-bold text-slate-800">Servis Rutin (Berbasis KM)</h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                @foreach ($rutinParts as $part)
                                    @php
                                        $km_berjalan = max(0, $car->km_saat_ini - $part->last_service_km);
                                        $persentase =
                                            $part->lifespan_km > 0
                                                ? min(100, ($km_berjalan / $part->lifespan_km) * 100)
                                                : 0;
                                        $sisa_km = max(0, $part->lifespan_km - $km_berjalan);
                                        $barColor =
                                            $persentase >= 90
                                                ? 'bg-red-500'
                                                : ($persentase >= 70
                                                    ? 'bg-amber-400'
                                                    : 'bg-emerald-500');
                                    @endphp

                                    <div
                                        class="border border-slate-200 rounded-xl p-4 relative overflow-hidden group hover:border-blue-300 transition-colors">

                                        {{-- TOMBOL HAPUS --}}
                                        <form action="{{ route('admin.armada.part.destroy', $part->id) }}"
                                            method="POST" class="absolute top-3 right-3 z-10"
                                            onsubmit="sessionStorage.setItem('activeMainTab', 'komponen'); return confirm('Hapus pelacakan komponen {{ $part->nama_part }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="w-7 h-7 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white border border-red-100 hover:border-red-500 rounded-md flex items-center justify-center transition-colors shadow-sm cursor-pointer"
                                                title="Berhenti Lacak Komponen">
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                        </form>

                                        <div class="flex items-start gap-3 mb-3">
                                            <div
                                                class="w-10 h-10 bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors mt-1">
                                                <span class="material-symbols-outlined">{{ $part->icon }}</span>
                                            </div>
                                            <div class="flex-1 min-w-0 pr-8">
                                                {{-- Teks Statis (Bisa diklik 2x untuk Ganti Nama) --}}
                                                <div id="part-name-display-{{ $part->id }}"
                                                    ondblclick="enableInlineEdit('{{ $part->id }}')"
                                                    class="cursor-text hover:bg-slate-100 rounded px-1 -ml-1 py-0.5 transition-colors"
                                                    title="Double click untuk Edit Nama & Target">
                                                    <h3 class="text-sm font-extrabold text-slate-900 truncate">
                                                        {{ $part->nama_part }}</h3>
                                                    <p class="text-[10px] text-slate-500 font-bold mt-0.5">Target:
                                                        {{ number_format($part->lifespan_km, 0, ',', '.') }} km</p>

                                                    <div
                                                        class="flex items-center gap-1 mt-1 text-[10px] text-slate-400">
                                                        <span
                                                            class="material-symbols-outlined text-[12px]">schedule</span>
                                                        <span>Terakhir: KM
                                                            {{ number_format($part->last_service_km, 0, ',', '.') }}</span>
                                                    </div>
                                                </div>

                                                {{-- FORM INLINE EDIT (Hanya untuk Nama & Target) --}}
                                                <form id="part-name-form-{{ $part->id }}"
                                                    action="{{ route('admin.armada.part.update', $part->id) }}"
                                                    method="POST" class="hidden w-full mt-1"
                                                    onsubmit="sessionStorage.setItem('activeMainTab', 'komponen');">
                                                    @csrf
                                                    @method('PUT')
                                                    <div
                                                        class="bg-blue-50/50 p-2.5 rounded-lg border border-blue-200 shadow-sm relative z-20">
                                                        <div class="mb-2">
                                                            <label
                                                                class="text-[9px] font-bold text-slate-500 uppercase block mb-0.5">Nama
                                                                Part</label>
                                                            <input type="text" name="nama_part"
                                                                value="{{ $part->nama_part }}" required
                                                                class="w-full px-2 py-1.5 text-sm font-extrabold text-slate-900 border border-blue-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label
                                                                class="text-[9px] font-bold text-slate-500 uppercase block mb-0.5">Target
                                                                Umur (KM)</label>
                                                            <input type="number" name="lifespan_km"
                                                                value="{{ $part->lifespan_km }}"
                                                                class="w-full px-2 py-1.5 text-xs font-medium text-slate-900 border border-blue-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                        </div>
                                                        <div class="flex justify-end gap-1.5">
                                                            <button type="button"
                                                                onclick="cancelInlineEdit('{{ $part->id }}')"
                                                                class="bg-slate-200 text-slate-600 text-[10px] font-bold px-3 py-1.5 rounded hover:bg-slate-300 transition-colors">Batal</button>
                                                            <button type="submit"
                                                                class="bg-blue-600 text-white text-[10px] font-bold px-3 py-1.5 rounded hover:bg-blue-700 flex items-center gap-1 transition-colors">
                                                                <span
                                                                    class="material-symbols-outlined text-[12px]">save</span>
                                                                Simpan
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- PROGRESS BAR AREA --}}
                                        <div class="mt-4">
                                            <div class="flex justify-between items-end mb-1.5">
                                                <span
                                                    class="text-[10px] font-bold uppercase tracking-wider {{ $persentase >= 90 ? 'text-red-500 animate-pulse' : 'text-slate-400' }}">
                                                    {{ $persentase >= 90 ? 'Waktunya Servis!' : 'Status Keausan' }}
                                                </span>
                                                <span
                                                    class="text-xs font-bold text-slate-700">{{ number_format($km_berjalan, 0, ',', '.') }}
                                                    km</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-2 mb-1.5 overflow-hidden">
                                                <div class="{{ $barColor }} h-2 rounded-full transition-all"
                                                    style="width: {{ $persentase }}%"></div>
                                            </div>
                                            <p class="text-[10px] text-slate-500 font-medium text-right">Sisa: <strong
                                                    class="text-slate-700">{{ number_format($sisa_km, 0, ',', '.') }}
                                                    km</strong></p>

                                            {{-- TOMBOL TOGGLE (EXPAND/COLLAPSE) UNTUK SLIDER --}}
                                            <div class="text-center mt-1">
                                                <button type="button" onclick="toggleWearEdit('{{ $part->id }}')"
                                                    class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-600 hover:text-blue-800 transition-colors bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-full">
                                                    Sesuaikan Keausan <span id="wear-icon-{{ $part->id }}"
                                                        class="material-symbols-outlined text-[14px] transition-transform duration-300">expand_more</span>
                                                </button>
                                            </div>

                                            {{-- PANEL SLIDER KEAUSAN (Tersembunyi secara default) --}}
                                            <form id="wear-form-{{ $part->id }}"
                                                action="{{ route('admin.armada.part.update', $part->id) }}"
                                                method="POST"
                                                class="hidden mt-3 p-3 bg-slate-50 rounded-xl border border-slate-200 animate-fade-in-up"
                                                onsubmit="sessionStorage.setItem('activeMainTab', 'komponen');">
                                                @csrf
                                                @method('PUT')
                                                {{-- Hidden input agar lolos validasi controller --}}
                                                <input type="hidden" name="nama_part"
                                                    value="{{ $part->nama_part }}">
                                                <input type="hidden" name="lifespan_km"
                                                    value="{{ $part->lifespan_km }}">

                                                <div class="flex items-center justify-between mb-2">
                                                    <label
                                                        class="text-[9px] font-bold text-slate-600 uppercase block">Geser
                                                        Keausan Aktual</label>
                                                    <span id="pct-{{ $part->id }}"
                                                        class="text-[10px] font-extrabold text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded">
                                                        {{ round($persentase) }}%
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 mb-3">
                                                    {{-- RANGE SLIDER DENGAN TRACK FILL BIRU --}}
                                                    <input type="range" id="range-{{ $part->id }}"
                                                        name="keausan_km" min="0"
                                                        max="{{ $part->lifespan_km }}" value="{{ $km_berjalan }}"
                                                        class="flex-1 h-1.5 rounded-lg appearance-none cursor-pointer accent-blue-600 outline-none"
                                                        style="background: linear-gradient(to right, #2563eb {{ $persentase }}%, #e2e8f0 {{ $persentase }}%);"
                                                        oninput="
                                                            document.getElementById('num-{{ $part->id }}').value = this.value;
                                                            let pct = (this.value / {{ max(1, $part->lifespan_km) }}) * 100;
                                                            document.getElementById('pct-{{ $part->id }}').innerText = Math.round(pct) + '%';
                                                            this.style.background = `linear-gradient(to right, #2563eb ${pct}%, #e2e8f0 ${pct}%)`;
                                                        ">

                                                    {{-- INPUT NUMBER (SINKRON DENGAN SLIDER) --}}
                                                    <input type="number" id="num-{{ $part->id }}"
                                                        min="0" max="{{ $part->lifespan_km }}"
                                                        value="{{ $km_berjalan }}"
                                                        class="w-16 px-1.5 py-1 text-[11px] font-bold text-blue-700 bg-white border border-slate-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 text-center"
                                                        oninput="
                                                            let rangeEl = document.getElementById('range-{{ $part->id }}');
                                                            rangeEl.value = this.value;
                                                            let pct = (this.value / {{ max(1, $part->lifespan_km) }}) * 100;
                                                            document.getElementById('pct-{{ $part->id }}').innerText = Math.round(pct) + '%';
                                                            rangeEl.style.background = `linear-gradient(to right, #2563eb ${pct}%, #e2e8f0 ${pct}%)`;
                                                        ">
                                                </div>
                                                <div class="flex justify-end">
                                                    <button type="submit"
                                                        class="bg-slate-800 text-white text-[10px] font-bold px-4 py-1.5 rounded-lg hover:bg-slate-900 transition-colors w-full">
                                                        Simpan Angka Keausan
                                                    </button>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- GRUP 2: AKSESORIS & INTERIOR --}}
                    @if ($aksesorisParts->count() > 0)
                        <div id="group-aksesoris" class="part-group">
                            <div class="flex items-center gap-2 border-b border-slate-100 pb-2 mb-4">
                                <span class="material-symbols-outlined text-[18px] text-purple-500">style</span>
                                <h3 class="text-sm font-bold text-slate-800">Aksesoris & Interior (Non-KM)</h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                @foreach ($aksesorisParts as $part)
                                    <div
                                        class="border border-slate-200 rounded-xl p-4 relative overflow-hidden group hover:border-purple-300 transition-colors">

                                        {{-- TOMBOL HAPUS --}}
                                        <form action="{{ route('admin.armada.part.destroy', $part->id) }}"
                                            method="POST" class="absolute top-3 right-3 z-10"
                                            onsubmit="sessionStorage.setItem('activeMainTab', 'komponen'); return confirm('Hapus pelacakan komponen {{ $part->nama_part }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="w-7 h-7 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white border border-red-100 hover:border-red-500 rounded-md flex items-center justify-center transition-colors shadow-sm cursor-pointer"
                                                title="Berhenti Lacak Komponen">
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                        </form>

                                        <div class="flex items-start gap-3 mb-3">
                                            <div
                                                class="w-10 h-10 bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-purple-50 group-hover:text-purple-600 transition-colors mt-1">
                                                <span class="material-symbols-outlined">{{ $part->icon }}</span>
                                            </div>
                                            <div class="flex-1 min-w-0 pr-8">
                                                {{-- Teks Statis --}}
                                                <div id="part-name-display-{{ $part->id }}"
                                                    ondblclick="enableInlineEdit('{{ $part->id }}')"
                                                    class="cursor-text hover:bg-slate-100 rounded px-1 -ml-1 py-0.5 transition-colors"
                                                    title="Double click untuk Edit">
                                                    <h3 class="text-sm font-extrabold text-slate-900 truncate">
                                                        {{ $part->nama_part }}</h3>
                                                    <span
                                                        class="inline-block mt-0.5 text-[10px] font-bold bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">Non-KM
                                                        Tracked</span>

                                                    <div
                                                        class="flex items-center gap-1 mt-1 text-[10px] text-slate-400">
                                                        <span
                                                            class="material-symbols-outlined text-[12px]">schedule</span>
                                                        <span>Dipasang: KM
                                                            {{ number_format($part->last_service_km, 0, ',', '.') }}</span>
                                                    </div>
                                                </div>

                                                {{-- Form Inline --}}
                                                <form id="part-name-form-{{ $part->id }}"
                                                    action="{{ route('admin.armada.part.update', $part->id) }}"
                                                    method="POST" class="hidden w-full mt-1"
                                                    onsubmit="sessionStorage.setItem('activeMainTab', 'komponen');">
                                                    @csrf
                                                    @method('PUT')
                                                    <div
                                                        class="bg-purple-50/50 p-2.5 rounded-lg border border-purple-200 shadow-sm relative z-20">
                                                        <div class="mb-3">
                                                            <label
                                                                class="text-[9px] font-bold text-slate-500 uppercase block mb-0.5">Nama
                                                                Aksesoris</label>
                                                            <input type="text"
                                                                id="part-name-input-{{ $part->id }}"
                                                                name="nama_part" value="{{ $part->nama_part }}"
                                                                required
                                                                class="w-full px-2 py-1.5 text-sm font-extrabold text-slate-900 border border-purple-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                                        </div>
                                                        <div class="flex justify-end gap-1.5">
                                                            <button type="button"
                                                                onclick="cancelInlineEdit('{{ $part->id }}')"
                                                                class="bg-slate-200 text-slate-600 text-[10px] font-bold px-3 py-1.5 rounded hover:bg-slate-300 transition-colors">Batal</button>
                                                            <button type="submit"
                                                                class="bg-purple-600 text-white text-[10px] font-bold px-3 py-1.5 rounded hover:bg-purple-700 flex items-center gap-1 transition-colors">
                                                                <span
                                                                    class="material-symbols-outlined text-[12px]">save</span>
                                                                Simpan
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                        <span class="material-symbols-outlined text-[40px] text-slate-300 mb-2">build</span>
                        <h3 class="text-sm font-bold text-slate-700 mb-1">Belum Ada Part yang Dilacak</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">Mulai lacak jadwal ganti oli, ban, dll di
                            sini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- KANAN: Riwayat Servis (Odometer Dihapus) --}}
    <div class="space-y-6">

        {{-- Quick Update Odometer --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 bg-purple-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-[16px] text-purple-600">speed</span>
                    </div>
                    <h2 class="text-sm font-bold text-gray-800">Odometer Terkini</h2>
                </div>
            </div>
            <div class="text-center bg-gray-50 rounded-xl py-4 border border-gray-100">
                <p class="text-3xl font-black text-gray-900">{{ number_format($car->km_saat_ini, 0, ',', '.') }}</p>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mt-1">Kilometer</p>
            </div>

            {{-- Log Odometer --}}
            <div class="mt-4 pt-4 border-t border-slate-100 space-y-2">
                <p class="text-xs font-bold text-slate-500 mb-2">Riwayat Update:</p>
                @forelse($latestOdometerLogs ?? [] as $log)
                @empty
                    <p class="text-xs text-gray-400 italic">Belum ada riwayat update.</p>
                @endforelse
            </div>
        </div>

        {{-- Riwayat Bengkel (UI Konsisten dengan Riwayat Servis Terakhir) --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            {{-- Header Konsisten --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px] text-amber-600">home_repair_service</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Riwayat Servis</h3>
                        <p class="text-[10px] text-slate-500 mt-0.5">Catatan perbaikan mobil ini</p>
                    </div>
                </div>
            </div>

            {{-- List Container dengan Scrollbar --}}
            <div class="flex flex-col space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($car->serviceLogs->sortByDesc('tanggal_masuk') as $log)
                    <div
                        class="border border-slate-100 rounded-xl p-3.5 hover:border-amber-200 transition-colors bg-white">

                        {{-- Baris Atas: Nama Bengkel & Status --}}
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex flex-col items-start gap-1">
                                <p class="text-[11px] font-extrabold text-slate-800 leading-none">
                                    <span
                                        class="material-symbols-outlined text-[12px] align-middle text-slate-400 mr-0.5">storefront</span>
                                    {{ $log->nama_bengkel ?: 'Bengkel Umum' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                @if ($log->status_bengkel === 'open')
                                    <span class="relative flex h-2 w-2" title="Sedang di bengkel">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                    </span>
                                @endif
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold {{ $log->status_bengkel === 'closed' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-amber-50 text-amber-600 border border-amber-200' }}">
                                    {{ $log->status_bengkel === 'closed' ? 'Selesai' : 'Berjalan' }}
                                </span>
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <p class="text-[11px] text-slate-500 font-medium line-clamp-2 mb-2">
                            {{ $log->catatan ?: 'Tidak ada catatan keluhan.' }}
                        </p>

                        {{-- Rincian Part yang Diservis (Spesifik Blok 2) --}}
                        @if (isset($log->serviceLogDetails) && $log->serviceLogDetails->count() > 0)
                            <div class="mb-3 flex flex-col gap-1.5">
                                @foreach ($log->serviceLogDetails as $detail)
                                    <div
                                        class="inline-flex items-start text-[10px] bg-slate-50 text-slate-600 px-2 py-1.5 rounded border border-slate-100">
                                        @if ($detail->carPart)
                                            <span
                                                class="material-symbols-outlined text-[12px] text-blue-500 mr-1.5 mt-0.5">{{ $detail->carPart->icon ?? 'build' }}</span>
                                            <div class="leading-tight">
                                                <span
                                                    class="font-extrabold text-blue-700 uppercase tracking-wider">{{ $detail->carPart->nama_part }}</span>
                                                <span class="text-slate-400 mx-0.5">•</span>
                                                <span class="font-medium">{{ $detail->deskripsi_tindakan }}</span>
                                            </div>
                                        @else
                                            <span
                                                class="material-symbols-outlined text-[12px] text-slate-400 mr-1.5 mt-0.5">build_circle</span>
                                            <div class="leading-tight">
                                                <span
                                                    class="font-extrabold text-slate-700 uppercase tracking-wider">Servis
                                                    Umum</span>
                                                <span class="text-slate-400 mx-0.5">•</span>
                                                <span class="font-medium">{{ $detail->deskripsi_tindakan }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Tanggal Pelaksanaan --}}
                        <div class="mt-2 mb-2">
                            <p class="text-[10px] text-slate-500 flex items-center gap-1 font-medium">
                                <span
                                    class="material-symbols-outlined text-[12px] text-slate-400">calendar_month</span>
                                {{ \Carbon\Carbon::parse($log->tanggal_masuk)->format('d M Y') }}
                                @if ($log->tanggal_keluar)
                                    &ndash; {{ \Carbon\Carbon::parse($log->tanggal_keluar)->format('d M Y') }}
                                @else
                                    &ndash; Sekarang
                                @endif
                            </p>
                        </div>

                        {{-- Footer: KM & Total Biaya --}}
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-50">
                            <span class="text-[10px] font-bold text-slate-400">
                                <span class="material-symbols-outlined text-[11px] align-middle mr-0.5">speed</span>
                                {{ number_format($log->km_saat_servis ?? 0, 0, ',', '.') }} km
                            </span>
                            <span class="text-[11px] font-extrabold text-orange-500">
                                Rp {{ number_format($log->serviceLogDetails->sum('biaya') ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                @empty
                    {{-- Tampilan Kosong --}}
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-slate-300 text-4xl block mb-2">history</span>
                        <p class="text-xs text-slate-400">Belum ada riwayat servis untuk mobil ini.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
