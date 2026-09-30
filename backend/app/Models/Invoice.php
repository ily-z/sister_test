<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'surat_id',
        'service_log_id',
        'jenis_invoice',
        'nomor_seri',
        'kode_mobil',
        'qr_code_string',
        'file_pdf_path',
        'pdf_generated_at',
        'document_hash',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function surat()
    {
        return $this->belongsTo(Surat::class);
    }

    public function serviceLog()
    {
        return $this->belongsTo(ServiceLog::class);
    }

    public function verification()
    {
        return $this->hasOne(InvoiceVerification::class);
    }

    public function signatures()
    {
        return $this->hasMany(InvoiceSignature::class);
    }

    /**
     * Ambil tanda tangan untuk satu blok tanda tangan.
     */
    public function signatureFor(string $role): ?InvoiceSignature
    {
        if ($this->relationLoaded('signatures')) {
            return $this->signatures->firstWhere('role', $role) ?: null;
        }

        return $this->signatures()->where('role', $role)->first();
    }

    /**
     * True hanya bila kedua blok tanda tangan (pelanggan + finance) sudah terisi.
     * Ini yang menjadi syarat invoice boleh diunduh.
     */
    public function isFullySigned(): bool
    {
        if ($this->relationLoaded('signatures')) {
            $roles = $this->signatures->pluck('role')->all();
        } else {
            $roles = $this->signatures()->pluck('role')->all();
        }

        return count(array_intersect(InvoiceSignature::ROLES, $roles)) === count(InvoiceSignature::ROLES);
    }

    /**
     * Blok tanda tangan yang belum ditandatangani.
     */
    public function missingSignatureRoles(): array
    {
        $signed = $this->relationLoaded('signatures')
            ? $this->signatures->pluck('role')->all()
            : $this->signatures()->pluck('role')->all();

        return array_values(array_diff(InvoiceSignature::ROLES, $signed));
    }

    /**
     * Label cetak untuk blok tanda tangan, menyesuaikan jenis dokumen.
     */
    public function signatureTitles(): array
    {
        return $this->jenis_invoice === 'BNG'
            ? InvoiceSignature::TITLES_BENGKEL
            : InvoiceSignature::TITLES;
    }
}
