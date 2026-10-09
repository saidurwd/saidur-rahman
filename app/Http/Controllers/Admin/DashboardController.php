<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessageStatus;
use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\CvDownload;
use App\Models\CvFile;
use App\Models\Message;
use App\Models\PageView;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const DAYS = 30;

    public function __invoke(): View
    {
        $from = now()->subDays(self::DAYS - 1)->startOfDay();

        $daily = PageView::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as views, COUNT(DISTINCT ip_hash) as visitors')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // One entry per day, including days with no traffic, so the chart has no gaps.
        $days = collect(range(0, self::DAYS - 1))->map(function (int $offset) use ($from, $daily) {
            $date = Carbon::parse($from)->addDays($offset);
            $row = $daily->get($date->toDateString());

            return ['date' => $date->toDateString(), 'label' => $date->format('j M'), 'views' => (int) ($row->views ?? 0), 'visitors' => (int) ($row->visitors ?? 0)];
        });

        return view('admin.dashboard', [
            'stats' => [
                'views' => $days->sum('views'),
                'visitors' => $days->sum('visitors'),
                'unread' => Message::unread()->count(),
                'messages' => Message::inbox()->where('created_at', '>=', $from)->count(),
                'downloads' => CvDownload::query()->where('created_at', '>=', $from)->count(),
                'projects' => Project::published()->count(),
            ],
            'days' => $days,
            'topPages' => PageView::query()->where('created_at', '>=', $from)
                ->selectRaw('path, COUNT(*) as views')->groupBy('path')->orderByDesc('views')->limit(8)->get(),
            'referrers' => PageView::query()->where('created_at', '>=', $from)->whereNotNull('referrer')
                ->selectRaw('referrer, COUNT(*) as views')->groupBy('referrer')->orderByDesc('views')->limit(5)->get(),
            'cvVersions' => CvFile::query()
                ->withCount(['downloadLog as recent' => fn ($q) => $q->where('created_at', '>=', $from)])
                ->orderBy('label')->get(),
            'drafts' => Post::query()->where('status', PostStatus::Draft)->latest()->limit(5)->get(),
            'latestMessages' => Message::query()->where('status', '!=', MessageStatus::Spam)->latest()->limit(5)->get(),
        ]);
    }
}
