<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Verifikasi Invoice - Precision Fleet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        @if ($valid)
            {{-- ═══ INVOICE VALID ═══ --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-emerald-200">
                <div class="bg-emerald-600 px-6 py-8 text-center">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="material-symbols-outlined text-[36px] text-emerald-600">verified</span>
                    </div>
                    <h1 class="text-xl font-extrabold text-white">Invoice Terverifikasi</h1>
                    <p class="text-emerald-100 text-sm mt-1">Dokumen ini asli & tercatat resmi di sistem</p>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nomor Dokumen</span>
                        <span class="text-sm font-mono font-bold text-slate-800">{{ $invoice->surat->nomor_surat ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nomor Seri</span>
                        <span class="text-sm font-mono font-bold text-slate-800">{{ $invoice->nomor_seri }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Jenis Dokumen</span>
                        <span class="text-sm font-semibold text-slate-800">
                            @php
                                $jenisLabel = match($invoice->jenis_invoice) {
                                    'SH' => 'Invoice Sewa Harian',
                                    'SK' => 'Invoice Sewa Kontrak',
                                    'AK' => 'Invoice Asuransi Kerusakan',
                                    'BNG' => 'Invoice Bengkel',
                                    default => 'Invoice',
                                };
                            @endphp
                            {{ $jenisLabel }}
                        </span>
                    </div>

                    @if ($invoice->booking)
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">Nomor Pesanan</span>
                            <span class="text-sm font-semibold text-slate-800">{{ $invoice->booking->nomor_pesanan }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">Pelanggan</span>
                            <span class="text-sm font-semibold text-slate-800">{{ $invoice->booking->nama_pelanggan }}</span>
                        </div>
                        @if ($invoice->booking->bookingCars->first())
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Kendaraan</span>
                                <span class="text-sm font-semibold text-slate-800">
                                    {{ $invoice->booking->bookingCars->first()->car->merk ?? '' }}
                                    {{ $invoice->booking->bookingCars->first()->car->model ?? '' }}
                                    ({{ $invoice->kode_mobil }})
                                </span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center pt-3 mt-1 border-t border-dashed border-slate-100">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Tagihan</span>
                            <span class="text-base font-black text-blue-700">Rp {{ number_format($invoice->booking->total_harga, 0, ',', '.') }}</span>
                        </div>
                    @elseif ($invoice->serviceLog)
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">Kendaraan</span>
                            <span class="text-sm font-semibold text-slate-800">
                                {{ $invoice->serviceLog->car->merk ?? '' }}
                                {{ $invoice->serviceLog->car->model ?? '' }}
                                ({{ $invoice->kode_mobil }})
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">Bengkel</span>
                            <span class="text-sm font-semibold text-slate-800">{{ $invoice->serviceLog->nama_bengkel ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 mt-1 border-t border-dashed border-slate-100">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Biaya</span>
                            <span class="text-base font-black text-blue-700">Rp {{ number_format($invoice->serviceLog->details->sum('biaya'), 0, ',', '.') }}</span>
                        </div>
                    @endif

                    {{-- ═══ STATUS PENDATA TANGAN ═══ --}}
                    @if ($invoice->signatures->isNotEmpty())
                        <div class="pt-4 border-t border-slate-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ditandatangani</span>
                                @if ($invoice->isFullySigned())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">
                                        <span class="material-symbols-outlined text-[12px]">verified</span>
                                        Lengkap
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700">
                                        <span class="material-symbols-outlined text-[12px]">pending</span>
                                        Belum Lengkap
                                    </span>
                                @endif
                            </div>

                            @foreach (\App\Models\InvoiceSignature::ROLES as $role)
                                @php
                                    $sig = $invoice->signatureFor($role);
                                    $roleTitle = $invoice->signatureTitles()[$role];
                                @endphp
                                <div class="flex items-center justify-between py-1.5">
                                    <span class="text-xs text-slate-500">{{ $roleTitle }}</span>
                                    @if ($sig)
                                        <span class="text-xs font-semibold text-slate-700 text-right">
                                            {{ $sig->signer_name }}
                                            <span class="block text-[10px] font-normal text-slate-400">
                                                {{ $sig->signed_at?->format('d M Y, H:i') }}
                                            </span>
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-300 italic">belum</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                        <span class="text-xs text-slate-500">Diterbitkan</span>
                        <span class="text-sm font-semibold text-slate-800">{{ $invoice->issued_at->format('d F Y, H:i') }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Total Verifikasi</span>
                        <span class="text-xs font-semibold text-slate-400">
                            {{ $verification->verified_at ? 'Pertama kali discan: ' . $verification->verified_at->format('d M Y, H:i') : '-' }}
                        </span>
                    </div>
                </div>

                <div class="px-6 pb-6 space-y-3">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-amber-600 text-[18px] shrink-0 mt-0.5">warning</span>
                        <p class="text-[11px] text-amber-700 leading-relaxed">
                            <strong>Penting:</strong> Cocokkan nama pelanggan, kendaraan, dan nominal di layar ini
                            dengan yang tertulis pada dokumen fisik. QR code yang valid tidak menjamin seluruh
                            isi kertas sesuai — selalu bandingkan detailnya secara manual.
                        </p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-[11px] text-slate-500">
                            Dokumen ini diterbitkan oleh <strong>PT. Jaya Royal Bersama</strong>.
                            Jika ada kejanggalan, hubungi kantor kami untuk konfirmasi.
                        </p>
                    </div>
                </div>
            </div>
        @else
            {{-- ═══ INVOICE TIDAK VALID ═══ --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-red-200">
                <div class="bg-red-600 px-6 py-8 text-center">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="material-symbols-outlined text-[36px] text-red-600">gpp_bad</span>
                    </div>
                    <h1 class="text-xl font-extrabold text-white">Invoice Tidak Valid</h1>
                    <p class="text-red-100 text-sm mt-1">Kode QR ini tidak ditemukan dalam sistem kami</p>
                </div>
                <div class="p-6">
                    <p class="text-sm text-slate-600 text-center leading-relaxed">
                        Dokumen ini kemungkinan <strong>bukan terbitan resmi</strong> dari PT. Jaya Royal Bersama,
                        atau kode QR sudah rusak/tidak terbaca sempurna.
                    </p>
                    <div class="mt-5 bg-amber-50 border border-amber-200 rounded-xl p-3">
                        <p class="text-xs text-amber-700 text-center">
                            Jika Anda ragu terhadap keaslian dokumen ini, silakan hubungi langsung
                            kantor PT. Jaya Royal Bersama untuk konfirmasi.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <p class="text-center text-xs text-slate-400 mt-4">
            Precision Fleet — Sistem Verifikasi Dokumen Resmi
        </p>
    </div>

</body>
</html>