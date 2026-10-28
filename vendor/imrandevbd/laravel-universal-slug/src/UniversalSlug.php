<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug;

use Illuminate\Contracts\Auth\Authenticatable;
use ImranDev\UniversalSlug\Data\Stopwords;
use ImranDev\UniversalSlug\Rules\UniqueUniversalSlugRule;
use ImranDev\UniversalSlug\Rules\UniversalSlugRule;
use Stringable;

class UniversalSlug implements Stringable
{
    protected SlugEngine $engine;

    protected ?string $title = null;

    protected ?string $separator = null;

    protected ?string $language = null;

    protected array $options = [];

    /**
     * Custom authorization closure for viewing slug histories.
     *
     * @var (callable(Authenticatable|null): bool)|null
     */
    protected static $authCallback = null;

    /**
     * Create a new UniversalSlug instance.
     */
    public function __construct(array $config = [])
    {
        $this->engine = new SlugEngine($config);
    }

    /**
     * Begin a fluent slug builder chain from a title string.
     */
    public static function of(?string $title): static
    {
        $config = function_exists('config') ? (array) config('universal-slug', []) : [];
        $instance = new static($config);
        $instance->title = $title;

        return $instance;
    }

    /**
     * Set the separator character.
     */
    public function separator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Set the language code for localized symbol replacements.
     */
    public function language(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Limit the slug to first N words.
     */
    public function words(int $count): static
    {
        $this->options['word_limit'] = $count;

        return $this;
    }

    /**
     * Alias for words().
     */
    public function wordLimit(int $count): static
    {
        return $this->words($count);
    }

    /**
     * Remove common stopwords / filler words from the slug.
     */
    public function removeStopwords(bool $remove = true): static
    {
        $this->options['remove_stopwords'] = $remove;

        return $this;
    }

    /**
     * Alias for removeStopwords().
     */
    public function withoutStopwords(): static
    {
        return $this->removeStopwords(true);
    }

    /**
     * Append a random alphanumeric suffix (e.g. 2 to 9 characters).
     */
    public function randomSuffix(int|bool $length = 5): static
    {
        $this->options['random_suffix'] = $length;

        return $this;
    }

    /**
     * Alias for randomSuffix().
     */
    public function withRandomSuffix(int|bool $length = 5): static
    {
        return $this->randomSuffix($length);
    }

    /**
     * Ensure only the pure slug is returned without random suffix.
     */
    public function onlySlug(): static
    {
        $this->options['random_suffix'] = false;

        return $this;
    }

    /**
     * Set slug generation mode ('preserve_unicode', 'transliterate', 'ascii_only').
     */
    public function mode(string $mode): static
    {
        $this->options['mode'] = $mode;

        return $this;
    }

    /**
     * Set max character length.
     */
    public function maxLength(?int $maxLength): static
    {
        $this->options['max_length'] = $maxLength;

        return $this;
    }

    /**
     * Set custom fallback slug.
     */
    public function fallback(string $fallback): static
    {
        $this->options['fallback'] = $fallback;

        return $this;
    }

    /**
     * Set lowercase transformation.
     */
    public function lowercase(bool $lowercase = true): static
    {
        $this->options['lowercase'] = $lowercase;

        return $this;
    }

    /**
     * Merge custom options.
     */
    public function withOptions(array $options): static
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Prepend a prefix segment to the generated slug.
     */
    public function prefix(string $prefix): static
    {
        $this->options['prefix'] = $prefix;

        return $this;
    }

    /**
     * Append a suffix segment to the generated slug.
     */
    public function suffix(string $suffix): static
    {
        $this->options['suffix'] = $suffix;

        return $this;
    }

    /**
     * Treat reserved route words as collisions.
     */
    public function avoidReserved(bool $avoid = true): static
    {
        $this->options['avoid_reserved'] = $avoid;

        return $this;
    }

    /**
     * Generate the final slug string.
     */
    public function make(
        ?string $title = null,
        ?string $separator = null,
        ?string $language = null,
        array $options = []
    ): string {
        $targetTitle = $title ?? $this->title;
        $targetSeparator = $separator ?? $this->separator;
        $targetLanguage = $language ?? $this->language;
        $mergedOptions = array_merge($this->options, $options);

        return $this->engine->generate($targetTitle, $targetSeparator, $targetLanguage, $mergedOptions);
    }

    /**
     * Static helper to generate slug quickly.
     */
    public static function generate(
        ?string $title,
        ?string $separator = null,
        ?string $language = null,
        array $options = []
    ): string {
        $config = function_exists('config') ? (array) config('universal-slug', []) : [];
        $instance = new static($config);

        return $instance->make($title, $separator, $language, $options);
    }

    /**
     * Static helper to generate only the clean slug without suffix.
     */
    public static function generateOnlySlug(
        ?string $title,
        ?string $separator = null,
        ?string $language = null,
        array $options = []
    ): string {
        $options['random_suffix'] = false;

        return static::generate($title, $separator, $language, $options);
    }

    /**
     * Set a custom callback for authorizing users to view slug histories.
     *
     * @param  callable(Authenticatable|null): bool  $callback
     */
    public static function authorizeHistory(callable $callback): void
    {
        static::$authCallback = $callback;
    }

    /**
     * Check if a user is authorized to view/inspect slug history.
     */
    public static function canViewHistory(?Authenticatable $user = null): bool
    {
        if (static::$authCallback !== null) {
            return (bool) call_user_func(static::$authCallback, $user);
        }

        if ($user === null && function_exists('auth') && auth()->check()) {
            $user = auth()->user();
        }

        if ($user === null) {
            return false;
        }

        // 1. Check custom permission if configured
        $permission = config('universal-slug.history.permission');
        if ($permission && method_exists($user, 'can') && $user->can($permission)) {
            return true;
        }

        // 2. Check allowed roles (e.g. 'admin', 'superadmin')
        $allowedRoles = (array) config('universal-slug.history.allowed_roles', ['admin', 'superadmin']);

        // Support spatie / adhikar / custom role packages (hasRole, hasAnyRole)
        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole($allowedRoles)) {
                return true;
            }
        } elseif (method_exists($user, 'hasRole')) {
            foreach ($allowedRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        // Support $user->role attribute
        if (isset($user->role) && in_array(strtolower((string) $user->role), array_map('strtolower', $allowedRoles), true)) {
            return true;
        }

        // Support is_admin / isAdmin() / isSuperAdmin()
        if (isset($user->is_admin) && (bool) $user->is_admin === true) {
            return true;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Get a validation rule instance.
     */
    public static function rule(?string $separator = '-', ?string $language = null, array $options = []): UniversalSlugRule
    {
        return new UniversalSlugRule($separator, $language, $options);
    }

    /**
     * Get a unique-slug validation rule for an Eloquent model.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public static function uniqueRule(string $model, string $column = 'slug'): UniqueUniversalSlugRule
    {
        return new UniqueUniversalSlugRule($model, $column);
    }

    /**
     * Register a custom processor for a specific language or script.
     */
    public static function registerProcessor(string $language, callable $callback): void
    {
        SlugEngine::registerCustomProcessor($language, $callback);
    }

    /**
     * Extend stopwords for a language.
     *
     * @param  array<string>  $words
     */
    public static function extendStopwords(string $language, array $words): void
    {
        Stopwords::extend($language, $words);
    }

    /**
     * Get underlying SlugEngine instance.
     */
    public function getEngine(): SlugEngine
    {
        return $this->engine;
    }

    /**
     * Convert to string.
     */
    public function toString(): string
    {
        return $this->make();
    }

    /**
     * Stringable interface implementation.
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
