<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'type',
        'context_date',
        'status',
        'summary',
        'payload',
        'error_message',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'context_date' => 'date',
        'payload' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}

