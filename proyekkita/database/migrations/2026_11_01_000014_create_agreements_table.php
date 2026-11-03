<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel agreements: kesepakatan pekerjaan antara UMKM dan kandidat yang diterima.
 * Berisi ruang lingkup, hasil yang harus diserahkan, tenggat, kompensasi, batas revisi, dan status kesepakatan.
 * Satu lamaran hanya menghasilkan paling banyak satu kesepakatan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('applications');
            $table->text('scope');
            $table->text('deliverables');
            $table->date('deadline');
            $table->decimal('total_compensation', 12, 2);
            $table->unsignedInteger('revision_limit');
            $table->string('status', 20)->default('draft')->comment('draft | sent | active | rejected | completed | cancelled');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
