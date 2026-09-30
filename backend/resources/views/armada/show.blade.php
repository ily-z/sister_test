{{-- resources/views/armada/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Mobil')

@section('content')
    <div class="min-h-screen bg-gray-50 pb-12">

        {{-- Page Header --}}
        <div class="bg-white border-b border-gray-200 px-6 py-4 sticky top-16 z-20 shadow-sm">
            <div class="max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.armada') }}"
                        class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-[20px] text-gray-600">arrow_back</span>
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-gray-900">{{ $car->merk }} {{ $car->model }}</h1>
                            <span class="text-xs font-mono bg-blue-50 text-blue-700 px-2 py-0.5 rounded-lg font-semibold">
                                {{ strtoupper(substr(preg_replace('/\s+/', '', $car->model), 0, 3)) }}
                            </span>
                            @php
                                $statusClasses = [
                                    'Tersedia' => 'bg-emerald-100 text-emerald-700',
                                    'Sedang Disewa' => 'bg-blue-100 text-blue-700',
                                    'Masuk Bengkel' => 'bg-amber-100 text-amber-700',
                                ];
                            @endphp
                            <span
                                class="{{ $statusClasses[$car->status] ?? 'bg-slate-100 text-slate-600' }} text-xs font-semibold px-2.5 py-1 rounded-full">●
                                {{ $car->status }}</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $car->plat_nomor }} · {{ $car->tahun }} ·
                            {{ $car->kapasitas }} Kursi</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($car->status !== 'Sedang Disewa')
                        <form action="{{ route('admin.armada.send_to_workshop', $car->id) }}" method="POST"
                            class="inline-block">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">build</span>
                                Kirim ke Bengkel
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('admin.armada.edit', $car->id) }}"
                        class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                        Edit
                    </a>
                    <form method="POST" action="{{ route('admin.surat-menyurat.balik-nama', $car->id) }}" target="_blank" class="inline-flex">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">swap_vert</span>
                            Balik Nama
                        </button>
                    </form>
                </div>
            </div>

            {{-- TAB NAVIGATION UTAMA --}}
            <div class="max-w-[1400px] mx-auto mt-6 border-b border-gray-200 flex gap-6 overflow-x-auto">
                <button id="btn-tab-umum" onclick="switchMainTab('umum', this)"
                    class="main-tab-btn pb-3 text-sm font-bold border-b-2 border-blue-600 text-blue-600 transition-colors whitespace-nowrap flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">info</span> Informasi Umum
                </button>
                <button id="btn-tab-komponen" onclick="switchMainTab('komponen', this)"
                    class="main-tab-btn pb-3 text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors whitespace-nowrap flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">engineering</span> Status Komponen & Servis
                </button>
            </div>
        </div>

        <div class="py-6 max-w-[1400px] mx-auto px-4 md:px-6">
            @if (session('success'))
                <div class="mb-5 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-700 px-5 py-4">
                    {{ session('success') }}
                </div>
            @endif

            <div id="tab-content-umum" class="main-tab-content block">
                @include('armada.tabs.umum')
            </div>

            <div id="tab-content-komponen" class="main-tab-content hidden">
                @include('armada.tabs.komponen')
            </div>
        </div>
    </div>

    {{-- MODAL: UPDATE KM (Animasi membal & blur dihilangkan) --}}
    <div id="modal-km" class="hidden fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-900">Update Kilometer</h3>
                <button onclick="document.getElementById('modal-km').classList.add('hidden')"
                    class="text-gray-400 hover:text-red-500">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('admin.armada.updateKm', $car->id) }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div class="bg-blue-50 rounded-xl p-3">
                    <p class="text-xs text-blue-600">KM Saat Ini: <span
                            class="font-bold">{{ number_format($car->km_saat_ini, 0, ',', '.') }} km</span></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">KM Baru <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="km_baru" placeholder="Masukkan KM baru"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none pr-10"
                            min="{{ $car->km_saat_ini + 1 }}" required>
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">km</span>
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2.5 rounded-xl">Simpan</button>
                    <button type="button" onclick="document.getElementById('modal-km').classList.add('hidden')"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold py-2.5 rounded-xl">Batal</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: TAMBAH PART BARU (Animasi membal & blur dihilangkan) --}}
    <div id="modal-add-part" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-slate-900/50 px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">add_box</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Mulai Lacak Komponen</h3>
                        <p class="text-[10px] text-slate-500 font-medium">Pilih beberapa komponen sekaligus</p>
                    </div>
                </div>
                <button onclick="document.getElementById('modal-add-part').classList.add('hidden')"
                    class="text-slate-400 hover:text-red-500">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form action="{{ route('admin.armada.part.store', $car->id) }}" method="POST"
                class="flex flex-col flex-1 overflow-hidden"
                onsubmit="sessionStorage.setItem('activeMainTab', 'komponen');">
                @csrf
                <input type="hidden" name="car_id" value="{{ $car->id }}">

                {{-- Scrollable Area --}}
                <div class="p-6 overflow-y-auto flex-1 space-y-6">

                    {{-- DIKUNCI: Odometer Pemasangan --}}
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-start gap-4">
                        <div
                            class="w-10 h-10 bg-slate-200 text-slate-500 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined">lock</span>
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Odometer
                                Pemasangan (Terkunci)</label>
                            <div class="relative max-w-xs">
                                <input type="number" name="last_service_km" value="{{ $car->km_saat_ini }}" required
                                    readonly
                                    class="w-full px-4 py-2.5 text-sm font-bold text-slate-500 border border-slate-200 rounded-lg bg-slate-100 cursor-not-allowed outline-none">
                                <span
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 uppercase tracking-wider">KM</span>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1.5">Odometer ini diambil dari data aktual. Ubah
                                Odometer mobil terlebih dahulu jika tidak sesuai.</p>
                        </div>
                    </div>

                    <div class="flex gap-2 border-b border-slate-200 pb-3">
                        <button type="button" onclick="switchModalTab('rutin', this)"
                            class="modal-tab-btn px-4 py-2 text-xs font-bold rounded-lg bg-slate-800 text-white">Servis
                            Rutin</button>
                        <button type="button" onclick="switchModalTab('aksesoris', this)"
                            class="modal-tab-btn px-4 py-2 text-xs font-bold rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200">Aksesoris</button>
                        <button type="button" onclick="switchModalTab('custom', this)"
                            class="modal-tab-btn px-4 py-2 text-xs font-bold rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 border border-dashed border-slate-300">
                            Custom Input</button>
                    </div>

                    {{-- TAB TEMPLATE RUTIN --}}
                    <div id="modal-tab-rutin" class="modal-tab-content block">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach ($masterParts->where('is_track_km', true) as $mp)
                                @php $isRecommended = in_array($mp->nama_part, ['Oli Mesin (Engine Oil)', 'Kampas Rem Depan (Brake Pad)', 'Ban (Tire)']); @endphp
                                <label class="relative block cursor-pointer group">
                                    <input type="checkbox" name="master_part_ids[]" value="{{ $mp->id }}"
                                        class="peer hidden" onchange="updateSelectedCount()">
                                    <div
                                        class="h-full border-2 border-slate-100 rounded-xl p-3 bg-white peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-slate-300">
                                        <div class="flex items-start justify-between mb-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center peer-checked:bg-blue-200 peer-checked:text-blue-700">
                                                <span
                                                    class="material-symbols-outlined text-[18px]">{{ $mp->icon }}</span>
                                            </div>
                                            <div
                                                class="w-5 h-5 rounded-md border-2 border-slate-200 flex items-center justify-center peer-checked:bg-blue-500 peer-checked:border-blue-500">
                                                <span
                                                    class="material-symbols-outlined text-white text-[14px] opacity-0 peer-checked:opacity-100">check</span>
                                            </div>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 leading-tight mb-1">
                                            {{ $mp->nama_part }}</h4>
                                        <p class="text-[9px] text-slate-400 font-medium">Umur:
                                            {{ number_format($mp->default_lifespan_km, 0, ',', '.') }} km</p>
                                        @if ($isRecommended)
                                            <div
                                                class="absolute top-0 right-0 transform translate-x-2 -translate-y-2 bg-amber-400 text-white text-[8px] font-extrabold px-2 py-0.5 rounded-full shadow-sm flex items-center gap-0.5">
                                                <span class="material-symbols-outlined text-[10px]">star</span> Rekomendasi
                                            </div>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- TAB TEMPLATE AKSESORIS --}}
                    <div id="modal-tab-aksesoris" class="modal-tab-content hidden">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach ($masterParts->where('is_track_km', false) as $mp)
                                <label class="relative block cursor-pointer group">
                                    <input type="checkbox" name="master_part_ids[]" value="{{ $mp->id }}"
                                        class="peer hidden" onchange="updateSelectedCount()">
                                    <div
                                        class="h-full border-2 border-slate-100 rounded-xl p-3 bg-white peer-checked:border-purple-500 peer-checked:bg-purple-50 hover:border-slate-300">
                                        <div class="flex items-start justify-between mb-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center peer-checked:bg-purple-200 peer-checked:text-purple-700">
                                                <span
                                                    class="material-symbols-outlined text-[18px]">{{ $mp->icon }}</span>
                                            </div>
                                            <div
                                                class="w-5 h-5 rounded-md border-2 border-slate-200 flex items-center justify-center peer-checked:bg-purple-500 peer-checked:border-purple-500">
                                                <span
                                                    class="material-symbols-outlined text-white text-[14px] opacity-0 peer-checked:opacity-100">check</span>
                                            </div>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 leading-tight">{{ $mp->nama_part }}
                                        </h4>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- TAB CUSTOM PENUH FITUR --}}
                    <div id="modal-tab-custom" class="modal-tab-content hidden">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                            <h4 class="text-sm font-bold text-blue-900 mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">draw</span> Buat Komponen Sendiri
                            </h4>

                            {{-- Pembungkus Grid Utama (2 Kolom) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                {{-- KOLOM KIRI: Form Input & Kategori --}}
                                <div class="space-y-5">
                                    {{-- Pilihan Kategori --}}
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                            Kategori Part <span class="text-red-500">*</span>
                                        </label>
                                        <select name="is_track_km_custom" id="custom-type-select"
                                            onchange="toggleCustomLifespan()"
                                            class="w-full px-4 py-2.5 text-sm font-medium border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                            <option value="1">Servis Rutin (Lacak KM)</option>
                                            <option value="0">Aksesoris (Non-KM)</option>
                                        </select>
                                    </div>

                                    {{-- Nama Komponen --}}
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                            Nama Komponen Baru <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="nama_part_custom"
                                            placeholder="Contoh: Bumper Depan Custom"
                                            class="w-full px-4 py-2.5 text-sm font-medium border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                    </div>

                                    {{-- Target Umur --}}
                                    <div id="custom-lifespan-container">
                                        <label
                                            class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                            Target Umur (KM) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" name="lifespan_km" id="custom-lifespan-input"
                                            placeholder="Misal: 15000"
                                            class="w-full px-4 py-2.5 text-sm font-medium border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                    </div>
                                </div>

                                {{-- KOLOM KANAN: Pilihan Ikon Dinamis --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                        Pilih Ikon
                                    </label>
                                    <div class="grid grid-cols-5 gap-2">
                                        @php
                                            // Ambil semua icon unik dari master_parts (menghindari duplikat)
                                            $uniqueIcons = $masterParts->pluck('icon')->unique()->filter()->values();
                                        @endphp

                                        @foreach ($uniqueIcons as $idx => $icon)
                                            <label class="cursor-pointer"
                                                title="{{ ucfirst(str_replace('_', ' ', $icon)) }}">
                                                <input type="radio" name="icon_custom" value="{{ $icon }}"
                                                    class="peer hidden" {{ $idx === 0 ? 'checked' : '' }}>
                                                <div
                                                    class="h-10 rounded-xl border border-slate-200 bg-white text-slate-400 flex items-center justify-center peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-600 hover:border-slate-300 hover:text-slate-600">
                                                    <span
                                                        class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div
                        class="px-6 py-4 border-t border-slate-100 bg-white flex items-center justify-between flex-shrink-0">
                        <p class="text-xs font-bold text-slate-500"><span id="selected-count"
                                class="text-blue-600 font-black">0</span> Template dipilih</p>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2.5 px-6 rounded-xl shadow-md">
                            Simpan & Mulai Lacak
                        </button>
                    </div>
            </form>
        </div>
    </div>

    {{-- SCRIPTS UTAMA --}}
    <script>
        // SCRIPT PINTAR: Menjaga tab tetap di 'komponen' setelah halaman ter-refresh
        window.addEventListener('DOMContentLoaded', () => {
            const activeTab = sessionStorage.getItem('activeMainTab');
            if (activeTab === 'komponen') {
                const btnKomponen = document.getElementById('btn-tab-komponen');
                if (btnKomponen) switchMainTab('komponen', btnKomponen);
                // Bersihkan session agar tidak terus-terusan terjebak di tab komponen saat baru buka halaman
                sessionStorage.removeItem('activeMainTab');
            }
        });

        // Logika Inline Editing (Double Click untuk Rename Part)
        function enableInlineEdit(partId) {
            document.getElementById('part-name-display-' + partId).classList.add('hidden');
            document.getElementById('part-name-form-' + partId).classList.remove('hidden');

            const inputEl = document.getElementById('part-name-input-' + partId);
            inputEl.focus();
            inputEl.select();
        }

        // Fungsi manual untuk membatalkan edit
        function cancelInlineEdit(partId) {
            document.getElementById('part-name-display-' + partId).classList.remove('hidden');
            document.getElementById('part-name-form-' + partId).classList.add('hidden');
        }

        // Menyembunyikan input umur KM jika tab Custom pilih "Aksesoris"
        function toggleCustomLifespan() {
            const typeSelect = document.getElementById('custom-type-select').value;
            const lifespanContainer = document.getElementById('custom-lifespan-container');
            const lifespanInput = document.getElementById('custom-lifespan-input');

            if (typeSelect === '0') {
                lifespanContainer.style.display = 'none';
                lifespanInput.removeAttribute('required');
            } else {
                lifespanContainer.style.display = 'block';
                lifespanInput.setAttribute('required', 'required');
            }
        }

        // Logika Pindah Tab Utama (Halaman Show)
        function switchMainTab(tabId, btnElement) {
            document.querySelectorAll('.main-tab-content').forEach(content => {
                content.classList.add('hidden');
                content.classList.remove('block');
            });
            document.getElementById('tab-content-' + tabId).classList.remove('hidden');
            document.getElementById('tab-content-' + tabId).classList.add('block');

            document.querySelectorAll('.main-tab-btn').forEach(btn => {
                btn.classList.remove('border-blue-600', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            btnElement.classList.remove('border-transparent', 'text-gray-500');
            btnElement.classList.add('border-blue-600', 'text-blue-600');
        }

        // Logika Filter Grup Komponen (Tanpa perlu manipulasi class UI)
        function filterGroup(type) {
            const rutinGrp = document.getElementById('group-rutin');
            const aksesorisGrp = document.getElementById('group-aksesoris');

            if (type === 'all') {
                if (rutinGrp) rutinGrp.style.display = 'block';
                if (aksesorisGrp) aksesorisGrp.style.display = 'block';
            } else if (type === 'rutin') {
                if (rutinGrp) rutinGrp.style.display = 'block';
                if (aksesorisGrp) aksesorisGrp.style.display = 'none';
            } else if (type === 'aksesoris') {
                if (rutinGrp) rutinGrp.style.display = 'none';
                if (aksesorisGrp) aksesorisGrp.style.display = 'block';
            }
        }

        // Logika Tab Dalam Modal Tambah Part
        function switchModalTab(tabId, btnElement) {
            document.querySelectorAll('.modal-tab-content').forEach(content => {
                content.classList.add('hidden');
                content.classList.remove('block');
            });
            document.getElementById('modal-tab-' + tabId).classList.remove('hidden');
            document.getElementById('modal-tab-' + tabId).classList.add('block');

            document.querySelectorAll('.modal-tab-btn').forEach(btn => {
                btn.classList.remove('bg-slate-800', 'text-white');
                btn.classList.add('bg-slate-100', 'text-slate-500');
            });
            btnElement.classList.remove('bg-slate-100', 'text-slate-500');
            btnElement.classList.add('bg-slate-800', 'text-white');

            if (tabId === 'custom') {
                document.querySelectorAll('input[name="master_part_ids[]"]').forEach(cb => cb.checked = false);
                updateSelectedCount();
            } else {
                document.querySelector('input[name="nama_part_custom"]').value = '';
                document.querySelector('input[name="lifespan_km"]').value = '';
            }
        }

        // Hitung checkbox di dalam Modal
        function updateSelectedCount() {
            const count = document.querySelectorAll('input[name="master_part_ids[]"]:checked').length;
            document.getElementById('selected-count').innerText = count;
        }

        function toggleWearEdit(partId) {
            const formEl = document.getElementById('wear-form-' + partId);
            const iconEl = document.getElementById('wear-icon-' + partId);

            if (formEl.classList.contains('hidden')) {
                formEl.classList.remove('hidden');
                iconEl.style.transform = 'rotate(180deg)';
            } else {
                formEl.classList.add('hidden');
                iconEl.style.transform = 'rotate(0deg)';
            }
        }
    </script>
@endsection
