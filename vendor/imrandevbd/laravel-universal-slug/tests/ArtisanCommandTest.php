<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Tests;

use ImranDev\UniversalSlug\UniversalSlug;

it('runs slug:generate command successfully from terminal', function () {
    $text = 'বাংলা ভাষা ও সাহিত্য পরিষদ ২০২৬';
    $expected = UniversalSlug::generate($text, options: ['word_limit' => 5]);

    $this->artisan('slug:generate', [
        'text' => $text,
        '--words' => 5,
    ])
        ->expectsOutput("Generated Slug: {$expected}")
        ->assertExitCode(0);
});

it('runs slug:regenerate command on Eloquent models', function () {
    TestArticle::create(['title' => 'প্রথম সংবাদ ২০২৬', 'slug' => null]);
    TestArticle::create(['title' => 'দ্বিতীয় সংবাদ ২০২৬', 'slug' => null]);

    $this->artisan('slug:regenerate', [
        'model' => TestArticle::class,
        '--field' => 'title',
        '--slug' => 'slug',
    ])
        ->assertExitCode(0);

    $articles = TestArticle::all();
    expect($articles[0]->slug)->toBe('প্রথম-সংবাদ-২০২৬');
    expect($articles[1]->slug)->toBe('দ্বিতীয়-সংবাদ-২০২৬');
});
