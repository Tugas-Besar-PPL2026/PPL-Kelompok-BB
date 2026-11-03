<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel payment_terms: skema pembayaran proyek per termin.
 * Berisi urutan termin, nominal, dan kondisi pembayarannya. Nomor termin tidak boleh berulang dalam satu proyek.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedInteger('term_number');
            $table->decimal('amount', 12, 2);
            $table->string('condition', 255);
            $table->unique(['project_id', 'term_number'], 'payment_terms_unique_1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};
