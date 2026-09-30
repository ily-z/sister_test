<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarPart extends Model
{
    protected $fillable = [
        'car_id', 
        'master_part_id', 
        'nama_part', 
        'icon', 
        'is_track_km', 
        'lifespan_km', 
        'last_service_km', 
        'last_service_date'
    ];

    // Relasi balik ke mobil
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    // Relasi ke Master Part (opsional/jika ada)
    public function masterPart()
    {
        return $this->belongsTo(MasterPart::class);
    }
}