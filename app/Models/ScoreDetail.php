<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'score',
        'student_score_id',
        'score_sub_category_id',
    ];
}
