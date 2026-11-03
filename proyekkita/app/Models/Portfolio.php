<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel portfolios.
 * Satu portofolio kandidat berupa tautan dan/atau dokumen.
 */
class Portfolio extends Model
{
    use HasFactory;

    protected $table = 'portfolios';

    public $timestamps = false;

    protected $fillable = [
        'candidate_profile_id',
        'title',
        'description',
        'link',
        'file_path',
    ];

    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }
}
