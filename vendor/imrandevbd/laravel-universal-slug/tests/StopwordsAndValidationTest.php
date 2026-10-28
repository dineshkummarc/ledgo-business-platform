<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use ImranDev\UniversalSlug\Data\Stopwords;
use ImranDev\UniversalSlug\Facades\UniversalSlug;
use ImranDev\UniversalSlug\Rules\UniversalSlugRule;

it('removes stopwords in English', function () {
    $title = 'The quick brown fox jumps over the lazy dog and a cat';
    $slug = UniversalSlug::of($title)->removeStopwords()->toString();

    expect($slug)->toBe('quick-brown-fox-jumps-lazy-dog-cat');
});

it('removes stopwords in Bengali', function () {
    $title = 'ঢাকা এবং চট্টগ্রাম ও রাজশাহী থেকে সিলেট';
    $slug = UniversalSlug::generate($title, language: 'bn', options: ['remove_stopwords' => true]);

    expect($slug)->toBe('ঢাকা-চট্টগ্রাম-রাজশাহী-সিলেট');
});

it('removes stopwords in Arabic', function () {
    $title = 'التكنولوجيا في العالم و الذكاء الاصطناعي مع التطوير';
    $slug = UniversalSlug::generate($title, language: 'ar', options: ['remove_stopwords' => true]);

    expect($slug)->toBe('التكنولوجيا-العالم-الذكاء-الاصطناعي-التطوير');
});

it('allows extending stopwords dynamically', function () {
    Stopwords::extend('bn', ['বিশেষ', 'সংবাদ']);

    $title = 'আজকের বিশেষ সংবাদ ঢাকা';
    $slug = UniversalSlug::generate($title, language: 'bn', options: ['remove_stopwords' => true]);

    expect($slug)->toBe('আজকের-ঢাকা');
});

it('allows registering custom language processors', function () {
    UniversalSlug::registerProcessor('custom_lang', function ($str, $sep) {
        return str_replace('test', 'custom_converted', $str);
    });

    $slug = UniversalSlug::generate('hello test world', language: 'custom_lang');
    expect($slug)->toBe('hello-custom-converted-world');
});

it('validates slugs with UniversalSlugRule and Rule macro', function () {
    // Valid universal slug
    $v1 = Validator::make(['slug' => 'রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা'], [
        'slug' => ['required', new UniversalSlugRule],
    ]);
    expect($v1->passes())->toBeTrue();

    // Invalid slug with spaces
    $v2 = Validator::make(['slug' => 'invalid slug with spaces'], [
        'slug' => ['required', Rule::universalSlug()],
    ]);
    expect($v2->fails())->toBeTrue();

    // Invalid slug with bad characters
    $v3 = Validator::make(['slug' => 'invalid--slug--'], [
        'slug' => ['required', Rule::universalSlug()],
    ]);
    expect($v3->fails())->toBeTrue();
});
