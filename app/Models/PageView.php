<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class PageView extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    protected $fillable = ['path', 'ip_hash', 'country', 'referrer', 'device'];

    /**
     * Page views older than about 13 months are dropped; the dashboard only ever looks 30 days back.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(400));
    }
}
