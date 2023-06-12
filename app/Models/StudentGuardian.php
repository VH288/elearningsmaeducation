<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentGuardian extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'occupation',
        'birth_date',
        'phone_number',
        'student_id',
        'guardian_type_id'
    ];
}
