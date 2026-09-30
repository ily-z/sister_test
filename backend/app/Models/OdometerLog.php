<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OdometerLog extends Model
{
    use HasFactory;

    protected $table = 'odometer_logs';
    public $timestamps = false;

    protected $fillable = [
        'car_id',
        'km_reading',
        'created_at',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
