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
            $table->dropColumn(['bl_mfo', 'bl_hsd']);
            $table->integer('static_bl_me')->default(0)->after('vessel_id');
            $table->integer('static_bl_ae')->default(0)->after('static_bl_me');
            $table->integer('dynamic_bl_me')->default(0)->after('static_bl_ae');
            $table->integer('ae_parallel_2')->nullable()->after('dynamic_bl_me');
            $table->integer('density')->default(0)->after('ae_parallel_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_baselines', function (Blueprint $table) {
            $table->dropColumn(['static_bl_me', 'static_bl_ae', 'dynamic_bl_me', 'ae_parallel_2', 'density']);
            $table->integer('bl_mfo')->default(0);
            $table->integer('bl_hsd')->default(0);
        });
    }
};
