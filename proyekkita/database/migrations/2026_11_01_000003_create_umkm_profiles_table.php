<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel umkm_profiles: profil usaha milik akun UMKM.
 * Berisi identitas usaha, kontak, alamat, informasi legalitas (NIB / tautan usaha), serta status dan hasil verifikasi oleh admin.
 * Satu akun UMKM hanya memiliki satu profil usaha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('business_name', 150);
            $table->text('description');
            $table->string('business_field', 100);
            $table->string('contact_phone', 20);
            $table->string('city', 100);
            $table->text('address');
            $table->string('nib', 20)->nullable();
            $table->string('business_link', 255)->nullable();
            $table->string('verification_status', 20)->default('pending')->comment('pending | verified | rejected');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_profiles');
    }
};
