<?php

namespace App\Models;

use App\Support\Integrations\SlugService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use ImranDev\UniversalSlug\SlugOptions;
use ImranDev\UniversalSlug\Traits\HasSlugHistory;
use ImranDev\UniversalSlug\Traits\HasUniversalSlug;

class Business extends Model
{
    use HasSlugHistory, HasUniversalSlug;

    protected $fillable = ['name', 'type', 'locale', 'country', 'currency'];

    protected function casts(): array
    {
        return ['free_until' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function ($business) {
            $business->uuid ??= (string) Str::uuid();
            $business->free_until ??= now()->addYearNoOverflow()->toDateString();
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return app(SlugService::class)->businessOptions();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role');
    }
}
