<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\PotentiallyTranslatedString;
use ImranDev\UniversalSlug\Models\SlugHistory;
use ImranDev\UniversalSlug\Support\ReservedSlugs;

class UniqueUniversalSlugRule implements ValidationRule
{
    /**
     * Optional uniqueness query scope.
     *
     * @var callable|null
     */
    protected $scope = null;

    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(
        protected string $model,
        protected string $column = 'slug',
        protected mixed $ignore = null,
        protected string $ignoreColumn = 'id',
        protected bool $checkHistory = true,
        protected bool $checkReserved = true,
    ) {}

    /**
     * Ignore a model (or key) when validating uniqueness.
     */
    public function ignore(mixed $id, string $column = 'id'): static
    {
        $this->ignore = $id instanceof Model ? $id->getKey() : $id;
        $this->ignoreColumn = $id instanceof Model ? $id->getKeyName() : $column;

        return $this;
    }

    /**
     * Restrict uniqueness to a query scope (e.g. tenant / locale).
     */
    public function where(callable $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Skip historical slug collision checks.
     */
    public function withoutHistory(): static
    {
        $this->checkHistory = false;

        return $this;
    }

    /**
     * Skip reserved-word checks.
     */
    public function withoutReserved(): static
    {
        $this->checkReserved = false;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail("The {$attribute} must be a valid slug.");

            return;
        }

        if ($this->checkReserved && ReservedSlugs::isReserved($value)) {
            $fail("The {$attribute} is reserved and cannot be used.");

            return;
        }

        $query = $this->model::query()->where($this->column, $value);

        if ($this->ignore !== null) {
            $query->where($this->ignoreColumn, '!=', $this->ignore);
        }

        if (is_callable($this->scope)) {
            call_user_func($this->scope, $query);
        }

        if ($query->exists()) {
            $fail("The {$attribute} has already been taken.");

            return;
        }

        if (! $this->checkHistory || ! config('universal-slug.history.dont_reuse', true)) {
            return;
        }

        $instance = new $this->model;

        $historyQuery = SlugHistory::query()
            ->where('sluggable_type', $instance->getMorphClass())
            ->where('slug', $value)
            ->where('field', $this->column);

        if ($this->ignore !== null) {
            $historyQuery->where('sluggable_id', '!=', $this->ignore);
        }

        if ($historyQuery->exists()) {
            $fail("The {$attribute} was previously used and is reserved for redirects.");
        }
    }
}
