<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'score_date',
        'scope_type',
        'scope_id',
        'score',
        'components',
    ];

    protected $casts = [
        'score_date' => 'date',
        'components' => 'array',
    ];
}

