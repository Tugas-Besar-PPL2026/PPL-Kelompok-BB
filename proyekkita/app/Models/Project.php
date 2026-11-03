<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel projects.
 * Proyek kerja jangka pendek milik UMKM, termasuk kategori, keterampilan yang dibutuhkan, termin pembayaran,
 * riwayat moderasi, dan lamaran yang masuk.
 */
class Project extends Model
{
    use HasFactory;

    protected $table = 'projects';

    public $timestamps = false;

    protected $fillable = [
        'umkm_profile_id',
        'category_id',
        'title',
        'description',
        'expected_output',
        'application_deadline',
        'target_completion_date',
        'quota',
        'total_compensation',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'application_deadline' => 'date',
            'target_completion_date' => 'date',
            'total_compensation' => 'decimal:2',
            'status' => ProjectStatus::class,
            'published_at' => 'datetime',
            'quota' => 'integer',
        ];
    }

    public function umkmProfile(): BelongsTo
    {
        return $this->belongsTo(UmkmProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'project_skill');
    }

    /** Skema pembayaran per termin. Minimal satu termin. */
    public function paymentTerms(): HasMany
    {
        return $this->hasMany(PaymentTerm::class)->orderBy('term_number');
    }

    public function moderations(): HasMany
    {
        return $this->hasMany(ProjectModeration::class)->latest('created_at');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /** Proyek dipublikasikan dan tenggat lamaran belum lewat. */
    public function isOpen(): bool
    {
        return $this->status === ProjectStatus::Published
            && $this->application_deadline !== null
            && $this->application_deadline->endOfDay()->isFuture();
    }

    public function acceptedCount(): int
    {
        return $this->applications()->where('status', ApplicationStatus::Accepted)->count();
    }

    /** Kuota kandidat masih tersedia. */
    public function hasRemainingQuota(): bool
    {
        return $this->acceptedCount() < $this->quota;
    }

    /** Proyek yang tampil di Eksplor Proyek. */
    public function scopeOpenForApplications(Builder $query): Builder
    {
        return $query->where('status', ProjectStatus::Published)
            ->whereDate('application_deadline', '>=', now()->toDateString());
    }
}
