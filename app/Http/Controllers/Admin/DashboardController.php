<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\CvFile;
use App\Models\Message;
use App\Models\PageView;
use App\Models\Post;
use App\Models\Project;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'views' => PageView::query()->where('created_at', '>=', now()->subDays(30))->count(),
                'messages' => Message::unread()->count(),
                'downloads' => (int) CvFile::query()->sum('downloads'),
                'projects' => Project::published()->count(),
            ],
            'drafts' => Post::query()->where('status', PostStatus::Draft)->latest()->limit(5)->get(),
            'latestMessages' => Message::inbox()->latest()->limit(5)->get(),
        ]);
    }
}
