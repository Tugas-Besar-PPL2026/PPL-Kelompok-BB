<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel experiences.
 * Satu pengalaman kerja kandidat: bidang/jenis pekerjaan, durasi, dan deskripsi.
 */
class Experience extends Model
{
    use HasFactory;

    protected $table = 'experiences';

    public $timestamps = false;

    protected $fillable = [
        'candidate_profile_id',
        'work_type',
        'duration',
        'description',
    ];

    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }
}
