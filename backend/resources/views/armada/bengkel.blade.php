{{-- resources/views/armada/bengkel.blade.php --}}
@extends('layouts.app')

@section('title', 'Servis & Bengkel')

@section('content')
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">

        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Servis & Bengkel</h1>
                <p class="text-sm text-slate-500 mt-1">Pantau kendaraan yang sedang dalam perawatan atau perbaikan.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.armada') }}"
                    class="flex items-center bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-600 shadow-sm hover:bg-slate-50 transition-colors font-medium">
                    <span class="material-symbols-outlined text-[18px] mr-2 text-slate-400">directions_car</span>
                    Lihat Semua Armada
                </a>
            </div>
        </div>

        {{-- Stats Bar (Konsisten dengan indexs.blade.php) --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            {{-- Sedang di Bengkel --}}
            <div
                class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Di Bengkel</p>
                        <h3 class="text-3xl font-bold text-slate-900 tracking-tight">{{ $carsInWorkshop->count() }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">build_circle</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="text-slate-500">Unit kendaraan dalam perbaikan</span>
                </div>
            </div>

            {{-- Total Pengeluaran Bulan Ini --}}
            <div
                class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Biaya Bulan Ini</p>
                        <h3 class="text-2xl font-bold text-slate-900 tracking-tight mt-1">Rp
                            {{ number_format($totalBiayaBulanIni ?? 0, 0, ',', '.') }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">receipt_long</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-3">
                    <span class="text-slate-500">Total pengeluaran biaya servis</span>
                </div>
            </div>

            {{-- Servis Open --}}
            <div
                class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Log Aktif</p>
                        <h3 class="text-3xl font-bold text-slate-900 tracking-tight">{{ $openServiceLogs ?? 0 }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">pending_actions</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="text-slate-500">Catatan servis yang sedang berjalan</span>
                </div>
            </div>

            {{-- Selesai Bulan Ini --}}
            <div
                class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Selesai Bulan Ini</p>
                        <h3 class="text-3xl font-bold text-slate-900 tracking-tight">{{ $closedThisMonth ?? 0 }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">task_alt</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="text-slate-500">Servis selesai dalam bulan ini</span>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

            {{-- Left: Cars in Workshop (takes 2 cols) --}}
            <div class="lg:col-span-2 flex flex-col gap-6">
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                    {{-- Header Konsisten dengan Dashboard --}}
                    <div class="p-6 flex justify-between items-center border-b border-slate-50">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Sedang di Bengkel</h3>
                            <p class="text-xs text-slate-500 mt-1">Daftar kendaraan yang sedang dalam perawatan</p>
                        </div>
                        <span
                            class="bg-amber-100 text-amber-700 text-xs font-bold px-3 py-1.5 rounded-lg">{{ $carsInWorkshop->count() }}
                            Unit</span>
                    </div>

                    <div class="p-6">
                        @if ($carsInWorkshop->count() > 0)
                            <div class="flex flex-col gap-4">
                                @foreach ($carsInWorkshop as $car)
                                    @php
                                        // Ambil service log open terbaru
                                        $activeLog = $car->serviceLogs
                                            ->where('status_bengkel', 'open')
                                            ->sortByDesc('tanggal_masuk')
                                            ->first();
                                        $hariTeks = '';
                                        $isTerlambat = false;

                                        if ($activeLog) {
                                            $sekarang = \Carbon\Carbon::now()->startOfDay();

                                            if ($activeLog->tanggal_keluar) {
                                                // Jika ada estimasi selesai, hitung sisa hari
                                                $keluar = \Carbon\Carbon::parse(
                                                    $activeLog->tanggal_keluar,
                                                )->startOfDay();
                                                $sisa = (int) $sekarang->diffInDays($keluar, false); // false agar jadi minus jika lewat

                                                if ($sisa < 0) {
                                                    $hariTeks = 'Telat ' . abs($sisa) . ' hari';
                                                    $isTerlambat = true;
                                                } elseif ($sisa == 0) {
                                                    $hariTeks = 'Selesai hari ini';
                                                } else {
                                                    $hariTeks = 'Sisa ' . $sisa . ' hari';
                                                }
                                            } else {
                                                // Jika tidak ada estimasi, hitung hari masuk
                                                $masuk = \Carbon\Carbon::parse($activeLog->tanggal_masuk)->startOfDay();
                                                $lama = (int) $masuk->diffInDays($sekarang);
                                                $hariTeks = 'Hari ke-' . ($lama + 1);
                                            }
                                        }
                                    @endphp
                                    <div
                                        class="border border-slate-100 rounded-xl p-4 hover:border-amber-300 hover:shadow-md transition-all">
                                        <div class="flex gap-4 items-start">
                                            {{-- Foto Mobil --}}
                                            <div
                                                class="w-20 h-20 bg-slate-100 rounded-xl overflow-hidden flex-shrink-0 border border-slate-100">
                                                @if ($car->foto)
                                                    <img src="{{ asset('storage/' . $car->foto) }}"
                                                        class="w-full h-full object-cover grayscale opacity-70"
                                                        alt="Foto">
                                                @else
                                                    <div
                                                        class="w-full h-full flex items-center justify-center text-slate-400">
                                                        <span
                                                            class="material-symbols-outlined text-3xl">directions_car</span>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Info Kendaraan --}}
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between gap-2 flex-wrap mb-2">
                                                    <div>
                                                        <p
                                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                                            {{ $car->merk }}</p>
                                                        <h3 class="text-sm font-bold text-slate-900 leading-tight">
                                                            {{ $car->model }}</h3>
                                                    </div>
                                                    @if ($hariTeks)
                                                        <span
                                                            class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full 
                                                            {{ $isTerlambat ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                                            <span
                                                                class="material-symbols-outlined text-[11px]">{{ $isTerlambat ? 'warning' : 'schedule' }}</span>
                                                            {{ $hariTeks }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="flex flex-wrap gap-2 mb-3">
                                                    <span
                                                        class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold font-mono">
                                                        <span class="material-symbols-outlined text-[11px]">pin</span>
                                                        {{ $car->plat_nomor }}
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-semibold">
                                                        <span class="material-symbols-outlined text-[11px]">speed</span>
                                                        {{ number_format($car->km_saat_ini, 0, ',', '.') }} KM
                                                    </span>
                                                </div>

                                                {{-- Detail Log Servis Aktif --}}
                                                @if ($activeLog)
                                                    <div
                                                        class="bg-amber-50/60 border border-amber-100 rounded-lg px-3 py-2 text-xs mb-3">
                                                        <div class="flex items-center gap-3 flex-wrap">
                                                            @if ($activeLog->nama_bengkel)
                                                                <span class="flex items-center gap-1 text-slate-600">
                                                                    <span
                                                                        class="material-symbols-outlined text-[13px] text-amber-500">store</span>
                                                                    <span
                                                                        class="font-semibold">{{ $activeLog->nama_bengkel }}</span>
                                                                </span>
                                                            @endif
                                                            <span class="flex items-center gap-1 text-slate-500">
                                                                <span
                                                                    class="material-symbols-outlined text-[13px]">calendar_today</span>
                                                                Masuk:
                                                                {{ \Carbon\Carbon::parse($activeLog->tanggal_masuk)->format('d M Y') }}
                                                            </span>
                                                            @if ($activeLog->tanggal_keluar)
                                                                <span class="flex items-center gap-1 text-slate-500">
                                                                    <span
                                                                        class="material-symbols-outlined text-[13px]">event_available</span>
                                                                    Est. Selesai:
                                                                    {{ \Carbon\Carbon::parse($activeLog->tanggal_keluar)->format('d M Y') }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        @if ($activeLog->catatan)
                                                            <p class="text-slate-500 mt-1.5 italic line-clamp-1">
                                                                "{{ $activeLog->catatan }}"</p>
                                                        @endif

                                                        {{-- Daftar tindakan servis --}}
                                                        @if ($activeLog->details && $activeLog->details->count() > 0)
                                                            <div class="mt-2 flex flex-col gap-1">
                                                                @foreach ($activeLog->details->take(3) as $detail)
                                                                    <div class="flex items-center justify-between">
                                                                        <span
                                                                            class="flex items-center gap-1 text-slate-600 text-[11px]">
                                                                            <span
                                                                                class="material-symbols-outlined text-[11px] text-amber-500">arrow_right</span>
                                                                            <span class="truncate max-w-[200px]"
                                                                                title="{{ $detail->carPart->nama_part ?? 'Umum' }} - {{ $detail->deskripsi_tindakan }}">
                                                                                @if ($detail->carPart)
                                                                                    <strong
                                                                                        class="text-slate-700">{{ $detail->carPart->nama_part }}</strong>:
                                                                                @endif
                                                                                {{ $detail->deskripsi_tindakan }}
                                                                            </span>
                                                                        </span>
                                                                        <span
                                                                            class="font-semibold text-slate-700 ml-2 whitespace-nowrap text-[11px]">
                                                                            Rp
                                                                            {{ number_format($detail->biaya, 0, ',', '.') }}
                                                                        </span>
                                                                    </div>
                                                                @endforeach
                                                                @if ($activeLog->details->count() > 3)
                                                                    <p class="text-slate-400 text-[10px] mt-0.5">
                                                                        +{{ $activeLog->details->count() - 3 }} tindakan
                                                                        lainnya...</p>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                {{-- Action Buttons --}}
                                                <div class="flex gap-2">
                                                    <a href="{{ route('admin.armada.bengkel.create', $car->id) }}"
                                                        class="flex-1 text-center py-2 text-xs font-bold text-slate-600 bg-slate-50 hover:bg-slate-100 border border-slate-100 rounded-lg transition-colors">
                                                        <span
                                                            class="material-symbols-outlined text-[13px] align-middle mr-0.5">add_circle</span>
                                                        Catat Servis
                                                    </a>
                                                    <a href="{{ route('admin.armada.bengkel.create', $car->id) }}"
                                                        class="flex-1 text-center py-2 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-600 hover:text-white border border-emerald-200 rounded-lg transition-colors">
                                                        <span
                                                            class="material-symbols-outlined text-[13px] align-middle mr-0.5">check_circle</span>
                                                        Selesai
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- Empty State --}}
                            <div class="text-center py-16">
                                <div
                                    class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-100">
                                    <span class="material-symbols-outlined text-emerald-500 text-3xl">task_alt</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 mb-1">Semua Kendaraan Aman!</h3>
                                <p class="text-sm text-slate-500 mb-6">Tidak ada armada yang sedang masuk bengkel saat ini.
                                </p>
                                <a href="{{ route('admin.armada') }}"
                                    class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-5 py-2.5 rounded-xl transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">directions_car</span>
                                    Lihat Daftar Armada
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right: Recent Service History & Upcoming --}}
            <div class="flex flex-col gap-6">

                {{-- Riwayat Servis Terakhir --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    {{-- Header Konsisten --}}
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                                <span
                                    class="material-symbols-outlined text-[18px] text-amber-600">home_repair_service</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 tracking-tight">Riwayat Servis</h3>
                                <p class="text-[10px] text-slate-500 mt-0.5">Catatan perbaikan terbaru</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.activity', ['type' => 'service']) }}"
                            class="text-xs font-bold text-blue-600 hover:bg-blue-50 px-3 py-1.5 rounded-lg transition-colors">
                            Lihat Semua
                        </a>
                    </div>

                    <div class="flex flex-col divide-y divide-slate-100">
                        @if (isset($recentServiceLogs) && $recentServiceLogs->count() > 0)
                            @foreach ($recentServiceLogs as $log)
                                <div
                                    class="border border-slate-100 rounded-xl p-3.5 hover:border-amber-200 transition-colors bg-white">
                                    {{-- Baris Atas: Identitas Mobil & Status --}}
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex flex-col items-start gap-1">
                                            <p class="text-xs text-blue-700">
                                                <span class="font-extrabold">{{ $log->car->merk ?? '' }}</span>
                                                <span class="font-medium">{{ $log->car->model ?? '' }}</span>
                                            </p>
                                            <span
                                                class="font-mono text-[9px] font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">
                                                {{ $log->car->plat_nomor ?? '' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            @if ($log->status_bengkel === 'open')
                                                <span class="relative flex h-2 w-2" title="Sedang di bengkel">
                                                    <span
                                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                                    <span
                                                        class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                                </span>
                                            @endif
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold {{ $log->status_bengkel === 'closed' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-amber-50 text-amber-600 border border-amber-200' }}">
                                                {{ $log->status_bengkel === 'closed' ? 'Selesai' : 'Berjalan' }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Detail Bengkel & Catatan --}}
                                    <p class="text-[11px] font-bold text-slate-800 truncate mb-1">
                                        <span
                                            class="material-symbols-outlined text-[11px] align-middle text-slate-400 mr-0.5">storefront</span>
                                        {{ $log->nama_bengkel ?: 'Bengkel Umum' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 font-medium line-clamp-2 mb-2">
                                        {{ $log->catatan ?: 'Tidak ada catatan keluhan.' }}
                                    </p>

                                    {{-- Tanggal Pelaksanaan (Pindah ke Bawah) --}}
                                    <div class="mt-2 mb-2">
                                        <p class="text-[10px] text-slate-500 flex items-center gap-1 font-medium">
                                            <span
                                                class="material-symbols-outlined text-[12px] text-slate-400">calendar_month</span>
                                            {{ \Carbon\Carbon::parse($log->tanggal_masuk)->format('d M Y') }}
                                            @if ($log->tanggal_keluar)
                                                &ndash; {{ \Carbon\Carbon::parse($log->tanggal_keluar)->format('d M Y') }}
                                            @else
                                                &ndash; Sekarang
                                            @endif
                                        </p>
                                    </div>

                                    {{-- Footer: KM & Total Biaya --}}
                                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-50">
                                        <span class="text-[10px] font-bold text-slate-400">
                                            <span
                                                class="material-symbols-outlined text-[11px] align-middle mr-0.5">speed</span>
                                            {{ number_format($log->km_saat_servis ?? 0, 0, ',', '.') }} km
                                        </span>
                                        <span class="text-[11px] font-extrabold text-orange-500">
                                            Rp {{ number_format($log->serviceLogDetails->sum('biaya') ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    @if ($log->status_bengkel === 'closed')
                                        @php $logInvoice = $bengkelInvoices[$log->id] ?? null; @endphp
                                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                            <form method="POST" action="{{ route('admin.invoice.bengkel', $log->id) }}" target="_blank" class="inline-flex">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-lg transition-colors">
                                                    <span class="material-symbols-outlined text-[12px]">receipt</span>
                                                    Invoice
                                                </button>
                                            </form>

                                            @if ($logInvoice)
                                                @if ($logInvoice->isFullySigned())
                                                    <a href="{{ route('admin.invoice.download', $logInvoice->id) }}"
                                                        class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-lg transition-colors"
                                                        title="Unduh PDF bertanda tangan">
                                                        <span class="material-symbols-outlined text-[12px]">download</span>
                                                        Unduh PDF
                                                    </a>
                                                @else
                                                    @php
                                                        $logSigBlocks = [];
                                                        foreach (\App\Models\InvoiceSignature::ROLES as $blockRole) {
                                                            $logSig = $logInvoice->signatureFor($blockRole);
                                                            $logSigBlocks[$blockRole] = [
                                                                'roleLabel' => $logInvoice->signatureTitles()[$blockRole] ?? 'Penanda tangan',
                                                                'signerName' => $logSig?->signer_name
                                                                    ?: (\App\Models\InvoiceSignature::ROLE_FINANCE === $blockRole ? auth()->user()?->nama : ''),
                                                                'existingName' => $logSig?->signer_name,
                                                                'existingUrl' => $logSig?->signature_url,
                                                                'existingAt' => $logSig?->signed_at?->format('d/m/Y H:i'),
                                                            ];
                                                        }
                                                    @endphp
                                                    {{--
                                                        Atribut HARUS diapit tanda kutip ganda; Js::from()
                                                        membungkus hasilnya sebagai JSON.parse('...') yang
                                                        memakai tanda kutip tunggal literal.
                                                    --}}
                                                    <button type="button"
                                                        onclick="JRBSignature.open({{ Js::from([
                                                            'actionUrl' => route('admin.invoice.signature.store', $logInvoice->id),
                                                            'subtitle' => $logInvoice->nomor_seri,
                                                            'focusRole' => $logInvoice->missingSignatureRoles()[0] ?? 'pelanggan',
                                                            'blocks' => $logSigBlocks,
                                                        ]) }})"
                                                        class="inline-flex items-center gap-1 text-[10px] font-bold text-brand-700 hover:bg-brand-50 bg-brand-50 hover:bg-brand-100 px-2.5 py-1 rounded-lg transition-colors"
                                                        title="Tanda tangani invoice bengkel ini sebelum diunduh">
                                                        <span class="material-symbols-outlined text-[12px]">draw</span>
                                                        Tanda Tangan
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8">
                                <span class="material-symbols-outlined text-slate-300 text-4xl block mb-2">history</span>
                                <p class="text-xs text-slate-400">Belum ada riwayat servis.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Estimasi Selesai Mendatang --}}
                @if (isset($upcomingFinish) && $upcomingFinish->count() > 0)
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                        {{-- Header Konsisten --}}
                        <div class="p-6 border-b border-slate-50">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 tracking-tight">Estimasi Selesai</h3>
                                <p class="text-xs text-slate-500 mt-1">Jadwal kendaraan keluar bengkel</p>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="flex flex-col gap-3">
                                @foreach ($upcomingFinish as $log)
                                    <div
                                        class="flex items-center gap-3 p-3 bg-purple-50/50 border border-purple-100 rounded-xl">
                                        <span
                                            class="material-symbols-outlined text-purple-400 text-[20px]">calendar_clock</span>
                                        <div>
                                            <p class="text-xs font-bold text-slate-900">{{ $log->car->model ?? '-' }}</p>
                                            <p class="text-[10px] font-mono text-slate-500">
                                                {{ $log->car->plat_nomor ?? '' }}</p>
                                            <p class="text-[10px] text-purple-600 font-semibold mt-0.5">
                                                Selesai: {{ \Carbon\Carbon::parse($log->tanggal_keluar)->format('d M Y') }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Modal tanda tangan invoice bengkel --}}
    @if ($bengkelInvoices->isNotEmpty())
        @include('components.signature-modal', [
            'invoice' => $bengkelInvoices->first(),
        ])
    @endif
@endsection

@push('scripts')
    @if ($bengkelInvoices->isNotEmpty())
        @vite('resources/js/invoice-signature.js')
    @endif
@endpush
