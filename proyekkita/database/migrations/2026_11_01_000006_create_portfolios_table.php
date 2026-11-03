<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel portfolios: portofolio kandidat berupa tautan dan/atau dokumen hasil pekerjaan sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description');
            $table->string('link', 255)->nullable();
            $table->string('file_path', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
