<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Car;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function referensi()
    {
        return $this->morphTo();
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
