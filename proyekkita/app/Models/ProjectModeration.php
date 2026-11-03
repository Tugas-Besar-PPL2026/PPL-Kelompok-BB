<?php

namespace App\Models;

use App\Enums\ModerationDecision;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel project_moderations.
 * Satu hasil pemeriksaan proyek oleh admin: keputusan, catatan, dan waktu pemeriksaan.
 */
class ProjectModeration extends Model
{
    use HasFactory;

    protected $table = 'project_moderations';

    public const UPDATED_AT = null;

    protected $fillable = [
        'project_id',
        'admin_id',
        'decision',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'decision' => ModerationDecision::class,
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
