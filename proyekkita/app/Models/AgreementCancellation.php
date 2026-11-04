<?php

namespace App\Models;

use App\Enums\CancellationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel agreement_cancellations.
 * Pengajuan pembatalan kesepakatan. Pembatalan baru berlaku jika pihak lain menyetujuinya.
 */
class AgreementCancellation extends Model
{
    use HasFactory;

    protected $table = 'agreement_cancellations';

    public const UPDATED_AT = null;

    protected $fillable = [
        'agreement_id',
        'requested_by',
        'reason',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CancellationStatus::class,
            'responded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
