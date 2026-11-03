<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel revision_requests.
 * Permintaan revisi dari UMKM beserta catatan perbaikan untuk suatu penyerahan hasil.
 */
class RevisionRequest extends Model
{
    use HasFactory;

    protected $table = 'revision_requests';

    public const UPDATED_AT = null;

    protected $fillable = [
        'submission_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
