<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlugGenerated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Fired after a slug is generated and persisted on a model.
     */
    public function __construct(
        public readonly Model $model,
        public readonly string $slug,
        public readonly string $field,
    ) {}
}
