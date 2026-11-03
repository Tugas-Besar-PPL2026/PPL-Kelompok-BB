<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Model untuk tabel notifications.
 * Notifikasi di dalam aplikasi. Memakai tabel notifications milik aplikasi, bukan notifikasi bawaan Laravel.
 */
class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'related_type',
        'related_id',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Data yang terkait (proyek, lamaran, kesepakatan, dst.) untuk membuka aktivitas terkait. */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }
}
