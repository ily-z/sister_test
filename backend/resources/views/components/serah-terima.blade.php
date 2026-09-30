{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- DOKUMEN CETAK — hanya tampil saat print, tersembunyi di layar --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
@foreach ($booking->bookingCars as $bc)
    <div class="print-page">

        {{-- ══ KOP SURAT ══ --}}
        <div class="kop">
            <div class="kop-logo">
                {{--
                LETAKKAN GAMBAR LOGO DI: public/images/logo-jrb.png
                Ganti komentar di bawah menjadi tag img jika logo sudah siap.
            --}}
                {{-- <img src="{{ asset('images/logo-jrb.png') }}" alt="Logo PT JRB" class="kop-img"> --}}
                <div class="kop-logo-placeholder">JRB</div>
            </div>
            <div class="kop-info">
                <div class="kop-perusahaan">PT JAYA ROYAL BERSAMA</div>
                <div class="kop-sub">Jasa Rental &amp; Transportasi Kendaraan</div>
                <div class="kop-alamat">Jl. Teuku Umar No. 18B, Bangkalan, Jawa Timur</div>
            </div>
            <div class="kop-no-doc">
                <div class="kop-no-label">NO. DOKUMEN</div>
                <div class="kop-no-value">
                    @if (isset($surat) && $surat)
                        {{ $surat->nomor_surat }}
                    @else
                        {{ $booking->nomor_pesanan }}-{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                    @endif
                </div>
                <div class="kop-tgl">{{ now()->isoFormat('D MMMM Y') }}</div>
            </div>
        </div>
        <div class="kop-divider-outer">
            <div class="kop-divider-inner"></div>
        </div>

        {{-- ══ JUDUL ══ --}}
        <div class="judul-wrap">
            <div class="judul-main">DELIVERY ORDER</div>
            <div class="judul-sub">BERITA ACARA SERAH TERIMA KENDARAAN</div>
        </div>

        {{-- ══ PARA PIHAK ══ --}}
        <div class="pihak-wrap">
            <div class="pihak-box">
                <div class="pihak-label">PENGIRIM / PIHAK PERTAMA</div>
                <table class="dtl-tbl">
                    <tr>
                        <td class="dtl-key">Nama</td>
                        <td>: PT Jaya Royal Bersama</td>
                    </tr>
                    <tr>
                        <td class="dtl-key">Alamat</td>
                        <td>: Jl. Teuku Umar No. 18B, Bangkalan</td>
                    </tr>
                    <tr>
                        <td class="dtl-key">Telepon</td>
                        <td>: —</td>
                    </tr>
                </table>
            </div>
            <div class="pihak-box">
                <div class="pihak-label">PENERIMA / PIHAK KEDUA</div>
                <table class="dtl-tbl">
                    <tr>
                        <td class="dtl-key">Nama</td>
                        <td>: <strong>{{ $booking->nama_pelanggan }}</strong></td>
                    </tr>
                    <tr>
                        <td class="dtl-key">Alamat</td>
                        <td>: —</td>
                    </tr>
                    <tr>
                        <td class="dtl-key">Telepon</td>
                        <td>: {{ $booking->kontak_pelanggan }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- ══ DATA KENDARAAN ══ --}}
        <div class="section">
            <div class="section-title">DATA KENDARAAN</div>
            <table class="dtl-tbl" style="width:100%">
                <tr>
                    <td class="dtl-key" style="width:18%">Merk / Model</td>
                    <td style="width:32%">: <strong>{{ $bc->car->merk }} {{ $bc->car->model }}</strong></td>
                    <td class="dtl-key" style="width:18%">Plat Nomor</td>
                    <td>: <strong>{{ $bc->car->plat_nomor }}</strong></td>
                </tr>
                <tr>
                    <td class="dtl-key">No. Mesin</td>
                    <td>: {{ $bc->car->no_mesin ?? '—' }}</td>
                    <td class="dtl-key">No. Rangka</td>
                    <td>: {{ $bc->car->no_rangka ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="dtl-key">Warna</td>
                    <td>: {{ $bc->car->warna }}</td>
                    <td class="dtl-key">Tahun</td>
                    <td>: {{ $bc->car->tahun }}</td>
                </tr>
                <tr>
                    <td class="dtl-key">Nama Pemilik</td>
                    <td>: PT Jaya Royal Bersama</td>
                    <td class="dtl-key">Kapasitas</td>
                    <td>: {{ $bc->car->kapasitas }} orang</td>
                </tr>
                <tr>
                    <td class="dtl-key">Periode Sewa</td>
                    <td colspan="3">:
                        {{ \Carbon\Carbon::parse($booking->tanggal_mulai)->isoFormat('D MMMM Y') }}
                        s/d
                        {{ \Carbon\Carbon::parse($booking->tanggal_selesai)->isoFormat('D MMMM Y') }}
                        &nbsp;({{ \Carbon\Carbon::parse($booking->tanggal_mulai)->diffInDays(\Carbon\Carbon::parse($booking->tanggal_selesai)) }}
                        hari)
                    </td>
                </tr>
            </table>
        </div>

        {{-- ══ CHECKLIST PERLENGKAPAN ══ --}}
        <div class="section">
            <div class="section-title">CEK KONDISI &amp; PERLENGKAPAN KENDARAAN</div>
            <p class="checklist-note">Beri tanda <strong>✓</strong> jika Ada atau <strong>✗</strong> jika Tidak Ada pada
                kolom yang sesuai saat serah terima.</p>
            <div class="checklist-grid">
                {{-- Kolom Perlengkapan Utama --}}
                <div>
                    <div class="cl-header">Perlengkapan Utama</div>
                    <table class="cl-tbl">
                        <thead>
                            <tr>
                                <th class="cl-th-item">Item</th>
                                <th class="cl-th-cb">Ada</th>
                                <th class="cl-th-cb">Tdk</th>
                                <th class="cl-th-ket">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['Ban Serep', 'Dongkrak', 'Kunci Roda', 'Segitiga Pengaman', 'Kotak P3K', 'STNK', 'Buku Service', 'Kunci Cadangan'] as $item)
                                <tr>
                                    <td class="cl-td-item">{{ $item }}</td>
                                    <td class="cl-td-cb"></td>
                                    <td class="cl-td-cb"></td>
                                    <td class="cl-td-ket"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Kolom Perlengkapan Tambahan --}}
                <div>
                    <div class="cl-header">Perlengkapan Tambahan</div>
                    <table class="cl-tbl">
                        <thead>
                            <tr>
                                <th class="cl-th-item">Item</th>
                                <th class="cl-th-cb">Ada</th>
                                <th class="cl-th-cb">Tdk</th>
                                <th class="cl-th-ket">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['AC (Berfungsi)', 'Radio / Audio', 'Karpet Dalam', 'Spion Lengkap', 'Wiper (Berfungsi)', 'Remote / Smart Key', 'Charger / Lighter', 'Sabuk Pengaman'] as $item)
                                <tr>
                                    <td class="cl-td-item">{{ $item }}</td>
                                    <td class="cl-td-cb"></td>
                                    <td class="cl-td-cb"></td>
                                    <td class="cl-td-ket"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ══ GAMBAR MOBIL + KETERANGAN ══ --}}
        <div class="section">
            <div class="section-title">KONDISI FISIK KENDARAAN</div>
            <p class="checklist-note">Tandai area pada gambar jika terdapat kerusakan/goresan, dan catat keterangannya
                di kolom kanan.</p>
            <div class="kondisi-wrap">
                <div class="car-img-wrap">
                    <img src="{{ asset('storage/images/car-template.jpg') }}" alt="Diagram Kendaraan" class="car-img">
                </div>

                {{-- KOLOM KETERANGAN --}}
                <div class="kondisi-ket-wrap">
                    <div class="kondisi-ket-title">Keterangan Kerusakan / Catatan Kondisi</div>

                    @if ($booking->catatan)
                        <div class="catatan-pesanan">
                            <strong>Catatan Pesanan:</strong> {{ $booking->catatan }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══ PERHATIAN ══ --}}
        <div class="perhatian-wrap">
            <div class="perhatian-title">Perhatian !!!</div>
            <ul class="perhatian-list">
                <li>Unit mobil dilengkapi dengan jaminan asuransi <em>all risk</em>.</li>
                <li>Klaim asuransi dibebankan kepada pengguna.</li>
                <li>Jika terjadi klaim asuransi yang melebihi 3×24 jam akan disediakan mobil pengganti.</li>
            </ul>
        </div>

        {{-- ══ TANDA TANGAN ══ --}}
        <div class="ttd-wrap">
            <div class="ttd-block">
                <div class="ttd-kota">Bangkalan, {{ now()->isoFormat('D MMMM Y') }}</div>
                <div class="ttd-label">Pengguna / User</div>
                <div class="ttd-space"></div>
                <div class="ttd-garis">( _________________________ )</div>
            </div>
            <div class="ttd-block">
                <div class="ttd-kota">&nbsp;</div>
                <div class="ttd-label">Penerima</div>
                <div class="ttd-space"></div>
                <div class="ttd-garis">( {{ $booking->nama_pelanggan }} )</div>
            </div>
            <div class="ttd-block">
                <div class="ttd-kota">&nbsp;</div>
                <div class="ttd-label">PT JAYA ROYAL BERSAMA</div>
                <div class="ttd-space"></div>
                <div class="ttd-garis">( _________________________ )</div>
            </div>
        </div>

        {{-- Footer --}}
        {{-- <div class="doc-footer">
            Dicetak dari Sistem Manajemen PT Jaya Royal Bersama &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}
             &nbsp;·&nbsp; Dokumen No.
            @if (isset($surat) && $surat)
                {{ $surat->nomor_surat }}
            @else
                {{ $booking->nomor_pesanan }}-{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
            @endif
        </div> --}}

    </div>
@endforeach

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- CSS PRINT                                                       --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<style>
    .print-page {
        display: none;
    }

    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-sizing: border-box;
        }

        body,
        html {
            margin: 0;
            padding: 0;
        }

        header,
        nav,
        aside,
        footer,
        .no-print,
        .sidebar {
            display: none !important;
        }

        body,
        main,
        #app {
            padding-top: 0 !important;
            margin-top: 0 !important;
            background: white !important;
        }

        [class*="max-w-"] {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        @page {
            size: A4 portrait;
            margin: 8mm 12mm 6mm 12mm;
            /* ↓ dari 12mm */
        }

        .print-page {
            display: block !important;
            width: 100%;
            font-family: 'Arial', sans-serif;
            font-size: 7.5pt;
            color: #1e293b;       
        }

        .print-page:last-child {
            break-after: avoid;
            page-break-after: avoid;
        }

        /* ── KOP ── */
        .kop {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
            /* ↓ dari 5px */
        }

        .kop-logo-placeholder {
            width: 40px;
            height: 40px;
            /* ↓ dari 44px */
            background: #1e3a5f;
            color: white;
            font-weight: 900;
            font-size: 12pt;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            letter-spacing: 1px;
            flex-shrink: 0;
        }

        .kop-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .kop-info {
            flex: 1;
        }

        .kop-perusahaan {
            font-size: 13pt;
            /* ↓ dari 14pt */
            font-weight: 900;
            color: #1e3a5f;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .kop-sub {
            font-size: 7pt;
            color: #475569;
            margin-top: 1px;
        }

        .kop-alamat {
            font-size: 6.5pt;
            color: #64748b;
            margin-top: 1px;
        }

        .kop-no-doc {
            text-align: right;
            border: 1.5px solid #1e3a5f;
            padding: 4px 8px;
            /* ↓ dari 5px 10px */
            min-width: 120px;
        }

        .kop-no-label {
            font-size: 6pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kop-no-value {
            font-size: 8.5pt;
            font-weight: 800;
            color: #1e3a5f;
            margin-top: 1px;
        }

        .kop-tgl {
            font-size: 6.5pt;
            color: #475569;
            margin-top: 1px;
        }

        .kop-divider-outer {
            margin: 4px 0 6px;
        }

        /* ↓ dari 5px 0 8px */
        .kop-divider-inner {
            height: 3px;
            background: #1e3a5f;
        }

        /* ── JUDUL ── */
        .judul-wrap {
            text-align: center;
            margin-bottom: 7px;
        }

        /* ↓ dari 10px */
        .judul-main {
            font-size: 13pt;
            font-weight: 900;
            letter-spacing: 2px;
            color: #1e3a5f;
            text-transform: uppercase;
        }

        .judul-sub {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 1px;
            letter-spacing: 0.5px;
        }

        /* ── PARA PIHAK ── */
        .pihak-wrap {
            display: flex;
            gap: 8px;
            margin-bottom: 7px;
        }

        /* ↓ dari 9px */
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
            padding: 2px 8px;
            /* ↓ dari 3px */
            text-transform: uppercase;
        }

        .dtl-tbl {
            width: 100%;
            font-size: 7pt;
            color: #334155;
            padding: 3px 8px;
        }

        /* ↓ font & padding */
        .dtl-tbl tr td {
            padding: 1px 4px 1px 0;
            vertical-align: top;
        }

        .dtl-key {
            color: #64748b;
            width: 30%;
            white-space: nowrap;
        }

        /* ── SECTION ── */
        .section {
            margin-bottom: 7px;
        }

        /* ↓ dari 9px */

        .section-title {
            background: #1e3a5f;
            color: white;
            font-size: 6.5pt;
            font-weight: 700;
            letter-spacing: 0.8px;
            padding: 2px 8px;
            /* ↓ dari 3px */
            margin-bottom: 4px;
            /* ↓ dari 5px */
            text-transform: uppercase;
        }

        /* ── CHECKLIST ── */
        .checklist-note {
            font-size: 6.5pt;
            color: #64748b;
            font-style: italic;
            margin-bottom: 4px;
        }

        .checklist-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .cl-header {
            font-size: 7pt;
            font-weight: 700;
            color: #1e3a5f;
            border-bottom: 1px solid #1e3a5f;
            padding-bottom: 2px;
            margin-bottom: 2px;
        }

        .cl-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }

        .cl-th-item {
            text-align: left;
            font-size: 6.5pt;
            color: #64748b;
            padding: 1.5px 4px;
            border-bottom: 1px solid #e2e8f0;
            width: 52%;
        }

        .cl-th-cb {
            text-align: center;
            font-size: 6.5pt;
            color: #64748b;
            padding: 1.5px 2px;
            border-bottom: 1px solid #e2e8f0;
            width: 14%;
        }

        .cl-th-ket {
            text-align: left;
            font-size: 6.5pt;
            color: #64748b;
            padding: 1.5px 4px;
            border-bottom: 1px solid #e2e8f0;
        }

        .cl-td-item {
            padding: 2px 4px;
            font-size: 7pt;
            border-bottom: 0.5px solid #f1f5f9;
        }

        /* ↓ dari 3px */
        .cl-td-cb {
            border: 1px solid #94a3b8 !important;
            text-align: center;
            width: 16px;
            height: 14px;
        }

        /* ↓ dari 18/16 */
        .cl-td-ket {
            border-bottom: 0.5px solid #94a3b8 !important;
            min-width: 30px;
        }

        /* ── KONDISI FISIK ── */
        .kondisi-wrap {
            display: flex;
            gap: 8px;
            align-items: stretch;
        }

        .car-img-wrap {
            flex: 0 40%;
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        /* ↓ dari 6px */
        .car-img {
            width: 100%;
            display: block;
        }

        .kondisi-ket-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .kondisi-ket-title {
            font-size: 7pt;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 4px;
            border-bottom: 1px solid #1e3a5f;
            padding-bottom: 2px;
        }

        .ket-line {
            border-bottom: 0.7px solid #94a3b8;
            height: 15px;
            margin-bottom: 1px;
        }

        .catatan-pesanan {
            margin-top: auto;
            font-size: 7pt;
            color: #334155;
            padding: 3px 6px;
            background: #fef9c3;
            border-left: 3px solid #eab308;
        }

        /* ── PERHATIAN ── */
        .perhatian-wrap {
            border: 1px solid #cbd5e1;
            padding: 2px 8px; /* ↓ dari 3px */
            margin-bottom: 4px; /* ↓ dari 6px */
            font-size: 7pt;
        }


        .perhatian-title {
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 1px;
        }

        .perhatian-list {
            margin: 0;
            padding-left: 12px;
            color: #334155;
        }

        .perhatian-list li {
            margin-bottom: 0; /* ↓ dari 0.5px */
            line-height: 1.3;
        }

        /* ↓ dari 1px */

        /* ── TANDA TANGAN ── */
        .ttd-wrap {
            display: flex;
            justify-content: space-around;
            margin-top: 6px;
            /* ↓ dari 8px */
            margin-bottom: 4px;
            /* ↓ dari 6px */
        }

        .ttd-block {
            text-align: center;
        }

        .ttd-kota {
            font-size: 7pt;
            color: #334155;
            margin-bottom: 1px;
        }

        .ttd-label {
            font-size: 7pt;
            font-weight: 700;
            color: #1e3a5f;
        }

        .ttd-space {
            height: 36px;
        }

        /* ↓ dari 48px — ini yang paling banyak makan ruang */

        .ttd-garis {
            font-size: 7.5pt;
            color: #1e293b;
            border-top: 1px solid #334155;
            padding-top: 2px;
            min-width: 120px;
            /* ↓ dari 130px */
        }

        /* ── FOOTER ── */
        .doc-footer {
            font-size: 6pt;
            color: #94a3b8;
            text-align: center;
            margin-top: 4px;
            /* ↓ dari 6px */
            padding-top: 4px;
            /* ↓ dari 5px */
            border-top: 0.5px solid #e2e8f0;
        }
    }
</style>
