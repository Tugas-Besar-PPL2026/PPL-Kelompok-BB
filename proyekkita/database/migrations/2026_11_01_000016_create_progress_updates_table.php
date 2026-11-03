<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel progress_updates: laporan progres pekerjaan dari kandidat (catatan dan persentase penyelesaian).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements');
            $table->text('note');
            $table->unsignedTinyInteger('percentage')->comment('0-100');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        // Batasan nilai (CHECK) hanya dipasang di MySQL/MariaDB; SQLite tidak mendukung ALTER TABLE ADD CONSTRAINT
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE progress_updates ADD CONSTRAINT chk_progress_updates_1 CHECK (percentage BETWEEN 0 AND 100)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_updates');
    }
};
