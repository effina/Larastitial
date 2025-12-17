<?php

declare(strict_types=1);

namespace effina\Larastitial\Commands;

use Illuminate\Console\Command;
use effina\Larastitial\Models\InterstitialView;

class CleanupViewsCommand extends Command
{
    protected $signature = 'larastitial:cleanup
                            {--days=90 : Delete view records older than this many days}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old interstitial view records';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoffDate = now()->subDays($days);

        $query = InterstitialView::where('viewed_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count === 0) {
            $this->info('No records to clean up.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Would delete {$count} view records older than {$days} days.");
            return self::SUCCESS;
        }

        if (!$this->confirm("This will delete {$count} view records older than {$days} days. Continue?")) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Deleted {$deleted} view records.");

        return self::SUCCESS;
    }
}
