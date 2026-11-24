<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [PBI-32 / FR-15] Kolom hasil peninjauan UMKM pada setiap versi penyerahan.
 *  - review_note : catatan opsional saat UMKM menyetujui hasil (catatan revisi tetap di revision_requests).
 *  - reviewed_at : waktu UMKM memberi keputusan (setuju atau minta revisi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->text('review_note')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['review_note', 'reviewed_at']);
        });
    }
};
