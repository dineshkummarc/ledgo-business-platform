<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

class RegenerateSlugsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $modelClass,
        public bool $force = true,
        public int $chunk = 200,
        public ?string $slugField = 'slug',
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        if (! class_exists($this->modelClass)) {
            throw new InvalidArgumentException("Class [{$this->modelClass}] does not exist.");
        }

        $instance = new $this->modelClass;

        if (! $instance instanceof Model) {
            throw new InvalidArgumentException("Class [{$this->modelClass}] is not an Eloquent model.");
        }

        $query = $this->modelClass::query();

        if (! $this->force && $this->slugField !== null) {
            $query->where(function ($q) {
                $q->whereNull($this->slugField)->orWhere($this->slugField, '=', '');
            });
        }

        $processed = 0;

        $query->chunkById($this->chunk, function ($records) use (&$processed) {
            foreach ($records as $record) {
                if (method_exists($record, 'regenerateSlug')) {
                    $record->regenerateSlug(save: true);
                    $processed++;
                }
            }
        });

        return $processed;
    }
}
