<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentScore extends Model
{
    use HasFactory;
    protected $fillable = [
        'average',
        'description',
        'score_id',
        'student_id',
    ];
}
