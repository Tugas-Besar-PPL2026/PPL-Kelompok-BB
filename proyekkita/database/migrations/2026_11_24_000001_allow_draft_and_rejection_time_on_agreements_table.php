<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [PBI-24 / PBI-25] Penyesuaian kecil tabel agreements (tidak menghapus data dan tidak mengganti nama kolom).
 *
 * 1. PBI-24: UMKM boleh menyimpan draf kesepakatan yang belum lengkap. Karena itu kolom isian kesepakatan
 *    (scope, deliverables, deadline, revision_limit) dibuat boleh kosong (nullable). Kelengkapan isian tetap
 *    diwajibkan oleh validasi saat UMKM menekan "Kirim ke Kandidat".
 * 2. PBI-25: menyimpan waktu kandidat menolak kesepakatan (rejected_at), pasangan dari approved_at.
 *
 * Data lama tetap utuh: kolom yang sudah terisi tidak berubah, dan rejected_at bernilai null untuk baris lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->text('scope')->nullable()->change();
            $table->text('deliverables')->nullable()->change();
            $table->date('deadline')->nullable()->change();
            $table->unsignedInteger('revision_limit')->nullable()->change();
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn('rejected_at');
        });
        // Kolom isian sengaja tidak dikembalikan menjadi NOT NULL: draf yang masih kosong akan membuat
        // perintah rollback gagal. Kembalikan secara manual bila memang diperlukan.
    }
};
