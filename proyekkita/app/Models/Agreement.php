<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Model untuk tabel agreements.
 * Kesepakatan pekerjaan antara UMKM dan kandidat yang diterima, beserta pembatalan, progres,
 * penyerahan hasil, pembayaran, dan evaluasi yang terkait.
 */
class Agreement extends Model
{
    use HasFactory;

    protected $table = 'agreements';

    public $timestamps = false;

    protected $fillable = [
        'application_id',
        'scope',
        'deliverables',
        'deadline',
        'total_compensation',
        'revision_limit',
        'status',
        'rejection_reason',
        'sent_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'total_compensation' => 'decimal:2',
            'revision_limit' => 'integer',
            'status' => AgreementStatus::class,
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(AgreementCancellation::class)->latest('created_at');
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ProgressUpdate::class)->latest('created_at');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class)->orderByDesc('version_number');
    }

    public function revisionRequests(): HasManyThrough
    {
        return $this->hasManyThrough(RevisionRequest::class, Submission::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function isActive(): bool
    {
        return $this->status === AgreementStatus::Active;
    }

    /** Jumlah revisi yang sudah diminta masih di bawah batas kesepakatan. */
    public function canRequestRevision(): bool
    {
        return $this->revisionRequests()->count() < $this->revision_limit;
    }
}
