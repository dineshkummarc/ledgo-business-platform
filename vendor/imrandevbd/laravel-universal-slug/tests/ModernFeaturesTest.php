<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use ImranDev\UniversalSlug\Attributes\Slug;
use ImranDev\UniversalSlug\Events\SlugChanged;
use ImranDev\UniversalSlug\Events\SlugGenerated;
use ImranDev\UniversalSlug\Exceptions\SlugRedirectException;
use ImranDev\UniversalSlug\Jobs\RegenerateSlugsJob;
use ImranDev\UniversalSlug\SlugOptions;
use ImranDev\UniversalSlug\Support\ReservedSlugs;
use ImranDev\UniversalSlug\Traits\HasSlugHistory;
use ImranDev\UniversalSlug\Traits\HasUniversalSlug;
use ImranDev\UniversalSlug\UniversalSlug;

#[Slug(from: 'title', words: 3)]
class AttributeConfiguredArticle extends Model
{
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];
}

class ImmutableArticle extends Model
{
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->immutable();
    }
}

class PrefixedArticle extends Model
{
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->withPrefix('news')
            ->withSuffix('live');
    }
}

class LocaleScopedArticle extends Model
{
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->usingLocale('locale');
    }
}

class SkipFilledArticle extends Model
{
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->skipIfFilled();
    }
}

class RouteKeyArticle extends Model
{
    use HasSlugHistory;
    use HasUniversalSlug;

    protected $table = 'articles';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->useAsRouteKey();
    }
}

class HistoryReuseArticle extends Model
{
    use HasSlugHistory;
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

class ModernBaseArticle extends Model
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

it('supports the fluent prefix, suffix, and reserved-word helpers', function () {
    $slug = UniversalSlug::of('Hello World')
        ->prefix('news')
        ->suffix('live')
        ->toString();

    expect($slug)->toBe('news-hello-world-live');

    expect(UniversalSlug::of('Admin')->avoidReserved()->toString())->toBe('admin-1');
    expect(slug_is_reserved('admin'))->toBeTrue();
    expect(preview_slug('Hello World'))->toBe('hello-world');
});

it('reads slug options from the #[Slug] attribute without getSlugOptions()', function () {
    $article = AttributeConfiguredArticle::create([
        'title' => 'One Two Three Four Five',
    ]);

    expect($article->slug)->toBe('one-two-three');
});

it('keeps an immutable slug when the title later changes', function () {
    $article = ImmutableArticle::create(['title' => 'First Title']);
    expect($article->slug)->toBe('first-title');

    $article->update(['title' => 'Second Title']);
    expect($article->slug)->toBe('first-title');
});

it('applies prefix and suffix on Eloquent models', function () {
    $article = PrefixedArticle::create(['title' => 'Hello World']);

    expect($article->slug)->toBe('news-hello-world-live');
});

it('scopes uniqueness by locale column', function () {
    $english = LocaleScopedArticle::create(['title' => 'Shared Title', 'locale' => 'en']);
    $bangla = LocaleScopedArticle::create(['title' => 'Shared Title', 'locale' => 'bn']);
    $englishDup = LocaleScopedArticle::create(['title' => 'Shared Title', 'locale' => 'en']);

    expect($english->slug)->toBe('shared-title');
    expect($bangla->slug)->toBe('shared-title');
    expect($englishDup->slug)->toBe('shared-title-1');
});

it('does not overwrite an existing slug when skipIfFilled is enabled', function () {
    $article = SkipFilledArticle::create([
        'title' => 'Original Title',
        'slug' => 'custom-slug',
    ]);
    expect($article->slug)->toBe('custom-slug');

    $article->update(['title' => 'Changed Title']);
    expect($article->slug)->toBe('custom-slug');
});

it('treats reserved words as taken on Eloquent models', function () {
    $article = ModernBaseArticle::create(['title' => 'Admin']);

    expect($article->slug)->toBe('admin-1');
});

it('does not reuse historical slugs so old URLs keep their 301 mapping', function () {
    $article = HistoryReuseArticle::create(['title' => 'Alpha Post']);
    $article->update(['title' => 'Beta Post']);

    $recycled = HistoryReuseArticle::create(['title' => 'Alpha Post']);

    expect($recycled->slug)->toBe('alpha-post-1');
});

it('dispatches slug generated and slug changed events', function () {
    Event::fake([SlugGenerated::class, SlugChanged::class]);

    $article = ModernBaseArticle::create(['title' => 'Event Post']);
    Event::assertDispatched(SlugGenerated::class, function (SlugGenerated $event) use ($article) {
        return $event->slug === 'event-post' && $event->model->is($article);
    });

    $article->update(['title' => 'Event Post Updated']);
    Event::assertDispatched(SlugChanged::class, function (SlugChanged $event) {
        return $event->oldSlug === 'event-post' && $event->newSlug === 'event-post-updated';
    });
});

it('previews and force-regenerates slugs without extra boilerplate', function () {
    $article = ModernBaseArticle::create(['title' => 'Preview Source']);
    expect($article->previewSlug('Another Title'))->toBe('another-title');

    $article->forceFill(['slug' => 'stale-slug'])->saveQuietly();
    $article->regenerateSlug(save: true);
    $article->refresh();

    expect($article->slug)->toBe('preview-source');
});

it('automatically 301 redirects historical slugs via route model binding', function () {
    $article = HistoryReuseArticle::create(['title' => 'Binding Original']);
    $oldSlug = $article->slug;

    $article->update(['title' => 'Binding Updated']);

    expect(fn () => (new HistoryReuseArticle)->resolveRouteBinding($oldSlug, 'slug'))
        ->toThrow(SlugRedirectException::class);

    Route::middleware(SubstituteBindings::class)->get('/stories/{article:slug}', function (HistoryReuseArticle $article) {
        return $article->title;
    });

    $this->get('/stories/'.$oldSlug)
        ->assertStatus(301)
        ->assertRedirect('/stories/'.$article->slug);

    $this->get('/stories/'.$article->slug)
        ->assertOk()
        ->assertSee('Binding Updated', false);
});

it('uses the slug as the implicit route key when configured', function () {
    $article = RouteKeyArticle::create(['title' => 'Route Key Post']);

    expect($article->getRouteKeyName())->toBe('slug');
    expect($article->getRouteKey())->toBe('route-key-post');
});

it('throws findOrRedirect for historical slugs and 404s missing ones', function () {
    $article = HistoryReuseArticle::create(['title' => 'Redirect Helper']);
    $oldSlug = $article->slug;
    $article->update(['title' => 'Redirect Helper New']);

    expect(fn () => HistoryReuseArticle::findOrRedirect($oldSlug))
        ->toThrow(SlugRedirectException::class);

    expect(HistoryReuseArticle::findOrRedirect($article->slug)->is($article))->toBeTrue();

    expect(fn () => HistoryReuseArticle::findOrRedirect('missing-slug'))
        ->toThrow(ModelNotFoundException::class);
});

it('validates unique and reserved slugs through Rule::uniqueUniversalSlug()', function () {
    ModernBaseArticle::create(['title' => 'Taken Slug']);

    $taken = Validator::make(
        ['slug' => 'taken-slug'],
        ['slug' => Rule::uniqueUniversalSlug(ModernBaseArticle::class)]
    );
    expect($taken->fails())->toBeTrue();

    $reserved = Validator::make(
        ['slug' => 'login'],
        ['slug' => Rule::uniqueUniversalSlug(ModernBaseArticle::class)]
    );
    expect($reserved->fails())->toBeTrue();

    $available = Validator::make(
        ['slug' => 'brand-new-slug'],
        ['slug' => Rule::uniqueUniversalSlug(ModernBaseArticle::class)]
    );
    expect($available->passes())->toBeTrue();
});

it('regenerates slugs through the queued job', function () {
    $article = ModernBaseArticle::create(['title' => 'Job Source']);
    $article->forceFill(['slug' => 'outdated-slug'])->saveQuietly();

    $processed = (new RegenerateSlugsJob(ModernBaseArticle::class))->handle();

    expect($processed)->toBeGreaterThan(0);
    expect($article->fresh()->slug)->toBe('job-source');
});

it('audits empty, duplicate, and reserved slugs from the terminal', function () {
    $healthy = ModernBaseArticle::create(['title' => 'Healthy Audit']);
    $healthy->forceFill(['slug' => 'admin'])->saveQuietly();

    $this->artisan('slug:audit', [
        'model' => ModernBaseArticle::class,
    ])->assertExitCode(1);

    ReservedSlugs::flush();
});
