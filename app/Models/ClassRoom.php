<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'generation',
        'status',
        'teacher_id',
        'room_id',
        'class_level_id',
    ];
}
