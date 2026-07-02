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
            $table->integer('bl_l_nm')->nullable()->after('ae_parallel_2');
            $table->integer('ss_multiplier_me_dynamic')->default(2)->after('ss_multiplier_ae');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_baselines', function (Blueprint $table) {
            $table->dropColumn(['bl_l_nm', 'ss_multiplier_me_dynamic']);
        });
    }
};
