<?php

namespace App\Console\Commands;

use App\Services\LeaderboardService;
use Illuminate\Console\Command;

class RecalculateLeaderboards extends Command
{
    protected $signature = 'paceboard:recalculate-leaderboards';

    protected $description = 'Recalculate all leaderboard rankings';

    public function handle(LeaderboardService $leaderboards): int
    {
        $leaderboards->recalculateRanks();
        $this->info('Leaderboard ranks recalculated.');

        return self::SUCCESS;
    }
}
