<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'start_date',
        'birth_date',
        'address',
        'last_education',
        'institute_name',
        'phone_number',
        'nik',
        'photo',
        'teacher_position_id',
        'user_id'
    ];
}
