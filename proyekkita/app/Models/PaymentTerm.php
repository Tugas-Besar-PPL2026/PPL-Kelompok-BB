<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel payment_terms.
 * Satu termin pembayaran proyek: urutan termin, nominal, dan kondisi pembayarannya.
 */
class PaymentTerm extends Model
{
    use HasFactory;

    protected $table = 'payment_terms';

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'term_number',
        'amount',
        'condition',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'term_number' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
