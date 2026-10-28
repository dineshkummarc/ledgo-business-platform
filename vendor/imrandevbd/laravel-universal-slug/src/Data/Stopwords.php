<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Data;

class Stopwords
{
    /**
     * Multilingual stopwords dictionary.
     *
     * @var array<string, array<string>>
     */
    protected static array $stopwords = [
        'en' => [
            'a', 'about', 'above', 'after', 'again', 'against', 'all', 'am', 'an', 'and', 'any', 'are', 'as', 'at',
            'be', 'because', 'been', 'before', 'being', 'below', 'between', 'both', 'but', 'by', 'during', 'each',
            'for', 'from', 'further', 'had', 'has', 'have', 'having', 'he', 'her', 'here', 'hers', 'herself', 'him',
            'himself', 'his', 'how', 'i', 'if', 'in', 'into', 'is', 'it', 'its', 'itself', 'just', 'me', 'more',
            'most', 'my', 'myself', 'no', 'nor', 'not', 'now', 'of', 'off', 'on', 'once', 'only', 'or', 'other',
            'our', 'ours', 'ourselves', 'out', 'over', 'own', 'same', 'so', 'some', 'such', 'than', 'that', 'the',
            'their', 'theirs', 'them', 'themselves', 'then', 'there', 'these', 'they', 'this', 'those', 'through',
            'to', 'too', 'under', 'until', 'up', 'very', 'was', 'we', 'were', 'what', 'when', 'where', 'which',
            'while', 'who', 'whom', 'why', 'with', 'would', 'you', 'your', 'yours', 'yourself', 'yourselves',
        ],
        'bn' => [
            'এবং', 'ও', 'বা', 'কিংবা', 'অথবা', 'কিন্তু', 'তথাপি', 'হতে', 'থেকে', 'চেয়ে', 'দ্বারা', 'দিয়ে',
            'কর্তৃক', 'জন্য', 'কারণে', 'নিমিত্ত', 'মত', 'মতো', 'মতই', 'সহ', 'সহিত', 'বিনা', 'ব্যতীত',
            'ছাড়া', 'উপর', 'উপরে', 'নিচে', 'ভিতর', 'ভিতরে', 'বাহির', 'বাহিরে', 'মাঝ', 'মাঝে', 'আগে',
            'পরে', 'কাছে', 'দিকে', 'প্রতি', 'ব্যাপারে', 'বিষয়ে', 'সম্পর্কে', 'একটি', 'একটা', 'এই', 'সেই',
            'ঐ', 'যা', 'তা', 'যে', 'সে', 'তিনি', 'তারা', 'তাহার', 'তাদের', 'ইহার', 'এদের', 'আমার',
            'আমাদের', 'তোমার', 'তোমাদের', 'আপনার', 'আপনাদের', 'কি', 'কী', 'কে', 'কার', 'কেন', 'কোথায়',
            'কখন', 'কীভাবে', 'কেমন', 'কত', 'কোন', 'কোনটি', 'হলো', 'হবে', 'ছিল', 'আছে', 'নাই', 'নেই',
        ],
        'ar' => [
            'في', 'من', 'على', 'إلى', 'عن', 'مع', 'أو', 'أن', 'إن', 'و', 'لا', 'ما', 'هذا', 'هذه', 'تم', 'كان',
            'هو', 'هي', 'التي', 'الذي', 'الذين', 'كل', 'قد', 'لم', 'لن', 'ثم', 'حتى', 'إذا', 'بعد', 'قبل',
            'عند', 'بين', 'حول', 'فوق', 'تحت', 'غير', 'ذلك', 'تلك', 'هنا', 'هناك', 'بل', 'لكن', 'فإن',
        ],
        'hi' => [
            'और', 'या', 'का', 'के', 'की', 'को', 'में', 'से', 'पर', 'ने', 'है', 'हैं', 'था', 'थे', 'थी',
            'होना', 'करने', 'किया', 'लिये', 'लिए', 'अपने', 'अपनी', 'यह', 'वह', 'जो', 'तो', 'भी', 'तक',
            'इस', 'उस', 'एक', 'सब', 'कुछ', 'बहुत', 'सकता', 'सकते', 'सकती', 'हो', 'गया', 'गए', 'गई',
        ],
        'fr' => [
            'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'au', 'aux', 'et', 'ou', 'mais', 'donc',
            'or', 'ni', 'car', 'ce', 'cet', 'cette', 'ces', 'dans', 'en', 'par', 'pour', 'sur', 'avec',
            'sans', 'sous', 'est', 'sont', 'que', 'qui', 'quoi', 'dont', 'où',
        ],
        'es' => [
            'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas', 'de', 'del', 'al', 'a', 'y', 'e',
            'o', 'u', 'pero', 'mas', 'aunque', 'en', 'para', 'por', 'con', 'sin', 'sobre', 'este',
            'esta', 'estos', 'estas', 'que', 'cual', 'quien', 'cuyo', 'donde', 'como', 'cuando',
        ],
        'de' => [
            'der', 'die', 'das', 'den', 'dem', 'des', 'ein', 'eine', 'einer', 'einem', 'einen', 'eines',
            'und', 'oder', 'aber', 'denn', 'doch', 'in', 'im', 'von', 'vom', 'zu', 'zum', 'zur', 'mit',
            'auf', 'für', 'an', 'am', 'aus', 'bei', 'nach', 'über', 'unter', 'vor', 'zwischen', 'ist', 'sind',
        ],
    ];

    /**
     * Get stopwords for a given language.
     *
     * @return array<string>
     */
    public static function get(string $language = 'en'): array
    {
        $lang = strtolower(trim($language));

        return static::$stopwords[$lang] ?? static::$stopwords['en'] ?? [];
    }

    /**
     * Add or override stopwords for a language.
     *
     * @param  array<string>  $words
     */
    public static function set(string $language, array $words): void
    {
        $lang = strtolower(trim($language));
        static::$stopwords[$lang] = $words;
    }

    /**
     * Extend stopwords for a language.
     *
     * @param  array<string>  $words
     */
    public static function extend(string $language, array $words): void
    {
        $lang = strtolower(trim($language));
        $existing = static::$stopwords[$lang] ?? [];
        static::$stopwords[$lang] = array_unique(array_merge($existing, $words));
    }
}
