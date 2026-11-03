<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Model untuk tabel users.
 * Menyimpan akun semua pengguna (admin, UMKM, kandidat). Kolom role menentukan hak akses,
 * dan model ini menyediakan helper untuk cek peran, rata-rata rating, serta halaman awal setelah login.
 */
class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';

    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'created_at' => 'datetime',
        ];
    }

    public function umkmProfile(): HasOne
    {
        return $this->hasOne(UmkmProfile::class);
    }

    public function candidateProfile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class);
    }

    /** Notifikasi dalam aplikasi. Memakai tabel notifications milik aplikasi, bukan notifikasi bawaan Laravel. */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest('created_at');
    }

    /** Moderasi proyek yang dilakukan admin ini. */
    public function moderations(): HasMany
    {
        return $this->hasMany(ProjectModeration::class, 'admin_id');
    }

    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(AgreementCancellation::class, 'requested_by');
    }

    public function givenEvaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'reviewer_id');
    }

    public function receivedEvaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'reviewee_id');
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $value = $role instanceof UserRole ? $role->value : $role;
            if ($this->role?->value === $value) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isUmkm(): bool
    {
        return $this->role === UserRole::Umkm;
    }

    public function isKandidat(): bool
    {
        return $this->role === UserRole::Kandidat;
    }

    /** Rata-rata rating yang diterima pengguna ini. */
    public function averageRating(): ?float
    {
        $avg = $this->receivedEvaluations()->avg('rating');

        return $avg === null ? null : round((float) $avg, 1);
    }

    /** Halaman awal setelah login sesuai peran. */
    public function homeRoute(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'admin.master.index',
            UserRole::Umkm => 'umkm.profil.edit',
            UserRole::Kandidat => 'kandidat.profil.edit',
        };
    }
}
