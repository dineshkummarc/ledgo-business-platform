<?php

declare(strict_types=1);

use ImranDev\UniversalSlug\Facades\UniversalSlug;

it('generates correct slug for Bengali text including the user test case', function () {
    $input = 'রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা';
    $expected = 'রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা';

    expect(UniversalSlug::generate($input))->toBe($expected);
    expect(universal_slug($input))->toBe($expected);
});

it('handles Bengali sentence ending punctuation such as Dari and Double Dari', function () {
    $input = 'এটি প্রথম বাক্য। এটি দ্বিতীয় বাক্য॥ তৃতীয় পর্ব!';
    $expected = 'এটি-প্রথম-বাক্য-এটি-দ্বিতীয়-বাক্য-তৃতীয়-পর্ব';

    expect(UniversalSlug::generate($input))->toBe($expected);
});

it('normalizes dash-like characters em-dash en-dash figure dash', function () {
    $input = 'ঢাকা – চট্টগ্রাম — রাজশাহী − সিলেট';
    $expected = 'ঢাকা-চট্টগ্রাম-রাজশাহী-সিলেট';

    expect(UniversalSlug::generate($input))->toBe($expected);
});

it('never leaves any space or produces percent encoded spaces in slug', function () {
    $inputs = [
        "  ঢাকা    \t\n  চট্টগ্রাম   ",
        "ঢাকা\u{00A0}চট্টগ্রাম\u{200B}সিলেট",
        'ঢাকা   --   চট্টগ্রাম',
    ];

    foreach ($inputs as $input) {
        $slug = UniversalSlug::generate($input);
        expect($slug)->not->toContain(' ');
        expect($slug)->not->toContain('%20');
        expect($slug)->not->toContain('--');
    }
});

it('supports word limit option (e.g. first 7 words or first 10 words)', function () {
    $input = 'রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা ও তার কারণ অনুসন্ধান';

    // Limit to 7 words
    $slug7 = UniversalSlug::of($input)->words(7)->toString();
    expect($slug7)->toBe('রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা');

    // Limit to 3 words
    $slug3 = UniversalSlug::generate($input, options: ['word_limit' => 3]);
    expect($slug3)->toBe('রাবিতে-সাড়ে-৬');
});

it('supports random alphanumeric suffix (e.g. 2 to 9 characters)', function () {
    $input = 'রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা';

    // Default 5-character suffix
    $slugWith5 = UniversalSlug::of($input)->randomSuffix(5)->toString();
    expect($slugWith5)->toMatch('/^রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা-[a-z0-9]{5}$/u');

    // 2-character suffix
    $slugWith2 = UniversalSlug::generate($input, options: ['random_suffix' => 2]);
    expect($slugWith2)->toMatch('/^রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা-[a-z0-9]{2}$/u');

    // 9-character suffix
    $slugWith9 = UniversalSlug::generate($input, options: ['random_suffix' => 9]);
    expect($slugWith9)->toMatch('/^রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা-[a-z0-9]{9}$/u');
});

it('supports onlySlug method to guarantee pure slug without suffix', function () {
    $input = 'রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা';
    $slug = UniversalSlug::of($input)->onlySlug()->toString();

    expect($slug)->toBe('রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা');
    expect(UniversalSlug::generateOnlySlug($input))->toBe('রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা');
});

it('provides safe fallback when string has no alphanumeric or letter characters', function () {
    expect(UniversalSlug::generate('???!!!***'))->toBe('default');
    expect(UniversalSlug::generate('', options: ['fallback' => 'untitled']))->toBe('untitled');
    expect(UniversalSlug::generate('   ', options: ['fallback' => 'default', 'random_suffix' => 4]))
        ->toMatch('/^default-[a-z0-9]{4}$/');
});

it('generates correct slug for Arabic language', function () {
    $input = 'أخبار التكنولوجيا، والذكاء الاصطناعي اليوم في العالم العربي؟';
    $expected = 'أخبار-التكنولوجيا-والذكاء-الاصطناعي-اليوم-في-العالم-العربي';

    expect(UniversalSlug::generate($input))->toBe($expected);
});

it('generates correct slug for Hindi / Devanagari script', function () {
    $input = 'नमस्ते भारत! कृत्रिम बुद्धिमत्ता और तकनीकी विकास २०२६';
    $expected = 'नमस्ते-भारत-कृत्रिम-बुद्धिमत्ता-और-तकनीकी-विकास-२०२६';

    expect(UniversalSlug::generate($input))->toBe($expected);
});

it('generates correct slug for Chinese / Japanese / Korean scripts', function () {
    // Chinese
    expect(UniversalSlug::generate('你好，世界！2026 年人工智能技术。'))
        ->toBe('你好-世界-2026-年人工智能技术');

    // Japanese
    expect(UniversalSlug::generate('こんにちは世界！最新のLaravelパッケージ'))
        ->toBe('こんにちは世界-最新のlaravelパッケージ');

    // Korean
    expect(UniversalSlug::generate('안녕하세요 세계! 2026년 최고의 패কি지'))
        ->toBe('안녕하세요-세계-2026년-최고의-패কি지');
});

it('generates correct slug for Russian / Cyrillic script', function () {
    $input = 'Привет, мир! Новейшие технологии веб-разработки';
    $expected = 'привет-мир-новейшие-технологии-веб-разработки';

    expect(UniversalSlug::generate($input))->toBe($expected);
});

it('generates correct slug for Greek and Hebrew scripts', function () {
    // Greek
    expect(UniversalSlug::generate('Γειά σου Κόσμε! Προγραμματισμός 2026'))
        ->toBe('γειά-σου-κόσμε-προγραμματισμός-2026');

    // Hebrew
    expect(UniversalSlug::generate('שלום עולם! פיתוח תוכנה מודרনি'))
        ->toBe('שלום-עולם-פיתוח-תוכנה-מודרনি');
});

it('cleans emojis and html tags from strings', function () {
    $input = '<h1>🔥 Best Laravel Package for 2026! 🚀</h1>';
    $expected = 'best-laravel-package-for-2026';

    expect(UniversalSlug::generate($input))->toBe($expected);
});

it('supports custom separators', function () {
    $input = 'রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা';
    $expected = 'রাবিতে_সাড়ে_৬_বছরে_১৬_শিক্ষার্থীর_আত্মহত্যা';

    expect(UniversalSlug::generate($input, '_'))->toBe($expected);
});

it('applies language specific symbol replacements', function () {
    $input = 'ঢাকা & চট্টগ্রাম @ বাংলাদেশ';

    // With Bengali language replacement (& -> এবং, @ -> এট)
    expect(UniversalSlug::generate($input, '-', 'bn'))
        ->toBe('ঢাকা-এবং-চট্টগ্রাম-এট-বাংলাদেশ');

    // With English default replacements
    expect(UniversalSlug::generate('PHP & Laravel @ 2026'))
        ->toBe('php-and-laravel-at-2026');
});

it('supports transliteration mode', function () {
    $input = 'Bonjour le café & l\'été!';
    $slug = UniversalSlug::generate($input, '-', null, ['mode' => 'transliterate']);

    expect($slug)->toBe('bonjour-le-cafe-and-lete');
});

it('truncates to max length safely without breaking multibyte characters', function () {
    $input = 'রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা';
    $slug = UniversalSlug::generate($input, '-', null, ['max_length' => 20]);

    expect(mb_strlen($slug, 'UTF-8'))->toBeLessThanOrEqual(20);
    expect($slug)->not->toEndWith('-');
});
