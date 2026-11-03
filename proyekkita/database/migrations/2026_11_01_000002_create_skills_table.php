<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel skills: daftar keterampilan yang dikelola admin.
 * Dipakai pada profil kandidat dan kebutuhan proyek. Dinonaktifkan lewat is_active, tidak dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
