<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'holiday_date',
        'name',
        'type',
        'year',
        'is_holiday',
        'is_joint_holiday',
        'is_observance',
        'source_id',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'year' => 'integer',
        'is_holiday' => 'boolean',
        'is_joint_holiday' => 'boolean',
        'is_observance' => 'boolean',
        'source_id' => 'integer',
    ];
}

