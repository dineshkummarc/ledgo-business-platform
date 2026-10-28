<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string make(?string $title, ?string $separator = null, ?string $language = null, array $options = [])
 * @method static string generate(?string $title, ?string $separator = null, ?string $language = null, array $options = [])
 * @method static \ImranDev\UniversalSlug\UniversalSlug of(?string $title)
 * @method static \ImranDev\UniversalSlug\Rules\UniversalSlugRule rule(?string $separator = '-', ?string $language = null, array $options = [])
 * @method static \ImranDev\UniversalSlug\Rules\UniqueUniversalSlugRule uniqueRule(string $model, string $column = 'slug')
 * @method static \ImranDev\UniversalSlug\SlugEngine getEngine()
 *
 * @see \ImranDev\UniversalSlug\UniversalSlug
 */
class UniversalSlug extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'universal-slug';
    }
}
