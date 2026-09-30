@extends('layouts.app')

@section('title', 'Template Surat')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 md:px-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Template Surat</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola template surat dan dokumen.</p>
        </div>
        <a href="{{ route('admin.surat-menyurat.template.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-md shadow-blue-100 active:scale-95">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Tambah Surat
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($templates as $template)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200 flex flex-col">
                <div class="p-5 flex-1">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="font-semibold text-slate-900 text-base leading-snug">{{ $template->judul }}</h3>
                        <span class="shrink-0 ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700">
                            {{ $template->jenis_surat }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-400 mb-3">
                        {{ $template->created_at->format('d M Y, H:i') }}
                    </div>

                    <div class="text-sm text-slate-600 line-clamp-4 leading-relaxed">
                        {!! $template->konten ? Str::limit(strip_tags($template->konten), 200) : '<span class="text-slate-400 italic">Kosong</span>' !!}
                    </div>
                </div>

                <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center gap-2">
                    <button onclick="openPreview({{ $template->id }})"
                        class="flex-1 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-white hover:bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-lg transition-colors flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                        Lihat
                    </button>
                    <a href="{{ route('admin.surat-menyurat.template.edit', $template->id) }}"
                        class="flex-1 text-xs font-semibold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-lg transition-colors flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                        Edit
                    </a>
                    <a href="{{ route('admin.surat-menyurat.template.print', $template->id) }}" target="_blank"
                        class="flex-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">print</span>
                        Cetak
                    </a>
                    <button onclick="confirmDelete({{ $template->id }}, '{{ addslashes($template->judul) }}')"
                        class="flex-1 text-xs font-semibold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                        Hapus
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center">
                <span class="material-symbols-outlined text-4xl text-slate-300 mb-3 block">description</span>
                <p class="text-slate-400">Belum ada template surat. Klik "Tambah Surat" untuk membuat yang baru.</p>
            </div>
        @endforelse
    </div>
</div>

{{-- Preview Modal --}}
<div id="previewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" onclick="closePreview()"></div>
    <div class="flex items-center justify-center min-h-full p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[85vh] flex flex-col relative z-10" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 shrink-0">
                <div>
                    <h3 id="previewTitle" class="text-lg font-bold text-slate-900"></h3>
                    <span id="previewJenis" class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="printPreview()" class="text-xs font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">print</span>
                        Cetak
                    </button>
                    <button type="button" onclick="closePreview()" class="p-1 rounded-lg hover:bg-slate-100 transition-colors">
                        <span class="material-symbols-outlined text-slate-500">close</span>
                    </button>
                </div>
            </div>
            <div class="overflow-auto" style="background:#e2e8f0;padding:2rem 1rem;display:flex;flex-direction:column;align-items:flex-start;">
                <div style="width:794px;background:#fff;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin:0 auto;">
                    <div class="kop-surat-header">
                        <img src="/storage/img/kop.png" alt="Kop Surat">
                    </div>
                    <div id="previewContent" class="template-content" style="padding:120px 120px;min-height:1123px;"></div>
                </div>
            </div></div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="flex items-center justify-center min-h-full p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="material-symbols-outlined text-red-500 text-2xl">warning</span>
                <h3 class="text-lg font-bold text-slate-900">Hapus Template</h3>
            </div>
            <p class="text-sm text-slate-600 mb-2">Anda yakin ingin menghapus template <strong id="deleteTitle" class="text-slate-800"></strong>?</p>
            <p class="text-xs text-slate-400 mb-6">Tindakan ini tidak dapat dibatalkan.</p>
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="closeDeleteModal()"
                        class="text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 py-2.5 px-5 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-2.5 px-5 rounded-xl transition-colors flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                        Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .line-clamp-4 {
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    #previewContent {
        position: relative;
    }
    #previewContent::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url('/watermark-page');
        background-repeat: repeat-y;
        background-position: center;
        background-size: 554px 1123px;
        pointer-events: none;
        z-index: 0;
    }
    #previewContent > * {
        position: relative;
        z-index: 1;
    }
    .template-content h1 { font-size: 1.875rem; font-weight: 800; color: #0f172a; margin-top: 2rem; }
    .template-content h2 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-top: 1.5rem; }
    .template-content h3 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-top: 1.25rem; }
    .template-content h4 { font-size: 1.125rem; font-weight: 600; color: #0f172a; margin-top: 1rem; }
    .template-content h5 { font-size: 1rem; font-weight: 600; color: #0f172a; margin-top: 0.75rem; }
    .template-content h6 { font-size: 0.875rem; font-weight: 600; color: #0f172a; margin-top: 0.75rem; }
    .template-content p { font-size: 0.875rem; line-height: 1.625; color: #334155; margin-top: 0.75rem; }
    .template-content p:first-child { margin-top: 0; }
    .template-content ul, .template-content ol { padding-left: 1.25rem; font-size: 0.875rem; color: #334155; margin-top: 0.75rem; }
    .template-content li { margin-top: 0.25rem; }
    .template-content hr { margin-top: 1.5rem; margin-bottom: 1.5rem; border: 0; border-top: 1px solid #e2e8f0; }
    .template-content blockquote { border-left: 4px solid #3b82f6; padding-left: 1rem; font-style: italic; color: #475569; margin-top: 0.75rem; }
    .template-content table { width: 100%; border-collapse: collapse; margin-top: 0.75rem; font-size: 0.875rem; }
    .template-content th, .template-content td { border: 1px solid #cbd5e1; padding: 0.5rem 0.75rem; text-align: left; }
    .template-content th { background: #f1f5f9; font-weight: 600; color: #0f172a; }
    .template-content a { color: #2563eb; text-decoration: underline; }
    .kop-surat-header {
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem;
        background: #fff;
    }
    .kop-surat-header img {
        display: block;
        width: 100%;
        height: auto;
    }
</style>
<script>
    var templates = @json($templates->keyBy->id);
    var currentPreviewId = null;

    function openPreview(id) {
        currentPreviewId = id;
        var tpl = templates[id];
        if (!tpl) return;
        document.getElementById('previewTitle').textContent = tpl.judul;
        document.getElementById('previewJenis').textContent = tpl.jenis_surat;
        document.getElementById('previewContent').innerHTML = tpl.konten || '<p class="text-slate-400 italic">Kosong</p>';
        document.getElementById('previewModal').classList.remove('hidden');
    }

    function closePreview() {
        document.getElementById('previewModal').classList.add('hidden');
    }

    function printPreview() {
        if (currentPreviewId) {
            window.open('{{ url('surat-menyurat/template') }}/' + currentPreviewId + '/print', '_blank');
        }
    }

    function confirmDelete(id, judul) {
        document.getElementById('deleteTitle').textContent = judul;
        document.getElementById('deleteForm').action = '{{ url('surat-menyurat/template') }}/' + id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePreview();
            closeDeleteModal();
        }
    });
</script>
@endpush
@endsection
