<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel evaluations: penilaian dua arah (UMKM ke kandidat dan kandidat ke UMKM) setelah kesepakatan selesai.
 * Rating 1–5 dan komentar. Setiap pihak hanya bisa memberi satu penilaian per kesepakatan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements');
            $table->foreignId('reviewer_id')->constrained('users');
            $table->foreignId('reviewee_id')->constrained('users');
            $table->unsignedTinyInteger('rating')->comment('1-5');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->unique(['agreement_id', 'reviewer_id'], 'evaluations_unique_1');
        });

        // Batasan nilai (CHECK) hanya dipasang di MySQL/MariaDB; SQLite tidak mendukung ALTER TABLE ADD CONSTRAINT
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE evaluations ADD CONSTRAINT chk_evaluations_1 CHECK (rating BETWEEN 1 AND 5)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
