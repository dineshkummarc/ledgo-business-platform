<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Support;

class ReservedSlugs
{
    /**
     * Extra reserved slugs registered at runtime.
     *
     * @var array<int, string>
     */
    protected static array $extra = [];

    /**
     * Add reserved slugs at runtime.
     *
     * @param  array<int, string>  $slugs
     */
    public static function extend(array $slugs): void
    {
        static::$extra = array_values(array_unique(array_merge(static::$extra, $slugs)));
    }

    /**
     * Replace the runtime extras (useful in tests).
     *
     * @param  array<int, string>  $slugs
     */
    public static function set(array $slugs): void
    {
        static::$extra = array_values($slugs);
    }

    /**
     * Reset runtime extras.
     */
    public static function flush(): void
    {
        static::$extra = [];
    }

    /**
     * Get every reserved slug (config + runtime), lowercased.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        $fromConfig = function_exists('config')
            ? (array) config('universal-slug.reserved', [])
            : [];

        $merged = array_merge($fromConfig, static::$extra);

        return array_values(array_unique(array_map(
            static fn (mixed $slug): string => mb_strtolower(trim((string) $slug), 'UTF-8'),
            $merged
        )));
    }

    /**
     * Determine whether a slug is reserved.
     */
    public static function isReserved(string $slug): bool
    {
        $normalized = mb_strtolower(trim($slug), 'UTF-8');

        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, static::all(), true);
    }
}
