<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use ImranDev\UniversalSlug\Models\SlugHistory;
use ImranDev\UniversalSlug\SlugOptions;
use ImranDev\UniversalSlug\Traits\HasSlugHistory;
use ImranDev\UniversalSlug\Traits\HasUniversalSlug;
use ImranDev\UniversalSlug\UniversalSlug;

class TestUser extends AuthenticatableUser
{
    protected $guarded = [];
}

class ArticleWithHistory extends Model
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

it('records historical slug when title and slug change on update', function () {
    $article = ArticleWithHistory::create([
        'title' => 'প্রথম বাংলা সংবাদ শিরোনাম',
    ]);

    expect($article->slug)->toBe('প্রথম-বাংলা-সংবাদ-শিরোনাম');
    expect($article->slugHistories()->count())->toBe(0);

    // Update title
    $article->update([
        'title' => 'দ্বিতীয় পরিবর্তিত বাংলা সংবাদ',
    ]);

    expect($article->slug)->toBe('দ্বিতীয়-পরিবর্তিত-বাংলা-সংবাদ');
    expect($article->slugHistories()->count())->toBe(1);
    expect($article->slugHistories()->first()->slug)->toBe('প্রথম-বাংলা-সংবাদ-শিরোনাম');
});

it('finds model and indicates redirection for historical slugs', function () {
    $article = ArticleWithHistory::create([
        'title' => 'মূল আর্টিকেল লিংক',
    ]);

    $oldSlug = $article->slug;

    $article->update([
        'title' => 'আপডেট করা নতুন আর্টিকেল লিংক',
    ]);

    $newSlug = $article->slug;

    // Search by new slug -> no redirection
    $resultCurrent = ArticleWithHistory::findWithHistory($newSlug);
    expect($resultCurrent)->not->toBeNull();
    expect($resultCurrent['model']->id)->toBe($article->id);
    expect($resultCurrent['was_redirected'])->toBeFalse();

    // Search by old slug -> was_redirected is true (for 301 redirect)
    $resultOld = ArticleWithHistory::findWithHistory($oldSlug);
    expect($resultOld)->not->toBeNull();
    expect($resultOld['model']->id)->toBe($article->id);
    expect($resultOld['was_redirected'])->toBeTrue();

    // Search non-existent
    expect(ArticleWithHistory::findWithHistory('non-existent-slug'))->toBeNull();
});

it('enforces maximum history entries per model', function () {
    config()->set('universal-slug.history.max_entries_per_model', 2);

    $article = ArticleWithHistory::create(['title' => 'টাইটেল ১']);
    $article->update(['title' => 'টাইটেল ২']); // records 1
    $article->update(['title' => 'টাইটেল ৩']); // records 2
    $article->update(['title' => 'টাইটেল ৪']); // records 3 -> trims oldest, keeping max 2

    expect($article->slugHistories()->count())->toBe(2);
    $slugs = $article->slugHistories()->pluck('slug')->all();
    expect($slugs)->not->toContain('টাইটেল-১');
    expect($slugs)->toContain('টাইটেল-২');
    expect($slugs)->toContain('টাইটেল-৩');
});

it('supports security query scope on findWithHistory', function () {
    $article = ArticleWithHistory::create([
        'title' => 'গোপন ড্রাফট সংবাদ',
        'subtitle' => 'draft', // unpublished
    ]);

    $oldSlug = $article->slug;
    $article->update(['title' => 'প্রকাশিত নতুন সংবাদ']);

    // When scoping to only published records (subtitle = 'published')
    $result = ArticleWithHistory::findWithHistory($oldSlug, scopeCallback: function ($q) {
        $q->where('subtitle', 'published');
    });

    // Cannot be found or accessed through history because scope protected it!
    expect($result)->toBeNull();
});

it('only allows admin or superadmin roles to see slug history list in live app', function () {
    $article = ArticleWithHistory::create(['title' => 'আর্টিকেল ভার্সন ১']);
    $article->update(['title' => 'আর্টিকেল ভার্সন ২']);

    $guestUser = null;
    $regularUser = new TestUser(['role' => 'subscriber']);
    $adminUser = new TestUser(['role' => 'admin']);
    $superAdminUser = new TestUser(['role' => 'superadmin']);

    // Guests and regular users cannot view history
    expect($article->canViewSlugHistory($guestUser))->toBeFalse();
    expect($article->getSlugHistoryForAdmin($guestUser))->toBeEmpty();

    expect($article->canViewSlugHistory($regularUser))->toBeFalse();
    expect($article->getSlugHistoryForAdmin($regularUser))->toBeEmpty();

    // Admins and Superadmins CAN view history
    expect($article->canViewSlugHistory($adminUser))->toBeTrue();
    expect($article->getSlugHistoryForAdmin($adminUser))->toHaveCount(1);
    expect($article->getSlugHistoryForAdmin($adminUser)->first()->slug)->toBe('আর্টিকেল-ভার্সন-১');

    expect($article->canViewSlugHistory($superAdminUser))->toBeTrue();
    expect($article->getSlugHistoryForAdmin($superAdminUser))->toHaveCount(1);
});

it('allows custom authorizeHistory closure', function () {
    $article = ArticleWithHistory::create(['title' => 'পোস্ট ১']);
    $article->update(['title' => 'পোস্ট ২']);

    UniversalSlug::authorizeHistory(function ($user) {
        return $user && $user->email === 'master@domain.com';
    });

    $user1 = new TestUser(['email' => 'other@domain.com']);
    $user2 = new TestUser(['email' => 'master@domain.com']);

    expect($article->canViewSlugHistory($user1))->toBeFalse();
    expect($article->canViewSlugHistory($user2))->toBeTrue();
});

it('prunes old slug histories via artisan command', function () {
    $history = SlugHistory::create([
        'sluggable_type' => ArticleWithHistory::class,
        'sluggable_id' => 1,
        'slug' => 'পুরনো-আর্কাইভ-লিংক',
        'created_at' => now()->subDays(100),
    ]);

    $this->artisan('slug:prune', ['--days' => 90])
        ->expectsOutputToContain('Successfully pruned 1 old slug history records')
        ->assertExitCode(0);

    expect(SlugHistory::find($history->id))->toBeNull();
});
