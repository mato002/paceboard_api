<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'vehicle_id', 'route_id', 'name', 'start_location', 'destination',
        'start_city', 'end_city', 'start_lat', 'start_lng', 'end_lat', 'end_lng',
        'distance', 'duration_seconds', 'average_speed', 'top_speed',
        'moving_time_seconds', 'stopped_time_seconds', 'score',
        'fuel_estimate_liters', 'weather', 'share_token', 'visibility',
        'started_at', 'ended_at', 'paused_at', 'total_paused_seconds',
        'analytics_processed_at', 'analytics_distance_applied', 'analytics_moving_seconds_applied',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'paused_at' => 'datetime',
        'analytics_processed_at' => 'datetime',
        'weather' => 'array',
        'distance' => 'float',
        'average_speed' => 'float',
        'top_speed' => 'float',
        'start_lat' => 'float',
        'start_lng' => 'float',
        'end_lat' => 'float',
        'end_lng' => 'float',
        'fuel_estimate_liters' => 'float',
        'analytics_distance_applied' => 'float',
        'duration_seconds' => 'integer',
        'moving_time_seconds' => 'integer',
        'stopped_time_seconds' => 'integer',
        'score' => 'integer',
        'total_paused_seconds' => 'integer',
        'analytics_moving_seconds_applied' => 'integer',
    ];

    public function isPaused(): bool
    {
        return $this->paused_at !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(TripLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TripComment::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TripPhoto::class);
    }

    public function telemetry(): HasMany
    {
        return $this->hasMany(VehicleTelemetry::class);
    }

    public function drivingAnalysis(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DrivingAnalysis::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(TripPoint::class);
    }
}
