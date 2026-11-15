<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('umkm_profiles', 'description')) {
            Schema::table('umkm_profiles', function (Blueprint $table) {
                $table->text('description')->nullable()->after('business_field');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('umkm_profiles', 'description')) {
            Schema::table('umkm_profiles', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};