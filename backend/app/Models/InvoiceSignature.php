<?php

namespace App\Models;

use App\Support\FileUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceSignature extends Model
{
    use HasFactory;

    /** Blok tanda tangan pelanggan (atau penerima, untuk invoice bengkel). */
    public const ROLE_PELANGGAN = 'pelanggan';

    /** Blok tanda tangan perusahaan (piroman / finance). */
    public const ROLE_FINANCE = 'finance';

    /** Label yang dicetak di atas blok tanda tangan, per jenis dokumen. */
    public const TITLES = [
        self::ROLE_PELANGGAN => 'Pelanggan',
        self::ROLE_FINANCE => 'Hormat Kami,',
    ];

    /** Label untuk invoice bengkel, memakai "Penerima" alih-alih "Pelanggan". */
    public const TITLES_BENGKEL = [
        self::ROLE_PELANGGAN => 'Penerima',
        self::ROLE_FINANCE => 'Hormat Kami,',
    ];

    public const ROLES = [self::ROLE_PELANGGAN, self::ROLE_FINANCE];

    protected $fillable = [
        'invoice_id',
        'role',
        'signer_name',
        'signer_title',
        'user_id',
        'signature_path',
        'signed_at',
        'ip_address',
        'user_agent',
        'document_hash',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * URL absolut file PNG tanda tangan.
     *
     * Dulu nilai ini dibangun dengan `asset('storage/' . ...)`, yang
     * bergantung pada symlink `public/storage`. Symlink itu tidak dipakai
     * lagi: folder public backend tidak di-expose ke browser, dan symlink
     * lama menunjuk ke path laptop sehingga langsung rusak begitu dipindah
     * ke server. `asset()` juga diam-diam menghasilkan URL yang 404 karena
     * symlink-nya tidak ada.
     *
     * Sekarang semua file disajikan lewat endpoint bertanda tangan
     * (lihat App\Support\FileUrl). Nilai kosong dikembalikan sebagai string
     * kosong supaya blade dan komponen Vue yang masih memakai atribut ini
     * tidak menampilkan <img src=""> yang meminta browser kembali ke
     * halaman awal.
     */
    public function getSignatureUrlAttribute(): string
    {
        if (blank($this->signature_path)) {
            return '';
        }

        return FileUrl::public($this->signature_path) ?? '';
    }

    public function isEmpty(): bool
    {
        return empty($this->signature_path);
    }
}
