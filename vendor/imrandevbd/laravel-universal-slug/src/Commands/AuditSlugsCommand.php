<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use ImranDev\UniversalSlug\Support\ReservedSlugs;

class AuditSlugsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slug:audit
                            {model : The fully qualified Eloquent model class (e.g. App\\Models\\Post)}
                            {--slug=slug : The slug column to inspect}
                            {--json : Output the report as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit Eloquent slug columns for empties, duplicates, and reserved words';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelClass = (string) $this->argument('model');
        $slugField = (string) $this->option('slug');

        if (! class_exists($modelClass)) {
            $this->error("Class '{$modelClass}' does not exist.");

            return self::FAILURE;
        }

        $instance = new $modelClass;

        if (! $instance instanceof Model) {
            $this->error("Class '{$modelClass}' is not an Eloquent Model.");

            return self::FAILURE;
        }

        $empty = $modelClass::query()
            ->where(function ($query) use ($slugField) {
                $query->whereNull($slugField)->orWhere($slugField, '=', '');
            })
            ->count();

        $duplicates = $modelClass::query()
            ->select($slugField)
            ->whereNotNull($slugField)
            ->where($slugField, '!=', '')
            ->groupBy($slugField)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($slugField)
            ->all();

        $reservedHits = $modelClass::query()
            ->whereIn($slugField, ReservedSlugs::all())
            ->pluck($slugField)
            ->unique()
            ->values()
            ->all();

        $report = [
            'model' => $modelClass,
            'empty' => $empty,
            'duplicates' => $duplicates,
            'reserved' => $reservedHits,
            'healthy' => $empty === 0 && $duplicates === [] && $reservedHits === [],
        ];

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return $report['healthy'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info("Slug audit for {$modelClass}");
        $this->table(
            ['Check', 'Result'],
            [
                ['Empty slugs', (string) $empty],
                ['Duplicate slugs', $duplicates === [] ? 'none' : implode(', ', $duplicates)],
                ['Reserved slugs', $reservedHits === [] ? 'none' : implode(', ', $reservedHits)],
            ]
        );

        if ($report['healthy']) {
            $this->info('All slug records look healthy.');

            return self::SUCCESS;
        }

        $this->warn('Issues found. Run `php artisan slug:regenerate` or fix reserved/duplicate values.');

        return self::FAILURE;
    }
}
