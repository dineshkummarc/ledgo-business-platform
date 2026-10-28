<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ImranDev\UniversalSlug\SlugOptions;
use ImranDev\UniversalSlug\Traits\HasUniversalSlug;

class TestArticle extends Model
{
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}

class TestArticleWithWordLimit extends Model
{
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->withWordLimit(7);
    }
}

class TestArticleWithRandomSuffix extends Model
{
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->withRandomSuffix(5);
    }
}

class TestArticleMultiSlug extends Model
{
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): array
    {
        return [
            SlugOptions::create()
                ->generateSlugsFrom('title')
                ->saveSlugsTo('slug'),
            SlugOptions::create()
                ->generateSlugsFrom('subtitle')
                ->saveSlugsTo('custom_slug')
                ->usingSeparator('_'),
        ];
    }
}

class TestArticleSoftDelete extends Model
{
    use HasUniversalSlug;
    use SoftDeletes;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}

class TestArticleTenantScoped extends Model
{
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->uniqueScope(function ($query, $model) {
                $query->where('tenant_id', $model->tenant_id);
            });
    }
}

it('automatically generates universal slug on model create', function () {
    $article = TestArticle::create([
        'title' => 'রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা',
    ]);

    expect($article->slug)->toBe('রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা');
});

it('supports word limit in Eloquent models', function () {
    $article = TestArticleWithWordLimit::create([
        'title' => 'রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা ও তার কারণ অনুসন্ধান',
    ]);

    expect($article->slug)->toBe('রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা');
});

it('supports random suffix in Eloquent models', function () {
    $article = TestArticleWithRandomSuffix::create([
        'title' => 'রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা',
    ]);

    expect($article->slug)->toMatch('/^রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা-[a-z0-9]{5}$/u');
});

it('handles duplicate collisions by adding numeric suffixes', function () {
    $article1 = TestArticle::create(['title' => 'বাংলা খবর ২০২৬']);
    $article2 = TestArticle::create(['title' => 'বাংলা খবর ২০২৬']);
    $article3 = TestArticle::create(['title' => 'বাংলা খবর ২০২৬']);

    expect($article1->slug)->toBe('বাংলা-খবর-২০২৬');
    expect($article2->slug)->toBe('বাংলা-খবর-২০২৬-1');
    expect($article3->slug)->toBe('বাংলা-খবর-২০২৬-2');
});

it('updates slug when title changes', function () {
    $article = TestArticle::create(['title' => 'প্রথম সংবাদ']);
    expect($article->slug)->toBe('প্রথম-সংবাদ');

    $article->update(['title' => 'দ্বিতীয় নতুন সংবাদ']);
    expect($article->slug)->toBe('দ্বিতীয়-নতুন-সংবাদ');
});

it('does not change slug on update if title did not change', function () {
    $article = TestArticle::create(['title' => 'আমার প্রিয় বই']);
    expect($article->slug)->toBe('আমার-প্রিয়-বই');

    $article->update(['subtitle' => 'নতুন সাবটাইটেল']);
    expect($article->slug)->toBe('আমার-প্রিয়-বই');
});

it('supports findBySlug and whereSlug scopes', function () {
    $created = TestArticle::create(['title' => 'অনুসন্ধানী প্রতিবেদন']);

    $found = TestArticle::findBySlug('অনুসন্ধানী-প্রতিবেদন');
    expect($found)->not->toBeNull();
    expect($found->id)->toBe($created->id);

    $foundViaScope = TestArticle::whereSlug('অনুসন্ধানী-প্রতিবেদন')->first();
    expect($foundViaScope)->not->toBeNull();
    expect($foundViaScope->id)->toBe($created->id);
});

it('supports multiple slug configurations per model', function () {
    $article = TestArticleMultiSlug::create([
        'title' => 'প্রধান শিরোনাম',
        'subtitle' => 'সহকারী বিস্তারিত তথ্য',
    ]);

    expect($article->slug)->toBe('প্রধান-শিরোনাম');
    expect($article->custom_slug)->toBe('সহকারী_বিস্তারিত_তথ্য');
});

it('prevents collisions with soft deleted records when includeTrashed is true', function () {
    $article1 = TestArticleSoftDelete::create(['title' => 'ডিজিটাল বাংলাদেশ']);
    expect($article1->slug)->toBe('ডিজিটাল-বাংলাদেশ');

    $article1->delete(); // Soft delete

    $article2 = TestArticleSoftDelete::create(['title' => 'ডিজিটাল বাংলাদেশ']);
    expect($article2->slug)->toBe('ডিজিটাল-বাংলাদেশ-1');
});

it('respects tenant scoped uniqueness', function () {
    $articleTenant1 = TestArticleTenantScoped::create([
        'title' => 'বিশ্ববিদ্যালয় সংবাদ',
        'tenant_id' => 1,
    ]);

    $articleTenant2 = TestArticleTenantScoped::create([
        'title' => 'বিশ্ববিদ্যালয় সংবাদ',
        'tenant_id' => 2, // Different tenant
    ]);

    expect($articleTenant1->slug)->toBe('বিশ্ববিদ্যালয়-সংবাদ');
    expect($articleTenant2->slug)->toBe('বিশ্ববিদ্যালয়-সংবাদ');
});
