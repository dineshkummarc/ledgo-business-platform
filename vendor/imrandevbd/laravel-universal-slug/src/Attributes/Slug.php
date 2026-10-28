<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Attributes;

use Attribute;
use ImranDev\UniversalSlug\SlugOptions;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Slug
{
    /**
     * Declarative slug configuration for Eloquent models.
     */
    public function __construct(
        public string $from = 'title',
        public string $to = 'slug',
        public ?int $words = null,
        public ?int $maxLength = null,
        public ?string $separator = null,
        public ?string $language = null,
        public ?string $mode = null,
        public bool $stopwords = false,
        public bool|int $randomSuffix = false,
        public string $strategy = 'numeric',
        public ?string $prefix = null,
        public ?string $suffix = null,
        public bool $immutable = false,
        public bool $skipIfFilled = false,
        public bool $routeKey = false,
        public bool $reserved = true,
        public bool $dontReuseHistorical = true,
        public ?string $locale = null,
    ) {}

    /**
     * Convert the attribute into a SlugOptions instance.
     */
    public function toSlugOptions(): SlugOptions
    {
        $options = SlugOptions::create()
            ->generateSlugsFrom($this->from)
            ->saveSlugsTo($this->to)
            ->usingSuffixStrategy($this->strategy)
            ->protectReserved($this->reserved)
            ->immutable($this->immutable)
            ->skipIfFilled($this->skipIfFilled)
            ->useAsRouteKey($this->routeKey)
            ->dontReuseHistoricalSlugs($this->dontReuseHistorical);

        if ($this->words !== null) {
            $options->withWordLimit($this->words);
        }

        if ($this->maxLength !== null) {
            $options->maxSlugLength($this->maxLength);
        }

        if ($this->separator !== null) {
            $options->usingSeparator($this->separator);
        }

        if ($this->language !== null) {
            $options->usingLanguage($this->language);
        }

        if ($this->mode !== null) {
            $options->usingMode($this->mode);
        }

        if ($this->stopwords) {
            $options->withoutStopwords();
        }

        if ($this->randomSuffix !== false) {
            $options->withRandomSuffix($this->randomSuffix);
        }

        if ($this->prefix !== null) {
            $options->withPrefix($this->prefix);
        }

        if ($this->suffix !== null) {
            $options->withSuffix($this->suffix);
        }

        if ($this->locale !== null) {
            $options->usingLocale($this->locale);
        }

        return $options;
    }
}
