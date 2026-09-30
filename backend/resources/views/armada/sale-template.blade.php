@extends('layouts.app')

@section('content')
<style>
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
.template-content s { text-decoration: line-through; color: #94a3b8; }
.template-content sub { font-size: 0.75em; vertical-align: sub; }
.template-content sup { font-size: 0.75em; vertical-align: super; }
.template-content ul[data-type="taskList"] { list-style: none; padding-left: 0; }
.template-content ul[data-type="taskList"] li { display: flex; align-items: flex-start; gap: 0.5rem; }
.template-content ul[data-type="taskList"] li label { flex-shrink: 0; margin-top: 0.25rem; }
.template-content ul[data-type="taskList"] li label input[type="checkbox"] { accent-color: #3b82f6; }
.template-content ul[data-type="taskList"] li div { flex: 1; }

#quill-editor .ql-editor { font-size: 0.875rem; line-height: 1.625; min-height: 400px; }
#quill-editor .ql-editor h1 { font-size: 1.875rem; font-weight: 800; color: #0f172a; }
#quill-editor .ql-editor h2 { font-size: 1.5rem; font-weight: 700; color: #0f172a; }
#quill-editor .ql-editor h3 { font-size: 1.25rem; font-weight: 700; color: #0f172a; }
#quill-editor .ql-editor h4 { font-size: 1.125rem; font-weight: 600; color: #0f172a; }
#quill-editor .ql-editor h5 { font-size: 1rem; font-weight: 600; color: #0f172a; }
#quill-editor .ql-editor h6 { font-size: 0.875rem; font-weight: 600; color: #0f172a; }

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
</style>

<div class="max-w-5xl mx-auto p-6 bg-slate-50 min-h-screen">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-slate-800">Template Surat Pelepasan</h2>
        <div class="flex gap-2">
            <button id="toggleEditBtn"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-xl transition-colors duration-150 bg-brand-600 text-white hover:bg-brand-700 shadow-sm">
                <span class="material-symbols-outlined text-lg">edit</span>
                <span id="toggleEditLabel">Edit</span>
            </button>
            <button id="saveBtn" style="display:none"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-xl transition-colors duration-150 bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                <span class="material-symbols-outlined text-lg">save</span>
                Simpan
            </button>
            <button id="cancelBtn" style="display:none"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-xl transition-colors duration-150 bg-white text-slate-600 hover:bg-slate-100 border border-slate-300 shadow-sm">
                Batal
            </button>
        </div>
    </div>

    <div id="viewMode" class="bg-white border border-slate-200 shadow-sm rounded-3xl overflow-hidden">
        <div id="viewContent" class="px-4 sm:px-10 py-8 template-content"></div>
    </div>

    <div id="editMode" style="display:none" class="bg-white border border-slate-200 shadow-sm rounded-3xl overflow-hidden">
        <div id="quill-editor"></div>
    </div>

    <div id="saveStatus" class="mt-3 text-sm text-slate-500 text-center"></div>
</div>

@push('scripts')
@vite('resources/js/quill-init.js')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        var DEFAULT_HTML = [
            '<p><strong>PT. JAYA ROYAL BERSAMA</strong></p>',
            '<p><strong>Alamat Kantor</strong><br>Jl. Teuku Umar No. 18B Bangkalan 69116 - Indonesia<br>HP: +62 851 0175 9149<br>Email: royaltourtravelbkl@gmail.com</p>',
            '<h2>Surat Pelepasan Hak Kepemilikan Kendaraan</h2>',
            '<p>Nomor : GEN / 2026 / 03 / 00</p>',
            '<hr>',
            '<p>Yang bertanda tangan di bawah ini :</p>',
            '<p><strong>Nama</strong> &nbsp;: ACHMAD MUNIR</p>',
            '<p><strong>Jabatan</strong> &nbsp;: Direktur Utama</p>',
            '<p><strong>Alamat</strong> &nbsp;: Jl. Teuku Umar No. 18-B Kelurahan Kemayoran Kecamatan Bangkalan Kabupaten Bangkalan, Jawa Timur</p>',
            '<hr>',
            '<p>Dengan ini menerangkan bahwa kendaraan dengan data identitas sebagai berikut :</p>',
            '<p><strong>Nomor Polisi</strong> &nbsp;: M 8266 GD</p>',
            '<p><strong>Merk / Type</strong> &nbsp;: Mitsubishi / Triton 2.4L DC Ultimate 4x4 AT</p>',
            '<p><strong>Jenis / Model</strong> &nbsp;: Mobil Barang / Double Cabin Pickup</p>',
            '<p><strong>Tahun Pembuatan</strong> &nbsp;: 2025</p>',
            '<p><strong>Isi Silinder</strong> &nbsp;: 2.442 CC</p>',
            '<p><strong>Nomor Mesin</strong> &nbsp;: 4N16UBA4024</p>',
            '<p><strong>Nomor Rangka</strong> &nbsp;: MMBJLLC20SH047221</p>',
            '<p><strong>Warna</strong> &nbsp;: Hitam</p>',
            '<hr>',
            '<p>Adalah benar kendaraan milik PT. Jaya Royal Bersama dan kendaraan dengan data identitas tersebut telah dijual.</p>',
            '<p>Oleh karena itu, mewakili dan atas nama PT. Jaya Royal Bersama, dengan ini menerangkan bahwa dengan sebenar-benarnya telah melepaskan hak kepemilikan atas kendaraan tersebut dan menjadi tanggung jawab sepenuhnya kepada pihak pembeli, dengan identitas :</p>',
            '<p><strong>Nama Pembeli</strong> &nbsp;: [NAMA PEMBELI]</p>',
            '<p><strong>Alamat</strong> &nbsp;: [ALAMAT PEMBELI]</p>',
            '<hr>',
            '<p>Demikian Surat Pelepasan Hak Kepemilikan Kendaraan ini dibuat untuk dapat dipergunakan sebagaimana sesuai peruntukannya.</p>',
            '<p>Bangkalan, 16 Maret 2026</p>',
            '<p><strong>PT. JAYA ROYAL BERSAMA</strong></p>',
            '<p><strong>ACHMAD MUNIR</strong><br><span style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.2em;color:#64748b;">Direktur Utama</span></p>',
            '<hr>',
            '<p><span style="font-size:0.75rem;color:#64748b;">PT. JAYA ROYAL BERSAMA | Jl. Teuku Umar No. 18B Bangkalan 69116 - Indonesia<br>HP: +62 851 0175 9149 | Email: royaltourtravelbkl@gmail.com</span></p>'
        ].join('\n');

        var savedRaw = @json($content);
        var editorHtml = savedRaw || DEFAULT_HTML;

        var viewMode = document.getElementById('viewMode');
        var editMode = document.getElementById('editMode');
        var viewContent = document.getElementById('viewContent');
        var toggleBtn = document.getElementById('toggleEditBtn');
        var toggleLabel = document.getElementById('toggleEditLabel');
        var saveBtn = document.getElementById('saveBtn');
        var cancelBtn = document.getElementById('cancelBtn');
        var saveStatus = document.getElementById('saveStatus');

        var isEditing = false;
        var quill = null;

        function showView() {
            if (quill) {
                quill.destroy();
                quill = null;
                document.getElementById('quill-editor').innerHTML = '';
            }
            viewContent.innerHTML = editorHtml;
            viewMode.style.display = 'block';
            editMode.style.display = 'none';
            saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            toggleBtn.style.display = 'inline-flex';
            toggleLabel.textContent = 'Edit';
            toggleBtn.querySelector('.material-symbols-outlined').textContent = 'edit';
            isEditing = false;
        }

        function showEdit() {
            viewMode.style.display = 'none';
            editMode.style.display = 'block';
            saveBtn.style.display = 'inline-flex';
            cancelBtn.style.display = 'inline-flex';
            toggleBtn.style.display = 'none';
            isEditing = true;

            quill = new window.Quill('#quill-editor', {
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

            quill.clipboard.dangerouslyPasteHTML(editorHtml, 'user');
        }

        showView();

        toggleBtn.addEventListener('click', function() {
            if (!isEditing) showEdit();
        });

        cancelBtn.addEventListener('click', function() {
            showView();
        });

        saveBtn.addEventListener('click', function() {
            if (!quill) return;

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="material-symbols-outlined text-lg animate-spin">progress_activity</span> Menyimpan...';

            editorHtml = quill.root.innerHTML;

            fetch('{{ route('admin.armada.sale-template.save') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ content: editorHtml })
            }).then(function(res) { return res.json() }).then(function(result) {
                if (result.success) {
                    saveStatus.textContent = 'Template berhasil disimpan.';
                    saveStatus.className = 'mt-3 text-sm text-emerald-600 text-center';
                    showView();
                } else {
                    saveStatus.textContent = 'Gagal menyimpan template.';
                    saveStatus.className = 'mt-3 text-sm text-red-500 text-center';
                }
            })['catch'](function() {
                saveStatus.textContent = 'Terjadi kesalahan saat menyimpan.';
                saveStatus.className = 'mt-3 text-sm text-red-500 text-center';
            }).finally(function() {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<span class="material-symbols-outlined text-lg">save</span> Simpan';
            });
        });

    });
</script>
@endpush
@endsection
