<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Bengkel - {{ $invoice->surat->nomor_surat ?? $invoice->nomor_seri }}</title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { size: A4 portrait; margin: 8mm 12mm 6mm 12mm; }
        body { margin: 0; padding: 0; font-family: 'Arial', sans-serif; color: #1e293b; font-size: 8pt; }
        .page { width: 100%; padding: 0; }
        .header { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .header-logo { width: 45px; height: 45px; background: #1e3a5f; color: white; font-weight: 900; font-size: 14pt; display: flex; align-items: center; justify-content: center; border-radius: 6px; letter-spacing: 1px; flex-shrink: 0; }
        .header-info { flex: 1; }
        .header-company { font-size: 14pt; font-weight: 900; color: #1e3a5f; letter-spacing: 0.5px; line-height: 1.1; }
        .header-sub { font-size: 7pt; color: #475569; margin-top: 1px; }
        .header-addr { font-size: 6.5pt; color: #64748b; margin-top: 1px; }
        .header-doc { text-align: right; border: 1.5px solid #1e3a5f; padding: 4px 8px; min-width: 130px; }
        .header-doc-label { font-size: 6pt; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-doc-value { font-size: 7.5pt; font-weight: 800; color: #1e3a5f; margin-top: 1px; }
        .header-doc-date { font-size: 6.5pt; color: #475569; margin-top: 1px; }
        .divider { height: 3px; background: #1e3a5f; margin: 4px 0 8px; }
        .title { text-align: center; font-size: 14pt; font-weight: 900; letter-spacing: 2px; color: #1e3a5f; text-transform: uppercase; margin-bottom: 10px; }
        .info-grid { display: flex; gap: 8px; margin-bottom: 8px; }
        .info-box { flex: 1; border: 1px solid #cbd5e1; }
        .info-box-header { background: #1e3a5f; color: white; font-size: 6.5pt; font-weight: 700; letter-spacing: 0.5px; padding: 2px 8px; text-transform: uppercase; }
        .info-table { width: 100%; font-size: 7pt; color: #334155; padding: 3px 8px; border-collapse: collapse; }
        .info-table td { padding: 1.5px 4px; vertical-align: top; }
        .info-table td:first-child { color: #64748b; width: 32%; white-space: nowrap; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-bottom: 8px; }
        .items-table th { background: #1e3a5f; color: white; font-size: 6.5pt; font-weight: 700; padding: 3px 8px; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-table td { padding: 3px 8px; border-bottom: 0.5px solid #e2e8f0; }
        .items-table tr:last-child td { border-bottom: none; }
        .items-table .text-right { text-align: right; }
        .items-table .text-center { text-align: center; }
        .total-section { border-top: 2px solid #1e3a5f; margin-top: 4px; padding-top: 4px; text-align: right; font-size: 8pt; }
        .total-row { display: flex; justify-content: flex-end; gap: 20px; padding: 2px 0; }
        .total-label { font-weight: 600; color: #475569; }
        .total-value { font-weight: 700; min-width: 90px; text-align: right; }
        .grand-total { font-size: 11pt; font-weight: 900; color: #1e3a5f; border-top: 1px solid #1e3a5f; padding-top: 4px; margin-top: 2px; }
        .footer-section { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 12px; }
        .qr-section { text-align: center; }
        .qr-section svg { display: block; margin: 0 auto 2px; }
        .qr-label { font-size: 5.5pt; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .ttd-wrap { display: flex; justify-content: space-around; text-align: center; flex: 1; max-width: 70%; }
        .ttd-block { text-align: center; }
        .ttd-label { font-size: 7pt; font-weight: 700; color: #1e3a5f; }
        .ttd-space { height: 40px; }
        .ttd-sign { height: 42px; padding-top: 4px; }
        .ttd-sign img { max-height: 38px; max-width: 130px; }
        .ttd-meta { font-size: 5.5pt; color: #64748b; margin-top: 1px; }
        .ttd-garis { font-size: 7pt; color: #1e293b; border-top: 1px solid #334155; padding-top: 2px; min-width: 110px; }
        .ttd-kota { font-size: 7pt; color: #334155; margin-bottom: 1px; }
        .terbilang { font-size: 6.5pt; color: #475569; font-style: italic; margin-top: 4px; padding-top: 4px; border-top: 0.5px dashed #cbd5e1; }
        .status-badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 6.5pt; font-weight: 700; }
        .status-selesai { background: #dcfce7; color: #166534; }
        .status-proses { background: #fef9c3; color: #854d0e; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="header-logo">JRB</div>
            <div class="header-info">
                <div class="header-company">PT JAYA ROYAL BERSAMA</div>
                <div class="header-sub">Jasa Rental & Transportasi Kendaraan</div>
                <div class="header-addr">Jl. Teuku Umar No. 18B, Bangkalan, Jawa Timur</div>
            </div>
            <div class="header-doc">
                <div class="header-doc-label">No. Dokumen</div>
                <div class="header-doc-value">{{ $invoice->surat->nomor_surat ?? $invoice->nomor_seri }}</div>
                <div class="header-doc-date">{{ $invoice->issued_at->isoFormat('D MMMM Y') }}</div>
            </div>
        </div>
        <div class="divider"></div>

        <div class="title">INVOICE BENGKEL</div>
        <div style="text-align:center;font-size:7.5pt;font-weight:700;color:#475569;margin-bottom:8px;">PERAWATAN & PERBAIKAN KENDARAAN</div>

        <div class="info-grid">
            <div class="info-box">
                <div class="info-box-header">Data Kendaraan</div>
                <table class="info-table">
                    <tr><td>Merk/Model</td><td>: <strong>{{ $serviceLog->car->merk }} {{ $serviceLog->car->model }}</strong></td></tr>
                    <tr><td>Plat Nomor</td><td>: {{ $serviceLog->car->plat_nomor }}</td></tr>
                    <tr><td>Kode Mobil</td><td>: {{ $invoice->kode_mobil ?? '-' }}</td></tr>
                    <tr><td>KM Saat Ini</td><td>: {{ number_format($serviceLog->car->km_saat_ini, 0, ',', '.') }} km</td></tr>
                </table>
            </div>
            <div class="info-box">
                <div class="info-box-header">Detail Servis</div>
                <table class="info-table">
                    <tr><td>Tanggal Masuk</td><td>: {{ \Carbon\Carbon::parse($serviceLog->tanggal_masuk)->isoFormat('D MMMM Y') }}</td></tr>
                    <tr><td>Tanggal Keluar</td><td>: {{ $serviceLog->tanggal_keluar ? \Carbon\Carbon::parse($serviceLog->tanggal_keluar)->isoFormat('D MMMM Y') : '-' }}</td></tr>
                    <tr><td>Status</td><td>: <span class="status-badge status-{{ $serviceLog->status_bengkel === 'closed' ? 'selesai' : 'proses' }}">{{ $serviceLog->status_bengkel === 'closed' ? 'Selesai' : 'Proses' }}</span></td></tr>
                    <tr><td>Bengkel</td><td>: {{ $serviceLog->nama_bengkel ?? '-' }}</td></tr>
                    @if ($serviceLog->catatan)<tr><td>Catatan</td><td>: {{ $serviceLog->catatan }}</td></tr>@endif
                </table>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:8%">No</th>
                    <th style="width:60%">Tindakan / Jasa Servis</th>
                    <th style="width:32%" class="text-right">Biaya</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($serviceLog->details as $detail)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $detail->deskripsi_tindakan ?? '-' }}</td>
                        <td class="text-right">Rp {{ number_format($detail->biaya ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center;color:#94a3b8;font-style:italic;">Tidak ada detail servis</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @php
            $totalBiaya = $serviceLog->details->sum('biaya');
        @endphp

        <div class="total-section">
            <div class="grand-total">
                <div class="total-row">
                    <span class="total-label">TOTAL:</span>
                    <span class="total-value">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        @php
            $terbilang = new \NumberFormatter('id', \NumberFormatter::SPELLOUT);
        @endphp
        <div class="terbilang">Terbilang: {{ $terbilang->format($totalBiaya) }} rupiah</div>

        <div class="footer-section">
            <div class="qr-section">
                {!! QrCode::format('svg')->size(70)->margin(0)->generate($invoice->qr_code_string) !!}
            </div>
            <div class="ttd-wrap">
                @foreach (\App\Models\InvoiceSignature::ROLES as $role)
                    @php
                        $sig = $invoice->signatureFor($role);
                        $roleTitle = $invoice->signatureTitles()[$role];
                    @endphp
                    <div class="ttd-block">
                        @if ($role === \App\Models\InvoiceSignature::ROLE_PELANGGAN)
                            <div class="ttd-kota">Bangkalan, {{ $invoice->issued_at->isoFormat('D MMMM Y') }}</div>
                        @else
                            <div class="ttd-kota">&nbsp;</div>
                        @endif
                        <div class="ttd-label">{{ $roleTitle }}</div>
                        @if ($sig)
                            <div class="ttd-sign">
                                <img src="{{ $sig->signature_url }}" alt="Tanda tangan {{ $sig->signer_name }}">
                            </div>
                            <div class="ttd-garis">( {{ $sig->signer_name }} )</div>
                            <div class="ttd-meta">Ditandatangani {{ $sig->signed_at?->format('j F Y H:i') }}</div>
                        @else
                            <div class="ttd-space"></div>
                            <div class="ttd-garis">( {{ $role === \App\Models\InvoiceSignature::ROLE_PELANGGAN ? '_________________________' : 'PT Jaya Royal Bersama' }} )</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        window.onload = function() { setTimeout(function() { window.print(); }, 500); };
        window.onafterprint = function() { window.close(); };
    </script>
</body>
</html>