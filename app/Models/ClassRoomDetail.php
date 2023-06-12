<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoomDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'class_room_id',
        'student_id',
    ];
}
