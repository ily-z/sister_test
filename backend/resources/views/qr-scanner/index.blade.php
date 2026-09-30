@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">QR Scanner</h1>
        <p class="text-sm text-slate-500 mt-1">Scan QR code untuk mencari data booking atau armada</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 sm:p-6">
            <div class="relative bg-slate-900 rounded-lg overflow-hidden" style="min-height: 360px;">
                <video id="qr-video" class="w-full h-full object-cover" style="min-height: 360px;"></video>

                <div id="btn-overlay" class="absolute inset-0 flex items-center justify-center">
                    <button id="btn-start"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white/90 hover:bg-white text-slate-900 rounded-xl font-semibold shadow-lg transition-all hover:scale-105">
                        <span class="material-symbols-outlined">videocam</span>
                        Mulai Scan
                    </button>
                </div>

                <div class="absolute top-3 right-3 flex gap-2">
                    <button id="btn-flash"
                        class="hidden p-2 bg-white/80 hover:bg-white rounded-lg text-slate-700 shadow-sm transition-colors text-sm">
                        Flash On
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 mt-4">
                <button id="btn-stop"
                    class="hidden inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors text-sm">
                    <span class="material-symbols-outlined text-[18px]">stop</span>
                    Stop
                </button>

                <button id="btn-file"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-medium transition-colors text-sm">
                    <span class="material-symbols-outlined text-[18px]">image</span>
                    Scan dari Gambar
                </button>
                <input id="file-input" type="file" accept="image/*" class="hidden">

                <select id="camera-select"
                    class="ml-auto px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">Kamera Default</option>
                </select>
            </div>
        </div>

        <div id="scan-result-container" class="hidden border-t border-slate-200 p-4 sm:p-6">
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Hasil Scan</h2>
            <div class="bg-slate-50 rounded-lg p-4">
                <p id="scan-result" class="text-slate-900 font-mono text-sm break-all"></p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite('resources/js/qr-scanner.js')
@endpush
