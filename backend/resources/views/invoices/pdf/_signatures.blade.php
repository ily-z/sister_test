{{--
    Blok tanda tangan untuk PDF invoice.

    $signatureBlocks[$role] berisi:
      data_url  -> PNG tanda tangan (data URL) atau null
      name      -> nama penanda tangan
      signed_at -> waktu penandatanganan, format untuk dicetak

    Kalau blok tersebut sudah ditandatangani, gambar PNG hasil signature_pad
    ditampilkan di atas garis, diikuti nama dan waktu penadatangan. Kalau belum,
    yang tampil hanya garis kosong seperti dokumen cetak biasa.
--}}
@php
    // Guard typo: pastikan setiap role punya entry, walau tidak ada tanda tangan.
    $signatureBlocks = $signatureBlocks ?? [];
@endphp
<table class="footer-section" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width:80px; vertical-align:bottom; text-align:center;">
            @if (!empty($qrDataUrl))
                <img src="{{ $qrDataUrl }}" width="66" height="66" alt="QR">
                <div class="qr-label">Scan untuk Verifikasi</div>
            @endif
        </td>
        <td style="vertical-align:bottom;">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    @foreach (\App\Models\InvoiceSignature::ROLES as $role)
                        @php
                            $block = $signatureBlocks[$role] ?? ['data_url' => null, 'name' => null, 'signed_at' => null];
                            $isSigned = !empty($block['data_url']);
                        @endphp
                        <td width="50%" style="text-align:center; vertical-align:bottom; padding:0 4px;">
                            @if ($role === \App\Models\InvoiceSignature::ROLE_PELANGGAN)
                                <div class="ttd-kota">Bangkalan, {{ $invoice->issued_at?->isoFormat('D MMMM Y') }}</div>
                            @else
                                <div class="ttd-kota">&nbsp;</div>
                            @endif

                            <div class="ttd-label">{{ $titles[$role] }}</div>

                            @if ($isSigned)
                                <div class="ttd-sign">
                                    <img src="{{ $block['data_url'] }}" alt="ttd {{ $role }}">
                                </div>
                                <div class="ttd-garis">( {{ $block['name'] }} )</div>
                                <div class="ttd-meta">Ditandatangani {{ $block['signed_at'] }}</div>
                            @else
                                <div class="ttd-space"></div>
                                <div class="ttd-garis">( ...................................... )</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>
</table>
