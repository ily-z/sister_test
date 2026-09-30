{{--
    Header dokumen untuk PDF invoice (dompdf).
    WAJIB table-based: dompdf tidak mendukung flexbox/grid, jadi layout
    kop surat disusun dengan tabel agar posisinya konsisten.
--}}
<table class="doc-header" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width:52px;">
            <div class="header-logo">JRB</div>
        </td>
        <td style="vertical-align:middle; padding-right:8px;">
            <div class="header-company">PT JAYA ROYAL BERSAMA</div>
            <div class="header-sub">Jasa Rental &amp; Transportasi Kendaraan</div>
            <div class="header-addr">Jl. Teuku Umar No. 18B, Bangkalan, Jawa Timur</div>
        </td>
        <td style="width:150px;">
            <table class="header-doc" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="header-doc-label">No. Dokumen</td>
                </tr>
                <tr>
                    <td class="header-doc-value">{{ $invoice->surat->nomor_surat ?? $invoice->nomor_seri }}</td>
                </tr>
                <tr>
                    <td class="header-doc-date">{{ $invoice->issued_at?->isoFormat('D MMMM Y') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<div class="divider"></div>
