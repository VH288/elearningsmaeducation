<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;
    protected $fillable = [
        'day',
        'session',
        'schedule_id',
        'teacher_id',
        'subject_id',
    ];
}
