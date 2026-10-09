<?php

use App\Actions\Admin\SaveCvFile;
use App\Actions\Admin\SaveSlugged;
use App\Actions\ApplyBulkAction;
use App\Actions\Auth\EnableTwoFactor;
use App\Actions\ReorderItems;
use App\Enums\ProjectStatus;
use App\Models\CvFile;
use App\Models\Page;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('ReorderItems', function () {
    it('swaps positions among the given records only', function () {
        [$a, $b, $c, $d] = collect([10, 20, 30, 40])->map(fn ($n) => Project::factory()->create(['sort_order' => $n]))->all();

        app(ReorderItems::class)(Project::class, [$c->id, $b->id]);

        expect(Project::ordered()->pluck('id')->all())->toBe([$a->id, $c->id, $b->id, $d->id]);
    });

    it('ignores ids that do not exist', function () {
        $a = Project::factory()->create(['sort_order' => 1]);

        app(ReorderItems::class)(Project::class, [9999, $a->id]);

        expect($a->fresh()->sort_order)->toBeInt();
    });
});

describe('ApplyBulkAction', function () {
    it('publishes, unpublishes, trashes, restores and force deletes', function () {
        $project = Project::factory()->create(['status' => ProjectStatus::Draft]);
        $apply = app(ApplyBulkAction::class);

        expect($apply(Project::class, 'publish', [$project->id]))->toBe(1);
        expect($project->fresh()->status)->toBe(ProjectStatus::Published);

        $apply(Project::class, 'unpublish', [$project->id]);
        expect($project->fresh()->status)->toBe(ProjectStatus::Draft);

        $apply(Project::class, 'delete', [$project->id]);
        expect(Project::find($project->id))->toBeNull();

        $apply(Project::class, 'restore', [$project->id]);
        expect(Project::find($project->id))->not->toBeNull();

        $apply(Project::class, 'delete', [$project->id]);
        $apply(Project::class, 'force-delete', [$project->id]);
        expect(Project::withTrashed()->find($project->id))->toBeNull();
    });

    it('rejects unknown actions and models that cannot be published', function () {
        $apply = app(ApplyBulkAction::class);

        expect(fn () => $apply(Project::class, 'explode', [1]))->toThrow(InvalidArgumentException::class);

        $redirect = Redirect::create(['from_path' => '/a', 'to_path' => '/b', 'code' => 301]);
        expect(fn () => $apply(Redirect::class, 'publish', [$redirect->id]))->toThrow(InvalidArgumentException::class);
    });

    it('reports how many records it changed', function () {
        $ids = Project::factory()->count(3)->create()->pluck('id')->all();

        expect(app(ApplyBulkAction::class)(Project::class, 'delete', [...$ids, 9999]))->toBe(3);
    });
});

describe('SaveSlugged', function () {
    it('generates a slug, purifies rich text and strips tags from plain fields', function () {
        $page = app(SaveSlugged::class)(new Page, ['title' => 'Privacy Policy', 'body' => '<p onclick="x()">Hi</p><script>x</script>', 'seo_title' => '<b>SEO</b>'], ['body'], ['seo_title']);

        expect($page->slug)->toBe('privacy-policy')->and($page->body)->toBe('<p>Hi</p>')->and($page->seo_title)->toBe('SEO');
    });

    it('keeps the slug when it is left blank on update, and records a redirect when it changes', function () {
        $save = app(SaveSlugged::class);
        $page = $save(new Page, ['title' => 'About', 'slug' => 'about-me']);

        $save($page, ['title' => 'About me', 'slug' => '']);
        expect($page->fresh()->slug)->toBe('about-me')->and(Redirect::count())->toBe(0);

        $save($page, ['title' => 'About me', 'slug' => 'about']);
        expect($page->fresh()->slug)->toBe('about')->and(Redirect::first()->only('from_path', 'to_path'))->toBe(['from_path' => '/about-me', 'to_path' => '/about']);
    });
});

describe('SaveCvFile', function () {
    beforeEach(function () {
        Storage::fake(CvFile::DISK);
    });

    it('stores the upload privately under a random name and replaces the old file', function () {
        $save = app(SaveCvFile::class);
        $cv = $save(new CvFile, ['label' => 'Engineering', 'file' => UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1'), 'is_active' => true]);
        $first = $cv->file_path;

        expect($first)->toMatch('#^cv/[A-Za-z0-9]{40}\.pdf$#');
        Storage::disk(CvFile::DISK)->assertExists($first);

        $save($cv, ['label' => 'Engineering', 'file' => UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-2'), 'is_active' => true]);
        Storage::disk(CvFile::DISK)->assertMissing($first);
    });

    it('keeps one active version per label and can activate another', function () {
        $save = app(SaveCvFile::class);
        $a = CvFile::factory()->create(['label' => 'X', 'is_active' => true]);
        $b = CvFile::factory()->create(['label' => 'X', 'is_active' => false]);
        $other = CvFile::factory()->create(['label' => 'Y', 'is_active' => true]);

        $save->activate($b);

        expect($a->fresh()->is_active)->toBeFalse()->and($b->fresh()->is_active)->toBeTrue()->and($other->fresh()->is_active)->toBeTrue();
    });
});

describe('EnableTwoFactor', function () {
    it('stores the secret, confirms it and returns eight plain recovery codes', function () {
        $user = User::factory()->create();

        $codes = app(EnableTwoFactor::class)($user, 'JBSWY3DPEHPK3PXP');

        expect($codes)->toHaveCount(8)->and($user->fresh()->hasTwoFactorEnabled())->toBeTrue()->and($user->fresh()->recoveryCodes())->toBe($codes)
            ->and($user->getRawOriginal('two_factor_secret'))->not->toBe('JBSWY3DPEHPK3PXP');
    });
});

describe('Setting model', function () {
    it('round-trips json values', function () {
        Setting::create(['key' => 'k', 'value' => ['a' => 1, 'b' => [true, null]], 'group' => 'g']);

        expect(Setting::firstWhere('key', 'k')->value)->toBe(['a' => 1, 'b' => [true, null]]);
    });
});
