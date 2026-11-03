<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel experiences: pengalaman kerja kandidat.
 * Setiap baris berisi bidang/jenis pekerjaan, durasi, dan deskripsi. Satu kandidat bisa memiliki banyak pengalaman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
            $table->string('work_type', 100);
            $table->string('duration', 50);
            $table->text('description');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
