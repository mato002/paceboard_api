<?php

namespace App\Console\Commands;

use App\Models\CommunityReport;
use Illuminate\Console\Command;

class PruneExpiredReports extends Command
{
    protected $signature = 'paceboard:prune-reports';

    protected $description = 'Deactivate expired community reports';

    public function handle(): int
    {
        $count = CommunityReport::where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['is_active' => false]);

        $this->info("Deactivated {$count} expired reports.");

        return self::SUCCESS;
    }
}
