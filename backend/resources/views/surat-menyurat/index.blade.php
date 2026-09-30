@extends('layouts.app')

@section('title', 'Surat Menyurat')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 md:px-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Surat Menyurat</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola nomor surat dan dokumen administrasi.</p>
        </div>
        <a href="{{ route('admin.surat-menyurat.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-md shadow-blue-100 active:scale-95">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Tambah Nomor Surat
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="space-y-3" id="surat-tree">
        @forelse ($grouped as $jenis => $group)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-center justify-between px-5 py-4 hover:bg-slate-50 transition-colors select-none">
                    <div class="flex items-center gap-3 min-w-0 flex-1 cursor-pointer"
                        onclick="toggleSub('{{ Str::slug($jenis) }}')">
                        <span id="chevron-{{ Str::slug($jenis) }}"
                            class="material-symbols-outlined text-slate-400 transition-transform duration-200 text-[20px] {{ $group['children']->count() > 0 ? '' : 'invisible' }}">
                            chevron_right
                        </span>
                        <div class="flex items-center gap-3 min-w-0">
                            <input type="checkbox" value="{{ $group['main']?->id }}"
                                class="surat-check rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer shrink-0"
                                onchange="updateBulkBar()" onclick="event.stopPropagation()">
                            <div class="flex flex-col min-w-0">
                                <span class="font-mono text-sm font-medium text-slate-800 truncate">
                                    @if ($group['main'] && $group['main']->nomor_surat)
                                        {{ $group['main']->nomor_surat }}
                                    @else
                                        <span class="font-sans text-slate-700">{{ $jenis }}</span>
                                    @endif
                                </span>
                                <span class="text-xs text-slate-400">
                                    {{ $jenis }}
                                    @if (!$group['main'] || !$group['main']->nomor_surat)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 ml-1">
                                            Draft
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button onclick="event.stopPropagation(); openAddSubModal('{{ $jenis }}')"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">add</span>
                            Sub
                        </button>
                        <a href="{{ route('admin.surat-menyurat.arsip', ['jenis_dokumen' => $group['main']?->jenis_dokumen]) }}"
                                                            onclick="event.stopPropagation()"
                                                            class="text-xs font-medium text-slate-500 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                                                            <span class="material-symbols-outlined text-[16px]">archive</span>
                                                            Arsip
                                                        </a>
                    </div>
                </div>

                @if ($group['children']->count() > 0)
                    <div id="sub-{{ Str::slug($jenis) }}" class="hidden divide-y divide-slate-50">
                        @foreach ($group['children'] as $child)
                            <div class="flex flex-wrap items-center justify-between px-5 py-3 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3 min-w-0 pl-8 flex-1">
                                    <span class="material-symbols-outlined text-slate-300 text-[16px] shrink-0">subdirectory_arrow_right</span>
                                    <input type="checkbox" value="{{ $child->id }}"
                                        class="surat-check rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer shrink-0"
                                        onchange="updateBulkBar()">
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-mono text-sm text-slate-700 truncate">
                                            @if ($child->nomor_surat)
                                                {{ $child->nomor_surat }}
                                            @else
                                                <span class="font-sans text-slate-500 italic">Draft</span>
                                            @endif
                                        </span>
                                        <span class="text-xs text-slate-400">{{ $child->subkategori }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('admin.surat-menyurat.arsip', ['jenis_dokumen' => $child->jenis_dokumen, 'subkategori' => $child->subkategori]) }}"
                                    class="text-xs font-medium text-slate-500 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1 shrink-0">
                                    <span class="material-symbols-outlined text-[16px]">archive</span>
                                    Arsip
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
                <span class="material-symbols-outlined text-4xl text-slate-300 mb-3 block">description</span>
                <p class="text-slate-400">Belum ada nomor surat. Klik "Tambah Nomor Surat" untuk membuat yang baru.</p>
            </div>
        @endforelse
    </div>

    <div id="bulkBar"
        class="fixed bottom-24 md:bottom-6 left-4 right-4 md:left-1/2 md:right-auto md:-translate-x-1/2 z-40 hidden items-center justify-between md:justify-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-2xl">
        <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" id="selectAllCheckbox"
                class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500 cursor-pointer"
                onchange="toggleSelectAll()">
            <span class="text-slate-300">Pilih Semua</span>
        </label>
        <span class="text-sm text-slate-400" id="selectedCount">0 dipilih</span>
        <div class="w-px h-5 bg-slate-700"></div>
        <button onclick="confirmBulkDelete()"
            class="flex items-center gap-1.5 text-sm font-semibold text-red-400 hover:text-red-300 bg-slate-800 hover:bg-slate-700 px-4 py-1.5 rounded-xl transition-colors">
            <span class="material-symbols-outlined text-[18px]">delete</span>
            Hapus
        </button>
    </div>
</div>

<form id="bulkDeleteForm" method="POST" action="{{ route('admin.surat-menyurat.bulk-delete') }}">
    @csrf
    @method('DELETE')
    <div id="bulkDeleteInputs"></div>
</form>

<div id="addSubModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" onclick="closeAddSubModal()"></div>
    <div class="flex items-center justify-center min-h-full p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 relative z-10" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-900">Tambah Subkategori</h3>
                <button type="button" onclick="closeAddSubModal()" class="p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <span class="material-symbols-outlined text-slate-500">close</span>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.surat-menyurat.store-sub') }}">
                @csrf
                <input type="hidden" name="jenis_dokumen" id="modal-jenis-dokumen">

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Jenis Dokumen</label>
                    <p id="modal-jenis-display" class="text-sm font-medium text-slate-800 bg-slate-50 rounded-xl px-3 py-2.5 border border-slate-200"></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Subkategori <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <select id="subkategori-select" name="subkategori"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50"
                            onchange="toggleSubkategoriInput()">
                            <option value="">Pilih subkategori...</option>
                            <option value="__new__">+ Subkategori Baru</option>
                        </select>
                        <input type="text" id="subkategori-input" placeholder="Ketik subkategori baru"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 hidden">
                    </div>
                </div>

                <div class="flex gap-2 mt-6">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2.5 px-5 rounded-xl transition-colors">
                        Simpan
                    </button>
                    <button type="button" onclick="closeAddSubModal()"
                        class="text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 py-2.5 px-5 rounded-xl transition-colors">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="confirmModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="flex items-center justify-center min-h-full p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="material-symbols-outlined text-red-500 text-2xl">warning</span>
                <h3 class="text-lg font-bold text-slate-900">Hapus Nomor Surat</h3>
            </div>
            <p class="text-sm text-slate-600 mb-2">Anda yakin ingin menghapus <span id="confirmCount" class="font-semibold">0</span> nomor surat?</p>
            <p class="text-xs text-slate-400 mb-6">Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeConfirmModal()"
                    class="text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 py-2.5 px-5 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="button" onclick="submitBulkDelete()"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-2.5 px-5 rounded-xl transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const subkategoris = @json($subkategorisByJenis->toArray() ?? []);

    function toggleSub(id) {
        const sub = document.getElementById('sub-' + id);
        const chevron = document.getElementById('chevron-' + id);
        if (sub) {
            sub.classList.toggle('hidden');
            if (chevron && !chevron.classList.contains('invisible')) {
                chevron.classList.toggle('rotate-90');
            }
        }
    }

    function getSelectedIds() {
        const checks = document.querySelectorAll('.surat-check:checked');
        return Array.from(checks).map(c => c.value).filter(Boolean);
    }

    function updateBulkBar() {
        const ids = getSelectedIds();
        const bar = document.getElementById('bulkBar');
        const count = document.getElementById('selectedCount');
        if (ids.length > 0) {
            bar.classList.remove('hidden');
            bar.classList.add('flex');
            count.textContent = ids.length + ' dipilih';
        } else {
            bar.classList.add('hidden');
            bar.classList.remove('flex');
        }
        const selectAll = document.getElementById('selectAllCheckbox');
        const total = document.querySelectorAll('.surat-check').length;
        selectAll.checked = ids.length === total;
        selectAll.indeterminate = ids.length > 0 && ids.length < total;
    }

    function toggleSelectAll() {
        const checked = document.getElementById('selectAllCheckbox').checked;
        document.querySelectorAll('.surat-check').forEach(c => c.checked = checked);
        updateBulkBar();
    }

    function confirmBulkDelete() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        document.getElementById('confirmCount').textContent = ids.length;
        document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.add('hidden');
    }

    function submitBulkDelete() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        const container = document.getElementById('bulkDeleteInputs');
        container.innerHTML = '';
        ids.forEach(function(id) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            container.appendChild(input);
        });
        document.getElementById('bulkDeleteForm').submit();
    }

    function openAddSubModal(jenis) {
        document.getElementById('modal-jenis-dokumen').value = jenis;
        document.getElementById('modal-jenis-display').textContent = jenis;

        const select = document.getElementById('subkategori-select');
        select.innerHTML = '<option value="">Pilih subkategori...</option>';
        select.innerHTML += '<option value="__new__">+ Subkategori Baru</option>';

        if (subkategoris[jenis]) {
            subkategoris[jenis].forEach(function(sub) {
                const opt = document.createElement('option');
                opt.value = sub;
                opt.textContent = sub;
                select.appendChild(opt);
            });
        }

        select.value = '';
        select.name = 'subkategori';
        const input = document.getElementById('subkategori-input');
        input.classList.add('hidden');
        input.value = '';
        input.name = 'subkategori_dummy';

        document.getElementById('addSubModal').classList.remove('hidden');
    }

    function closeAddSubModal() {
        document.getElementById('addSubModal').classList.add('hidden');
    }

    function toggleSubkategoriInput() {
        const select = document.getElementById('subkategori-select');
        const input = document.getElementById('subkategori-input');
        if (select.value === '__new__') {
            select.name = 'subkategori_dummy';
            input.classList.remove('hidden');
            input.name = 'subkategori';
            input.focus();
        } else {
            input.classList.add('hidden');
            input.name = 'subkategori_dummy';
            select.name = 'subkategori';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAddSubModal();
            closeConfirmModal();
        }
    });
</script>
<style>
    .rotate-90 {
        transform: rotate(90deg);
    }
</style>
@endpush
