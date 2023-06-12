<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $fillable = [
        'do_date',
        'description',
        'file_path',
        'check',
        'student_score',
        'task_material_id',
        'student_id',
    ];
}
