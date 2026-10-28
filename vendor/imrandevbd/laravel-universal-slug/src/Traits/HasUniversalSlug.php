<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use ImranDev\UniversalSlug\Attributes\Slug as SlugAttribute;
use ImranDev\UniversalSlug\Events\SlugChanged;
use ImranDev\UniversalSlug\Events\SlugGenerated;
use ImranDev\UniversalSlug\Exceptions\SlugRedirectException;
use ImranDev\UniversalSlug\Models\SlugHistory;
use ImranDev\UniversalSlug\SlugEngine;
use ImranDev\UniversalSlug\SlugOptions;
use ImranDev\UniversalSlug\Support\ReservedSlugs;
use ImranDev\UniversalSlug\UniversalSlug;
use LogicException;
use ReflectionClass;
use Throwable;

/**
 * Trait HasUniversalSlug
 *
 * @mixin Model
 */
trait HasUniversalSlug
{
    /**
     * Pending slug lifecycle events dispatched after the model is saved.
     *
     * @var array<int, array{generated: bool, changed: bool, field: string, old: mixed, new: string}>
     */
    protected array $pendingSlugEvents = [];

    /**
     * When true, the next save regenerates slugs from source fields.
     */
    protected bool $forceSlugRegeneration = false;

    /**
     * Boot the trait and register model lifecycle hooks.
     */
    public static function bootHasUniversalSlug(): void
    {
        static::saving(function (Model $model) {
            $model->generateUniversalSlugsOnSaving();
        });

        static::saved(function (Model $model) {
            $model->forceSlugRegeneration = false;
            $model->flushSlugEvents();
        });
    }

    /**
     * Get the options for generating the slug.
     *
     * Override this method, or declare one or more `#[Slug]` attributes on the model.
     */
    public function getSlugOptions(): SlugOptions|array
    {
        $fromAttributes = $this->slugOptionsFromAttributes();

        if ($fromAttributes !== null) {
            return $fromAttributes;
        }

        throw new LogicException(sprintf(
            '%s must implement getSlugOptions() or declare a #[Slug] attribute.',
            static::class
        ));
    }

    /**
     * Build SlugOptions from PHP attributes when present.
     */
    protected function slugOptionsFromAttributes(): SlugOptions|array|null
    {
        $attributes = (new ReflectionClass($this))->getAttributes(SlugAttribute::class);

        if ($attributes === []) {
            return null;
        }

        $options = array_map(
            static fn ($attribute) => $attribute->newInstance()->toSlugOptions(),
            $attributes
        );

        return count($options) === 1 ? $options[0] : $options;
    }

    /**
     * Normalize configured slug options to a list.
     *
     * @return array<int, SlugOptions>
     */
    protected function normalizedSlugOptions(): array
    {
        $options = $this->getSlugOptions();

        return is_array($options) ? array_values($options) : [$options];
    }

    /**
     * Resolve the primary (first) slug options set.
     */
    public function resolvePrimarySlugOptions(): SlugOptions
    {
        return $this->normalizedSlugOptions()[0];
    }

    /**
     * Generate slugs for all configured SlugOptions on saving.
     */
    protected function generateUniversalSlugsOnSaving(): void
    {
        foreach ($this->normalizedSlugOptions() as $slugOptions) {
            $this->generateSingleSlug($slugOptions);
        }
    }

    /**
     * Force-regenerate slugs from the current source fields.
     */
    public function regenerateSlug(bool $save = false): static
    {
        $this->forceSlugRegeneration = true;

        if ($save) {
            $this->save();

            return $this;
        }

        foreach ($this->normalizedSlugOptions() as $slugOptions) {
            $this->generateSingleSlug($slugOptions, forceFromSource: true);
        }

        $this->forceSlugRegeneration = false;

        return $this;
    }

    /**
     * Preview the slug that would be generated without persisting it.
     */
    public function previewSlug(?string $title = null, ?SlugOptions $options = null): string
    {
        $options ??= $this->resolvePrimarySlugOptions();
        $source = $title ?? $this->resolveSourceString($options);

        return UniversalSlug::generate(
            $source,
            $options->separator,
            $options->language,
            $this->engineOptionsFrom($options)
        );
    }

    /**
     * Generate a single slug based on SlugOptions.
     */
    protected function generateSingleSlug(SlugOptions $options, bool $forceFromSource = false): void
    {
        $slugField = $options->slugField;
        $isCreating = ! $this->exists;
        $forceFromSource = $forceFromSource || $this->forceSlugRegeneration;

        if (! $forceFromSource) {
            if ($isCreating && ! $options->generateOnCreate) {
                return;
            }

            if (! $isCreating && ! $options->generateOnUpdate && $this->isClean($slugField)) {
                return;
            }

            if ($options->immutable && ! $isCreating && ! empty($this->getOriginal($slugField))) {
                return;
            }

            if ($options->skipIfFilled && ! empty($this->getAttribute($slugField)) && ! $this->isDirty($slugField)) {
                return;
            }
        }

        if (! $forceFromSource && $this->isDirty($slugField) && ! empty($this->getAttribute($slugField))) {
            $sourceString = (string) $this->getAttribute($slugField);
        } else {
            if (! $forceFromSource && ! $isCreating && ! $this->hasSourceFieldsChanged($options)) {
                return;
            }

            $sourceString = $this->resolveSourceString($options);
        }

        $separator = $options->separator ?? config('universal-slug.default_separator', '-');
        $baseSlug = UniversalSlug::generate($sourceString, $separator, $options->language, $this->engineOptionsFrom($options));

        if ($baseSlug === '') {
            return;
        }

        $finalSlug = $options->preventDuplicates
            ? $this->makeSlugUnique($baseSlug, $options)
            : $baseSlug;

        $original = $this->exists ? $this->getOriginal($slugField) : null;

        $this->setAttribute($slugField, $finalSlug);

        if ($original !== $finalSlug) {
            $this->pendingSlugEvents[] = [
                'generated' => true,
                'changed' => $this->exists && $original !== null && $original !== '',
                'field' => $slugField,
                'old' => $original,
                'new' => $finalSlug,
            ];
        }
    }

    /**
     * Build engine options from SlugOptions.
     *
     * @return array<string, mixed>
     */
    protected function engineOptionsFrom(SlugOptions $options): array
    {
        return [
            'mode' => $options->mode ?? config('universal-slug.mode', 'preserve_unicode'),
            'lowercase' => $options->lowercase,
            'word_limit' => $options->wordLimit ?? config('universal-slug.word_limit'),
            'max_length' => $options->maximumLength ?? config('universal-slug.max_length'),
            'random_suffix' => $options->randomSuffix,
            'remove_stopwords' => $options->removeStopwords,
            'fallback' => $options->fallback ?? config('universal-slug.fallback', 'default'),
            'replacements' => $options->customReplacements,
            'prefix' => $options->prefix,
            'suffix' => $options->suffix,
            'avoid_reserved' => $options->protectReserved,
        ];
    }

    /**
     * Resolve the source string from model attributes or callable.
     */
    protected function resolveSourceString(SlugOptions $options): string
    {
        $source = $options->generateSlugFrom;

        if (is_callable($source)) {
            return (string) call_user_func($source, $this);
        }

        if (is_array($source)) {
            $parts = [];
            foreach ($source as $field) {
                $val = $this->getAttribute($field);
                if ($val !== null && trim((string) $val) !== '') {
                    $parts[] = (string) $val;
                }
            }

            return implode(' ', $parts);
        }

        return (string) $this->getAttribute($source);
    }

    /**
     * Check if any of the source fields have changed on update.
     */
    protected function hasSourceFieldsChanged(SlugOptions $options): bool
    {
        $source = $options->generateSlugFrom;

        if (is_callable($source)) {
            return true;
        }

        if (is_array($source)) {
            foreach ($source as $field) {
                if ($this->isDirty($field)) {
                    return true;
                }
            }

            return false;
        }

        return $this->isDirty($source);
    }

    /**
     * Generate a unique slug by checking database for duplicates.
     */
    protected function makeSlugUnique(string $baseSlug, SlugOptions $options): string
    {
        if (! config('universal-slug.unique_lock.enabled', true)) {
            return $this->resolveUniqueSlug($baseSlug, $options);
        }

        $lockKey = 'universal-slug:'.$this->getMorphClass().':'.$options->slugField.':'.$baseSlug;
        $seconds = (int) config('universal-slug.unique_lock.seconds', 10);

        try {
            return Cache::lock($lockKey, $seconds)->block($seconds, function () use ($baseSlug, $options) {
                return $this->resolveUniqueSlug($baseSlug, $options);
            });
        } catch (Throwable) {
            return $this->resolveUniqueSlug($baseSlug, $options);
        }
    }

    /**
     * Resolve a unique slug candidate against the database, reserved words, and history.
     */
    protected function resolveUniqueSlug(string $baseSlug, SlugOptions $options): string
    {
        $slugField = $options->slugField;
        $suffixSeparator = $options->suffixSeparator ?? (string) config('universal-slug.unique_suffix.separator', '-');
        $startIndex = $options->suffixStartIndex;
        $strategy = $options->suffixStrategy;

        $query = static::query();

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            if ($options->includeTrashed) {
                $query->withTrashed();
            } else {
                $query->withoutTrashed();
            }
        }

        if ($options->localeColumn !== null) {
            $query->where($options->localeColumn, $this->getAttribute($options->localeColumn));
        }

        if (is_callable($options->uniqueScope)) {
            call_user_func($options->uniqueScope, $query, $this);
        }

        if ($this->exists && $this->getKey() !== null) {
            $query->where($this->getQualifiedKeyName(), '!=', $this->getKey());
        }

        $matchingSlugs = $query
            ->where(function (Builder $q) use ($slugField, $baseSlug, $suffixSeparator) {
                $q->where($slugField, '=', $baseSlug)
                    ->orWhere($slugField, 'LIKE', $baseSlug.$suffixSeparator.'%');
            })
            ->pluck($slugField)
            ->all();

        if ($options->protectReserved && ReservedSlugs::isReserved($baseSlug)) {
            $matchingSlugs[] = $baseSlug;
        }

        if ($options->dontReuseHistorical && config('universal-slug.history.dont_reuse', true)) {
            $matchingSlugs = array_merge($matchingSlugs, $this->historicalSlugsMatching($baseSlug, $slugField, $suffixSeparator));
        }

        $matchingSlugs = array_values(array_unique($matchingSlugs));

        if (empty($matchingSlugs) || ! in_array($baseSlug, $matchingSlugs, true)) {
            return $baseSlug;
        }

        if ($strategy === 'random') {
            do {
                $randomSuffix = (new SlugEngine)->generateRandomSuffix(5);
                $candidate = $baseSlug.$suffixSeparator.$randomSuffix;
            } while (in_array($candidate, $matchingSlugs, true) || ($options->protectReserved && ReservedSlugs::isReserved($candidate)));

            return $candidate;
        }

        if ($strategy === 'timestamp') {
            return $baseSlug.$suffixSeparator.time();
        }

        if ($strategy === 'date') {
            $date = date('Y-m-d');
            $candidate = $baseSlug.$suffixSeparator.$date;
            if (! in_array($candidate, $matchingSlugs, true)) {
                return $candidate;
            }
        }

        $suffix = $startIndex;
        while (
            in_array($baseSlug.$suffixSeparator.$suffix, $matchingSlugs, true)
            || ($options->protectReserved && ReservedSlugs::isReserved($baseSlug.$suffixSeparator.$suffix))
        ) {
            $suffix++;
        }

        return $baseSlug.$suffixSeparator.$suffix;
    }

    /**
     * Collect historical slugs that would collide with the candidate.
     *
     * @return array<int, string>
     */
    protected function historicalSlugsMatching(string $baseSlug, string $slugField, string $suffixSeparator): array
    {
        if (! class_exists(SlugHistory::class)) {
            return [];
        }

        try {
            return SlugHistory::query()
                ->where('sluggable_type', $this->getMorphClass())
                ->where('field', $slugField)
                ->where(function ($query) use ($baseSlug, $suffixSeparator) {
                    $query->where('slug', $baseSlug)
                        ->orWhere('slug', 'like', $baseSlug.$suffixSeparator.'%');
                })
                ->pluck('slug')
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Dispatch queued slug events after the model is persisted.
     */
    protected function flushSlugEvents(): void
    {
        foreach ($this->pendingSlugEvents as $event) {
            event(new SlugGenerated($this, $event['new'], $event['field']));

            if ($event['changed']) {
                event(new SlugChanged($this, (string) $event['old'], $event['new'], $event['field']));
            }
        }

        $this->pendingSlugEvents = [];
    }

    /**
     * Use the slug column as the implicit route key when configured.
     */
    public function getRouteKeyName(): string
    {
        $options = $this->resolvePrimarySlugOptions();

        if ($options->useAsRouteKey) {
            return $options->slugField;
        }

        return parent::getRouteKeyName();
    }

    /**
     * Resolve implicit/explicit route bindings, with automatic 301 on historical slugs.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();
        $slugField = $this->resolvePrimarySlugOptions()->slugField;

        if ((string) $field === $slugField && method_exists(static::class, 'findWithHistory')) {
            $result = static::findWithHistory((string) $value, $field);

            if ($result === null) {
                return null;
            }

            if ($result['was_redirected'] && config('universal-slug.history.auto_redirect', true)) {
                throw new SlugRedirectException($result['model'], (string) $value, $field);
            }

            return $result['model'];
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * Scope a query to find model by slug.
     */
    public function scopeWhereSlug(Builder $query, string $slug, string $field = 'slug'): Builder
    {
        return $query->where($field, $slug);
    }

    /**
     * Find model by slug.
     */
    public static function findBySlug(string $slug, string $field = 'slug'): ?static
    {
        return static::query()->where($field, $slug)->first();
    }

    /**
     * Find model by slug or throw 404 / ModelNotFoundException.
     */
    public static function findBySlugOrFail(string $slug, string $field = 'slug'): static
    {
        return static::query()->where($field, $slug)->firstOrFail();
    }
}
