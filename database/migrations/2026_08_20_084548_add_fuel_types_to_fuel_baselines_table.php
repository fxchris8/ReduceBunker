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
            $table->string('me_fuel_type')->nullable()->after('vessel_id');
            $table->string('ae_fuel_type')->nullable()->after('me_fuel_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_baselines', function (Blueprint $table) {
            $table->dropColumn('me_fuel_type', 'ae_fuel_type');
        });
    }
};
