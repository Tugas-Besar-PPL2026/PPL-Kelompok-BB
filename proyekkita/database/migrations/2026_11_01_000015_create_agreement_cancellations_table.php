<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel agreement_cancellations: pengajuan pembatalan kesepakatan.
 * Menyimpan siapa yang mengajukan, alasan, waktu pengajuan, dan tanggapan pihak lainnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements');
            $table->foreignId('requested_by')->constrained('users');
            $table->text('reason');
            $table->string('status', 20)->default('pending')->comment('pending | approved | rejected');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_cancellations');
    }
};
