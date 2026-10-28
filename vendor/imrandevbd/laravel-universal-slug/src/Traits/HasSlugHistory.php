<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Traits;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use ImranDev\UniversalSlug\Exceptions\SlugRedirectException;
use ImranDev\UniversalSlug\Models\SlugHistory;
use ImranDev\UniversalSlug\UniversalSlug;

/**
 * Trait HasSlugHistory
 *
 * Automatically records old slugs on update and provides secure redirect lookup helpers.
 *
 * @mixin Model
 */
trait HasSlugHistory
{
    /**
     * Boot the slug history trait.
     */
    public static function bootHasSlugHistory(): void
    {
        static::updating(function (Model $model) {
            if (! $model->shouldRecordSlugHistory()) {
                return;
            }

            $slugField = method_exists($model, 'getSlugHistoryField')
                ? $model->getSlugHistoryField()
                : 'slug';

            if ($model->isDirty($slugField)) {
                $oldSlug = (string) $model->getOriginal($slugField);
                $newSlug = (string) $model->getAttribute($slugField);

                if (! empty($oldSlug) && $oldSlug !== $newSlug) {
                    $model->recordSlugHistory($oldSlug, $slugField);
                }
            }
        });

        static::deleted(function (Model $model) {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->slugHistories()->delete();
            }
        });
    }

    /**
     * Determine whether slug history should be recorded for this model.
     */
    public function shouldRecordSlugHistory(): bool
    {
        return (bool) config('universal-slug.history.enabled', true);
    }

    /**
     * Check if a specific user is authorized to view this model's slug history.
     */
    public function canViewSlugHistory(?Authenticatable $user = null): bool
    {
        return UniversalSlug::canViewHistory($user);
    }

    /**
     * Get historical slugs only if the current/specified user is an authorized admin/superadmin.
     */
    public function getSlugHistoryForAdmin(?Authenticatable $user = null): Collection
    {
        if (! $this->canViewSlugHistory($user)) {
            return new Collection;
        }

        return $this->slugHistories()->get();
    }

    /**
     * Get maximum history entries allowed per model.
     */
    public function getMaxSlugHistoryEntries(): int
    {
        return (int) config('universal-slug.history.max_entries_per_model', 5);
    }

    /**
     * Get all historical slugs for this model.
     */
    public function slugHistories(): MorphMany
    {
        return $this->morphMany(SlugHistory::class, 'sluggable');
    }

    /**
     * Record a historical slug and enforce retention limit.
     */
    public function recordSlugHistory(string $oldSlug, string $slugField = 'slug'): SlugHistory
    {
        $history = $this->slugHistories()->firstOrCreate([
            'slug' => $oldSlug,
            'field' => $slugField,
        ]);

        // Enforce max history limit per model to prevent unbounded table growth
        $maxEntries = $this->getMaxSlugHistoryEntries();
        if ($maxEntries > 0) {
            $excessCount = $this->slugHistories()->where('field', $slugField)->count() - $maxEntries;
            if ($excessCount > 0) {
                $oldestIds = $this->slugHistories()
                    ->where('field', $slugField)
                    ->orderBy('id', 'asc')
                    ->limit($excessCount)
                    ->pluck('id');

                SlugHistory::query()->whereIn('id', $oldestIds)->delete();
            }
        }

        return $history;
    }

    /**
     * Find a model by current slug, or historical slug with redirect awareness.
     *
     * Returns an array: ['model' => Model, 'was_redirected' => bool] or null
     *
     * @param  callable|null  $scopeCallback  Optional custom query scope (e.g. fn($query) => $query->where('status', 'published'))
     * @return array{model: static, was_redirected: bool}|null
     */
    public static function findWithHistory(string $slug, string $field = 'slug', ?callable $scopeCallback = null): ?array
    {
        $query = static::query();

        if ($scopeCallback !== null) {
            call_user_func($scopeCallback, $query);
        }

        // 1. Check current slug first
        $model = (clone $query)->where($field, $slug)->first();
        if ($model) {
            return [
                'model' => $model,
                'was_redirected' => false,
            ];
        }

        // 2. Check slug history (only if history is enabled)
        if (! config('universal-slug.history.enabled', true)) {
            return null;
        }

        $history = SlugHistory::query()
            ->where('sluggable_type', (new static)->getMorphClass())
            ->where('slug', $slug)
            ->where('field', $field)
            ->latest('id')
            ->first();

        if ($history) {
            $historyModelQuery = (clone $query)->where((new static)->getQualifiedKeyName(), $history->sluggable_id);
            $sluggable = $historyModelQuery->first();

            if ($sluggable) {
                return [
                    'model' => $sluggable,
                    'was_redirected' => true,
                ];
            }
        }

        return null;
    }

    /**
     * Find by current slug, or throw a 301 redirect for historical slugs.
     *
     * @throws ModelNotFoundException<static>
     * @throws SlugRedirectException
     */
    public static function findOrRedirect(string $slug, string $field = 'slug', ?callable $scopeCallback = null): static
    {
        $result = static::findWithHistory($slug, $field, $scopeCallback);

        if ($result === null) {
            throw (new ModelNotFoundException)->setModel(static::class);
        }

        if ($result['was_redirected']) {
            throw new SlugRedirectException($result['model'], $slug, $field);
        }

        return $result['model'];
    }
}
