<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HodAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'hod_id',
        'division_id',
    ];

    public function hod()
    {
        return $this->belongsTo(User::class, 'hod_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}

