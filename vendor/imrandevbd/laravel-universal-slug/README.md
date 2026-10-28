# Laravel Universal Slug 🌐

[![Latest Version on Packagist](https://img.shields.io/packagist/v/imrandevbd/laravel-universal-slug.svg?style=flat-square)](https://packagist.org/packages/imrandevbd/laravel-universal-slug)
[![Total Downloads](https://img.shields.io/packagist/dt/imrandevbd/laravel-universal-slug.svg?style=flat-square)](https://packagist.org/packages/imrandevbd/laravel-universal-slug)
[![License](https://img.shields.io/packagist/l/imrandevbd/laravel-universal-slug.svg?style=flat-square)](https://packagist.org/packages/imrandevbd/laravel-universal-slug)
[![Tests Passing](https://img.shields.io/badge/tests-62%20passed-brightgreen.svg?style=flat-square)](https://github.com/imrandevbd/laravel-universal-slug)

A fast, Unicode-safe URL slug generator for Laravel 10–13. Generate clean, SEO-ready slugs in **any language and writing system**—Bengali (বাংলা), Arabic (العربية), Hindi (हिन्दी), Chinese (中文), Japanese (日本語), Korean (한국어), Russian (Русский), Greek, Hebrew, Urdu, and European accented text.

Built for production: **zero spaces / no `%20`**, **automatic 301 redirects**, **PHP 8 attributes**, **reserved-path protection**, **lifecycle events**, **atomic uniqueness locks**, **queued regeneration**, **locale-scoped uniqueness**, **admin history security**, **multilingual stopwords**, **Artisan CLI**, **validation rules**, and **Blade directives**.

**Requirements:** PHP 8.1–8.5 · Laravel 10 / 11 / 12 / 13 · `ext-mbstring`

---

## 📑 Table of Contents

- [Features](#-features)
- [Installation](#-installation)
- [Requirements](#-requirements)
- [Quick Start](#-quick-start)
  - [1. Global Helpers](#1-global-helpers)
  - [2. Fluent Builder `UniversalSlug::of()`](#2-fluent-builder-universalslugof)
  - [3. Static `UniversalSlug::generate()`](#3-static-universalsluggenerate)
  - [4. Str & Stringable Macros](#4-str--stringable-macros)
  - [5. Facade](#5-facade)
  - [6. Blade Directive](#6-blade-directive)
  - [7. Validation Rules](#7-validation-rules)
- [Generation Modes](#-generation-modes)
- [Multilingual Examples](#-multilingual-examples)
- [Word Limits, Length, Suffixes & Affixes](#-word-limits-length-suffixes--affixes)
- [Multilingual Stopwords](#-multilingual-stopwords)
- [Reserved Route Words](#-reserved-route-words)
- [Symbol Replacements & Custom Processors](#-symbol-replacements--custom-processors)
- [Eloquent Model Integration](#-eloquent-model-integration)
  - [Basic Setup](#basic-model-setup)
  - [PHP 8 `#[Slug]` Attribute](#php-8-slug-attribute)
  - [SlugOptions API](#slugoptions-api)
  - [Collision Suffix Strategies](#collision-suffix-strategies)
  - [Multiple Slug Fields](#multiple-slug-fields)
  - [Source from Multiple Columns or a Closure](#source-from-multiple-columns-or-a-closure)
  - [Multi-Tenant Scoped Uniqueness](#multi-tenant-scoped-uniqueness)
  - [Locale-scoped Uniqueness](#locale-scoped-uniqueness)
  - [Soft Deletes](#soft-deletes)
  - [Immutable, Skip-if-filled & Lifecycle Flags](#immutable-skip-if-filled--lifecycle-flags)
  - [Prefix & Suffix](#prefix--suffix)
  - [Manual Slug Override](#manual-slug-override)
  - [Preview & Force Regenerate](#preview--force-regenerate)
  - [Model Finding Helpers](#model-finding-helpers)
  - [Route Key Binding](#route-key-binding)
- [SEO Slug History & 301 Redirects](#-seo-slug-history--301-redirects)
  - [Setup](#history-setup)
  - [Zero-config Route Binding Redirects](#zero-config-route-binding-redirects)
  - [Controller Helpers](#controller-helpers)
  - [Do Not Recycle Old Slugs](#do-not-recycle-old-slugs)
  - [Admin History Security](#admin-history-security)
- [Lifecycle Events](#-lifecycle-events)
- [Queued Regeneration](#-queued-regeneration)
- [Atomic Uniqueness Lock](#-atomic-uniqueness-lock)
- [Artisan CLI Commands](#-artisan-cli-commands)
- [Scheduled Prune](#-scheduled-prune)
- [Complete API Reference](#-complete-api-reference)
- [Configuration Reference](#-configuration-reference)
- [Recipes / Common Use Cases](#-recipes--common-use-cases)
- [Testing](#-testing)
- [License](#-license)

---

## 🌟 Features

| Area | What you get |
| :--- | :--- |
| **Universal scripts** | Keeps letters, vowels, marks, and Nuktas. NFC normalize + Indic recomposition. |
| **Zero-space URLs** | Never emits whitespace or `%20`. Collapses dashes and multilingual punctuation. |
| **3 engine modes** | `preserve_unicode` (default), `transliterate`, `ascii_only`. |
| **Eloquent trait** | Auto unique slugs on create/update: `my-slug`, `my-slug-1`, `my-slug-2`. |
| **PHP 8 attribute** | `#[Slug(from: 'title')]` — no `getSlugOptions()` needed. |
| **301 history** | `HasSlugHistory` archives old slugs; `{post:slug}` 301-redirects automatically. |
| **Reserved words** | Never assign `admin`, `api`, `login`, `filament`, `livewire`, etc. |
| **Stopwords** | Built-in `en`, `bn`, `ar`, `hi`, `fr`, `es`, `de` + runtime extend. |
| **Word / length limits** | First N words and/or max UTF-8 character length. |
| **Random suffix** | 2–9+ alphanumeric chars (`my-slug-8xf2k`). |
| **Collision strategies** | `numeric`, `random`, `timestamp`, `date`. |
| **Prefix / suffix** | `news-my-title-live`. |
| **Locale uniqueness** | Same title can share a slug across `en` / `bn` / `ar`. |
| **Tenant uniqueness** | `uniqueScope()` for multi-tenant apps. |
| **Immutable / skip-if-filled** | Freeze after publish, or keep editor-supplied slugs. |
| **Soft deletes** | Optionally treat trashed rows as collisions. |
| **Events** | `SlugGenerated`, `SlugChanged`. |
| **Validation** | `Rule::universalSlug()` + `Rule::uniqueUniversalSlug()`. |
| **CLI** | `slug:generate`, `slug:regenerate`, `slug:prune`, `slug:audit`. |
| **Queue job** | `RegenerateSlugsJob` for large tables. |
| **Cache lock** | Race-safe uniqueness under concurrent creates. |
| **Admin security** | History list only for admin / superadmin / custom gate. |
| **Blade / Str / helpers** | `@slug()`, `Str::universalSlug()`, `universal_slug()`, `preview_slug()`, `slug_is_reserved()`. |

---

## 📦 Installation

```bash
composer require imrandevbd/laravel-universal-slug
```

The service provider and `UniversalSlug` facade alias are auto-discovered.

Publish config and the optional history migration:

```bash
php artisan vendor:publish --tag="universal-slug-config"
php artisan vendor:publish --tag="universal-slug-migrations"
php artisan migrate
```

Composer scripts (in this package):

```bash
composer test    # vendor/bin/pest
composer lint    # vendor/bin/pint --test
composer fix     # vendor/bin/pint
```

---

## ✅ Requirements

| Dependency | Versions |
| :--- | :--- |
| PHP | `^8.1` `^8.2` `^8.3` `^8.4` `^8.5` |
| Laravel / Illuminate | `^10` `^11` `^12` `^13` |
| Extension | `ext-mbstring` |
| Optional | `intl` (better transliteration), cache store that supports locks |

---

## 🚀 Quick Start

### 1. Global Helpers

```php
// Any language
universal_slug('রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা');
// রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা

// Named arguments
universal_slug('রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা ও তার কারণ অনুসন্ধান', word_limit: 7);
universal_slug('আমার পোস্ট টাইটেল', random_suffix: 5);
universal_slug('???!!!', fallback: 'untitled');

// Language + options array
universal_slug('ঢাকা এবং চট্টগ্রাম ও রাজশাহী থেকে সিলেট', language: 'bn', options: [
    'remove_stopwords' => true,
]);
// ঢাকা-চট্টগ্রাম-রাজশাহী-সিলেট

// Preview (same engine as universal_slug)
preview_slug('Hello World'); // hello-world

// Reserved-word check
slug_is_reserved('admin');   // true
slug_is_reserved('my-post'); // false
```

**`universal_slug()` signature**

```php
universal_slug(
    ?string $title,
    ?string $separator = null,
    ?string $language = null,
    array $options = [],
    bool|int|null $random_suffix = null,
    ?int $word_limit = null,
    ?string $fallback = null,
): string
```

### 2. Fluent Builder `UniversalSlug::of()`

```php
use ImranDev\UniversalSlug\UniversalSlug;

$slug = UniversalSlug::of('রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা ও বিস্তারিত খবর')
    ->words(7)
    ->removeStopwords()
    ->randomSuffix(5)
    ->toString();

$slug = UniversalSlug::of('বাংলা শিরোনাম')->onlySlug()->toString();

$slug = UniversalSlug::of('Hello World')
    ->separator('_')
    ->language('en')
    ->mode('transliterate')
    ->maxLength(40)
    ->lowercase()
    ->fallback('untitled')
    ->prefix('blog')
    ->suffix('en')
    ->avoidReserved()
    ->withOptions(['strip_emojis' => true]);

(string) UniversalSlug::of('Hello World'); // Stringable
```

### 3. Static `UniversalSlug::generate()`

```php
use ImranDev\UniversalSlug\UniversalSlug;

UniversalSlug::generate('Hello World');
UniversalSlug::generate('Hello World', '_', 'en', ['word_limit' => 2]);
UniversalSlug::generateOnlySlug('Hello World', options: ['random_suffix' => 5]); // suffix forced off
```

### 4. Str & Stringable Macros

```php
use Illuminate\Support\Str;

Str::universalSlug('রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা');
Str::universalSlug('Hello World', '_', 'en', ['mode' => 'ascii_only']);

str('রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা')->universalSlug();
str('Hello World')->universalSlug('_', 'en', ['word_limit' => 1]);
```

### 5. Facade

```php
use ImranDev\UniversalSlug\Facades\UniversalSlug;

UniversalSlug::generate('Hello World');
UniversalSlug::of('Hello World')->words(2)->toString();
UniversalSlug::rule();
UniversalSlug::uniqueRule(\App\Models\Post::class);
```

The container binding is `universal-slug`.

### 6. Blade Directive

```blade
<a href="/articles/@slug($post->title)">{{ $post->title }}</a>

{{-- Extra arguments are passed through to UniversalSlug::generate() --}}
@slug($post->title, '-', 'bn', ['word_limit' => 7])
```

### 7. Validation Rules

**Format rule** — value must already be a clean slug (no spaces; regenerating it must not change it):

```php
use Illuminate\Validation\Rule;
use ImranDev\UniversalSlug\Rules\UniversalSlugRule;
use ImranDev\UniversalSlug\UniversalSlug;

$request->validate([
    'slug' => ['required', Rule::universalSlug()],
    'slug' => ['required', new UniversalSlugRule(separator: '-', language: 'bn')],
    'slug' => ['required', UniversalSlug::rule('_')],
]);
```

**Uniqueness rule** — not taken on the model, not reserved, and (by default) not an archived historical slug:

```php
use Illuminate\Validation\Rule;
use ImranDev\UniversalSlug\UniversalSlug;

$request->validate([
    'slug' => [
        'required',
        Rule::universalSlug(),
        Rule::uniqueUniversalSlug(Post::class)
            ->ignore($post)                          // ignore current row on update
            ->where(fn ($q) => $q->where('tenant_id', $tenantId))
            ->withoutHistory()                       // skip slug_histories check
            ->withoutReserved(),                     // allow reserved words
    ],
]);

// Same rule via the class
UniversalSlug::uniqueRule(Post::class, 'slug')->ignore($post->id);
```

---

## ⚡ Generation Modes

Set globally in `config/universal-slug.php` as `mode`, or per call / per model.

| Mode | Behavior | Example |
| :--- | :--- | :--- |
| `preserve_unicode` *(default)* | Keep native scripts. Best for local SEO. | `你好-世界` |
| `transliterate` | Latin/ASCII via `intl` Transliterator, then Laravel `Str::transliterate`. | `lete-a-paris` |
| `ascii_only` | Strict `[a-zA-Z0-9]` after ASCII conversion. | `bonjour-le-cafe` |

```php
UniversalSlug::of('Bonjour le café & l\'été!')->mode('transliterate')->toString();
// bonjour-le-cafe-and-lete

SlugOptions::create()->usingMode('ascii_only');
```

The engine also:

- Forces valid UTF-8
- Strips HTML tags (optional) and decodes entities
- Strips emojis (optional)
- Removes zero-width / invisible characters
- Normalizes dash variants (`– — − ‑ ‒ ―`)
- Strips multilingual punctuation (`। ॥ ، ؟` and CJK punctuation)
- NFC-normalizes and recomposes Indic Nukta composites (`ড + ়` → `ড়`)
- Removes quotes/apostrophes cleanly (`l'été` → `lete`)
- Collapses separators and trims them from both ends
- Truncates on UTF-8 character boundaries (never splits a letter)

---

## 🌍 Multilingual Examples

| Language / Script | Input | Slug |
| :--- | :--- | :--- |
| **Bengali** | `রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা` | `রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা` |
| **Arabic** | `أخبار التكنولوجيا والذكاء الاصطناعي اليوم؟` | `أخبار-التكنولوجيا-والذكاء-الاصطناعي-اليوم` |
| **Hindi** | `नमस्ते भारत! कृत्रिम बुद्धिमत्ता २०२६` | `नमस्ते-भारत-कृत्रिम-बुद्धिमत्ता-२०२६` |
| **Chinese** | `你好，世界！2026 年人工智能技术。` | `你好-世界-2026-年人工智能技术` |
| **Japanese** | `こんにちは世界！最新のLaravelパッケージ` | `こんにちは世界-最新のlaravelパッケージ` |
| **Korean** | `안녕하세요 세계! 2026년 최고의 패키지` | `안녕하세요-세계-2026년-최고의-패키지` |
| **Russian** | `Привет, мир! Новейшие технологии веб-разработки` | `привет-мир-новейшие-технологии-веб-разработки` |
| **Greek** | `Γειά σου Κόσμε! Προγραμματισμός 2026` | `γειά-σου-κόσμε-προγραμματισμός-2026` |
| **Hebrew** | `שלום עולם! פיתוח תוכנה מודרני` | `שלום-עולם-פיתוח-תוכנה-מודרני` |
| **French** | `L'été à Paris & Café 100%!` | `lete-a-paris-and-cafe-100` |

HTML + emoji cleanup:

```php
UniversalSlug::generate('<h1>🔥 Best Laravel Package for 2026! 🚀</h1>');
// best-laravel-package-for-2026
```

---

## ✂️ Word Limits, Length, Suffixes & Affixes

```php
// First 7 words
universal_slug($title, word_limit: 7);
UniversalSlug::of($title)->words(7)->toString();

// Max UTF-8 characters (never cuts mid-character; trims trailing separator)
UniversalSlug::of($title)->maxLength(20)->toString();

// Random alphanumeric suffix (cryptographically secure)
universal_slug($title, random_suffix: 2);  // …-9z
universal_slug($title, random_suffix: 5);  // …-8xf2k
universal_slug($title, random_suffix: 9);  // …-3km8z0x9q
UniversalSlug::of($title)->onlySlug()->toString(); // never append random suffix

// Prefix / suffix segments (also slugged)
UniversalSlug::of('Hello World')->prefix('blog')->suffix('en')->toString();
// blog-hello-world-en
```

Empty / symbol-only input uses the fallback (default `default`):

```php
UniversalSlug::generate('???!!!***');                    // default
UniversalSlug::generate('', options: ['fallback' => 'untitled']);
```

---

## 🚫 Multilingual Stopwords

Built-in dictionaries: **`en`**, **`bn`**, **`ar`**, **`hi`**, **`fr`**, **`es`**, **`de`**. Unknown language codes fall back to English.

```php
universal_slug('The quick brown fox jumps over the lazy dog and a cat', options: [
    'remove_stopwords' => true,
]);
// quick-brown-fox-jumps-lazy-dog-cat

universal_slug('ঢাকা এবং চট্টগ্রাম ও রাজশাহী থেকে সিলেট', language: 'bn', options: [
    'remove_stopwords' => true,
]);
// ঢাকা-চট্টগ্রাম-রাজশাহী-সিলেট
```

If removing stopwords would leave the string empty, the original words are kept.

**Extend or replace at runtime:**

```php
use ImranDev\UniversalSlug\Data\Stopwords;
use ImranDev\UniversalSlug\UniversalSlug;

Stopwords::extend('bn', ['বিশেষ', 'ব্রেকিং', 'সংবাদ']);
Stopwords::set('bn', ['এবং', 'ও']);          // replace the whole list
Stopwords::get('bn');                         // array of words

UniversalSlug::extendStopwords('en', ['breaking', 'exclusive']);
```

---

## 🚫 Reserved Route Words

Eloquent models treat reserved words as **already taken** and append a unique suffix (`admin` → `admin-1`). Standalone `universal_slug()` does **not** change reserved words unless you opt in.

**Default reserved list** (config `reserved`):

`admin`, `administrator`, `superadmin`, `api`, `login`, `logout`, `register`, `signup`, `signin`, `auth`, `oauth`, `callback`, `dashboard`, `settings`, `profile`, `account`, `user`, `users`, `livewire`, `filament`, `horizon`, `telescope`, `nova`, `pulse`, `sanctum`, `broadcasting`, `graphql`, `sitemap`, `robots`, `feed`, `rss`, `search`, `tags`, `categories`, `category`, `admin-panel`, `backend`, `console`, `webhook`, `webhooks`, `null`, `undefined`, `new`, `edit`, `create`, `delete`, `update`

```php
use ImranDev\UniversalSlug\Support\ReservedSlugs;
use ImranDev\UniversalSlug\UniversalSlug;

UniversalSlug::of('Admin')->avoidReserved()->toString(); // admin-1
slug_is_reserved('login');                               // true

ReservedSlugs::extend(['billing', 'checkout']);
ReservedSlugs::set(['only-these']);
ReservedSlugs::all();
ReservedSlugs::flush(); // clear runtime extras (tests)

SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->protectReserved(false); // allow reserved words on this model
```

---

## 🔤 Symbol Replacements & Custom Processors

Global and per-language maps live in config. Language maps **override** the global map for that code.

```php
UniversalSlug::generate('PHP & Laravel @ 2026');
// php-and-laravel-at-2026

UniversalSlug::generate('ঢাকা & চট্টগ্রাম @ বাংলাদেশ', '-', 'bn');
// ঢাকা-এবং-চট্টগ্রাম-এট-বাংলাদেশ
```

Built-in language maps: `bn`, `ar`, `hi`, `fr`, `es`, `de`.

Per-model replacements:

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->replacements(['™' => 'tm', '©' => 'copy']);
```

**Custom language processor** (runs after replacements, before stopwords):

```php
use ImranDev\UniversalSlug\UniversalSlug;

UniversalSlug::registerProcessor('ja', function (string $str, string $separator, array $opts): string {
    // Custom Japanese normalization...
    return $str;
});
```

---

## 🗄️ Eloquent Model Integration

### Basic Model Setup

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ImranDev\UniversalSlug\SlugOptions;
use ImranDev\UniversalSlug\Traits\HasUniversalSlug;

class Post extends Model
{
    use HasUniversalSlug;

    protected $fillable = ['title', 'slug', 'content'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->withWordLimit(7)
            ->withoutStopwords();
    }
}
```

Creating `Post::create(['title' => '...'])` fills `slug` automatically. Updating `title` regenerates `slug` unless you freeze it.

### PHP 8 `#[Slug]` Attribute

Skip `getSlugOptions()` on simple models. Repeat the attribute for multiple columns.

```php
use Illuminate\Database\Eloquent\Model;
use ImranDev\UniversalSlug\Attributes\Slug;
use ImranDev\UniversalSlug\Traits\HasUniversalSlug;

#[Slug(from: 'title', words: 7, reserved: true)]
#[Slug(from: 'author_name', to: 'author_slug', separator: '_')]
class Post extends Model
{
    use HasUniversalSlug;
}
```

| Argument | Type | Default | Meaning |
| :--- | :--- | :--- | :--- |
| `from` | `string` | `title` | Source attribute |
| `to` | `string` | `slug` | Destination column |
| `words` | `?int` | `null` | Word limit |
| `maxLength` | `?int` | `null` | Max characters |
| `separator` | `?string` | `null` | Separator |
| `language` | `?string` | `null` | Language code |
| `mode` | `?string` | `null` | Engine mode |
| `stopwords` | `bool` | `false` | Strip stopwords |
| `randomSuffix` | `bool\|int` | `false` | Random suffix length |
| `strategy` | `string` | `numeric` | Collision strategy |
| `prefix` / `suffix` | `?string` | `null` | Affixes |
| `immutable` | `bool` | `false` | Never change after first save |
| `skipIfFilled` | `bool` | `false` | Keep existing slug |
| `routeKey` | `bool` | `false` | `{post}` binds by slug |
| `reserved` | `bool` | `true` | Protect reserved words |
| `dontReuseHistorical` | `bool` | `true` | Do not recycle archived slugs |
| `locale` | `?string` | `null` | Locale column for uniqueness |

For tenant closures or computed sources, implement `getSlugOptions()` instead (it wins over the attribute).

### SlugOptions API

Every fluent setter returns `$this`.

| Method | Purpose |
| :--- | :--- |
| `create()` | New options instance |
| `generateSlugsFrom($field)` | `string`, `array` of columns, or `callable($model): string` |
| `saveSlugsTo($field)` | Destination column |
| `usingSeparator($char)` | `-`, `_`, etc. |
| `usingLanguage($code)` | `bn`, `ar`, `en`, … |
| `usingMode($mode)` | `preserve_unicode` / `transliterate` / `ascii_only` |
| `lowercase($bool)` | Multibyte lowercasing |
| `withWordLimit($n)` / `words($n)` | First N words |
| `withoutStopwords()` / `removeStopwords()` | Strip filler words |
| `maxSlugLength($n)` | Max UTF-8 length |
| `withRandomSuffix($n)` / `randomSuffix($n)` | Random suffix |
| `onlySlug()` | Disable random suffix |
| `usingSuffixStrategy($s)` | `numeric` / `random` / `timestamp` / `date` |
| `usingSuffixSeparator($char)` | Collision suffix separator |
| `startSuffixAt($n)` | First numeric suffix (default `1`) |
| `fallback($string)` | Empty-input fallback |
| `replacements($map)` | Extra symbol map |
| `withPrefix($text)` / `withSuffix($text)` | Affixes |
| `protectReserved($bool)` | Reserved-word protection (default on) |
| `immutable($bool)` | Freeze after first slug |
| `skipIfFilled($bool)` | Do not overwrite a stored slug |
| `useAsRouteKey($bool)` | Implicit `{post}` uses slug |
| `dontReuseHistoricalSlugs($bool)` | Treat history as taken |
| `usingLocale($column)` | Scope uniqueness by locale column |
| `uniqueScope($callback)` | Extra uniqueness query (`fn ($q, $model)`) |
| `includeTrashed($bool)` | Soft-deleted rows count as collisions |
| `allowDuplicates($bool)` | Skip uniqueness |
| `doNotGenerateSlugsOnCreate()` | Skip on create |
| `doNotGenerateSlugsOnUpdate()` | Skip on update |
| `generateSlugsOnUpdate($bool)` | Toggle update generation |

### Collision Suffix Strategies

When the base slug is taken:

| Strategy | Result |
| :--- | :--- |
| `numeric` *(default)* | `my-slug-1`, `my-slug-2`, … |
| `random` | `my-slug-a8k3x` |
| `timestamp` | `my-slug-1718294400` |
| `date` | `my-slug-2026-08-30` (falls back to numeric if that date exists) |

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->usingSuffixStrategy('numeric')
    ->startSuffixAt(1)
    ->usingSuffixSeparator('-');
```

### Multiple Slug Fields

```php
public function getSlugOptions(): array
{
    return [
        SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug'),
        SlugOptions::create()->generateSlugsFrom('author_name')->saveSlugsTo('author_slug')->usingSeparator('_'),
    ];
}
```

### Source from Multiple Columns or a Closure

```php
SlugOptions::create()
    ->generateSlugsFrom(['brand', 'name'])
    ->saveSlugsTo('slug');
// "Acme" + "Widget" → acme-widget

SlugOptions::create()
    ->generateSlugsFrom(fn ($model) => $model->title.' '.$model->city)
    ->saveSlugsTo('slug');
```

Closures are treated as “always changed” on update (slug regenerates every save unless `immutable()` / `skipIfFilled()` / `doNotGenerateSlugsOnUpdate()`).

### Multi-Tenant Scoped Uniqueness

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->uniqueScope(function ($query, $model) {
        $query->where('tenant_id', $model->tenant_id);
    });
```

Two tenants can both own `hello-world`.

### Locale-scoped Uniqueness

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->usingLocale('locale');
```

`Hello World` in `en` and `bn` can both be `hello-world`. A second English row becomes `hello-world-1`.

### Soft Deletes

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->includeTrashed(true);  // default: trashed rows still occupy the slug
```

Set `includeTrashed(false)` to reuse slugs of soft-deleted rows.

### Immutable, Skip-if-filled & Lifecycle Flags

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->immutable();           // never change after first successful slug

SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->skipIfFilled();        // keep whatever is already stored

SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->doNotGenerateSlugsOnUpdate();

SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->doNotGenerateSlugsOnCreate();

SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->allowDuplicates();    // no -1 / -2 suffixes
```

### Prefix & Suffix

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->withPrefix('news')
    ->withSuffix('live');
// news-hello-world-live
```

### Manual Slug Override

If you set `slug` on create/update, that value is cleaned through the engine (not the title) and then made unique. Use `skipIfFilled()` if you do not want later title edits to replace it.

### Preview & Force Regenerate

```php
$post->previewSlug();                // from current source fields
$post->previewSlug('Another Title'); // dry-run a new title
$post->regenerateSlug();             // rewrite attributes, do not save
$post->regenerateSlug(save: true);   // rewrite and persist
```

### Model Finding Helpers

```php
Post::findBySlug('hello-world');
Post::findBySlugOrFail('hello-world');          // 404 if missing
Post::whereSlug('hello-world')->get();
Post::findBySlug('hello-world', 'custom_slug'); // other column
```

### Route Key Binding

```php
// Explicit column (recommended)
Route::get('/posts/{post:slug}', [PostController::class, 'show']);

// Implicit {post} uses slug instead of id
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->useAsRouteKey();
```

`$post->getRouteKeyName()` returns `slug` when `useAsRouteKey()` is on.

---

## 🔄 SEO Slug History & 301 Redirects

When a slug changes, the old value is stored in `slug_histories` so bookmarks and search results keep working.

### History Setup

1. Publish and run the migration (`--tag="universal-slug-migrations"`).
2. Use both traits:

```php
use ImranDev\UniversalSlug\Traits\HasSlugHistory;
use ImranDev\UniversalSlug\Traits\HasUniversalSlug;

class Post extends Model
{
    use HasUniversalSlug;
    use HasSlugHistory;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
```

Optional hooks on the model:

```php
public function shouldRecordSlugHistory(): bool { return true; }
public function getSlugHistoryField(): string { return 'slug'; }
public function getMaxSlugHistoryEntries(): int { return 5; }
```

On force-delete, related history rows are removed. Soft-delete keeps them.

### Zero-config Route Binding Redirects

With both traits, `{post:slug}` (or `useAsRouteKey()`) **301-redirects** archived slugs. JSON clients get `{ "message": "...", "redirect": "..." }` plus a `Location` header. Query strings are preserved.

```php
Route::get('/posts/{post:slug}', [PostController::class, 'show']);
// /posts/old-title  →  301  /posts/new-title
```

This uses Laravel’s `SubstituteBindings` middleware (already in the default `web` / `api` groups).

```php
'history' => [
    'enabled' => true,
    'auto_redirect' => true,  // set false to resolve old slugs without redirecting
    'dont_reuse' => true,
    'max_entries_per_model' => 5,
    'prune_after_days' => 90,
],
```

### Controller Helpers

```php
// Array: ['model' => Post, 'was_redirected' => bool] or null
$result = Post::findWithHistory($slug, field: 'slug', scopeCallback: function ($query) {
    $query->where('status', 'published');
});

if (! $result) {
    abort(404);
}

if ($result['was_redirected']) {
    return redirect()->route('posts.show', $result['model']->slug, 301);
}

return view('posts.show', ['post' => $result['model']]);
```

Shortcut that 301s or 404s for you:

```php
$post = Post::findOrRedirect($slug);
// throws SlugRedirectException (rendered as 301) or ModelNotFoundException
```

```php
$post->slugHistories;              // MorphMany SlugHistory
$post->recordSlugHistory($old);    // manual archive + retention trim
```

### Do Not Recycle Old Slugs

By default a new row will not take a slug that still lives in history (`hello-world` archived → next create gets `hello-world-1`). That keeps the 301 mapping valid.

```php
->dontReuseHistoricalSlugs(false)
```

### Admin History Security

History listing is **not** public. `getSlugHistoryForAdmin()` returns an empty collection unless the user is allowed.

Allowed by default when any of these match:

- Config permission (`history.permission`) via `$user->can()`
- Spatie-style `hasAnyRole()` / `hasRole()` against `history.allowed_roles`
- `$user->role` in `admin` / `superadmin` (case-insensitive)
- `$user->is_admin === true`, `isAdmin()`, or `isSuperAdmin()`

```php
$histories = $post->getSlugHistoryForAdmin();
if ($post->canViewSlugHistory()) {
    // admin UI
}

use ImranDev\UniversalSlug\UniversalSlug;

UniversalSlug::authorizeHistory(function ($user) {
    return $user && ($user->hasRole(['admin', 'superadmin']) || $user->is_super_admin);
});

UniversalSlug::canViewHistory($user);
```

---

## 📡 Lifecycle Events

Fired **after** the model is saved.

```php
use ImranDev\UniversalSlug\Events\SlugChanged;
use ImranDev\UniversalSlug\Events\SlugGenerated;

Event::listen(SlugGenerated::class, function (SlugGenerated $event) {
    // $event->model  $event->slug  $event->field
});

Event::listen(SlugChanged::class, function (SlugChanged $event) {
    // $event->model  $event->oldSlug  $event->newSlug  $event->field
    // Refresh Scout, sitemap, CDN, ...
});
```

`SlugChanged` fires only when an **existing** row’s slug is replaced.

---

## 🧵 Queued Regeneration

```php
use ImranDev\UniversalSlug\Jobs\RegenerateSlugsJob;

RegenerateSlugsJob::dispatch(Post::class);                    // force all rows
RegenerateSlugsJob::dispatch(Post::class, force: false);      // empty slugs only
RegenerateSlugsJob::dispatch(Post::class, force: true, chunk: 200);
```

The job implements `ShouldQueue` and calls `regenerateSlug(save: true)` per row.

---

## 🔒 Atomic Uniqueness Lock

Uniqueness checks run inside a cache lock (`universal-slug:{morph}:{field}:{base}`) so concurrent creates of the same title do not race to the same slug.

```php
'unique_lock' => [
    'enabled' => true,
    'seconds' => 10,
],
```

If the lock cannot be acquired, uniqueness still runs without the lock. Use Redis/database cache in production for the lock to matter.

---

## 💻 Artisan CLI Commands

### `slug:generate` — try a slug in the terminal

```bash
php artisan slug:generate "রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা"
php artisan slug:generate "রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা" --words=7 --suffix=5
php artisan slug:generate "Bonjour le café!" --mode=transliterate
php artisan slug:generate "The quick brown fox" --no-stopwords --separator=_ --lang=en
```

| Argument / option | Meaning |
| :--- | :--- |
| `text` | Source string |
| `--separator=` | Default `-` |
| `--lang=` | Language code |
| `--words=` | Word limit |
| `--suffix=` | Random suffix length |
| `--mode=` | `preserve_unicode` / `transliterate` / `ascii_only` |
| `--no-stopwords` | Strip stopwords |

### `slug:regenerate` — rebuild stored slugs

```bash
php artisan slug:regenerate "App\Models\Post" --dry-run
php artisan slug:regenerate "App\Models\Post" --field=title --slug=slug --chunk=200 --force
```

| Option | Meaning |
| :--- | :--- |
| `model` | FQCN |
| `--field=` | Source column (default `title`) |
| `--slug=` | Slug column (default `slug`) |
| `--chunk=` | Chunk size (default `200`) |
| `--force` | Rebuild even if slug is already filled |
| `--dry-run` | Preview only |

Uses `regenerateSlug(save: true)` when the model has the trait.

### `slug:prune` — delete old history rows

```bash
php artisan slug:prune --days=90
php artisan slug:prune --days=60 --model="App\Models\Post"
```

### `slug:audit` — production health check

```bash
php artisan slug:audit "App\Models\Post"
php artisan slug:audit "App\Models\Post" --slug=slug --json
```

Reports **empty slugs**, **duplicates**, and **reserved-word** collisions. Exit code `1` when issues exist.

---

## ⏰ Scheduled Prune

```php
'schedule' => [
    'prune' => true, // register daily: php artisan slug:prune --days={history.prune_after_days}
],
```

Or schedule it yourself:

```php
$schedule->command('slug:prune', ['--days' => 90])->daily();
$schedule->command('slug:audit', ['App\\Models\\Post'])->weekly();
```

---

## 📚 Complete API Reference

### Helpers

| Function | Returns |
| :--- | :--- |
| `universal_slug(...)` | `string` |
| `preview_slug(...)` | `string` |
| `slug_is_reserved($slug)` | `bool` |

### `UniversalSlug` (class / facade)

| Method | Returns |
| :--- | :--- |
| `of($title)` | fluent instance |
| `generate(...)` / `make(...)` / `toString()` / `__toString()` | `string` |
| `generateOnlySlug(...)` | `string` (no random suffix) |
| `separator()` `language()` `words()` `wordLimit()` | fluent |
| `removeStopwords()` `withoutStopwords()` | fluent |
| `randomSuffix()` `withRandomSuffix()` `onlySlug()` | fluent |
| `mode()` `maxLength()` `fallback()` `lowercase()` | fluent |
| `prefix()` `suffix()` `avoidReserved()` `withOptions()` | fluent |
| `rule()` | `UniversalSlugRule` |
| `uniqueRule($model, $column)` | `UniqueUniversalSlugRule` |
| `registerProcessor($lang, $callback)` | `void` |
| `extendStopwords($lang, $words)` | `void` |
| `authorizeHistory($callback)` | `void` |
| `canViewHistory($user)` | `bool` |
| `getEngine()` | `SlugEngine` |

### `HasUniversalSlug` trait

| Member | Role |
| :--- | :--- |
| `getSlugOptions()` | Required unless `#[Slug]` is present |
| `previewSlug()` / `regenerateSlug()` | Dry-run / rebuild |
| `findBySlug()` / `findBySlugOrFail()` / `scopeWhereSlug()` | Lookups |
| `getRouteKeyName()` / `resolveRouteBinding()` | Routing + 301 |
| `resolvePrimarySlugOptions()` | First options set |

### `HasSlugHistory` trait

| Member | Role |
| :--- | :--- |
| `slugHistories()` | `MorphMany` |
| `findWithHistory()` | `['model', 'was_redirected']` or `null` |
| `findOrRedirect()` | model, or 301 / 404 |
| `recordSlugHistory()` | Archive + trim |
| `getSlugHistoryForAdmin()` / `canViewSlugHistory()` | Secured listing |
| `shouldRecordSlugHistory()` | Toggle |
| `getMaxSlugHistoryEntries()` | Retention |

### Exceptions & jobs

| Class | Role |
| :--- | :--- |
| `SlugRedirectException` | Renderable 301 (HTML redirect or JSON) |
| `RegenerateSlugsJob` | Queued bulk rebuild |
| `SlugHistory` | Eloquent model for `slug_histories` |

### `ReservedSlugs`

`extend()` · `set()` · `flush()` · `all()` · `isReserved()`

### `Stopwords`

`get()` · `set()` · `extend()`

---

## 🛠️ Configuration Reference

Publish: `php artisan vendor:publish --tag="universal-slug-config"`

```php
return [
    'default_separator' => '-',
    'mode' => 'preserve_unicode', // preserve_unicode | transliterate | ascii_only
    'lowercase' => true,
    'word_limit' => null,
    'max_length' => null,
    'random_suffix' => false,
    'random_suffix_length' => 5,
    'remove_stopwords' => false,
    'fallback' => 'default',
    'strip_tags' => true,
    'strip_emojis' => true,

    'history' => [
        'enabled' => true,
        'max_entries_per_model' => 5,
        'prune_after_days' => 90,
        'allowed_roles' => ['admin', 'superadmin'],
        'permission' => null,          // e.g. 'view-slug-history'
        'auto_redirect' => true,
        'dont_reuse' => true,
    ],

    'reserved' => [
        'admin', 'administrator', 'superadmin', 'api', 'login', 'logout',
        'register', 'signup', 'signin', 'auth', 'oauth', 'callback',
        'dashboard', 'settings', 'profile', 'account', 'user', 'users',
        'livewire', 'filament', 'horizon', 'telescope', 'nova', 'pulse',
        'sanctum', 'broadcasting', 'graphql', 'sitemap', 'robots',
        'feed', 'rss', 'search', 'tags', 'categories', 'category',
        'admin-panel', 'backend', 'console', 'webhook', 'webhooks',
        'null', 'undefined', 'new', 'edit', 'create', 'delete', 'update',
    ],

    'unique_lock' => [
        'enabled' => true,
        'seconds' => 10,
    ],

    'schedule' => [
        'prune' => false,
    ],

    'unique_suffix' => [
        'separator' => '-',
        'start_index' => 1,
    ],

    'replacements' => [
        '@' => 'at', '&' => 'and', '%' => 'percent', '#' => 'number',
        '+' => 'plus', '=' => 'equals', '€' => 'eur', '£' => 'gbp',
        '$' => 'usd', '¥' => 'jpy', '৳' => 'taka', '₹' => 'rupee',
    ],

    'language_replacements' => [
        'bn' => ['&' => 'এবং', '@' => 'এট', '%' => 'শতাংশ', '+' => 'যোগ', '৳' => 'টাকা'],
        'ar' => ['&' => 'و', '%' => 'في المئة', '+' => 'زائد'],
        'hi' => ['&' => 'और', '%' => 'प्रतिशत', '+' => 'धन', '₹' => 'रुपया'],
        'fr' => ['&' => 'et', '@' => 'arobase', '%' => 'pourcent', '+' => 'plus', '€' => 'euro'],
        'es' => ['&' => 'y', '@' => 'arroba', '%' => 'por-ciento', '+' => 'mas', '€' => 'euro'],
        'de' => ['&' => 'und', '@' => 'an', '%' => 'prozent', '+' => 'plus', '€' => 'euro'],
    ],
];
```

Engine option keys you can pass in `options:` / `withOptions()`:

`mode`, `lowercase`, `word_limit`, `max_length`, `random_suffix`, `random_suffix_length`, `remove_stopwords`, `fallback`, `strip_tags`, `strip_emojis`, `replacements`, `language_replacements`, `prefix`, `suffix`, `avoid_reserved`.

---

## 🧩 Recipes / Common Use Cases

**News site, first 7 words, Bengali, unique, 301 on rename**

```php
#[Slug(from: 'title', words: 7, language: 'bn', stopwords: true)]
class Article extends Model
{
    use HasUniversalSlug, HasSlugHistory;
}

Route::get('/news/{article:slug}', [ArticleController::class, 'show']);
```

**SaaS / multi-tenant**

```php
->uniqueScope(fn ($q, $m) => $q->where('tenant_id', $m->tenant_id));
```

**Localized slugs (`en` / `bn` columns or rows)**

```php
->usingLocale('locale');
```

**Published posts: never change the URL again**

```php
->immutable();
```

**Admin can type a custom slug; title edits must not overwrite it**

```php
->skipIfFilled();
```

**ASCII-only feeds / sitemaps for a global brand**

```php
->usingMode('ascii_only')->withPrefix('blog');
```

**Form request**

```php
'slug' => ['nullable', Rule::universalSlug(), Rule::uniqueUniversalSlug(Post::class)->ignore($this->post)],
```

**Scout / sitemap on rename**

```php
Event::listen(SlugChanged::class, fn ($e) => $e->model->searchable());
```

**Backfill a legacy table**

```bash
php artisan slug:audit "App\Models\Post"
php artisan slug:regenerate "App\Models\Post" --dry-run
php artisan slug:regenerate "App\Models\Post" --force
# or
RegenerateSlugsJob::dispatch(\App\Models\Post::class);
```

---

## 🧪 Testing

```bash
vendor/bin/pest
# or
composer test
```

**62 tests** cover generation (Bengali, Arabic, CJK, Cyrillic, Greek, Hebrew), Eloquent uniqueness, history 301s, attributes, reserved words, events, validation, jobs, and Artisan commands.

---

## 📄 License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).

---

## 👨‍💻 Connect & Collaborate

> **Remote Senior Full-Stack Roles · Freelance Contracts · Technical Partnerships · Long-Term Collaborations**  
> in **Laravel · WordPress · React/Next.js · AI-powered Platforms · Security Audits · SaaS Architecture**  
> 📍 **Timezone**: UTC+6 (Dhaka/Rangpur) — flexible overlap for US, EU & Asia  
> ⚡ **Available**: Immediately · Production-first · Fast delivery · Transparent communication

| Platform | Link |
| :--- | :--- |
| 🌐 **Portfolio** | [imrandev.bd](https://imrandev.bd/) |
| 💼 **LinkedIn** | [linkedin.com/in/imranbru99](https://www.linkedin.com/in/imranbru99/) |
| 🐙 **GitHub** | [github.com/imranbru99](https://github.com/imranbru99) |
| 🐦 **X / Twitter** | [@imrandev_bd](https://x.com/imrandev_bd) |
| 📺 **YouTube** | [@ImranDevBD](https://www.youtube.com/@ImranDevBD) |
| 📸 **Instagram** | [@imranbru99](https://www.instagram.com/imranbru99/) |
| 📘 **Facebook** | [ExpertImranDev](https://www.facebook.com/ExpertImranDev/) |
| 🎵 **TikTok** | [@imrandev_bd](https://www.tiktok.com/@imrandev_bd) |
| 🧵 **Threads** | [@imranbru99](https://www.threads.com/@imranbru99) |
| 📌 **Pinterest** | [@imrandev_bd](https://www.pinterest.com/imrandev_bd/) |
| 💬 **WhatsApp** | [+880 1576-918420](http://wa.me/+8801576918420) |
| 📧 **Email** | [me@imrandev.bd](mailto:me@imrandev.bd) |
| 🔗 **All Links** | [linktr.ee/ExpertImranDev](https://linktr.ee/ExpertImranDev) |

> *"Security isn't an add-on — it's the foundation. Scale, speed, and trust drive every line of code I write."*  
> — **Imran Ahmed**
