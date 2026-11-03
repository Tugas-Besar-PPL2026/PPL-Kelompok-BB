<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel submissions: hasil pekerjaan yang diserahkan kandidat per versi (file, tautan, deskripsi) beserta status peninjauannya.
 * Nomor versi tidak boleh berulang dalam satu kesepakatan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements');
            $table->unsignedInteger('version_number');
            $table->string('file_path', 255)->nullable();
            $table->string('result_link', 255)->nullable();
            $table->text('description');
            $table->string('status', 20)->default('submitted')->comment('submitted | approved | revision_requested');
            $table->timestamp('submitted_at')->nullable();
            $table->unique(['agreement_id', 'version_number'], 'submissions_unique_1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
