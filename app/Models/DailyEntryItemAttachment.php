<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyEntryItemAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_entry_item_id',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    public function item()
    {
        return $this->belongsTo(DailyEntryItem::class, 'daily_entry_item_id');
    }
}

