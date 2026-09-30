@extends('layouts.app')

@section('content')
<div class="max-w-[900px] mx-auto px-4 md:px-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Tambah Pengeluaran Kantor</h1>
            <p class="text-sm text-slate-500">Masukkan beberapa pengeluaran sekaligus.</p>
        </div>
        <a href="{{ route('admin.keuangan.pengeluaran-kantor') }}" class="text-sm text-slate-600 hover:underline">Kembali</a>
    </div>

    <form action="{{ route('admin.keuangan.pengeluaran-kantor.store') }}" method="POST" id="form-bulk-pengeluaran">
        @csrf

        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm mb-4">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h3 class="text-lg font-semibold">Daftar Pengeluaran</h3>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-add-row" class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm">Tambah Baris</button>
                    <button type="button" id="btn-clear-rows" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm">Kosongkan</button>
                </div>
            </div>

            <p class="text-xs text-slate-400 mb-3">Quick templates:</p>
            <div class="flex flex-wrap gap-2 mb-5">
                <button type="button" data-desc="Pulsa kantor" class="btn-quick px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg">Pulsa</button>
                <button type="button" data-desc="Alat Tulis Kantor" class="btn-quick px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg">ATK</button>
                <button type="button" data-desc="Biaya Listrik" class="btn-quick px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg">Listrik</button>
                <button type="button" data-desc="Biaya Internet" class="btn-quick px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg">Internet</button>
            </div>

            <div id="container-rows" class="space-y-3">
                <!-- rows inserted here -->
            </div>

            <p id="empty-state" class="text-sm text-slate-400 text-center py-6">Belum ada pengeluaran ditambahkan.</p>

        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg">Simpan Semua</button>
            <a href="{{ route('admin.keuangan.pengeluaran-kantor') }}" class="px-4 py-2 border border-slate-200 rounded-lg">Batal</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('container-rows');
        const empty = document.getElementById('empty-state');
        const btnAdd = document.getElementById('btn-add-row');
        const btnClear = document.getElementById('btn-clear-rows');

        function updateEmpty() {
            empty.classList.toggle('hidden', container.children.length > 0);
        }

        function buatRow(presetDesc = '', presetJumlah = '') {
            const idx = Date.now();
            const el = document.createElement('div');
            el.className = 'grid grid-cols-12 gap-3 items-center addon-item';
            el.innerHTML = `
                <div class="col-span-12 sm:col-span-3">
                    <input type="date" name="tanggal[]" value="${new Date().toISOString().slice(0,10)}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm" />
                </div>
                <div class="col-span-12 sm:col-span-5">
                    <input type="text" name="deskripsi[]" value="${presetDesc}" placeholder="Deskripsi (misal: Pulsa kantor)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm" />
                </div>
                <div class="col-span-9 sm:col-span-3 relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">Rp</span>
                    <input type="text" name="jumlah[]" value="${presetJumlah}" placeholder="0" class="w-full pl-10 pr-3 py-2 border border-slate-200 rounded-lg text-sm jumlah-input" />
                </div>
                <div class="col-span-3 sm:col-span-1 text-right">
                    <button type="button" class="btn-remove-row inline-flex items-center justify-center w-9 h-9 rounded-lg text-red-600 hover:bg-red-50 border border-red-100">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            `;

            // attach listeners
            el.querySelector('.btn-remove-row').addEventListener('click', function () { el.remove(); updateEmpty(); });
            const inputJumlah = el.querySelector('.jumlah-input');
            inputJumlah.addEventListener('input', function () {
                // allow only numbers and thousand separators
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            container.appendChild(el);
            updateEmpty();
            inputJumlah.focus();
        }

        btnAdd.addEventListener('click', () => buatRow());
        btnClear.addEventListener('click', () => { container.innerHTML = ''; updateEmpty(); });

        document.querySelectorAll('.btn-quick').forEach(b => {
            b.addEventListener('click', function () {
                buatRow(this.dataset.desc, '');
            });
        });

        // Initialize with one row
        buatRow();

        // Before submit, sanitize jumlah fields to numeric (convert to proper decimal string)
        document.getElementById('form-bulk-pengeluaran').addEventListener('submit', function (e) {
            document.querySelectorAll('input[name="jumlah[]"]').forEach(inp => {
                let v = inp.value.toString().replace(/[^0-9]/g, '');
                if (v === '') v = '0';
                inp.value = v;
            });
        });
    });
</script>
@endpush

@endsection