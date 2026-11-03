<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel submissions.
 * Satu versi hasil pekerjaan yang diserahkan kandidat beserta status peninjauannya.
 */
class Submission extends Model
{
    use HasFactory;

    protected $table = 'submissions';

    public $timestamps = false;

    protected $fillable = [
        'agreement_id',
        'version_number',
        'file_path',
        'result_link',
        'description',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function revisionRequests(): HasMany
    {
        return $this->hasMany(RevisionRequest::class);
    }
}
