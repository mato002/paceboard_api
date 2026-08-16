<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id', 'latitude', 'longitude', 'altitude', 'heading',
        'accuracy', 'speed', 'recorded_at'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'altitude' => 'float',
        'heading' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
