<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug;

use Illuminate\Support\Str;
use ImranDev\UniversalSlug\Data\Stopwords;
use ImranDev\UniversalSlug\Support\ReservedSlugs;
use Normalizer;

class SlugEngine
{
    /**
     * Emojis and miscellaneous symbols Unicode pattern.
     */
    protected const EMOJI_PATTERN = '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{FE00}-\x{FE0F}\x{1F900}-\x{1F9FF}\x{1FA70}-\x{1FAFF}]/u';

    /**
     * Zero-width and invisible characters.
     */
    protected const INVISIBLE_CHARS_PATTERN = '/[\x{200B}-\x{200D}\x{FEFF}\x{200E}\x{200F}\x{202A}-\x{202E}]/u';

    /**
     * Dash-like unicode characters (em-dash, en-dash, figure dash, etc.).
     */
    protected const DASH_CHARS_PATTERN = '/[–—−‑‒―]/u';

    /**
     * Sentence-ending and language-specific punctuation (Bangla Dari, Arabic comma/question mark, CJK punctuation).
     */
    protected const MULTILINGUAL_PUNCTUATION_PATTERN = '/[।॥،؟؛؛\x{3001}\x{3002}\x{FF0C}\x{FF01}\x{FF1F}\x{FF1A}\x{FF1B}]/u';

    /**
     * Indic script recomposition map (Nukta / Nuqta composites).
     */
    protected const INDIC_RECOMPOSE_MAP = [
        // Bengali / Assamese
        "\u{09A1}\u{09BC}" => "\u{09DC}", // ড + ় -> ড়
        "\u{09A2}\u{09BC}" => "\u{09DD}", // ঢ + ় -> ঢ়
        "\u{09AF}\u{09BC}" => "\u{09DF}", // য + ় -> য়
        "\u{09AC}\u{09BC}" => "\u{09F0}", // Assamese ৰ
        "\u{09B0}\u{09BC}" => "\u{09F1}", // Assamese ৱ

        // Devanagari (Hindi, Marathi, Nepali)
        "\u{0915}\u{093C}" => "\u{0958}", // क़
        "\u{0916}\u{093C}" => "\u{0959}", // ख़
        "\u{0917}\u{093C}" => "\u{095A}", // ग़
        "\u{091C}\u{093C}" => "\u{095B}", // ज़
        "\u{0921}\u{093C}" => "\u{095C}", // ड़
        "\u{0922}\u{093C}" => "\u{095D}", // ढ़
        "\u{092B}\u{093C}" => "\u{095E}", // फ़
        "\u{092F}\u{093C}" => "\u{095F}", // य़
    ];

    /**
     * Registered custom language processors.
     *
     * @var array<string, callable>
     */
    protected static array $customProcessors = [];

    /**
     * Create a new SlugEngine instance.
     */
    public function __construct(
        protected array $config = []
    ) {
        $this->config = array_merge([
            'default_separator' => '-',
            'mode' => 'preserve_unicode',
            'lowercase' => true,
            'word_limit' => null,
            'max_length' => null,
            'random_suffix' => false,
            'random_suffix_length' => 5,
            'remove_stopwords' => false,
            'fallback' => 'default',
            'strip_tags' => true,
            'strip_emojis' => true,
            'replacements' => [],
            'language_replacements' => [],
            'prefix' => null,
            'suffix' => null,
            'avoid_reserved' => false,
        ], $config);
    }

    /**
     * Register a custom processor for a specific language or script.
     */
    public static function registerCustomProcessor(string $language, callable $callback): void
    {
        static::$customProcessors[strtolower(trim($language))] = $callback;
    }

    /**
     * Generate a clean, space-free slug from the given string.
     */
    public function generate(
        ?string $title,
        ?string $separator = null,
        ?string $language = null,
        array $options = []
    ): string {
        $opts = array_merge($this->config, $options);
        $separator = $separator ?? (string) ($opts['default_separator'] ?? '-');
        $mode = $opts['mode'] ?? 'preserve_unicode';
        $lowercase = (bool) ($opts['lowercase'] ?? true);
        $wordLimit = isset($opts['word_limit']) && $opts['word_limit'] !== null ? (int) $opts['word_limit'] : null;
        $maxLength = isset($opts['max_length']) && $opts['max_length'] !== null ? (int) $opts['max_length'] : null;
        $removeStopwords = (bool) ($opts['remove_stopwords'] ?? false);
        $fallback = $opts['fallback'] ?? 'default';

        if ($title === null || trim($title) === '') {
            return $this->finalizeFallback($fallback, $separator, $opts);
        }

        // 1. Ensure valid UTF-8
        $str = mb_convert_encoding($title, 'UTF-8', 'UTF-8');

        // 2. Strip HTML tags & decode HTML entities (&amp; -> &)
        if ($opts['strip_tags'] ?? true) {
            $str = strip_tags($str);
        }
        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 3. Strip Emojis
        if ($opts['strip_emojis'] ?? true) {
            $str = (string) preg_replace(self::EMOJI_PATTERN, ' ', $str);
        }

        // 4. Remove invisible and zero-width characters
        $str = (string) preg_replace(self::INVISIBLE_CHARS_PATTERN, '', $str);

        // 5. Normalize dash-like characters & multilingual punctuation to spaces
        $str = (string) preg_replace(self::DASH_CHARS_PATTERN, ' ', $str);
        $str = (string) preg_replace(self::MULTILINGUAL_PUNCTUATION_PATTERN, ' ', $str);

        // 6. Convert non-breaking spaces and whitespace
        $str = str_replace(["\u{00A0}", "\t", "\r", "\n"], ' ', $str);

        // 7. Unicode normalization (NFC) & Indic script canonical recomposition
        if (class_exists(Normalizer::class) && Normalizer::isNormalized($str, Normalizer::FORM_C) === false) {
            $normalized = Normalizer::normalize($str, Normalizer::FORM_C);
            if ($normalized !== false) {
                $str = $normalized;
            }
        }
        $str = strtr($str, self::INDIC_RECOMPOSE_MAP);

        // 8. Remove quotes and apostrophes cleanly (e.g. "l'été" -> "lete", "don't" -> "dont")
        $str = str_replace(["'", '"', '`', '’', '‘', '”', '“', '«', '»', '‹', '›'], '', $str);

        // 9. Apply Language-specific and Global Symbol Replacements
        $replacements = $this->resolveReplacements($language, $opts);
        if (! empty($replacements)) {
            foreach ($replacements as $search => $replace) {
                $str = str_replace((string) $search, ' '.$replace.' ', $str);
            }
        }

        // 10. Execute Custom Language Processor if registered
        if ($language !== null) {
            $langKey = strtolower(trim($language));
            if (isset(static::$customProcessors[$langKey])) {
                $str = (string) call_user_func(static::$customProcessors[$langKey], $str, $separator, $opts);
            }
        }

        // 11. Remove Stopwords if enabled
        if ($removeStopwords) {
            $str = $this->removeStopwords($str, $language);
        }

        // 12. Word limitation (e.g. first 7 or 10 words)
        if ($wordLimit !== null && $wordLimit > 0) {
            $str = $this->limitWords($str, $wordLimit);
        }

        // 13. Handle Generation Mode
        $slug = match ($mode) {
            'transliterate' => $this->handleTransliteration($str, $separator),
            'ascii_only' => $this->handleAsciiOnly($str, $separator),
            default => $this->handlePreserveUnicode($str, $separator),
        };

        // 14. Case conversion (multibyte safe)
        if ($lowercase) {
            $slug = mb_strtolower($slug, 'UTF-8');
        }

        // 15. Max length truncation (word & multibyte safe)
        if ($maxLength !== null && $maxLength > 0 && mb_strlen($slug, 'UTF-8') > $maxLength) {
            $slug = $this->truncate($slug, $maxLength, $separator);
        }

        // 16. Ensure zero spaces exist in the final slug
        $escapedSeparator = preg_quote($separator, '/');
        $slug = preg_replace('/\s+/u', $separator, $slug);
        $slug = preg_replace('/'.$escapedSeparator.'+/u', $separator, (string) $slug);
        $slug = trim((string) $slug, $separator);

        // 17. Fallback if slug is completely empty
        if ($slug === '') {
            return $this->finalizeFallback($fallback, $separator, $opts);
        }

        // 18. Optional prefix / suffix segments
        $slug = $this->applyAffixes($slug, $separator, $opts);

        // 19. Avoid reserved route words (admin, api, login, ...)
        if ((bool) ($opts['avoid_reserved'] ?? false) && ReservedSlugs::isReserved($slug)) {
            $slug = $this->disambiguateReserved($slug, $separator);
        }

        // 20. Random Suffix Option (e.g. 2-9 random alphanumeric characters)
        if ($this->shouldAppendRandomSuffix($opts)) {
            $length = $this->resolveRandomSuffixLength($opts);
            $randomSuffix = $this->generateRandomSuffix($length);
            $slug .= $separator.$randomSuffix;
        }

        return $slug;
    }

    /**
     * Process string preserving Unicode characters (Bengali, Arabic, Hindi, Chinese, etc.).
     */
    protected function handlePreserveUnicode(string $str, string $separator): string
    {
        $escapedSeparator = preg_quote($separator, '/');
        $pattern = '/[^\p{L}\p{M}\p{N}]+/u';
        $slug = preg_replace($pattern, $separator, $str);

        // Collapse multiple consecutive separators into one
        $slug = preg_replace('/'.$escapedSeparator.'+/u', $separator, (string) $slug);

        return trim((string) $slug, $separator);
    }

    /**
     * Process string with Transliteration to Latin characters.
     */
    protected function handleTransliteration(string $str, string $separator): string
    {
        if (function_exists('transliterator_transliterate')) {
            $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0080-\uffff] remove;', $str);
            if ($transliterated !== false && trim($transliterated) !== '') {
                return $this->handleAsciiOnly($transliterated, $separator);
            }
        }

        if (class_exists(Str::class) && method_exists(Str::class, 'transliterate')) {
            $trans = Str::transliterate($str);

            return $this->handleAsciiOnly($trans, $separator);
        }

        return $this->handlePreserveUnicode($str, $separator);
    }

    /**
     * Process string in strict ASCII-only mode.
     */
    protected function handleAsciiOnly(string $str, string $separator): string
    {
        if (class_exists(Str::class) && method_exists(Str::class, 'ascii')) {
            $str = Str::ascii($str);
        } else {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
            if ($converted !== false) {
                $str = $converted;
            }
        }

        $escapedSeparator = preg_quote($separator, '/');
        $slug = preg_replace('/[^a-zA-Z0-9]+/u', $separator, $str);
        $slug = preg_replace('/'.$escapedSeparator.'+/u', $separator, (string) $slug);

        return trim((string) $slug, $separator);
    }

    /**
     * Remove stopwords from the string.
     */
    protected function removeStopwords(string $str, ?string $language): string
    {
        $stopwords = Stopwords::get($language ?? 'en');
        if (empty($stopwords)) {
            return $str;
        }

        $words = preg_split('/\s+/u', trim($str));
        if ($words === false || empty($words)) {
            return $str;
        }

        $stopwordLookup = array_flip(array_map('mb_strtolower', $stopwords));

        $filtered = array_filter($words, function ($word) use ($stopwordLookup) {
            $cleanWord = mb_strtolower(trim($word));

            return ! isset($stopwordLookup[$cleanWord]);
        });

        // If removing stopwords made the string empty, retain original
        if (empty($filtered)) {
            return $str;
        }

        return implode(' ', $filtered);
    }

    /**
     * Limit string to first N words safely.
     */
    protected function limitWords(string $str, int $limit): string
    {
        $words = preg_split('/\s+/u', trim($str));
        if ($words === false || empty($words)) {
            return $str;
        }

        $filteredWords = array_values(array_filter($words, fn ($w) => $w !== ''));
        $sliced = array_slice($filteredWords, 0, $limit);

        return implode(' ', $sliced);
    }

    /**
     * Truncate string to max length safely without cutting multibyte characters.
     */
    protected function truncate(string $slug, int $maxLength, string $separator): string
    {
        $truncated = mb_substr($slug, 0, $maxLength, 'UTF-8');
        $clean = rtrim($truncated, $separator);

        return $clean !== '' ? $clean : $truncated;
    }

    /**
     * Resolve symbol replacements dictionary.
     */
    protected function resolveReplacements(?string $language, array $options): array
    {
        $replacements = $options['replacements'] ?? [];

        if ($language !== null) {
            $langKey = strtolower(trim($language));
            $langReplacements = $options['language_replacements'][$langKey] ?? [];
            $replacements = array_merge($replacements, $langReplacements);
        }

        return $replacements;
    }

    /**
     * Check if random suffix should be appended.
     */
    protected function shouldAppendRandomSuffix(array $options): bool
    {
        if (isset($options['random_suffix'])) {
            if (is_bool($options['random_suffix'])) {
                return $options['random_suffix'];
            }
            if (is_numeric($options['random_suffix']) && (int) $options['random_suffix'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve random suffix character length (supports 2-9 or custom).
     */
    protected function resolveRandomSuffixLength(array $options): int
    {
        if (isset($options['random_suffix']) && is_numeric($options['random_suffix']) && (int) $options['random_suffix'] > 0) {
            return (int) $options['random_suffix'];
        }

        return (int) ($options['random_suffix_length'] ?? 5);
    }

    /**
     * Generate cryptographically secure random alphanumeric suffix.
     */
    public function generateRandomSuffix(int $length = 5): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charLength = strlen($characters);
        $random = '';

        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[random_int(0, $charLength - 1)];
        }

        return $random;
    }

    /**
     * Apply optional prefix and suffix segments.
     */
    protected function applyAffixes(string $slug, string $separator, array $options): string
    {
        if (! empty($options['prefix'])) {
            $prefix = $this->handlePreserveUnicode((string) $options['prefix'], $separator);
            if ($prefix !== '') {
                $slug = $prefix.$separator.$slug;
            }
        }

        if (! empty($options['suffix'])) {
            $suffix = $this->handlePreserveUnicode((string) $options['suffix'], $separator);
            if ($suffix !== '') {
                $slug .= $separator.$suffix;
            }
        }

        return $slug;
    }

    /**
     * Append a numeric disambiguator when the slug is a reserved word.
     */
    protected function disambiguateReserved(string $slug, string $separator): string
    {
        $candidate = $slug;
        $index = 1;

        while (ReservedSlugs::isReserved($candidate)) {
            $candidate = $slug.$separator.$index;
            $index++;
        }

        return $candidate;
    }

    /**
     * Finalize fallback string.
     */
    protected function finalizeFallback(string|false|null $fallback, string $separator, array $options): string
    {
        if ($fallback === false || $fallback === null || $fallback === '') {
            return '';
        }

        $slug = $fallback;

        if ($this->shouldAppendRandomSuffix($options)) {
            $length = $this->resolveRandomSuffixLength($options);
            $slug .= $separator.$this->generateRandomSuffix($length);
        }

        return $slug;
    }
}
