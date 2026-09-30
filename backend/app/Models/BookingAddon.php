<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingAddon extends Model
{
    use HasFactory;

    // Matikan fitur otomatis timestamps Laravel untuk tabel ini
    public $timestamps = false; 

    protected $guarded = ['id'];

    // Relasi balik ke Booking
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}