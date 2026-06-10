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
        Schema::table('fuel_baselines', function (Blueprint $table) {
            $table->integer('bl_ae_1_reffer')->nullable()->after('bl_hsd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_baselines', function (Blueprint $table) {
            $table->dropColumn('bl_ae_1_reffer');
        });
    }
};
