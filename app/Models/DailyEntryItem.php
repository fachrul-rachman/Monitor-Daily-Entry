<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyEntryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_entry_id',
        'big_rock_id',
        'roadmap_item_id',
        'plan_title',
        'plan_text',
        'plan_relation_reason',
        'plan_duration_minutes',
        'realization_status',
        'realization_text',
        'realization_reason',
        'realization_duration_minutes',
        'realization_attachment_path',
    ];

    public function entry()
    {
        return $this->belongsTo(DailyEntry::class, 'daily_entry_id');
    }

    public function bigRock()
    {
        return $this->belongsTo(BigRock::class);
    }

    public function roadmapItem()
    {
        return $this->belongsTo(RoadmapItem::class);
    }

    public function attachments()
    {
        return $this->hasMany(DailyEntryItemAttachment::class, 'daily_entry_item_id');
    }
}
