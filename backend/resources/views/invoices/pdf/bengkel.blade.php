<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice Bengkel - {{ $invoice->surat->nomor_surat ?? $invoice->nomor_seri }}</title>
    <style>
        /*
         * Template PDF untuk dompdf.
         * Aturan: JANGAN pakai flexbox/grid — dompdf tidak merendernya.
         * Susun layout dengan <table> dan position:absolute.
         */
        @page { margin: 10mm 12mm 8mm 12mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b; font-size: 8pt; line-height: 1.35;
        }

        /* Header */
        .header-logo {
            width: 45px; height: 45px; line-height: 45px; text-align: center;
            background: #1e3a5f; color: #ffffff; font-weight: bold; font-size: 14pt;
            border-radius: 5px; letter-spacing: 1px;
        }
        .header-company { font-size: 13pt; font-weight: bold; color: #1e3a5f; letter-spacing: 0.5px; }
        .header-sub { font-size: 7pt; color: #475569; }
        .header-addr { font-size: 6.5pt; color: #64748b; }
        .header-doc { border: 1.2pt solid #1e3a5f; padding: 4px 8px; }
        .header-doc-label { font-size: 6pt; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-doc-value { font-size: 7.5pt; font-weight: bold; color: #1e3a5f; }
        .header-doc-date { font-size: 6.5pt; color: #475569; }
        .divider { height: 3px; background: #1e3a5f; margin: 5px 0 8px; }

        /* Judul */
        .title { text-align: center; font-size: 14pt; font-weight: bold; letter-spacing: 2px; color: #1e3a5f; text-transform: uppercase; }
        .subtitle { text-align: center; font-size: 7.5pt; font-weight: bold; color: #475569; margin: 3px 0 8px; }

        /* Info box */
        .info-box { border: 1px solid #cbd5e1; }
        .info-box-header { background: #1e3a5f; color: #ffffff; font-size: 6.5pt; font-weight: bold; letter-spacing: 0.5px; padding: 2px 8px; text-transform: uppercase; }
        .info-table { width: 100%; font-size: 7pt; color: #334155; border-collapse: collapse; }
        .info-table td { padding: 1.5px 4px; vertical-align: top; }
        .info-label { color: #64748b; width: 34%; }
        .info-value { width: 66%; }
        .status-selesai { background: #d1fae5; color: #065f46; }
        .status-proses { background: #fef9c3; color: #854d0e; }

        /* Tabel item */
        .items-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
        .items-table th { background: #1e3a5f; color: #ffffff; font-size: 6.5pt; font-weight: bold; padding: 3px 8px; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-table td { padding: 3px 8px; border-bottom: 0.5px solid #e2e8f0; }
        .items-table .tr { text-align: right; }
        .items-table .tc { text-align: center; }
        .empty-row { text-align: center; color: #94a3b8; font-style: italic; }

        /* Total */
        .total-section { border-top: 2px solid #1e3a5f; margin-top: 4px; padding-top: 4px; text-align: right; font-size: 8pt; }
        .total-label { font-weight: bold; color: #475569; }
        .total-value { font-weight: bold; }
        .grand-total { font-size: 11pt; font-weight: bold; color: #1e3a5f; border-top: 1px solid #1e3a5f; padding-top: 4px; margin-top: 2px; }
        .terbilang { font-size: 6.5pt; color: #475569; font-style: italic; margin-top: 4px; padding-top: 4px; border-top: 0.5px dashed #cbd5e1; }

        /* Footer / tanda tangan */
        .footer-section { margin-top: 14px; }
        .qr-label { font-size: 5.5pt; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        .ttd-kota { font-size: 7pt; color: #334155; }
        .ttd-label { font-size: 7pt; font-weight: bold; color: #1e3a5f; margin-top: 2px; }
        .ttd-space { height: 38px; }
        .ttd-sign { height: 40px; padding-top: 4px; }
        .ttd-sign img { max-height: 36px; max-width: 150px; }
        .ttd-garis { font-size: 7pt; color: #1e293b; border-top: 1px solid #334155; padding-top: 2px; }
        .ttd-meta { font-size: 5.5pt; color: #64748b; margin-top: 1px; }
    </style>
</head>
<body>

@include('invoices.pdf._header', ['invoice' => $invoice])

<div class="title">INVOICE BENGKEL</div>
<div class="subtitle">PERAWATAN &amp; PERBAIKAN KENDARAAN</div>

<table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:8px;">
    <tr>
        <td width="50%" style="padding-right:4px; vertical-align:top;">
            <div class="info-box">
                <div class="info-box-header">Data Kendaraan</div>
                <table class="info-table">
                    <tr><td class="info-label">Merk/Model</td><td class="info-value">: <strong>{{ $serviceLog->car->merk }} {{ $serviceLog->car->model }}</strong></td></tr>
                    <tr><td class="info-label">Plat Nomor</td><td class="info-value">: {{ $serviceLog->car->plat_nomor }}</td></tr>
                    <tr><td class="info-label">Kode Mobil</td><td class="info-value">: {{ $invoice->kode_mobil ?? '-' }}</td></tr>
                    <tr><td class="info-label">KM Saat Ini</td><td class="info-value">: {{ number_format($serviceLog->car->km_saat_ini ?? 0, 0, ',', '.') }} km</td></tr>
                </table>
            </div>
        </td>
        <td width="50%" style="padding-left:4px; vertical-align:top;">
            <div class="info-box">
                <div class="info-box-header">Detail Servis</div>
                <table class="info-table">
                    <tr><td class="info-label">Tanggal Masuk</td><td class="info-value">: {{ \Carbon\Carbon::parse($serviceLog->tanggal_masuk)->isoFormat('D MMMM Y') }}</td></tr>
                    <tr><td class="info-label">Tanggal Keluar</td><td class="info-value">: {{ $serviceLog->tanggal_keluar ? \Carbon\Carbon::parse($serviceLog->tanggal_keluar)->isoFormat('D MMMM Y') : '-' }}</td></tr>
                    <tr>
                        <td class="info-label">Status</td>
                        <td class="info-value">:
                            <span style="font-size:6.5pt; padding:1px 5px; border-radius:3px; {{ $serviceLog->status_bengkel === 'closed' ? 'background:#d1fae5; color:#065f46;' : 'background:#fef9c3; color:#854d0e;' }}">
                                {{ $serviceLog->status_bengkel === 'closed' ? 'Selesai' : 'Proses' }}
                            </span>
                        </td>
                    </tr>
                    <tr><td class="info-label">Bengkel</td><td class="info-value">: {{ $serviceLog->nama_bengkel ?? '-' }}</td></tr>
                    @if ($serviceLog->catatan)
                        <tr><td class="info-label">Catatan</td><td class="info-value">: {{ $serviceLog->catatan }}</td></tr>
                    @endif
                </table>
            </div>
        </td>
    </tr>
</table>

<table class="items-table">
    <thead>
        <tr>
            <th style="width:8%">No</th>
            <th style="width:60%">Tindakan / Jasa Servis</th>
            <th style="width:32%" class="tr">Biaya</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($serviceLog->details as $detail)
            <tr>
                <td class="tc">{{ $loop->iteration }}</td>
                <td>{{ $detail->deskripsi_tindakan ?? '-' }}</td>
                <td class="tr">Rp {{ number_format($detail->biaya ?? 0, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="empty-row">Tidak ada detail servis</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="total-section">
    <div class="grand-total">
        <span class="total-label">TOTAL:</span>
        &nbsp;<span class="total-value">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
    </div>
</div>

<div class="terbilang">Terbilang: {{ $terbilangText }} rupiah</div>

@include('invoices.pdf._signatures', [
    'invoice' => $invoice,
    'signatureBlocks' => $signatureBlocks,
    'titles' => $titles,
    'qrDataUrl' => $qrDataUrl,
])

</body>
</html>
