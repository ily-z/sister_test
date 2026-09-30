{{--
    Modal tanda tangan invoice dengan DUA blok sekaligus (pelanggan + finance),
    supaya kedua tanda tangan bisa diisi dalam satu kali buka modal dan PDF
    langsung ter-generate satu kali untuk keduanya.

    Dipakai lewat @include di halaman yang punya tombol "Tanda Tangan",
    lalu dipanggil dari JS: window.JRBSignature.open({...}).

    -- $invoice  Invoice placeholder untuk action URL (diperbarui JS per invoice)

    Catatan: karena satu modal dipakai untuk banyak invoice di halaman arsip,
    data per blok (label, nama, tanda tangan lama) TIDAK di-render di sini.
    Semuanya diisi dari JS lewat config saat modal dibuka.
--}}
@once('jrb-signature-modal')
<div id="jrb-sig-modal" class="fixed inset-0 z-[70] hidden overflow-y-auto overscroll-contain" role="dialog"
    aria-modal="true" aria-labelledby="jrb-sig-title">
    <div id="jrb-sig-backdrop" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

    <div class="relative min-h-full flex items-start sm:items-center justify-center p-4">
        <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden my-4">
            <form id="jrb-sig-form" method="POST" action="{{ route('admin.invoice.signature.store', $invoice->id) }}">
                @csrf

                <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4 border-b border-slate-100">
                    <div>
                        <h3 id="jrb-sig-title" class="text-lg font-bold text-slate-900">Tanda tangan</h3>
                        <p id="jrb-sig-subtitle" class="text-xs text-slate-500 mt-0.5">
                            {{ $invoice->surat?->nomor_surat ?? $invoice->nomor_seri }}
                        </p>
                    </div>
                    <button type="button" data-sig-cancel
                        class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                        title="Tutup">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="px-6 py-5">
                    <p id="jrb-sig-error" class="hidden mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-sm text-rose-700"></p>

                    <p class="text-xs text-slate-500 mb-4">
                        Isi kedua blok tanda tangan, lalu simpan sekali jalan. Blok yang dikosongkan tidak akan diubah.
                    </p>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        @foreach (\App\Models\InvoiceSignature::ROLES as $sigRole)
                            @php $sigLabel = $invoice->signatureTitles()[$sigRole] ?? 'Penanda tangan'; @endphp
                            <section data-sig-block="{{ $sigRole }}"
                                class="rounded-2xl border border-slate-200 bg-slate-50/40 p-4 flex flex-col">

                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-bold text-slate-800" data-sig-title>{{ $sigLabel }}</h4>
                                        <p class="text-[11px] text-slate-400 mt-0.5" data-sig-badge>Belum ditandatangani</p>
                                    </div>
                                    <span data-sig-existing-tag
                                        class="hidden shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                                        <span class="material-symbols-outlined text-[13px]">verified</span> Tersimpan
                                    </span>
                                </div>

                                {{-- Pratinjau tanda tangan lama (hanya blok yang sudah ditandatangani) --}}
                                <div data-sig-preview-row
                                    class="hidden mb-3 p-2 bg-white border border-slate-200 rounded-xl">
                                    <img data-sig-preview alt="Tanda tangan tersimpan" class="block w-full h-14 object-contain">
                                    <p class="text-[10px] text-slate-400 mt-1 text-center" data-sig-preview-meta></p>
                                </div>

                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                                    for="jrb-sig-name-{{ $sigRole }}">
                                    Nama penanda tangan
                                </label>
                                <input type="text" id="jrb-sig-name-{{ $sigRole }}" data-sig-name
                                    name="signatures[{{ $sigRole }}][signer_name]" maxlength="100" required
                                    autocomplete="off"
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"
                                    placeholder="Nama lengkap">

                                <div class="flex items-center justify-between mt-4 mb-1.5">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Coret di
                                        sini</span>
                                    <button type="button" data-sig-clear
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 hover:text-rose-600 hover:bg-rose-50 px-2 py-1 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-[15px]">ink_eraser</span>
                                        Bersihkan
                                    </button>
                                </div>

                                <div class="rounded-xl border-2 border-dashed border-slate-200 bg-white p-2">
                                    <canvas id="jrb-sig-canvas-{{ $sigRole }}" data-sig-canvas
                                        class="block w-full h-[140px] touch-none cursor-crosshair rounded-lg bg-white"></canvas>
                                </div>

                                <p data-sig-block-error class="hidden mt-2 text-[11px] text-rose-600 font-medium"></p>

                                <div data-sig-replace-row class="hidden mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                                    <label class="flex items-start gap-2 text-[11px] text-amber-800 cursor-pointer">
                                        <input type="checkbox" data-sig-replace
                                            name="signatures[{{ $sigRole }}][confirm_replace]" value="1"
                                            class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500/30">
                                        <span>
                                            Blok ini sudah ditandatangani. Centang untuk
                                            <strong>mengganti</strong> tanda tangan lama dan membuat ulang PDF invoice.
                                        </span>
                                    </label>
                                </div>
                            </section>
                        @endforeach
                    </div>

                    <p class="text-[11px] text-slate-400 mt-4">
                        Bisa juga memakai mouse, sentuh, atau pena stylus. Tanda tangan disimpan sebagai bagian dari
                        dokumen invoice yang diarsipkan.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4 bg-slate-50 border-t border-slate-100">
                    <button type="button" data-sig-cancel
                        class="px-4 py-2.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-semibold transition-all active:scale-95">
                        Batal
                    </button>
                    <button type="submit" id="jrb-sig-save"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-60 disabled:cursor-not-allowed text-white rounded-xl text-sm font-bold transition-all shadow-md active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">draw</span>
                        <span data-sig-save-label>Simpan &amp; Terapkan</span>
                    </button>
                </div>

                {{-- Diisi JS hanya untuk blok yang benar-benar digambar. --}}
                @foreach (\App\Models\InvoiceSignature::ROLES as $sigRole)
                    <input type="hidden" data-sig-data name="signatures[{{ $sigRole }}][signature_data]" value="">
                @endforeach
            </form>
        </div>
    </div>
</div>
@endonce
