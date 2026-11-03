<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel categories.
 * Kategori proyek yang dikelola admin. Kategori dinonaktifkan (is_active = false), bukan dihapus.
 */
class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** Data master nonaktif tidak dapat dipilih untuk data baru. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
