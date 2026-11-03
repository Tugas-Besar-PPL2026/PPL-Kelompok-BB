<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel application_status_histories: riwayat setiap perubahan status lamaran beserta catatannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('status', 20);
            $table->text('note')->nullable();
            $table->timestamp('changed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
    }
};
