<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel umkm_profiles.
 * Profil usaha milik akun UMKM beserta status verifikasinya. UMKM baru bisa mengajukan proyek setelah terverifikasi.
 */
class UmkmProfile extends Model
{
    use HasFactory;

    protected $table = 'umkm_profiles';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'business_name',
        'description',
        'business_field',
        'contact_phone',
        'city',
        'address',
        'nib',
        'business_link',
        'verification_status',
        'rejection_reason',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /** Atribut yang, jika diubah setelah terverifikasi, mengembalikan status ke Menunggu Pemeriksaan. */
    public const VERIFICATION_FIELDS = ['business_name', 'nib', 'business_link'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::Verified;
    }

    /** Rata-rata penilaian dari kandidat. */
    public function averageRating(): ?float
    {
        return $this->user?->averageRating();
    }
}
