{{--
    Tombol "Tanda Tangan" untuk sebuah invoice.
    Membuka modal signature_pad dua blok (resources/js/invoice-signature.js):
    pelanggan dan finance bisa diisi dalam satu kali buka modal.

    -- $invoice   App\Models\Invoice
    -- $role      'pelanggan' | 'finance'  (opsional: blok mana yang difokuskan dulu;
                                          default = blok yang belum ditandatangani)
    -- $compact   Tombol ikon saja (opsional, untuk di dalam tabel)
    -- $label     Teks tombol (opsional)
--}}
@php
    use App\Models\InvoiceSignature;

    $role = $role ?? ($invoice->missingSignatureRoles()[0] ?? InvoiceSignature::ROLE_PELANGGAN);
    $focusSignature = $invoice->signatureFor($role);
    $focusLabel = $invoice->signatureTitles()[$role] ?? 'Penanda tangan';

    $blocks = [];

    foreach (InvoiceSignature::ROLES as $blockRole) {
        $existing = $invoice->signatureFor($blockRole);

        // Nama yang disarankan: pelanggan dari booking, finance dari user yang login.
        $suggestedName = match ($blockRole) {
            InvoiceSignature::ROLE_FINANCE => auth()->user()?->nama ?? '',
            default => $invoice->jenis_invoice === 'BNG' ? '' : ($invoice->booking?->nama_pelanggan ?? ''),
        };

        $blocks[$blockRole] = [
            'roleLabel' => $invoice->signatureTitles()[$blockRole] ?? 'Penanda tangan',
            'signerName' => $existing?->signer_name ?: $suggestedName,
            'existingName' => $existing?->signer_name,
            'existingUrl' => $existing?->signature_url,
            'existingAt' => $existing?->signed_at?->format('d/m/Y H:i'),
        ];
    }

    $config = [
        'actionUrl' => route('admin.invoice.signature.store', $invoice->id),
        'subtitle' => $invoice->surat?->nomor_surat ?? $invoice->nomor_seri,
        'focusRole' => $role,
        'blocks' => $blocks,
    ];
@endphp

{{--
    Catatan: atribut HARUS diapit tanda kutip ganda.
    Js::from() selalu membungkus hasilnya sebagai JSON.parse('...') dengan tanda
    kutip tunggal literal (lihat Illuminate\Support\Js). Isi JSON-nya sendiri
    sudah di-escape ke \u0022 / \u0027, jadi tidak pernah memuat " atau ' mentah.
    Kalau atributnya diapit kutip tunggal, HTML parser akan menutup atributnya
    di kutip tunggal milik JSON.parse dan onclick-nya jadi tidak valid.
--}}
<button type="button" onclick="JRBSignature.open({{ Js::from($config) }})"
    @class([
        'inline-flex items-center justify-center gap-2 text-sm font-bold transition-all active:scale-95',
        'px-5 py-2.5 rounded-xl shadow-md bg-brand-600 hover:bg-brand-700 text-white' => !($compact ?? false),
        'p-2 rounded-lg shadow-none bg-transparent text-slate-500 hover:text-blue-600 hover:bg-blue-50' => ($compact ?? false),
    ])
    title="{{ $focusSignature ? 'Ganti tanda tangan ' . $focusLabel : 'Tanda tangan ' . $focusLabel }}">
    <span class="material-symbols-outlined text-[18px]">draw</span>
    @unless ($compact ?? false)
        {{ $label ?? 'TTD ' . $focusLabel }}
    @endunless
</button>
