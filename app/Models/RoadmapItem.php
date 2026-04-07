<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoadmapItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'big_rock_id',
        'title',
        'status',
        'sort_order',
    ];

    public function bigRock()
    {
        return $this->belongsTo(BigRock::class);
    }
}

