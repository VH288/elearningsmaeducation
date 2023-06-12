<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'pet_name',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'nis',
        'photo',
        'user_id'
    ];
}
