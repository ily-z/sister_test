@extends('layouts.app')

@section('title', 'Buat Template Surat')

@section('content')
<style>
.editor-wrapper {
    background: #e2e8f0;
    padding: 2rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rem;
}
@media (max-width: 850px) {
    .editor-wrapper {
        align-items: flex-start;
        overflow-x: auto;
    }
    .editor-wrapper > * {
        margin-left: auto;
        margin-right: auto;
    }
    .editor-wrapper .ql-toolbar,
    .editor-wrapper .ql-container,
    .editor-wrapper .page-break {
        width: 820px;
    }
    .kop-surat-header {
        position: static;
    }
}
.editor-wrapper .ql-toolbar {
    width: 794px;
    border-radius: 8px 8px 0 0;
    border: 1px solid #cbd5e1;
    border-bottom: none;
    background: #fff;
    z-index: 10;
}
.editor-wrapper .ql-container {
    width: 794px;
    border: 1px solid #cbd5e1;
    border-top: none;
    border-radius: 0 0 8px 8px;
    background: #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    font-size: 0.875rem;
    line-height: 1.625;
}
.editor-wrapper .ql-editor {
    padding: 120px 120px;
    min-height: 1123px;
    background: #fff;
    position: relative;
}
.editor-wrapper .ql-editor::after {
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
.editor-wrapper .ql-editor h1 { font-size: 1.875rem; font-weight: 800; color: #0f172a; margin-top: 2rem; }
.editor-wrapper .ql-editor h2 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-top: 1.5rem; }
.editor-wrapper .ql-editor h3 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-top: 1.25rem; }
.editor-wrapper .ql-editor h4 { font-size: 1.125rem; font-weight: 600; color: #0f172a; margin-top: 1rem; }
.editor-wrapper .ql-editor h5 { font-size: 1rem; font-weight: 600; color: #0f172a; margin-top: 0.75rem; }
.editor-wrapper .ql-editor h6 { font-size: 0.875rem; font-weight: 600; color: #0f172a; margin-top: 0.75rem; }
.editor-wrapper .ql-editor p { font-size: 0.875rem; line-height: 1.625; color: #334155; margin-top: 0.75rem; }
.editor-wrapper .ql-editor p:first-child { margin-top: 0; }
.editor-wrapper .ql-editor ul,
.editor-wrapper .ql-editor ol { padding-left: 1.25rem; font-size: 0.875rem; color: #334155; margin-top: 0.75rem; }
.editor-wrapper .ql-editor li { margin-top: 0.25rem; }
.editor-wrapper .ql-editor hr { margin-top: 1.5rem; margin-bottom: 1.5rem; border: 0; border-top: 1px solid #e2e8f0; }
.editor-wrapper .ql-editor blockquote { border-left: 4px solid #3b82f6; padding-left: 1rem; font-style: italic; color: #475569; margin-top: 0.75rem; }
.editor-wrapper .ql-editor table { width: 100%; border-collapse: collapse; margin-top: 0.75rem; font-size: 0.875rem; }
.editor-wrapper .ql-editor th,
.editor-wrapper .ql-editor td { border: 1px solid #cbd5e1; padding: 0.5rem 0.75rem; text-align: left; }
.editor-wrapper .ql-editor th { background: #f1f5f9; font-weight: 600; color: #0f172a; }
.editor-wrapper .ql-editor a { color: #2563eb; text-decoration: underline; }

.ql-font-arial { font-family: Arial, sans-serif; }
.ql-font-sans-serif { font-family: sans-serif; }
.ql-font-serif { font-family: serif; }
.ql-font-monospace { font-family: monospace; }
.ql-font-times-new-roman { font-family: 'Times New Roman', serif; }
.ql-font-courier-new { font-family: 'Courier New', monospace; }
.ql-font-georgia { font-family: Georgia, serif; }
.ql-font-verdana { font-family: Verdana, sans-serif; }
.ql-font-tahoma { font-family: Tahoma, sans-serif; }
.ql-font-trebuchet-ms { font-family: 'Trebuchet MS', sans-serif; }
.ql-font-inter { font-family: 'Inter', sans-serif; }

.page-break {
    width: 794px;
    height: 0;
    border-top: 1px dashed #94a3b8;
    margin: 0;
    position: relative;
}
.page-break::after {
    content: 'Page Break';
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: #e2e8f0;
    padding: 0 0.75rem;
    font-size: 0.7rem;
    color: #94a3b8;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.kop-surat-header {
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem;
    background: #fff;
    pointer-events: none;
    user-select: none;
    position: sticky;
    top: 0;
    z-index: 11;
}
.kop-surat-header img {
    display: block;
    width: 100%;
    height: auto;
}
</style>

<div class="max-w-5xl mx-auto px-4 md:px-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.surat-menyurat.template') }}"
            class="w-8 h-8 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center transition-colors">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Buat Template Surat</h1>
            <p class="text-sm text-slate-500 mt-0.5">Buat template surat baru dengan editor.</p>
        </div>
    </div>

    <form id="templateForm" method="POST" action="{{ route('admin.surat-menyurat.template.store') }}">
        @csrf

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Judul <span class="text-red-500">*</span></label>
                    <input type="text" id="judul-input" name="judul" placeholder="Masukkan judul template"
                        class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50"
                        value="{{ old('judul') }}" required>
                    @error('judul')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nomor Surat <span class="text-red-500">*</span></label>
                    <div id="nomor-surat-display" class="hidden">
                        <div class="flex items-center gap-2">
                            <span id="nomor-surat-badge" class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-semibold bg-blue-50 text-blue-700 border border-blue-200"></span>
                            <button type="button" onclick="openNomorSuratModal()"
                                class="text-xs font-semibold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">swap_horiz</span>
                                Ganti
                            </button>
                        </div>
                        <input type="hidden" name="jenis_surat" id="jenis-surat-hidden">
                    </div>
                    <div id="nomor-surat-empty" class="text-sm text-slate-400 italic">Belum dipilih</div>
                    @error('jenis_surat')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div id="editor-section" class="hidden">
            <div class="editor-wrapper mb-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="kop-surat-header" style="width:794px;margin:0 auto;">
                    <img src="/storage/img/kop.png" alt="Kop Surat">
                </div>
                <div id="quill-editor" class="relative"></div>
            </div>

            <div class="flex gap-2 mb-8">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2.5 px-5 rounded-xl transition-colors shadow-sm flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Simpan Template
                </button>
                <a href="{{ route('admin.surat-menyurat.template') }}"
                    class="text-center text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 py-2.5 px-5 rounded-xl transition-colors">
                    Batal
                </a>
            </div>
        </div>

        <input type="hidden" name="konten" id="konten-input">
    </form>
</div>

{{-- Nomor Surat Picker Modal --}}
<div id="nomorSuratModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="flex items-center justify-center min-h-full p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 relative z-10">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-600">pin</span>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Pilih Nomor Surat</h3>
                    <p class="text-xs text-slate-500">Pilih jenis nomor surat untuk template ini.</p>
                </div>
            </div>

            <div class="space-y-2 max-h-64 overflow-y-auto mb-4" id="jenis-list">
                @foreach ($jenisSurat as $js)
                    <button type="button" onclick="selectJenis('{{ addslashes($js) }}')"
                        class="jenis-option w-full text-left px-4 py-3 rounded-xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50 transition-colors text-sm font-medium text-slate-700 flex items-center gap-3">
                        <span class="material-symbols-outlined text-[18px] text-slate-400">description</span>
                        {{ $js }}
                    </button>
                @endforeach
                <button type="button" onclick="showNewJenisInput()"
                    class="w-full text-left px-4 py-3 rounded-xl border border-dashed border-slate-300 hover:border-blue-300 hover:bg-blue-50 transition-colors text-sm font-semibold text-blue-600 flex items-center gap-3">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Jenis Baru
                </button>
            </div>

            <div id="new-jenis-section" class="hidden mb-4">
                <div class="flex gap-2">
                    <input type="text" id="new-jenis-input" placeholder="Ketik jenis baru..."
                        class="flex-1 px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50">
                    <button type="button" onclick="confirmNewJenis()"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2.5 px-4 rounded-xl transition-colors">
                        Pilih
                    </button>
                </div>
            </div>

            <button type="button" onclick="closeNomorSuratModal()"
                class="w-full text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 py-2.5 rounded-xl transition-colors">
                Batal
            </button>
        </div>
    </div>
</div>

@push('scripts')
@vite('resources/js/quill-init.js')
<script>
    var STORAGE_KEY = 'surat_template_jenis_surat';
    var quillInstance = null;

    function getSavedJenis() {
        return localStorage.getItem(STORAGE_KEY);
    }

    function setSavedJenis(val) {
        localStorage.setItem(STORAGE_KEY, val);
    }

    function selectJenis(value) {
        setSavedJenis(value);
        applyJenis(value);
        closeNomorSuratModal();
    }

    function applyJenis(value) {
        document.getElementById('jenis-surat-hidden').value = value;
        document.getElementById('nomor-surat-badge').textContent = value;
        document.getElementById('nomor-surat-display').classList.remove('hidden');
        document.getElementById('nomor-surat-empty').classList.add('hidden');
        document.getElementById('editor-section').classList.remove('hidden');
        initQuillIfNeeded();
        syncNomorSurat(value);
    }

    function openNomorSuratModal() {
        document.getElementById('nomorSuratModal').classList.remove('hidden');
        document.getElementById('new-jenis-section').classList.add('hidden');
        document.getElementById('new-jenis-input').value = '';
    }

    function closeNomorSuratModal() {
        document.getElementById('nomorSuratModal').classList.add('hidden');
    }

    function showNewJenisInput() {
        document.getElementById('new-jenis-section').classList.remove('hidden');
        document.getElementById('new-jenis-input').focus();
    }

    function confirmNewJenis() {
        var val = document.getElementById('new-jenis-input').value.trim();
        if (!val) return;
        selectJenis(val);
    }

    var isSyncing = false;

    function syncJudul(value) {
        if (!quillInstance) return;
        isSyncing = true;
        var text = quillInstance.getText();
        var firstNewline = text.indexOf('\n');
        var firstLineLen = firstNewline === -1 ? text.length : firstNewline;
        if (firstLineLen > 0) {
            quillInstance.deleteText(0, firstLineLen);
        }
        if (value) {
            quillInstance.insertText(0, value, { header: 1, align: 'center' });
        }
        var cursorPos = value ? value.length : 0;
        quillInstance.setSelection(cursorPos, 0);
        isSyncing = false;
    }

    function syncNomorSurat(value) {
        if (!quillInstance) return;
        isSyncing = true;
        var text = quillInstance.getText();
        var lines = text.split('\n');
        var noLine = 'NO: ' + value;
        if (lines.length >= 2 && lines[1].startsWith('NO: ')) {
            var oldLen = lines[1].length;
            quillInstance.deleteText(lines[0].length + 1, oldLen);
            quillInstance.insertText(lines[0].length + 1, noLine, { align: 'center', underline: true });
        } else {
            var insertPos = lines[0].length + 1;
            quillInstance.insertText(insertPos, noLine + '\n', { align: 'center', underline: true });
        }
        isSyncing = false;
    }

    function initQuillIfNeeded() {
        if (quillInstance) return;
        quillInstance = new window.Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ color: [] }, { background: [] }],
                    [{ font: [] }],
                    [{ size: ['small', false, 'large', 'huge'] }],
                    [{ align: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ indent: '-1' }, { indent: '+1' }],
                    [{ script: 'sub' }, { script: 'super' }],
                    ['blockquote', 'code-block'],
                    ['link', 'image'],
                    ['clean'],
                ],
            },
        });

        var judulVal = document.getElementById('judul-input').value.trim() || 'contoh judul';
        document.getElementById('judul-input').value = judulVal;
        var jenisVal = document.getElementById('jenis-surat-hidden').value || '';
        var initText = judulVal + '\n' + (jenisVal ? 'NO: ' + jenisVal : '') + '\n';
        quillInstance.insertText(0, judulVal + '\n', { header: 1, align: 'center' });
        if (jenisVal) {
            quillInstance.insertText(judulVal.length + 1, 'NO: ' + jenisVal + '\n', { align: 'center', underline: true });
        }
        quillInstance.setSelection(judulVal.length + 1, 0);

        quillInstance.on('text-change', function() {
            if (isSyncing) return;
            var t = quillInstance.getText();
            var nl = t.indexOf('\n');
            var firstLine = nl === -1 ? t.trim() : t.substring(0, nl).trim();
            var judulInput = document.getElementById('judul-input');
            if (firstLine !== judulInput.value) {
                judulInput.value = firstLine;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('templateForm').addEventListener('submit', function() {
            document.getElementById('konten-input').value = quillInstance ? quillInstance.root.innerHTML : '';
        });

        document.getElementById('judul-input').addEventListener('input', function() {
            syncJudul(this.value.trim());
        });

        var saved = getSavedJenis();
        if (saved) {
            applyJenis(saved);
        } else {
            openNomorSuratModal();
        }
    });

    document.getElementById('new-jenis-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            confirmNewJenis();
        }
    });
</script>
@endpush
@endsection
