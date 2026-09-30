<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPart extends Model
{
    protected $fillable = [
        'nama_part', 
        'kategori', 
        'icon', 
        'is_track_km', 
        'default_lifespan_km', 
        'deskripsi'
    ];
}