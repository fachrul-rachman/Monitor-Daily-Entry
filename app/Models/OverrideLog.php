<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverrideLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_user_id',
        'target_type',
        'target_id',
        'context_date',
        'reason',
        'changes',
    ];

    protected $casts = [
        'context_date' => 'date',
        'changes' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

