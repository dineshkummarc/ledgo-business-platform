<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Commands;

use Illuminate\Console\Command;
use ImranDev\UniversalSlug\Models\SlugHistory;

class PruneSlugHistoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slug:prune
                            {--days=90 : Delete slug histories older than this number of days}
                            {--model= : Optional model class to prune specifically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old historical slugs from the database to maintain clean and secure history';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $model = $this->option('model') ? (string) $this->option('model') : null;

        $cutoff = now()->subDays($days);

        $query = SlugHistory::query()->where('created_at', '<', $cutoff);

        if ($model) {
            $query->where('sluggable_type', $model);
        }

        $deletedCount = $query->delete();

        $this->info("Successfully pruned {$deletedCount} old slug history records older than {$days} days.");

        return self::SUCCESS;
    }
}
