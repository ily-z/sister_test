<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surat extends Model
{
    use HasFactory;

    protected $table = 'surat';

    protected $fillable = [
        'jenis_dokumen',
        'kode',
        'subkategori',
        'perusahaan',
        'perusahaan_singkat',
        'no_urut',
        'bulan',
        'tahun',
        'nomor_surat',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function car()
    {
        return $this->hasOne(Car::class);
    }
}
