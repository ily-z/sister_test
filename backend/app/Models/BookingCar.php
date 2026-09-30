<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingCar extends Model
{
    use HasFactory;

    // 1. Beri tahu Laravel nama tabel yang benar di database kamu
    protected $table = 'booking_car'; 
    
    // 2. Nonaktifkan timestamps karena di tabel SQL 'booking_car' kamu tidak ada kolom created_at & updated_at
    public $timestamps = false; 

    protected $guarded = ['id'];

    // 👇 TAMBAHKAN BLOK INI
    protected $casts = [
        'foto_kondisi' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }
}