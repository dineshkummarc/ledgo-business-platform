<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use ImranDev\UniversalSlug\UniversalSlug;

class UniversalSlugRule implements ValidationRule
{
    public function __construct(
        protected ?string $separator = '-',
        protected ?string $language = null,
        protected array $options = []
    ) {}

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

        // Check if slug has whitespaces or unallowed URL characters
        if (preg_match('/\s+/u', $value)) {
            $fail("The {$attribute} must not contain any whitespace characters.");

            return;
        }

        // A valid universal slug equals the regenerated clean slug
        $clean = UniversalSlug::generate($value, $this->separator, $this->language, $this->options);

        if ($clean !== $value) {
            $fail("The {$attribute} is not a valid URL slug format.");
        }
    }
}
