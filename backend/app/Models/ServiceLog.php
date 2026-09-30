<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id', 'nama_bengkel', 'foto_nota', 'catatan',
        'km_saat_servis', 'tanggal_masuk', 'tanggal_keluar', 'status_bengkel',
    ];

    /**
     * Relasi: Satu catatan servis memiliki banyak detail (part yang diganti)
     */
    public function serviceLogDetails()
    {
        return $this->hasMany(ServiceLogDetail::class);
    }

    /**
     * Relasi: Catatan servis ini milik mobil siapa
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Alias Relasi: Untuk dipanggil di tampilan blade (bengkel.blade.php)
     */
    public function details()
    {
        return $this->hasMany(ServiceLogDetail::class, 'service_log_id');
    }
}