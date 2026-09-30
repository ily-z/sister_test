<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    // Mengizinkan semua kolom diisi (mass assignment)
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'foto_ktp' => 'array',
        'foto_penyewa' => 'array',
    ];

    // Relasi ke tabel booking_cars
    public function bookingCars()
    {
        return $this->hasMany(BookingCar::class, 'booking_id');
    }

    // Relasi ke tabel booking_addons
    public function addons()
    {
        return $this->hasMany(BookingAddon::class, 'booking_id');
    }

    // Relasi ke tabel transactions (polymorphic via referensi_type / referensi_id)
    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'referensi');
    }
}