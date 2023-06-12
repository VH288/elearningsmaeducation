<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreWeight extends Model
{
    use HasFactory;
    protected $fillable = [
        'weight',
        'score_category_id',
        'score_id',
    ];
}
