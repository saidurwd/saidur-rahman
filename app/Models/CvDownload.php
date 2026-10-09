<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvDownload extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['cv_file_id', 'ip_hash', 'country', 'referrer'];

    /** @return BelongsTo<CvFile, $this> */
    public function cvFile(): BelongsTo
    {
        return $this->belongsTo(CvFile::class);
    }
}
