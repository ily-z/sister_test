<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'icon',
        'color',
        'title',
        'description',
        'user',
        'subject_type',
        'subject_id',
    ];

    public function userRelation()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
