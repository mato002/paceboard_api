<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'manufacturer', 'model', 'nickname', 'year', 'color',
        'registration_number', 'fuel_type', 'mileage',
        'last_service_odometer_km', 'last_service_at', 'service_interval_km',
    ];

    protected $casts = [
        'last_service_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * @return array{trips_count: int, total_distance: float, top_speed: float, average_speed: float}
     */
    public function tripStats(): array
    {
        $trips = $this->trips()->whereNotNull('ended_at');

        return [
            'trips_count' => (int) $trips->count(),
            'total_distance' => round((float) $trips->sum('distance'), 2),
            'top_speed' => round((float) ($trips->max('top_speed') ?? 0), 1),
            'average_speed' => round((float) ($trips->avg('average_speed') ?? 0), 1),
        ];
    }

    /**
     * @return array{km_since_service: int, km_until_service: int, service_due: bool, service_due_in_days: int|null}
     */
    public function serviceSummary(): array
    {
        $currentOdometer = (int) ($this->mileage ?? 0);
        $lastService = (int) ($this->last_service_odometer_km ?? 0);
        $interval = (int) ($this->service_interval_km ?? 10000);
        $kmSinceService = max(0, $currentOdometer - $lastService);
        $kmUntilService = max(0, $interval - $kmSinceService);
        $serviceDue = $kmSinceService >= $interval;
        $daysUntilService = null;

        if ($this->last_service_at) {
            $daysSinceService = (int) $this->last_service_at->diffInDays(now());
            $daysUntilService = max(0, 180 - $daysSinceService);
        }

        return [
            'km_since_service' => $kmSinceService,
            'km_until_service' => $kmUntilService,
            'service_due' => $serviceDue,
            'service_due_in_days' => $serviceDue ? 0 : $daysUntilService,
        ];
    }

    public function toApiArray(): array
    {
        return array_merge($this->toArray(), $this->tripStats(), [
            'service' => $this->serviceSummary(),
        ]);
    }
}
