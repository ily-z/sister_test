<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPayment extends Model
{
    public $timestamps = false;

    protected $table = 'booking_payments';

    protected $fillable = [
        'booking_id',
        'jumlah',
        'status_sebelum',
        'status_sesudah',
        'metode_bayar',
        'catatan',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'jumlah'     => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}