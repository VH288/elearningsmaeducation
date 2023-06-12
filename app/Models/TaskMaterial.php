<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskMaterial extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'distribute_date',
        'start_date',
        'deadline',
        'file_path',
        'class_room_id',
        'task_material_type_id',
        'subject_id',
        'question_bank_id',
    ];
}
