<?php

declare(strict_types=1);

use ImranDev\UniversalSlug\UniversalSlug;

if (! function_exists('universal_slug')) {
    /**
     * Generate a universal, multilingual URL slug.
     */
    function universal_slug(
        ?string $title,
        ?string $separator = null,
        ?string $language = null,
        array $options = [],
        bool|int|null $random_suffix = null,
        ?int $word_limit = null,
        ?string $fallback = null
    ): string {
        if ($random_suffix !== null) {
            $options['random_suffix'] = $random_suffix;
        }

        if ($word_limit !== null) {
            $options['word_limit'] = $word_limit;
        }

        if ($fallback !== null) {
            $options['fallback'] = $fallback;
        }

        return UniversalSlug::generate($title, $separator, $language, $options);
    }
}

if (! function_exists('slug_is_reserved')) {
    /**
     * Determine whether a slug is a reserved route word.
     */
    function slug_is_reserved(string $slug): bool
    {
        return \ImranDev\UniversalSlug\Support\ReservedSlugs::isReserved($slug);
    }
}

if (! function_exists('preview_slug')) {
    /**
     * Preview a slug the same way `universal_slug()` generates one.
     */
    function preview_slug(
        ?string $title,
        ?string $separator = null,
        ?string $language = null,
        array $options = []
    ): string {
        return UniversalSlug::generate($title, $separator, $language, $options);
    }
}
