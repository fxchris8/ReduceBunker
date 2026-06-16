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
            $table->renameColumn('ss_multiplier_mfo', 'ss_multiplier_me');
            $table->renameColumn('ss_multiplier_hsd', 'ss_multiplier_ae');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_baselines', function (Blueprint $table) {
            $table->renameColumn('ss_multiplier_me', 'ss_multiplier_mfo');
            $table->renameColumn('ss_multiplier_ae', 'ss_multiplier_hsd');
        });
    }
};
