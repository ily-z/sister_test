<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OdometerLog;
use App\Models\BookingCar;
use App\Models\Booking;

class Car extends Model
{
    use HasFactory;

    protected $table = 'cars';

    // Kolom yang diizinkan untuk diisi dari form
    protected $fillable = [
        'plat_nomor', 
        'merk', 
        'model',
        'warna', 
        'tahun', 
        'kapasitas', 
        'harga_default',   // harga harian
        'harga_kontrak',   // harga kontrak  ← tambah ini
        'foto', 
        'foto_dokumen',
        'surat_id',
        'no_mesin', 
        'no_rangka', 
        'km_saat_ini', 
        'threshold_servis_km', 
        'km_servis_terakhir', 
        'status'
    ];

    public function odometerLogs()
    {
        return $this->hasMany(OdometerLog::class);
    }
    // Relasi komponen/part yang menempel di mobil ini
    public function parts()
    {
        return $this->hasMany(CarPart::class, 'car_id');
    }
    public function serviceLogs()
    {
        return $this->hasMany(ServiceLog::class);
    }

    // Surat Pelepasan Hak Kendaraan yang menyertai mobil ini
    public function surat()
    {
        return $this->belongsTo(Surat::class);
    }

    // Semua baris booking_car yang pernah pakai mobil ini (histori + aktif)
    public function bookingCars()
    {
        return $this->hasMany(BookingCar::class, 'car_id');
    }

    /**
     * BARU: Booking yang SEDANG aktif memakai mobil ini (status Menunggu/Aktif).
     * Dipakai untuk menampilkan link "Sedang dipakai di pesanan #..." di card & detail mobil.
     */
    public function activeBooking()
    {
        return $this->hasOneThrough(
            Booking::class,
            BookingCar::class,
            'car_id',      // FK di tabel booking_car yang mengarah ke cars
            'id',           // FK di tabel bookings (primary key)
            'id',           // Local key di cars
            'booking_id'    // Local key di booking_car yang mengarah ke bookings
        )->whereIn('status_rental', ['Aktif', 'Menunggu'])
         ->latest('bookings.created_at');
    }
}