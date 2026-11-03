<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel revision_requests: permintaan revisi dari UMKM beserta catatan perbaikan untuk suatu penyerahan hasil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions');
            $table->text('note');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_requests');
    }
};
