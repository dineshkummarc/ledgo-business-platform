<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug;

class SlugOptions
{
    /**
     * The field(s) or callable from which to generate the slug.
     *
     * @var string|array<string>|callable
     */
    public $generateSlugFrom = 'title';

    /**
     * The database column where the slug should be saved.
     */
    public string $slugField = 'slug';

    /**
     * The separator character.
     */
    public ?string $separator = null;

    /**
     * The language code for specific dictionary replacements.
     */
    public ?string $language = null;

    /**
     * The slug generation mode ('preserve_unicode', 'transliterate', 'ascii_only').
     */
    public ?string $mode = null;

    /**
     * Whether to convert the slug to lowercase.
     */
    public bool $lowercase = true;

    /**
     * Maximum words limit.
     */
    public ?int $wordLimit = null;

    /**
     * Maximum character length of the generated slug.
     */
    public ?int $maximumLength = null;

    /**
     * Whether to remove stopwords.
     */
    public bool $removeStopwords = false;

    /**
     * Random suffix setting (false, true, or int length 2-9).
     */
    public bool|int $randomSuffix = false;

    /**
     * Duplicate collision suffix strategy ('numeric', 'random', 'timestamp', 'date').
     */
    public string $suffixStrategy = 'numeric';

    /**
     * Default fallback slug if input is empty.
     */
    public ?string $fallback = null;

    /**
     * Whether to generate slug on model creation.
     */
    public bool $generateOnCreate = true;

    /**
     * Whether to regenerate slug when source fields are updated.
     */
    public bool $generateOnUpdate = true;

    /**
     * Whether to prevent duplicate slugs by appending unique numeric suffixes.
     */
    public bool $preventDuplicates = true;

    /**
     * The separator for numeric suffix (e.g. "-1", "_1").
     */
    public ?string $suffixSeparator = null;

    /**
     * Starting number for duplicate suffix.
     */
    public int $suffixStartIndex = 1;

    /**
     * Whether to include soft-deleted records when checking for uniqueness.
     */
    public bool $includeTrashed = true;

    /**
     * Additional query scoping for uniqueness checks (e.g. multi-tenant).
     *
     * @var callable|null
     */
    public $uniqueScope = null;

    /**
     * Custom symbol/character replacements.
     */
    public array $customReplacements = [];

    /**
     * Optional prefix prepended to the generated slug.
     */
    public ?string $prefix = null;

    /**
     * Optional suffix appended to the generated slug (before uniqueness).
     */
    public ?string $suffix = null;

    /**
     * Whether reserved route words (admin, api, login...) should be treated as taken.
     */
    public bool $protectReserved = true;

    /**
     * Never regenerate an existing slug, even if the source title changes.
     */
    public bool $immutable = false;

    /**
     * Skip generation when the slug column already has a value.
     */
    public bool $skipIfFilled = false;

    /**
     * Use the slug column as the implicit route key (`{model}`).
     */
    public bool $useAsRouteKey = false;

    /**
     * Treat historical slugs as taken so old URLs keep their 301 mapping.
     */
    public bool $dontReuseHistorical = true;

    /**
     * Optional locale/language column used to scope uniqueness (e.g. `locale`).
     */
    public ?string $localeColumn = null;

    /**
     * Create a new SlugOptions instance.
     */
    public static function create(): static
    {
        return new static;
    }

    /**
     * Set the source field(s) or callable for slug generation.
     *
     * @param  string|array<string>|callable  $field
     */
    public function generateSlugsFrom(string|array|callable $field): static
    {
        $this->generateSlugFrom = $field;

        return $this;
    }

    /**
     * Set the destination column for saving the slug.
     */
    public function saveSlugsTo(string $field): static
    {
        $this->slugField = $field;

        return $this;
    }

    /**
     * Set separator character.
     */
    public function usingSeparator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Set language code.
     */
    public function usingLanguage(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Set slug generation mode ('preserve_unicode', 'transliterate', 'ascii_only').
     */
    public function usingMode(string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Set lowercase conversion.
     */
    public function lowercase(bool $lowercase = true): static
    {
        $this->lowercase = $lowercase;

        return $this;
    }

    /**
     * Limit the slug to first N words (e.g. 7 or 10 words).
     */
    public function withWordLimit(int $count): static
    {
        $this->wordLimit = $count;

        return $this;
    }

    /**
     * Alias for withWordLimit().
     */
    public function words(int $count): static
    {
        return $this->withWordLimit($count);
    }

    /**
     * Automatically strip common stopwords/filler words.
     */
    public function withoutStopwords(bool $without = true): static
    {
        $this->removeStopwords = $without;

        return $this;
    }

    /**
     * Alias for withoutStopwords().
     */
    public function removeStopwords(bool $remove = true): static
    {
        return $this->withoutStopwords($remove);
    }

    /**
     * Set maximum character length.
     */
    public function maxSlugLength(int $length): static
    {
        $this->maximumLength = $length;

        return $this;
    }

    /**
     * Append a random alphanumeric suffix (e.g. 2 to 9 chars).
     */
    public function withRandomSuffix(bool|int $length = 5): static
    {
        $this->randomSuffix = $length;

        return $this;
    }

    /**
     * Alias for withRandomSuffix().
     */
    public function randomSuffix(bool|int $length = 5): static
    {
        return $this->withRandomSuffix($length);
    }

    /**
     * Set collision suffix strategy ('numeric', 'random', 'timestamp', 'date').
     */
    public function usingSuffixStrategy(string $strategy): static
    {
        $this->suffixStrategy = $strategy;

        return $this;
    }

    /**
     * Disable random suffix.
     */
    public function onlySlug(): static
    {
        $this->randomSuffix = false;

        return $this;
    }

    /**
     * Set fallback slug string when empty.
     */
    public function fallback(string $fallback): static
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * Prevent slug generation when updating an existing model.
     */
    public function doNotGenerateSlugsOnUpdate(): static
    {
        $this->generateOnUpdate = false;

        return $this;
    }

    /**
     * Enable/disable slug generation on model update.
     */
    public function generateSlugsOnUpdate(bool $generate = true): static
    {
        $this->generateOnUpdate = $generate;

        return $this;
    }

    /**
     * Prevent slug generation when creating a model.
     */
    public function doNotGenerateSlugsOnCreate(): static
    {
        $this->generateOnCreate = false;

        return $this;
    }

    /**
     * Enable/disable unique slug generation.
     */
    public function allowDuplicates(bool $allow = true): static
    {
        $this->preventDuplicates = ! $allow;

        return $this;
    }

    /**
     * Define the suffix separator for unique collisions.
     */
    public function usingSuffixSeparator(string $separator): static
    {
        $this->suffixSeparator = $separator;

        return $this;
    }

    /**
     * Define starting number for unique suffix.
     */
    public function startSuffixAt(int $index): static
    {
        $this->suffixStartIndex = $index;

        return $this;
    }

    /**
     * Define whether soft-deleted records should be considered in unique check.
     */
    public function includeTrashed(bool $include = true): static
    {
        $this->includeTrashed = $include;

        return $this;
    }

    /**
     * Add a custom query scope for uniqueness checks (e.g. multi-tenant).
     */
    public function uniqueScope(callable $scope): static
    {
        $this->uniqueScope = $scope;

        return $this;
    }

    /**
     * Set custom symbol / character replacements.
     */
    public function replacements(array $replacements): static
    {
        $this->customReplacements = $replacements;

        return $this;
    }

    /**
     * Prepend a prefix segment to the generated slug.
     */
    public function withPrefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Append a suffix segment to the generated slug.
     */
    public function withSuffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Treat reserved words as collisions (admin, api, login, ...).
     */
    public function protectReserved(bool $protect = true): static
    {
        $this->protectReserved = $protect;

        return $this;
    }

    /**
     * Freeze the slug after the first successful generation.
     */
    public function immutable(bool $immutable = true): static
    {
        $this->immutable = $immutable;

        return $this;
    }

    /**
     * Do not overwrite a slug that is already stored.
     */
    public function skipIfFilled(bool $skip = true): static
    {
        $this->skipIfFilled = $skip;

        return $this;
    }

    /**
     * Use this slug column as the model's implicit route key.
     */
    public function useAsRouteKey(bool $use = true): static
    {
        $this->useAsRouteKey = $use;

        return $this;
    }

    /**
     * Prevent recycling historical slugs that should keep 301 redirects.
     */
    public function dontReuseHistoricalSlugs(bool $dontReuse = true): static
    {
        $this->dontReuseHistorical = $dontReuse;

        return $this;
    }

    /**
     * Scope uniqueness to a locale / language column.
     */
    public function usingLocale(?string $column = 'locale'): static
    {
        $this->localeColumn = $column;

        return $this;
    }
}
