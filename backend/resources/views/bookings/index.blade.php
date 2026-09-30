@extends('layouts.app')

@section('content')
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Booking Management</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola reservasi armada, kontrak, dan status operasional.</p>
            </div>

            <a href="{{ route('admin.bookings.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all shadow-md shadow-blue-100 active:scale-95">
                <span class="material-symbols-outlined text-[20px]">add</span>
                Tambah Pesanan
            </a>
        </div>

        <form method="GET" action="{{ route('admin.bookings') }}"
            class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-500 mb-2 uppercase tracking-wider">Cari</label>
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input name="search" value="{{ request('search') }}"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all text-sm text-slate-800"
                        placeholder="Nomor pesanan, nama, kontak..." type="text" />
                </div>
            </div>
            <div class="w-full md:w-48">
                <label class="block text-xs font-semibold text-slate-500 mb-2 uppercase tracking-wider">Status
                    Pembayaran</label>
                <div class="relative">
                    <select name="status_pembayaran"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all text-sm text-slate-800 appearance-none cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="Lunas" {{ request('status_pembayaran') == 'Lunas' ? 'selected' : '' }}>Lunas
                        </option>
                        <option value="DP" {{ request('status_pembayaran') == 'DP' ? 'selected' : '' }}>DP
                        </option>
                        <option value="Belum Bayar"{{ request('status_pembayaran') == 'Belum Bayar' ? 'selected' : '' }}>
                            Belum Bayar</option>
                    </select>
                    <span
                        class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                </div>
            </div>
            <div class="w-full md:w-48">
                <label class="block text-xs font-semibold text-slate-500 mb-2 uppercase tracking-wider">Jenis Sewa</label>
                <div class="relative">
                    <select name="tipe_sewa"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all text-sm text-slate-800 appearance-none cursor-pointer">
                        <option value="">Semua Tipe</option>
                        <option value="harian" {{ request('tipe_sewa') == 'harian' ? 'selected' : '' }}>Harian</option>
                        <option value="kontrak" {{ request('tipe_sewa') == 'kontrak' ? 'selected' : '' }}>Kontrak</option>
                    </select>
                    <span
                        class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                </div>
            </div>
            <button type="submit"
                class="px-6 py-2.5 border border-slate-200 text-slate-700 bg-white rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors h-[42px]">
                Filter
            </button>
            @if (request()->hasAny(['search', 'status_pembayaran', 'tipe_sewa']))
                <a href="{{ route('admin.bookings') }}"
                    class="px-4 py-2.5 text-slate-500 hover:text-slate-700 text-sm font-medium h-[42px] flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-[16px]">close</span> Reset
                </a>
            @endif
        </form>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden flex-1 mt-6">

            {{-- ═══ MOBILE: tampilan kartu (di bawah md) ═══ --}}
            <div class="md:hidden divide-y divide-slate-100">
                @forelse ($bookings as $booking)
                    @php
                        $step = 1;
                        $statusText = 'Menunggu Pembayaran';
                        $isBatal = $booking->status_rental == 'Batal';

                        if (!$isBatal) {
                            if ($booking->status_pembayaran == 'DP' || $booking->status_pembayaran == 'Lunas') {
                                $step = 2;
                                $statusText = 'Menunggu Pengambilan';
                            }
                            if ($booking->status_rental == 'Aktif') {
                                $step = 3;
                                $statusText = 'Mobil Sedang Disewa';
                            }
                            if ($booking->status_rental == 'Selesai') {
                                $step = 4;
                                $statusText = 'Selesai';
                            }
                        }
                    @endphp
                    <div onclick="window.location='{{ route('admin.bookings.show', $booking->id) }}'"
                        class="p-4 active:bg-blue-50/50 transition-colors cursor-pointer">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 truncate">{{ $booking->nama_pelanggan }}</p>
                                <p class="text-xs font-mono text-slate-400 mt-0.5">{{ $booking->nomor_pesanan }}</p>
                            </div>
                            <span
                                class="shrink-0 capitalize text-xs font-medium px-2 py-1 rounded-full {{ $booking->tipe_sewa == 'kontrak' ? 'bg-purple-50 text-purple-600' : 'bg-amber-50 text-amber-600' }}">
                                {{ $booking->tipe_sewa }}
                            </span>
                        </div>

                        <div class="text-xs text-slate-500 flex items-center gap-1 mb-3">
                            <span class="material-symbols-outlined text-[14px]">calendar_month</span>
                            {{ \Carbon\Carbon::parse($booking->tanggal_mulai)->format('d M') }} -
                            {{ \Carbon\Carbon::parse($booking->tanggal_selesai)->format('d M Y') }}
                        </div>

                        @if ($isBatal)
                            <div class="text-sm font-bold text-red-600 mb-3">Pesanan Dibatalkan</div>
                        @else
                            <div class="flex items-center w-full mb-2">
                                <div
                                    class="w-2.5 h-2.5 rounded-full shrink-0 {{ $step > 1 ? 'bg-emerald-500' : ($step == 1 ? 'bg-blue-500 ring-4 ring-blue-100' : 'bg-slate-200') }}">
                                </div>
                                <div class="flex-1 h-0.5 {{ $step >= 2 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                <div
                                    class="w-2.5 h-2.5 rounded-full shrink-0 {{ $step > 2 ? 'bg-emerald-500' : ($step == 2 ? 'bg-blue-500 ring-4 ring-blue-100' : 'bg-slate-200') }}">
                                </div>
                                <div class="flex-1 h-0.5 {{ $step >= 3 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                <div
                                    class="w-2.5 h-2.5 rounded-full shrink-0 {{ $step > 3 ? 'bg-emerald-500' : ($step == 3 ? 'bg-blue-500 ring-4 ring-blue-100' : 'bg-slate-200') }}">
                                </div>
                                <div class="flex-1 h-0.5 {{ $step >= 4 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                <div
                                    class="w-2.5 h-2.5 rounded-full shrink-0 {{ $step == 4 ? 'bg-emerald-500 ring-4 ring-emerald-100' : 'bg-slate-200' }}">
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mb-3">
                                <span
                                    class="text-xs font-semibold {{ $step == 4 ? 'text-emerald-600' : 'text-slate-700' }}">
                                    {{ $statusText }}
                                </span>
                                @if ($step == 3 && $booking->status_pembayaran != 'Lunas')
                                    <span
                                        class="bg-red-50 text-red-600 px-1.5 py-0.5 rounded text-[9px] uppercase font-bold border border-red-200">
                                        Belum Lunas
                                    </span>
                                @endif
                            </div>
                        @endif

                        <div class="flex items-center gap-2 pt-2 border-t border-slate-50">
                            @if ($step == 2)
                                <button type="button"
                                    onclick="event.stopPropagation(); alert('Fitur Serahkan Mobil (Ubah ke Aktif)')"
                                    class="flex-1 text-blue-600 bg-blue-50 border border-blue-200 text-xs font-semibold py-2 rounded-lg flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">key</span> Serahkan
                                </button>
                            @elseif($step == 3)
                                <button type="button"
                                    onclick="event.stopPropagation(); alert('Fitur Terima Mobil (Cek sisa tagihan & Selesaikan)')"
                                    class="flex-1 text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold py-2 rounded-lg flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">done_all</span> Terima
                                </button>
                            @endif
                            <a href="{{ route('admin.bookings.edit', $booking->id) }}" onclick="event.stopPropagation()"
                                class="text-slate-500 border border-slate-200 w-9 h-9 rounded-lg flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </a>
                            <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST"
                                onsubmit="event.stopPropagation(); return confirm('Yakin ingin menghapus pesanan ini? Status mobil akan kembali Tersedia.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="event.stopPropagation()"
                                    class="text-slate-500 border border-slate-200 w-9 h-9 rounded-lg flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="py-12 px-6 text-center text-slate-500 bg-slate-50/50">
                        <span class="material-symbols-outlined text-[48px] mb-3 text-slate-300 block">event_busy</span>
                        <p class="font-medium text-slate-600">Belum ada data pesanan</p>
                        <p class="text-xs mt-1 text-slate-400">Pesanan baru akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>

            {{-- ═══ DESKTOP: tampilan tabel (md ke atas) ═══ --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="py-4 px-6 text-xs text-slate-500 uppercase tracking-wider font-semibold">Order #</th>
                            <th class="py-4 px-6 text-xs text-slate-500 uppercase tracking-wider font-semibold">Customer
                            </th>
                            <th class="py-4 px-6 text-xs text-slate-500 uppercase tracking-wider font-semibold">Progress
                            </th>
                            <th
                                class="py-4 px-6 text-xs text-slate-500 uppercase tracking-wider font-semibold text-center w-28">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-800 divide-y divide-slate-100">
                        @forelse ($bookings as $booking)
                            @php
                                // Menentukan node aktif berdasarkan kombinasi status pembayaran dan rental
                                $step = 1;
                                $statusText = 'Menunggu Pembayaran';
                                $isBatal = $booking->status_rental == 'Batal';

                                if (!$isBatal) {
                                    if ($booking->status_pembayaran == 'DP' || $booking->status_pembayaran == 'Lunas') {
                                        $step = 2;
                                        $statusText = 'Menunggu Pengambilan';
                                    }
                                    if ($booking->status_rental == 'Aktif') {
                                        $step = 3;
                                        $statusText = 'Mobil Sedang Disewa';
                                    }
                                    if ($booking->status_rental == 'Selesai') {
                                        $step = 4;
                                        $statusText = 'Selesai';
                                    }
                                }
                            @endphp

                            <tr onclick="window.location='{{ route('admin.bookings.show', $booking->id) }}'"
                                class="hover:bg-blue-50/50 hover:shadow-sm transition-all group cursor-pointer relative">

                                <td class="py-5 px-6 font-mono text-slate-600 align-top">{{ $booking->nomor_pesanan }}</td>

                                <td class="py-5 px-6 align-top">
                                    <div
                                        class="font-medium text-slate-900 group-hover:text-blue-700 transition-colors text-base mb-1">
                                        {{ $booking->nama_pelanggan }}
                                    </div>
                                    <div class="text-xs text-slate-500 flex items-center gap-1.5 flex-wrap">
                                        <span
                                            class="capitalize font-medium {{ $booking->tipe_sewa == 'kontrak' ? 'text-purple-600' : 'text-amber-600' }}">
                                            {{ $booking->tipe_sewa }}
                                        </span>
                                        <span class="text-slate-300">•</span>
                                        <span class="flex items-center gap-0.5">
                                            <span class="material-symbols-outlined text-[14px]">calendar_month</span>
                                            {{ \Carbon\Carbon::parse($booking->tanggal_mulai)->format('d M') }} -
                                            {{ \Carbon\Carbon::parse($booking->tanggal_selesai)->format('d M Y') }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-5 px-6 min-w-[200px] align-top">
                                    @if ($isBatal)
                                        <div class="flex items-center w-full max-w-[250px] mb-2 px-1">
                                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                            <div class="flex-1 h-0.5 bg-red-200"></div>
                                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                        </div>
                                        <div class="text-sm font-bold text-red-600">Pesanan Dibatalkan</div>
                                    @else
                                        <div class="flex items-center w-full max-w-[250px] mb-2 px-1">
                                            <div
                                                class="w-3 h-3 rounded-full shrink-0 {{ $step > 1 ? 'bg-emerald-500' : ($step == 1 ? 'bg-blue-500 ring-4 ring-blue-100' : 'bg-slate-200') }}">
                                            </div>
                                            <div class="flex-1 h-0.5 {{ $step >= 2 ? 'bg-emerald-500' : 'bg-slate-200' }}">
                                            </div>

                                            <div
                                                class="w-3 h-3 rounded-full shrink-0 {{ $step > 2 ? 'bg-emerald-500' : ($step == 2 ? 'bg-blue-500 ring-4 ring-blue-100' : 'bg-slate-200') }}">
                                            </div>
                                            <div class="flex-1 h-0.5 {{ $step >= 3 ? 'bg-emerald-500' : 'bg-slate-200' }}">
                                            </div>

                                            <div
                                                class="w-3 h-3 rounded-full shrink-0 {{ $step > 3 ? 'bg-emerald-500' : ($step == 3 ? 'bg-blue-500 ring-4 ring-blue-100' : 'bg-slate-200') }}">
                                            </div>
                                            <div class="flex-1 h-0.5 {{ $step >= 4 ? 'bg-emerald-500' : 'bg-slate-200' }}">
                                            </div>

                                            <div
                                                class="w-3 h-3 rounded-full shrink-0 {{ $step == 4 ? 'bg-emerald-500 ring-4 ring-emerald-100' : 'bg-slate-200' }}">
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm font-semibold {{ $step == 4 ? 'text-emerald-600' : 'text-slate-700' }}">
                                                {{ $statusText }}
                                            </span>

                                            @if ($step == 3 && $booking->status_pembayaran != 'Lunas')
                                                <span
                                                    class="bg-red-50 text-red-600 px-2 py-0.5 rounded text-[10px] uppercase font-bold border border-red-200">
                                                    Belum Lunas
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <td class="py-5 px-6 align-middle">
                                    <div class="flex items-center justify-end gap-2">

                                        @if ($step == 2)
                                            <button type="button"
                                                onclick="event.stopPropagation(); alert('Fitur Serahkan Mobil (Ubah ke Aktif)')"
                                                title="Serahkan Mobil"
                                                class="text-blue-500 hover:bg-blue-50 transition-colors inline-flex items-center justify-center border border-blue-200 bg-white w-8 h-8 rounded-lg shadow-sm">
                                                <span class="material-symbols-outlined text-[18px]">key</span>
                                            </button>
                                        @elseif($step == 3)
                                            <button type="button"
                                                onclick="event.stopPropagation(); alert('Fitur Terima Mobil (Cek sisa tagihan & Selesaikan)')"
                                                title="Terima Mobil & Selesaikan"
                                                class="text-emerald-500 hover:bg-emerald-50 transition-colors inline-flex items-center justify-center border border-emerald-200 bg-white w-8 h-8 rounded-lg shadow-sm">
                                                <span class="material-symbols-outlined text-[18px]">done_all</span>
                                            </button>
                                        @endif

                                        <a href="{{ route('admin.bookings.edit', $booking->id) }}"
                                            onclick="event.stopPropagation()"
                                            class="text-slate-400 hover:text-amber-500 transition-colors inline-flex items-center justify-center border border-slate-200 hover:border-amber-200 bg-white w-8 h-8 rounded-lg shadow-sm"
                                            title="Edit Data">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </a>

                                        <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST"
                                            class="inline-block"
                                            onsubmit="event.stopPropagation(); return confirm('Yakin ingin menghapus pesanan ini? Status mobil akan kembali Tersedia.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="event.stopPropagation()"
                                                class="text-slate-400 hover:text-red-600 transition-colors inline-flex items-center justify-center border border-slate-200 hover:border-red-200 bg-white w-8 h-8 rounded-lg shadow-sm"
                                                title="Hapus">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 px-6 text-center text-slate-500 bg-slate-50/50">
                                    <div class="flex flex-col items-center justify-center">
                                        <span
                                            class="material-symbols-outlined text-[48px] mb-3 text-slate-300">event_busy</span>
                                        <p class="font-medium text-slate-600">Belum ada data pesanan</p>
                                        <p class="text-xs mt-1 text-slate-400">Pesanan baru akan muncul di sini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($bookings->hasPages())
                <div
                    class="px-6 py-4 flex flex-col sm:flex-row gap-4 items-center justify-between border-t border-slate-200 bg-white rounded-b-2xl mt-auto">
                    <div class="text-sm text-slate-500">
                        Menampilkan <span class="font-medium text-slate-900">{{ $bookings->firstItem() ?? 0 }}</span>
                        sampai
                        <span class="font-medium text-slate-900">{{ $bookings->lastItem() ?? 0 }}</span> dari <span
                            class="font-medium text-slate-900">{{ $bookings->total() }}</span> pesanan
                    </div>
                    <div class="flex items-center gap-1">
                        @foreach ($bookings->linkCollection() as $link)
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
                                <span
                                    class="w-8 h-8 rounded-md bg-blue-600 text-white font-medium text-sm flex items-center justify-center">
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
                <div
                    class="px-6 py-4 flex items-center justify-between border-t border-slate-200 bg-white rounded-b-2xl mt-auto">
                    <span class="text-sm text-slate-500">
                        Menampilkan <span class="font-medium text-slate-900">{{ $bookings->count() }}</span> pesanan
                    </span>
                </div>
            @endif
        </div>
    </div>
@endsection