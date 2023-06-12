<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreSubCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'short_name',
        'score_category_id',
        'score_id',
        'task_material_id'
    ];
}
