<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class RegenerateModelSlugsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slug:regenerate
                            {model : The fully qualified Eloquent model class (e.g. App\Models\Post)}
                            {--field=title : The source field name}
                            {--slug=slug : The target slug field name}
                            {--chunk=200 : Chunk size for processing}
                            {--force : Force regenerate slugs even if already present}
                            {--dry-run : Preview changes without saving to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate universal slugs for all existing Eloquent model records in the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelClass = (string) $this->argument('model');
        $field = (string) $this->option('field');
        $slugField = (string) $this->option('slug');
        $chunkSize = (int) $this->option('chunk');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        if (! class_exists($modelClass)) {
            $this->error("Class '{$modelClass}' does not exist.");

            return self::FAILURE;
        }

        /** @var Model $instance */
        $instance = new $modelClass;
        if (! $instance instanceof Model) {
            $this->error("Class '{$modelClass}' is not an Eloquent Model.");

            return self::FAILURE;
        }

        $query = $modelClass::query();

        if (! $force) {
            $query->where(function ($q) use ($slugField) {
                $q->whereNull($slugField)->orWhere($slugField, '=', '');
            });
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info("No records found to regenerate for {$modelClass}.");

            return self::SUCCESS;
        }

        $this->info("Processing {$totalCount} records for {$modelClass}...".($dryRun ? ' (DRY RUN)' : ''));
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $processed = 0;
        $query->chunkById($chunkSize, function ($records) use ($field, $slugField, $dryRun, $bar, &$processed) {
            foreach ($records as $record) {
                $sourceVal = $record->{$field};
                if (! empty($sourceVal)) {
                    if (! $dryRun) {
                        if (method_exists($record, 'regenerateSlug')) {
                            $record->regenerateSlug(save: true);
                        } else {
                            $record->{$slugField} = null;
                            $record->save();
                        }
                    }
                }
                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Successfully processed {$processed} records.");

        return self::SUCCESS;
    }
}
