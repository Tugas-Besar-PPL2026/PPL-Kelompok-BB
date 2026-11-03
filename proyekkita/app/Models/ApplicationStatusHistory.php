<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel application_status_histories.
 * Satu catatan perubahan status lamaran beserta waktunya.
 */
class ApplicationStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'application_status_histories';

    public $timestamps = false;

    protected $fillable = [
        'application_id',
        'status',
        'note',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
