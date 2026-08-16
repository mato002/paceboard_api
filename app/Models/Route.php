<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'start_city', 'end_city', 'total_trips', 'is_popular',
    ];

    protected $casts = [
        'is_popular' => 'boolean',
    ];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function leaderboards(): HasMany
    {
        return $this->hasMany(RouteLeaderboard::class);
    }
}
