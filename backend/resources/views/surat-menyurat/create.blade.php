@extends('layouts.app')

@section('title', 'Tambah Nomor Surat')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.surat-menyurat') }}"
                class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center transition-colors">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Tambah Nomor Surat</h1>
                <p class="text-sm text-gray-500 mt-0.5">Buat entri nomor surat baru. Nomor unik akan di-generate saat pembuatan dokumen.</p>
            </div>
        </div>
    </div>

    <div class="px-6 py-6 max-w-2xl">
        <form action="{{ route('admin.surat-menyurat.store') }}" method="POST">
            @csrf

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Jenis Dokumen <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <select name="jenis_dokumen_select" id="jenis-select"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                            onchange="toggleJenisInput()">
                            <option value="">Pilih atau ketik baru...</option>
                            @foreach ($jenisDokumen as $jd)
                                <option value="{{ $jd }}" {{ old('jenis_dokumen') == $jd ? 'selected' : '' }}>{{ $jd }}</option>
                            @endforeach
                            <option value="__new__">+ Jenis Baru</option>
                        </select>
                        <input type="text" name="jenis_dokumen" id="jenis-input" placeholder="Ketik jenis dokumen baru"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50 hidden"
                            value="{{ old('jenis_dokumen') }}">
                    </div>
                    @error('jenis_dokumen')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kode Dokumen <span class="text-red-500">*</span></label>
                    <input type="text" name="kode" id="kode-input" placeholder="Contoh: BN, ST, INV"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                        value="{{ old('kode') }}">
                    @error('kode')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">Kode singkat untuk jenis dokumen (contoh: BN untuk Balik Nama).</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Subkategori <span class="text-gray-400">(opsional)</span></label>
                    <input type="text" name="subkategori" id="subkategori-input" placeholder="Contoh: Perlengkapan, Operasional"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-gray-50"
                        value="{{ old('subkategori') }}">
                    @error('subkategori')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">Subkategori akan tersedia sebagai dropdown dari jenis dokumen yang sudah ada.</p>
                </div>

                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Format Nomor Surat:</p>
                    <p class="text-sm font-mono text-gray-700" id="nomor-preview">
                        [001]/SRT/[Kode]/JRB/[Bulan]/[Tahun]
                    </p>
                    <p class="text-xs text-gray-400 mt-2">No Urut, Bulan, dan Tahun akan diisi otomatis saat dibuat.</p>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2.5 px-5 rounded-xl transition-colors shadow-sm">
                        Simpan
                    </button>
                    <a href="{{ route('admin.surat-menyurat') }}"
                        class="text-center text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 py-2.5 px-5 rounded-xl transition-colors">
                        Batal
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleJenisInput() {
        const select = document.getElementById('jenis-select');
        const input = document.getElementById('jenis-input');
        if (select.value === '__new__') {
            select.name = 'jenis_dokumen_select_dummy';
            input.classList.remove('hidden');
            input.name = 'jenis_dokumen';
            input.focus();
        } else {
            input.classList.add('hidden');
            input.name = 'jenis_dokumen_dummy';
            select.name = 'jenis_dokumen';
        }
        updatePreview();
    }

    function updatePreview() {
        const kodeInput = document.getElementById('kode-input');
        const preview = document.getElementById('nomor-preview');

        let kode = kodeInput.value || '[Kode]';

        if (!kode) {
            preview.textContent = '[001]/SRT/[Kode]/JRB/[Bulan]/[Tahun]';
        } else {
            preview.textContent = `[001]/SRT/${kode}/JRB/[Bulan]/[Tahun]`;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('jenis-select');
        const input = document.getElementById('jenis-input');
        const kodeInput = document.getElementById('kode-input');

        if (select.value && select.value !== '__new__') {
            select.name = 'jenis_dokumen';
        } else if (select.value === '__new__') {
            toggleJenisInput();
        }

        select.addEventListener('change', updatePreview);
        input.addEventListener('input', updatePreview);
        kodeInput.addEventListener('input', updatePreview);
        updatePreview();
    });
</script>
@endsection
