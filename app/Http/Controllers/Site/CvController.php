<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CvDownload;
use App\Models\CvFile;
use App\Services\Analytics;
use App\Services\Bots;
use App\Services\SeoService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvController extends Controller
{
    public function show(SeoService $seo): View
    {
        return view('site.cv.show', [
            'cvs' => CvFile::query()->published()->orderBy('label')->get()->filter(fn (CvFile $cv) => Storage::disk(CvFile::DISK)->exists($cv->file_path))->values(),
            'schema' => $seo->breadcrumbs([['CV', null]]),
        ]);
    }

    public function download(int $cvFile, Request $request, Analytics $analytics, SettingService $settings): StreamedResponse
    {
        $cv = CvFile::query()->published()->findOrFail($cvFile);
        abort_unless(Storage::disk(CvFile::DISK)->exists($cv->file_path), 404);

        $this->log($cv, $request, $analytics);

        return Storage::disk(CvFile::DISK)->download(
            $cv->file_path,
            Str::slug((string) $settings->get('site_name', config('app.name'))).'-'.Str::slug($cv->label).'-cv.pdf',
            ['X-Robots-Tag' => 'noindex, nofollow'],
        );
    }

    /** Records who downloaded which version, without personal data: a same-day hash, country and referring host only. */
    private function log(CvFile $cv, Request $request, Analytics $analytics): void
    {
        if (Bots::isBot($request->userAgent()) || $request->isMethod('HEAD')) {
            return;
        }

        $visitor = $analytics->visitorHash($request);

        // The same visitor asking again within ten minutes counts once.
        if (! Cache::add("cv-download.{$cv->id}.{$visitor}", 1, now()->addMinutes(10))) {
            return;
        }

        DB::transaction(function () use ($cv, $request, $analytics, $visitor) {
            CvDownload::query()->create([
                'cv_file_id' => $cv->id,
                'ip_hash' => $visitor,
                'country' => $analytics->country($request),
                'referrer' => $analytics->referrerHost($request),
            ]);

            // Straight to the table so this does not touch updated_at or clear the public cache.
            DB::table('cv_files')->where('id', $cv->id)->increment('downloads');
        });
    }
}
