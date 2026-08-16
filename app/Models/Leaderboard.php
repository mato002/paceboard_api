<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category', 'period', 'rank_position', 'score_value'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
