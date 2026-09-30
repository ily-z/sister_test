<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLogDetail extends Model
{
    use HasFactory;

    protected $fillable = ['service_log_id', 'car_part_id', 'deskripsi_tindakan', 'biaya'];

    public function serviceLog()
    {
        return $this->belongsTo(ServiceLog::class);
    }

    public function carPart()
    {
        return $this->belongsTo(CarPart::class, 'car_part_id');
    }
}