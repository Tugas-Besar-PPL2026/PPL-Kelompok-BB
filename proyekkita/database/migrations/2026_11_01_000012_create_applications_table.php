<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel applications: lamaran kandidat pada sebuah proyek.
 * Berisi pesan lamaran, status seleksi, dan catatan seleksi dari UMKM.
 * Kombinasi proyek + kandidat dibuat unik agar kandidat tidak bisa melamar dua kali pada proyek yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('candidate_profile_id')->constrained('candidate_profiles');
            $table->text('message');
            $table->string('status', 20)->default('pending')->comment('pending | shortlisted | accepted | rejected | cancelled');
            $table->text('selection_note')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->unique(['project_id', 'candidate_profile_id'], 'applications_unique_1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
