<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Observers\ContentObserver;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(ContentObserver::class)]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    use HasSlug;

    protected $fillable = ['name', 'slug'];

    protected function slugSource(): string
    {
        return 'name';
    }

    /** @return BelongsToMany<Post, $this> */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
