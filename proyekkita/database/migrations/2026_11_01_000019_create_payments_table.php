<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel payments: catatan pembayaran kompensasi per termin.
 * Pembayaran dilakukan di luar aplikasi; tabel ini menyimpan tanggal, nomor referensi, bukti transfer,
 * status, dan catatan dari kandidat. Setiap termin hanya dicatat satu kali per kesepakatan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements');
            $table->foreignId('payment_term_id')->constrained('payment_terms');
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending')->comment('pending | paid | confirmed | reported | cancelled');
            $table->date('paid_at')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('proof_file', 255)->nullable();
            $table->text('candidate_note')->nullable();
            $table->unique(['agreement_id', 'payment_term_id'], 'payments_unique_1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
