<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model untuk tabel skills.
 * Keterampilan yang dikelola admin, dipakai di profil kandidat dan kebutuhan proyek. Dinonaktifkan, bukan dihapus.
 */
class Skill extends Model
{
    use HasFactory;

    protected $table = 'skills';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_skill');
    }

    public function candidateProfiles(): BelongsToMany
    {
        return $this->belongsToMany(CandidateProfile::class, 'candidate_skill');
    }

    /** Data master nonaktif tidak dapat dipilih untuk data baru. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
