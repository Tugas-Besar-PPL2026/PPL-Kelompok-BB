<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel payments.
 * Catatan pembayaran satu termin. Transfer dilakukan di luar aplikasi; di sini hanya dicatat dan dikonfirmasi.
 */
class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    public $timestamps = false;

    protected $fillable = [
        'agreement_id',
        'payment_term_id',
        'amount',
        'status',
        'paid_at',
        'reference_number',
        'proof_file',
        'candidate_note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'paid_at' => 'date',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }
}
