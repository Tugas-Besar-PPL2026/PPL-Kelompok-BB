<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel project_moderations: riwayat pemeriksaan proyek oleh admin.
 * Setiap baris menyimpan keputusan (disetujui, minta perbaikan, ditolak), catatan, dan waktu pemeriksaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_moderations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('admin_id')->constrained('users');
            $table->string('decision', 20)->comment('approved | rejected | revision_requested');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_moderations');
    }
};
