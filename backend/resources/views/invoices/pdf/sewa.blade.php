<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $invoice->surat->nomor_surat ?? $invoice->nomor_seri }}</title>
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
        .note-box { border: 1px solid #cbd5e1; padding: 6px 8px; margin-bottom: 8px; font-size: 7pt; }

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

        .sign-notice { font-size: 6pt; color: #64748b; text-align: center; margin-top: 6px; font-style: italic; }
    </style>
</head>
<body>

@include('invoices.pdf._header', ['invoice' => $invoice])

<div class="title">INVOICE</div>
<div class="subtitle">{{ $jenisText }}</div>

@if ($booking)
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:8px;">
        <tr>
            <td width="50%" style="padding-right:4px; vertical-align:top;">
                <div class="info-box">
                    <div class="info-box-header">Pelanggan</div>
                    <table class="info-table">
                        <tr><td class="info-label">Nama</td><td class="info-value">: <strong>{{ $booking->nama_pelanggan }}</strong></td></tr>
                        <tr><td class="info-label">Telepon</td><td class="info-value">: {{ $booking->kontak_pelanggan }}</td></tr>
                        @if ($booking->email)
                            <tr><td class="info-label">Email</td><td class="info-value">: {{ $booking->email }}</td></tr>
                        @endif
                        <tr><td class="info-label">No. Pesanan</td><td class="info-value">: {{ $booking->nomor_pesanan }}</td></tr>
                    </table>
                </div>
            </td>
            <td width="50%" style="padding-left:4px; vertical-align:top;">
                <div class="info-box">
                    <div class="info-box-header">Jadwal Sewa</div>
                    <table class="info-table">
                        <tr><td class="info-label">Tipe Sewa</td><td class="info-value">: {{ ucfirst($booking->tipe_sewa) }}</td></tr>
                        <tr><td class="info-label">Mulai</td><td class="info-value">: {{ \Carbon\Carbon::parse($booking->tanggal_mulai)->isoFormat('D MMMM Y') }}</td></tr>
                        <tr><td class="info-label">Selesai</td><td class="info-value">: {{ \Carbon\Carbon::parse($booking->tanggal_selesai)->isoFormat('D MMMM Y') }}</td></tr>
                        <tr><td class="info-label">Durasi</td><td class="info-value">: {{ $durasi }} hari</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
@endif

@if ($invoice->jenis_invoice === 'AK')
    <div class="note-box">
        <strong>Keterangan:</strong> Invoice ini merupakan tagihan atas biaya perbaikan kerusakan kendaraan selama masa sewa.
    </div>
@endif

<table class="items-table">
    <thead>
        <tr>
            <th style="width:12%">Kode</th>
            <th style="width:38%">Keterangan</th>
            <th style="width:18%" class="tr">Harga</th>
            <th style="width:12%" class="tc">Qty</th>
            <th style="width:20%" class="tr">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['kode'] }}</td>
                <td>{{ $row['keterangan'] }}</td>
                <td class="tr">Rp {{ number_format($row['harga'], 0, ',', '.') }}</td>
                <td class="tc">{{ $row['qty'] }}</td>
                <td class="tr">Rp {{ number_format($row['subtotal'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="empty-row">Tidak ada item invoice</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="total-section">
    <div>
        <span class="total-label">Subtotal Kendaraan:</span>
        &nbsp;<span class="total-value">Rp {{ number_format($subtotalKendaraan, 0, ',', '.') }}</span>
    </div>
    @if ($totalAddons > 0)
        <div>
            <span class="total-label">Biaya Tambahan:</span>
            &nbsp;<span class="total-value">Rp {{ number_format($totalAddons, 0, ',', '.') }}</span>
        </div>
    @endif
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
