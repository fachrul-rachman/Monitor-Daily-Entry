<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Finding extends Model
{
    use HasFactory;

    protected $fillable = [
        'finding_date',
        'scope_type',
        'scope_id',
        'user_id',
        'division_id',
        'type',
        'severity',
        'title',
        'description',
        'meta',
    ];

    protected $casts = [
        'finding_date' => 'date',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}

