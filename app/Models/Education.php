<?php

namespace App\Models;

use App\Models\Concerns\AdminListFilters;
use App\Models\Concerns\HasPublishState;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Orderable;
use App\Observers\ContentObserver;
use Database\Factories\EducationFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ContentObserver::class)]
class Education extends Model
{
    use AdminListFilters, HasPublishState;

    /** @use HasFactory<EducationFactory> */
    use HasFactory;
    use IsPublishable, LogsChanges, Orderable, SoftDeletes;

    protected $table = 'educations';

    protected $fillable = ['institution', 'degree', 'field', 'start_year', 'end_year', 'sort_order', 'is_published'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /** @return list<string> */
    protected function adminSearchColumns(): array
    {
        return ['institution', 'degree', 'field'];
    }
}
