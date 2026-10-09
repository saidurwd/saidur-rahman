<?php

namespace App\Models;

use App\Models\Concerns\AdminListFilters;
use App\Models\Concerns\HasPublishState;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Orderable;
use App\Observers\ContentObserver;
use Database\Factories\CertificationFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ContentObserver::class)]
class Certification extends Model
{
    use AdminListFilters, HasPublishState;

    /** @use HasFactory<CertificationFactory> */
    use HasFactory;
    use IsPublishable, LogsChanges, Orderable, SoftDeletes;

    protected $fillable = ['name', 'issuer', 'issued_at', 'expires_at', 'credential_url', 'sort_order', 'is_published'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /** @return list<string> */
    protected function adminSearchColumns(): array
    {
        return ['name', 'issuer'];
    }
}
