<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel candidate_profiles: profil kandidat (mahasiswa atau lulusan baru).
 * Berisi data pribadi, data akademik, domisili, dan ringkasan diri. Satu akun kandidat hanya memiliki satu profil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('full_name', 100);
            $table->string('photo', 255)->nullable();
            $table->string('headline', 150);
            $table->string('university', 150);
            $table->string('major', 100);
            $table->string('city', 100);
            $table->text('summary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profiles');
    }
};
