<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CommunityReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'latitude', 'longitude', 'road_name',
        'description', 'photo_url', 'verification_score',
        'confirmations_count', 'dismissals_count', 'last_confirmed_at',
        'status', 'is_active', 'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_confirmed_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected $appends = ['confidence'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getConfidenceAttribute(): int
    {
        $value = 50
            + ((int) $this->verification_score * 8)
            + ((int) $this->confirmations_count * 5)
            - ((int) $this->dismissals_count * 10);

        return max(8, min(99, $value));
    }

    public static function expiryForType(string $type): Carbon
    {
        return now()->addHours(match ($type) {
            'traffic' => 2,
            'accident' => 8,
            'police' => 4,
            'road_closure' => 24,
            'flooding' => 12,
            'debris' => 24,
            'pothole' => 24 * 14,
            'speed_camera' => 24 * 30,
            'school_zone' => 24 * 30,
            default => 48,
        });
    }

    public function isStale(): bool
    {
        $anchor = $this->last_confirmed_at ?? $this->created_at;
        if ($anchor === null) {
            return false;
        }

        $hours = match ($this->type) {
            'traffic' => 6,
            'accident' => 24,
            'police' => 12,
            'road_closure' => 48,
            'pothole' => 24 * 45,
            'speed_camera' => 24 * 90,
            default => 72,
        };

        return $anchor->lt(now()->subHours($hours));
    }
}
