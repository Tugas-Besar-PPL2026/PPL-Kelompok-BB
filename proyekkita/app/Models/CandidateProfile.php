<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel candidate_profiles.
 * Profil kandidat (mahasiswa/lulusan baru) beserta relasi ke keterampilan, pengalaman, portofolio, dan lamaran.
 */
class CandidateProfile extends Model
{
    use HasFactory;

    protected $table = 'candidate_profiles';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'full_name',
        'photo',
        'headline',
        'university',
        'major',
        'city',
        'summary',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    /** Keterampilan dari data master. */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'candidate_skill');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function averageRating(): ?float
    {
        return $this->user?->averageRating();
    }

    /** Jumlah kesepakatan yang selesai. */
    public function getCompletedProjectCount(): int
    {
        return Agreement::query()
            ->whereHas('application', fn ($q) => $q->where('candidate_profile_id', $this->id))
            ->where('status', AgreementStatus::Completed)
            ->count();
    }
}
