<?php

use App\Models\CvDownload;
use App\Models\CvFile;
use App\Services\CacheService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake(CvFile::DISK);
    app(SettingService::class)->set('site_name', 'Saidur Rahman');
});

function storedCv(array $attributes = []): CvFile
{
    $cv = CvFile::factory()->create($attributes + ['is_active' => true]);
    Storage::disk(CvFile::DISK)->put($cv->file_path, "%PDF-1.4\nfake");

    return $cv;
}

function download(object $test, CvFile $cv, array $headers = [], string $ip = '10.1.1.1'): TestResponse
{
    return $test->withHeaders($headers + ['User-Agent' => 'Mozilla/5.0 (Macintosh) Safari'])->withServerVariables(['REMOTE_ADDR' => $ip])->get("/cv/download/{$cv->id}");
}

describe('the CV page', function () {
    it('lists the active versions with a download button each', function () {
        storedCv(['label' => 'IT Leadership']);
        storedCv(['label' => 'Engineering']);
        storedCv(['label' => 'Old version', 'is_active' => false]);

        $this->get('/cv')->assertOk()->assertSee('IT Leadership')->assertSee('Engineering')->assertDontSee('Old version')->assertSee('Choose the version');
    });

    it('hides a version whose file is missing', function () {
        CvFile::factory()->create(['label' => 'Ghost', 'is_active' => true]);

        $this->get('/cv')->assertDontSee('Ghost')->assertSee('not available to download yet');
    });

    it('explains when there is nothing to download', function () {
        $this->get('/cv')->assertOk()->assertSee('not available to download yet');
    });

    it('turns on the home page button', function () {
        $this->get('/')->assertDontSee('Download CV');
        storedCv();
        app(CacheService::class)->flush();

        $this->get('/')->assertSee('Download CV')->assertSee('href="'.url('/cv').'"', false);
    });
});

describe('downloading', function () {
    it('streams the PDF with a tidy file name and keeps it out of search engines', function () {
        $cv = storedCv(['label' => 'IT Leadership']);

        download($this, $cv)->assertOk()->assertDownload('saidur-rahman-it-leadership-cv.pdf')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    });

    it('logs the download without personal data and bumps the counter', function () {
        $cv = storedCv(['downloads' => 4]);

        download($this, $cv, ['Referer' => 'https://www.linkedin.com/in/someone?trk=abc', 'CF-IPCountry' => 'qa'], '203.0.113.9')->assertOk();

        $log = CvDownload::firstOrFail();
        expect($log->cv_file_id)->toBe($cv->id)
            ->and($log->ip_hash)->toHaveLength(64)->not->toContain('203.0.113.9')
            ->and($log->country)->toBe('QA')
            ->and($log->referrer)->toBe('www.linkedin.com')
            ->and($cv->fresh()->downloads)->toBe(5);
    });

    it('does not touch updated_at or clear the public cache', function () {
        $cv = storedCv(['updated_at' => now()->subWeek()]);
        $updated = $cv->fresh()->updated_at;

        download($this, $cv);

        expect($cv->fresh()->updated_at->equalTo($updated))->toBeTrue();
    });

    it('counts the same visitor once within ten minutes, and again later', function () {
        $cv = storedCv();

        download($this, $cv);
        download($this, $cv);
        expect(CvDownload::count())->toBe(1);

        download($this, $cv, ip: '10.9.9.9');
        expect(CvDownload::count())->toBe(2);

        $this->travel(11)->minutes();
        download($this, $cv);
        expect(CvDownload::count())->toBe(3)->and($cv->fresh()->downloads)->toBe(3);
    });

    it('serves bots but does not count them', function () {
        $cv = storedCv();

        download($this, $cv, ['User-Agent' => 'Googlebot/2.1'])->assertOk();

        expect(CvDownload::count())->toBe(0)->and($cv->fresh()->downloads)->toBe(0);
    });

    it('ignores a referrer from our own site', function () {
        $cv = storedCv();

        download($this, $cv, ['Referer' => url('/cv')]);

        expect(CvDownload::first()->referrer)->toBeNull();
    });

    it('404s for inactive, trashed, unknown and file-less versions', function () {
        $inactive = storedCv(['is_active' => false]);
        $trashed = storedCv();
        $trashed->delete();
        $noFile = CvFile::factory()->create(['is_active' => true]);

        foreach ([$inactive->id, $trashed->id, $noFile->id, 9999] as $id) {
            $this->get("/cv/download/{$id}")->assertNotFound();
        }
        expect(CvDownload::count())->toBe(0);
    });

    it('rejects non-numeric ids', function () {
        $this->get('/cv/download/abc')->assertNotFound();
    });
});
