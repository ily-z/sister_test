@extends('layouts.app')

@section('title', 'Arsip Dokumen')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 md:px-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Arsip Dokumen</h1>
            <p class="text-sm text-slate-500 mt-1">Daftar dokumen yang telah diterbitkan berdasarkan nomor surat.</p>
        </div>
        <a href="{{ route('admin.surat-menyurat') }}"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all active:scale-95">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            Kembali ke Nomor Surat
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="p-4 border-b border-slate-100">
            <form method="GET" action="{{ route('admin.surat-menyurat.arsip') }}">
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="relative flex-1">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari nomor seri, nomor surat, atau nomor pesanan..."
                            class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-slate-50/50 transition-all">
                    </div>
                    <select name="jenis_dokumen"
                        class="px-3 py-2.5 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors"
                        onchange="this.form.submit()">
                        <option value="">Semua Jenis</option>
                        @foreach ($jenisDokumenList as $jenis)
                            <option value="{{ $jenis }}" {{ request('jenis_dokumen') == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                        @endforeach
                    </select>
                    <select name="subkategori"
                        class="px-3 py-2.5 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors"
                        onchange="this.form.submit()">
                        <option value="">Semua Subkategori</option>
                        @foreach ($subkategorisByJenis as $jenis => $subs)
                            <optgroup label="{{ $jenis }}">
                                @foreach ($subs as $sub)
                                    <option value="{{ $sub }}" {{ request('subkategori') == $sub ? 'selected' : '' }}>{{ $sub }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <input type="month" name="bulan" value="{{ request('bulan') }}"
                        class="px-3 py-2.5 text-sm border border-slate-200 rounded-xl outline-none bg-slate-50/50 focus:border-blue-500 transition-colors">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm active:scale-95">
                        Cari
                    </button>
                    @if (request()->anyFilled(['search', 'jenis_dokumen', 'subkategori', 'bulan']))
                        <a href="{{ route('admin.surat-menyurat.arsip') }}"
                            class="flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-red-600 bg-slate-50 hover:bg-red-50 px-4 py-2.5 rounded-xl transition-colors">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left">
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nomor Seri</th>
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nomor Surat</th>
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jenis Dokumen</th>
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Subkategori</th>
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kendaraan</th>
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pesanan</th>
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tanda Tangan</th>
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4">
                                <span class="font-mono text-xs font-semibold text-slate-800">{{ $invoice->nomor_seri }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if ($invoice->surat)
                                    <span class="font-mono text-xs text-slate-600">{{ $invoice->surat->nomor_surat }}</span>
                                @else
                                    <span class="text-xs text-slate-400 italic">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($invoice->surat)
                                    <span class="text-xs font-medium text-slate-700">{{ $invoice->surat->jenis_dokumen }}</span>
                                @else
                                    <span class="text-xs text-slate-400 italic">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($invoice->surat && $invoice->surat->subkategori)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-50 text-blue-700">{{ $invoice->surat->subkategori }}</span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($invoice->kode_mobil)
                                    <span class="font-mono text-xs font-semibold text-slate-700">{{ $invoice->kode_mobil }}</span>
                                @elseif ($invoice->serviceLog && $invoice->serviceLog->car)
                                    <span class="font-mono text-xs font-semibold text-slate-700">{{ $invoice->serviceLog->car->plat_nomor }}</span>
                                @elseif ($invoice->booking && $invoice->booking->bookingCars->first())
                                    <span class="font-mono text-xs font-semibold text-slate-700">{{ $invoice->booking->bookingCars->first()->car->plat_nomor ?? '-' }}</span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($invoice->booking)
                                    <a href="{{ route('admin.bookings.show', $invoice->booking->id) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                        #{{ $invoice->booking->nomor_pesanan }}
                                    </a>
                                @elseif ($invoice->serviceLog)
                                    <span class="text-xs text-slate-500">Servis #{{ $invoice->serviceLog->id }}</span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-xs text-slate-600">{{ $invoice->issued_at ? $invoice->issued_at->format('d/m/Y') : '-' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col gap-1.5 min-w-[190px]">
                                    @foreach (\App\Models\InvoiceSignature::ROLES as $role)
                                        @php
                                            $sig = $invoice->signatureFor($role);
                                            $roleTitle = $invoice->signatureTitles()[$role];
                                        @endphp
                                        @if ($sig)
                                            <div class="flex items-center gap-2 group">
                                                <img src="{{ $sig->signature_url }}" alt="TTD {{ $sig->signer_name }}"
                                                    class="h-7 w-16 object-contain object-left bg-white border border-slate-200 rounded px-1">
                                                <div class="min-w-0">
                                                    <p class="text-[11px] font-semibold text-slate-700 truncate">{{ $roleTitle }}</p>
                                                    <p class="text-[10px] text-slate-400 truncate">
                                                        {{ $sig->signer_name }} · {{ $sig->signed_at?->format('d/m/Y H:i') }}
                                                    </p>
                                                </div>
                                                <div class="ml-auto flex items-center gap-0.5">
                                                    @include('components.invoice-sign-button', [
                                                        'invoice' => $invoice,
                                                        'role' => $role,
                                                        'compact' => true,
                                                    ])
                                                    <form method="POST" class="inline-flex"
                                                        action="{{ route('admin.invoice.signature.destroy', [$invoice->id, $role]) }}"
                                                        onsubmit="return confirm('Batalkan tanda tangan {{ $roleTitle }}? PDF invoice ikut dihapus dari arsip.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                            title="Batalkan tanda tangan {{ $roleTitle }}">
                                                            <span class="material-symbols-outlined text-[16px]">close</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <span class="h-7 w-16 inline-flex items-center justify-center border border-dashed border-slate-300 rounded text-slate-300">
                                                    <span class="material-symbols-outlined text-[14px]">draw</span>
                                                </span>
                                                <p class="text-[11px] text-slate-400">Belum TTD {{ $roleTitle }}</p>
                                                @include('components.invoice-sign-button', [
                                                    'invoice' => $invoice,
                                                    'role' => $role,
                                                    'compact' => true,
                                                ])
                                            </div>
                                        @endif
                                    @endforeach

                                    @if ($invoice->isFullySigned())
                                        <span class="inline-flex items-center gap-1 self-start px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700">
                                            <span class="material-symbols-outlined text-[12px]">verified</span>
                                            Lengkap · siap diunduh
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1">
                                    @if ($invoice->isFullySigned())
                                        <a href="{{ route('admin.invoice.download', $invoice->id) }}"
                                            class="p-2 rounded-lg text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 transition-colors"
                                            title="Unduh PDF bertanda tangan">
                                            <span class="material-symbols-outlined text-[18px]">download</span>
                                        </a>
                                        <a href="{{ route('admin.invoice.preview', $invoice->id) }}" target="_blank"
                                            class="p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                            title="Lihat PDF">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        </a>
                                    @else
                                        <span class="p-2 rounded-lg text-slate-300 cursor-not-allowed"
                                            title="Tanda tangan kedua blok dulu sebelum mengunduh">
                                            <span class="material-symbols-outlined text-[18px]">lock</span>
                                        </span>
                                    @endif
                                    @if ($invoice->jenis_invoice === 'BNG')
                                        <a href="{{ route('admin.invoice.print-bengkel', $invoice->id) }}" target="_blank"
                                            class="p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                            title="Cetak Invoice">
                                            <span class="material-symbols-outlined text-[18px]">print</span>
                                        </a>
                                    @else
                                        <a href="{{ route('admin.invoice.print', $invoice->id) }}" target="_blank"
                                            class="p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                            title="Cetak Invoice">
                                            <span class="material-symbols-outlined text-[18px]">print</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center">
                                <span class="material-symbols-outlined text-3xl text-slate-300 mb-2 block">archive</span>
                                <p class="text-sm text-slate-400">Belum ada dokumen yang diarsipkan.</p>
                                <p class="text-xs text-slate-300 mt-1">Dokumen akan muncul di sini setelah invoice atau surat diterbitkan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="px-5 py-4 flex flex-col sm:flex-row gap-4 items-center justify-between border-t border-slate-200">
                <div class="text-sm text-slate-500">
                    Menampilkan <span class="font-medium text-slate-900">{{ $invoices->firstItem() ?? 0 }}</span> sampai
                    <span class="font-medium text-slate-900">{{ $invoices->lastItem() ?? 0 }}</span> dari
                    <span class="font-medium text-slate-900">{{ $invoices->total() }}</span> entri
                </div>
                <div class="flex items-center gap-1">
                    @foreach ($invoices->linkCollection() as $link)
                        @if ($link['url'] == null)
                            @if (str_contains($link['label'], '...'))
                                <span class="px-2 text-slate-400">...</span>
                            @else
                                <span class="p-1 text-slate-300 cursor-not-allowed flex items-center justify-center">
                                    @if (str_contains($link['label'], 'Previous'))
                                        <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                                    @else
                                        <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                                    @endif
                                </span>
                            @endif
                        @elseif ($link['active'])
                            <span class="w-8 h-8 rounded-md bg-blue-600 text-white font-medium text-sm flex items-center justify-center">
                                {{ $link['label'] }}
                            </span>
                        @else
                            @if (str_contains($link['label'], 'Previous'))
                                <a href="{{ $link['url'] }}"
                                    class="p-1 text-slate-400 hover:text-blue-600 transition-colors flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                                </a>
                            @elseif (str_contains($link['label'], 'Next'))
                                <a href="{{ $link['url'] }}"
                                    class="p-1 text-slate-400 hover:text-blue-600 transition-colors flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                                </a>
                            @else
                                <a href="{{ $link['url'] }}"
                                    class="w-8 h-8 rounded-md hover:bg-slate-100 text-slate-600 font-medium text-sm flex items-center justify-center transition-colors">
                                    {{ $link['label'] }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <div class="px-5 py-4 flex items-center justify-between border-t border-slate-200">
                <span class="text-sm text-slate-500">
                    Menampilkan <span class="font-medium text-slate-900">{{ $invoices->count() }}</span> entri
                </span>
            </div>
        @endif
    </div>
</div>

{{-- Modal tanda tangan (hanya dirender sekali per halaman) --}}
@if ($invoices->first())
    @include('components.signature-modal', ['invoice' => $invoices->first()])
@endif
@endsection

@push('scripts')
    @vite('resources/js/invoice-signature.js')
@endpush