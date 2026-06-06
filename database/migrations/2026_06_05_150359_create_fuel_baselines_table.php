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
        Schema::create('fuel_baselines', function (Blueprint $table) {
            $table->id();
            $table->string('vessel_id', 3);
            $table->foreign('vessel_id')->references('vessel_id')->on('vessels')->cascadeOnDelete();
            $table->integer('bl_mfo')->default(0);
            $table->integer('bl_hsd')->default(0);
            $table->integer('speed')->default(0);
            $table->tinyInteger('ss_multiplier_mfo')->default(2);
            $table->tinyInteger('ss_multiplier_hsd')->default(2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_baselines');
    }
};
