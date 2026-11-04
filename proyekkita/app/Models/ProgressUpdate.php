<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel progress_updates.
 * Satu laporan progres pekerjaan dari kandidat: catatan dan persentase penyelesaian.
 */
class ProgressUpdate extends Model
{
    use HasFactory;

    protected $table = 'progress_updates';

    public const UPDATED_AT = null;

    protected $fillable = [
        'agreement_id',
        'note',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }
}
