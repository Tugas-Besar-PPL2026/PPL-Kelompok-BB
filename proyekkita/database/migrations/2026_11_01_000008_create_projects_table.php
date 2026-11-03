<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel projects: proyek kerja jangka pendek yang dibuat UMKM.
 * Berisi judul, deskripsi, hasil yang diharapkan, tenggat lamaran, target penyelesaian, kuota kandidat,
 * total kompensasi per kandidat, dan status proyek (draf sampai ditutup).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_profile_id')->constrained('umkm_profiles');
            $table->foreignId('category_id')->constrained('categories');
            $table->string('title', 150);
            $table->text('description');
            $table->text('expected_output');
            $table->date('application_deadline');
            $table->date('target_completion_date');
            $table->unsignedInteger('quota');
            $table->decimal('total_compensation', 12, 2);
            $table->string('status', 20)->default('draft')->comment('draft | pending_review | revision_requested | rejected | published | closed');
            $table->timestamp('published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
