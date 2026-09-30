{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- SURAT PELEPASAN HAK KEPEMILIKAN KENDARAAN — dokumen cetak A4    --}}
{{-- Format mengikuti Pelepasan_Hak_Kendaraan.md                     --}}
{{-- Data mobil & pembeli dikirim lewat query string (belum tersimpan)--}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Pelepasan Hak Kendaraan - {{ $surat->nomor_surat }}</title>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 portrait;
            margin: 8mm 12mm 6mm 12mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            color: #1e293b;
            font-size: 8pt;
        }

        .page {
            width: 100%;
        }

        /* ── KOP ── */
        .header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .header-logo {
            width: 45px;
            height: 45px;
            background: #1e3a5f;
            color: white;
            font-weight: 900;
            font-size: 14pt;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            letter-spacing: 1px;
            flex-shrink: 0;
        }

        .header-info {
            flex: 1;
        }

        .header-company {
            font-size: 14pt;
            font-weight: 900;
            color: #1e3a5f;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .header-sub {
            font-size: 7pt;
            color: #475569;
            margin-top: 1px;
        }

        .header-addr {
            font-size: 6.5pt;
            color: #64748b;
            margin-top: 1px;
        }

        .header-doc {
            text-align: right;
            border: 1.5px solid #1e3a5f;
            padding: 4px 8px;
            min-width: 150px;
        }

        .header-doc-label {
            font-size: 6pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-doc-value {
            font-size: 7.5pt;
            font-weight: 800;
            color: #1e3a5f;
            margin-top: 1px;
        }

        .header-doc-date {
            font-size: 6.5pt;
            color: #475569;
            margin-top: 1px;
        }

        .divider {
            height: 3px;
            background: #1e3a5f;
            margin: 4px 0 10px;
        }

        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: 900;
            letter-spacing: 1.5px;
            color: #1e3a5f;
            text-transform: uppercase;
            line-height: 1.4;
            margin-bottom: 14px;
        }

        /* ── SECTION ── */
        .section {
            margin-bottom: 10px;
        }

        .section-title {
            background: #1e3a5f;
            color: white;
            font-size: 6.5pt;
            font-weight: 700;
            letter-spacing: 0.8px;
            padding: 3px 8px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .dtl {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .dtl td {
            padding: 3px 6px 3px 8px;
            vertical-align: top;
            border-bottom: 0.5px solid #e2e8f0;
        }

        .dtl .key {
            color: #64748b;
            width: 28%;
            white-space: nowrap;
        }

        /* ── PERNYATAAN ── */
        .pernyataan {
            margin: 0;
            padding-left: 16px;
            font-size: 8pt;
            line-height: 1.6;
            color: #334155;
        }

        .pernyataan li {
            margin-bottom: 4px;
        }

        /* ── PARA PIHAK ── */
        .pihak-grid {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }

        .pihak-box {
            flex: 1;
            border: 1px solid #cbd5e1;
        }

        .pihak-label {
            background: #1e3a5f;
            color: white;
            font-size: 6.5pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 3px 8px;
            text-transform: uppercase;
        }

        .pihak-box .dtl td {
            border-bottom: none;
        }

        /* ── PENUTUP ── */
        .penutup {
            font-size: 8pt;
            color: #334155;
            margin-bottom: 24px;
        }

        /* ── TANDA TANGAN ── */
        .ttd-wrap {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-top: 8px;
        }

        .ttd-block {
            text-align: center;
        }

        .ttd-kota {
            font-size: 8pt;
            color: #334155;
            margin-bottom: 2px;
        }

        .ttd-label {
            font-size: 7.5pt;
            font-weight: 700;
            color: #1e3a5f;
        }

        .ttd-space {
            height: 48px;
        }

        .ttd-garis {
            font-size: 7.5pt;
            color: #1e293b;
            border-top: 1px solid #334155;
            padding-top: 2px;
            min-width: 130px;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 28px;
            padding-top: 5px;
            border-top: 0.5px solid #e2e8f0;
            font-size: 6pt;
            color: #94a3b8;
            text-align: center;
        }

        .no-print {
            display: none;
        }

        @media screen {
            .no-print {
                display: block;
                text-align: center;
                padding: 1rem;
                font-family: sans-serif;
                background: #f1f5f9;
                border-bottom: 1px solid #e2e8f0;
            }

            .no-print button {
                padding: 0.5rem 1.5rem;
                font-size: 0.9rem;
                font-weight: 600;
                border: none;
                border-radius: 8px;
                cursor: pointer;
            }

            .no-print .btn-print {
                background: #2563eb;
                color: #fff;
                margin-right: 0.5rem;
            }

            .no-print .btn-close {
                background: #e2e8f0;
                color: #475569;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
        <button class="btn-close" onclick="window.close()">Tutup</button>
    </div>

    <div class="page">
        {{-- KOP --}}
        <div class="header">
            <div class="header-logo">JRB</div>
            <div class="header-info">
                <div class="header-company">PT JAYA ROYAL BERSAMA</div>
                <div class="header-sub">Jasa Rental &amp; Transportasi Kendaraan</div>
                <div class="header-addr">Jl. Teuku Umar No. 18B, Bangkalan, Jawa Timur</div>
            </div>
            <div class="header-doc">
                <div class="header-doc-label">No. Dokumen</div>
                <div class="header-doc-value">{{ $surat->nomor_surat }}</div>
                <div class="header-doc-date">{{ now()->isoFormat('D MMMM Y') }}</div>
            </div>
        </div>
        <div class="divider"></div>

        <div class="title">Surat Pelepasan Hak<br>Kepemilikan Kendaraan</div>

        {{-- DATA KENDARAAN --}}
        <div class="section">
            <div class="section-title">Data Kendaraan yang Dilepaskan</div>
            <table class="dtl">
                <tr>
                    <td class="key">Nomor Polisi</td>
                    <td>: <strong>{{ $fields['plat'] ?: '________________' }}</strong></td>
                </tr>
                <tr>
                    <td class="key">Merk / Type</td>
                    <td>: {{ $fields['merk'] ?: '________________' }} {{ $fields['model'] ?: '' }}</td>
                </tr>
                <tr>
                    <td class="key">Jenis / Model</td>
                    <td>: {{ $fields['jenis'] ?: '________________' }}</td>
                </tr>
                <tr>
                    <td class="key">Tahun Pembuatan</td>
                    <td>: {{ $fields['tahun'] ?: '________________' }}</td>
                </tr>
                <tr>
                    <td class="key">Isi Silinder</td>
                    <td>: {{ $fields['isi_silinder'] ? $fields['isi_silinder'] . ' CC' : '________ CC' }}</td>
                </tr>
                <tr>
                    <td class="key">Nomor Mesin</td>
                    <td>: {{ $fields['mesin'] ?: '________________' }}</td>
                </tr>
                <tr>
                    <td class="key">Nomor Rangka</td>
                    <td>: {{ $fields['rangka'] ?: '________________' }}</td>
                </tr>
                <tr>
                    <td class="key">Warna</td>
                    <td>: {{ $fields['warna'] ?: '________________' }}</td>
                </tr>
            </table>
        </div>

        {{-- PERNYATAAN --}}
        <div class="section">
            <div class="section-title">Pernyataan</div>
            <ol class="pernyataan">
                <li>Kendaraan tersebut adalah benar milik <strong>PT. Jaya Royal Bersama</strong>.</li>
                <li>Kendaraan tersebut telah dijual kepada pihak pembeli.</li>
                <li>PT. Jaya Royal Bersama (diwakili oleh <strong>Achmad Munir</strong> selaku <strong>Direktur
                        Utama</strong>) dengan ini <strong>melepaskan hak kepemilikan</strong> atas kendaraan tersebut.</li>
                <li>Tanggung jawab sepenuhnya atas kendaraan beralih kepada pihak pembeli.</li>
            </ol>
        </div>

        {{-- PARA PIHAK --}}
        <div class="pihak-grid">
            <div class="pihak-box">
                <div class="pihak-label">Pihak Pertama (Penjual)</div>
                <table class="dtl">
                    <tr>
                        <td class="key">Nama</td>
                        <td>: <strong>Achmad Munir</strong></td>
                    </tr>
                    <tr>
                        <td class="key">Jabatan</td>
                        <td>: Direktur Utama</td>
                    </tr>
                    <tr>
                        <td class="key">Perusahaan</td>
                        <td>: PT. Jaya Royal Bersama</td>
                    </tr>
                    <tr>
                        <td class="key">Alamat</td>
                        <td>: Jl. Teuku Umar No. 18-B, Kel. Kemayoran, Kec. Bangkalan, Kab. Bangkalan, Jawa Timur</td>
                    </tr>
                </table>
            </div>
            <div class="pihak-box">
                <div class="pihak-label">Pihak Kedua (Pembeli)</div>
                <table class="dtl">
                    <tr>
                        <td class="key">Nama</td>
                        <td>: <strong>{{ $fields['pembeli'] ?: '________________' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="key">Alamat</td>
                        <td>: {{ $fields['alamat_pembeli'] ?: '________________' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="penutup">
            Surat ini dibuat untuk dapat dipergunakan sebagaimana sesuai peruntukannya.
        </div>

        {{-- TANDA TANGAN --}}
        <div class="ttd-wrap">
            <div class="ttd-block">
                <div class="ttd-kota">Bangkalan, {{ now()->isoFormat('D MMMM Y') }}</div>
                <div class="ttd-label">Pihak Kedua (Pembeli)</div>
                <div class="ttd-space"></div>
                <div class="ttd-garis">( {{ $fields['pembeli'] ?: '________________' }} )</div>
            </div>
            <div class="ttd-block">
                <div class="ttd-kota">&nbsp;</div>
                <div class="ttd-label">PT JAYA ROYAL BERSAMA</div>
                <div class="ttd-kota">Achmad Munir — Direktur Utama</div>
                <div class="ttd-space"></div>
                <div class="ttd-garis">( _________________________ )</div>
            </div>
        </div>

        <div class="footer">
            PT Jaya Royal Bersama · Jl. Teuku Umar No. 18B, Bangkalan 69116, Indonesia · +62 851 0175 9149 ·
            royaltourtravelbkl@gmail.com
        </div>
    </div>

    @if (request('download'))
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    @endif
</body>
</html>
