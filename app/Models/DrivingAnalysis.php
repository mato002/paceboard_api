<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrivingAnalysis extends Model
{
    protected $fillable = [
        'trip_id', 'safety_score', 'eco_score', 'smoothness_score',
        'consistency_score', 'harsh_braking_count', 'harsh_acceleration_count',
        'speeding_events', 'insights',
    ];

    protected $casts = [
        'insights' => 'array',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
