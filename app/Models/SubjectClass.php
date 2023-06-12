<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectClass extends Model
{
    use HasFactory;
    protected $fillable = [
        'subject_id',
        'class_level_id',
    ];
}
