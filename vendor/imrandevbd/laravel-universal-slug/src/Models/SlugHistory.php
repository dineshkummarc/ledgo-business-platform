<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SlugHistory extends Model
{
    protected $table = 'slug_histories';

    protected $guarded = [];

    /**
     * Get the owning sluggable model.
     */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }
}
