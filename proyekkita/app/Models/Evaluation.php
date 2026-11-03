<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel evaluations.
 * Penilaian (rating 1–5 dan komentar) dari satu pihak ke pihak lain setelah kesepakatan selesai.
 * Satu pihak hanya bisa menilai satu kali per kesepakatan dan penilaian tidak bisa diubah.
 */
class Evaluation extends Model
{
    use HasFactory;

    protected $table = 'evaluations';

    public const UPDATED_AT = null;

    protected $fillable = [
        'agreement_id',
        'reviewer_id',
        'reviewee_id',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
