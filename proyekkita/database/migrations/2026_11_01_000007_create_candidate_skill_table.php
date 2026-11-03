<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel candidate_skill: tabel penghubung antara kandidat dan keterampilan yang dimilikinya (many-to-many).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_skill', function (Blueprint $table) {
            $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->primary(['candidate_profile_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_skill');
    }
};
