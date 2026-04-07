<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entry_date',
        'plan_text',
        'plan_status',
        'plan_submitted_at',
        'realization_text',
        'realization_status',
        'realization_submitted_at',
        'plan_title',
        'plan_relation_reason',
        'realization_notes',
        'realization_reason',
        'big_rock_id',
        'roadmap_item_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'plan_submitted_at' => 'datetime',
        'realization_submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bigRock()
    {
        return $this->belongsTo(BigRock::class);
    }

    public function roadmapItem()
    {
        return $this->belongsTo(RoadmapItem::class);
    }

    public function items()
    {
        return $this->hasMany(DailyEntryItem::class, 'daily_entry_id');
    }
}
