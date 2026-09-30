<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balik Nama - {{ $surat->nomor_surat }}</title>
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
        .title { text-align: center; font-size: 13pt; font-weight: 900; letter-spacing: 2px; color: #1e3a5f; text-transform: uppercase; margin-bottom: 4px; }
        .subtitle { text-align: center; font-size: 8pt; color: #475569; margin-bottom: 10px; font-style: italic; }
        .info-box { border: 1px solid #cbd5e1; margin-bottom: 8px; }
        .info-box-header { background: #1e3a5f; color: white; font-size: 6.5pt; font-weight: 700; letter-spacing: 0.5px; padding: 2px 8px; text-transform: uppercase; }
        .info-table { width: 100%; font-size: 7pt; color: #334155; padding: 3px 8px; border-collapse: collapse; }
        .info-table td { padding: 1.5px 4px; vertical-align: top; }
        .info-table td:first-child { color: #64748b; width: 30%; white-space: nowrap; }
        .pihak-wrap { display: flex; gap: 8px; margin-bottom: 8px; }
        .pihak-box { flex: 1; border: 1px solid #cbd5e1; }
        .pihak-label { background: #1e3a5f; color: white; font-size: 6.5pt; font-weight: 700; letter-spacing: 0.5px; padding: 2px 8px; text-transform: uppercase; }
        .dtl-tbl { width: 100%; font-size: 7pt; color: #334155; padding: 3px 8px; }
        .dtl-tbl td { padding: 1px 4px 1px 0; vertical-align: top; }
        .dtl-key { color: #64748b; width: 30%; white-space: nowrap; }
        .section { margin-bottom: 7px; }
        .section-title { background: #1e3a5f; color: white; font-size: 6.5pt; font-weight: 700; letter-spacing: 0.8px; padding: 2px 8px; margin-bottom: 4px; text-transform: uppercase; }
        .pernyataan { font-size: 7.5pt; color: #334155; line-height: 1.6; padding: 6px 8px; margin-bottom: 8px; border: 1px solid #cbd5e1; }
        .pernyataan p { margin: 4px 0; text-align: justify; }
        .ttd-wrap { display: flex; justify-content: space-around; text-align: center; margin-top: 12px; }
        .ttd-block { text-align: center; }
        .ttd-label { font-size: 7pt; font-weight: 700; color: #1e3a5f; }
        .ttd-space { height: 45px; }
        .ttd-garis { font-size: 7pt; color: #1e293b; border-top: 1px solid #334155; padding-top: 2px; min-width: 120px; }
        .ttd-kota { font-size: 7pt; color: #334155; margin-bottom: 1px; }
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
                <div class="header-doc-value">{{ $surat->nomor_surat }}</div>
                <div class="header-doc-date">{{ now()->isoFormat('D MMMM Y') }}</div>
            </div>
        </div>
        <div class="divider"></div>

        <div class="title">BERITA ACARA SERAH TERIMA</div>
        <div class="subtitle">BALIK NAMA KEPEMILIKAN KENDARAAN</div>

        <div class="pihak-wrap">
            <div class="pihak-box">
                <div class="pihak-label">PIHAK PERTAMA (PEMBERI)</div>
                <table class="dtl-tbl">
                    <tr><td class="dtl-key">Nama</td><td>: {{ $pihakPertama ?? '_________________________' }}</td></tr>
                    <tr><td class="dtl-key">Alamat</td><td>: {{ $alamatPertama ?? '_________________________' }}</td></tr>
                    <tr><td class="dtl-key">Telepon</td><td>: {{ $teleponPertama ?? '_________________________' }}</td></tr>
                </table>
            </div>
            <div class="pihak-box">
                <div class="pihak-label">PIHAK KEDUA (PENERIMA)</div>
                <table class="dtl-tbl">
                    <tr><td class="dtl-key">Nama</td><td>: <strong>PT Jaya Royal Bersama</strong></td></tr>
                    <tr><td class="dtl-key">Alamat</td><td>: Jl. Teuku Umar No. 18B, Bangkalan</td></tr>
                    <tr><td class="dtl-key">Telepon</td><td>: —</td></tr>
                </table>
            </div>
        </div>

        <div class="info-box">
            <div class="info-box-header">DATA KENDARAAN</div>
            <table class="info-table">
                <tr><td>Merk / Model</td><td>: <strong>{{ $car->merk }} {{ $car->model }}</strong></td><td>Plat Nomor</td><td>: <strong>{{ $car->plat_nomor }}</strong></td></tr>
                <tr><td>No. Mesin</td><td>: {{ $car->no_mesin ?? '—' }}</td><td>No. Rangka</td><td>: {{ $car->no_rangka ?? '—' }}</td></tr>
                <tr><td>Warna</td><td>: {{ $car->warna }}</td><td>Tahun</td><td>: {{ $car->tahun }}</td></tr>
                <tr><td>Nama Pemilik STNK</td><td>: {{ $car->pemilik_stnk ?? $pihakPertama ?? '—' }}</td><td>Kapasitas</td><td>: {{ $car->kapasitas }} orang</td></tr>
            </table>
        </div>

        <div class="pernyataan">
            <p>Bahwa pada hari ini, <strong>{{ now()->isoFormat('dddd') }}</strong> tanggal <strong>{{ now()->isoFormat('D MMMM Y') }}</strong>, telah dilakukan serah terima kendaraan bermotor dari <strong>Pihak Pertama</strong> kepada <strong>PT Jaya Royal Bersama</strong> untuk keperluan kepemilihan dan operasional perusahaan.</p>
            <p>Dengan diserahkannya kendaraan tersebut, maka segala hak dan kewajiban kepemilikan kendaraan beralih kepada <strong>PT Jaya Royal Bersama</strong>.</p>
            <p>Demikian Berita Acara Serah Terima ini dibuat dengan sebenarnya dan untuk dipergunakan sebagaimana mestinya.</p>
        </div>

        <div class="section">
            <div class="section-title">KELENGKAPAN DOKUMEN</div>
            <table class="dtl-tbl" style="width:100%;padding:3px 8px;">
                <tr><td style="width:50%">✓ STNK Asli</td><td style="width:50%">✓ Faktur / Kwitansi Pembelian</td></tr>
                <tr><td>✓ BPKB Asli</td><td>✓ Surat Keterangan Fiskal</td></tr>
                <tr><td>✓ KTP Pemilik (Pihak Pertama)</td><td>✓ Form Cek Fisik</td></tr>
            </table>
        </div>

        <div class="ttd-wrap">
            <div class="ttd-block">
                <div class="ttd-kota">Bangkalan, {{ now()->isoFormat('D MMMM Y') }}</div>
                <div class="ttd-label">PIHAK PERTAMA</div>
                <div class="ttd-space"></div>
                <div class="ttd-garis">( _________________________ )</div>
            </div>
            <div class="ttd-block">
                <div class="ttd-kota">&nbsp;</div>
                <div class="ttd-label">PIHAK KEDUA</div>
                <div class="ttd-space"></div>
                <div class="ttd-garis">( PT Jaya Royal Bersama )</div>
            </div>
            <div class="ttd-block">
                <div class="ttd-kota">&nbsp;</div>
                <div class="ttd-label">MENGETAHUI</div>
                <div class="ttd-space"></div>
                <div class="ttd-garis">( _________________________ )</div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() { setTimeout(function() { window.print(); }, 500); };
        window.onafterprint = function() { window.close(); };
    </script>
</body>
</html>
