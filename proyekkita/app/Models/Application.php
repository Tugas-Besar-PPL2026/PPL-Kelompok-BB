<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model untuk tabel applications.
 * Lamaran kandidat pada proyek, lengkap dengan status seleksi, riwayat status, dan kesepakatan yang dihasilkan.
 */
class Application extends Model
{
    use HasFactory;

    protected $table = 'applications';

    public const UPDATED_AT = null;

    protected $fillable = [
        'project_id',
        'candidate_profile_id',
        'message',
        'status',
        'selection_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->latest('changed_at');
    }

    public function agreement(): HasOne
    {
        return $this->hasOne(Agreement::class);
    }

    /** Lamaran hanya dapat dibatalkan saat Menunggu Keputusan / Terpilih Sementara. */
    public function isCancellable(): bool
    {
        return in_array($this->status, [ApplicationStatus::Pending, ApplicationStatus::Shortlisted], true);
    }
}
