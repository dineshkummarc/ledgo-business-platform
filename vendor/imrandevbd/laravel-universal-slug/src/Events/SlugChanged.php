<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlugChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Fired after an existing model's slug is replaced with a new value.
     */
    public function __construct(
        public readonly Model $model,
        public readonly string $oldSlug,
        public readonly string $newSlug,
        public readonly string $field,
    ) {}
}
