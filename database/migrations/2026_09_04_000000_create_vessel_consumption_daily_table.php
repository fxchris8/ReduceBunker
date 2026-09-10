<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessel_consumption_daily', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->string('vessel_id');
            $table->string('session_type', 10);
            $table->decimal('me_mfo', 14, 2)->default(0);
            $table->decimal('me_hsd', 14, 2)->default(0);
            $table->decimal('ae_mfo', 14, 2)->default(0);
            $table->decimal('ae_hsd', 14, 2)->default(0);
            $table->decimal('boiler_hsd', 14, 2)->default(0);
            $table->decimal('boiler_mfo', 14, 2)->default(0);
            $table->decimal('genset_consum_hsd', 14, 2)->default(0);
            $table->text('deck_daily_work')->nullable();
            $table->text('engine_daily_work')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('steam_time', 14, 2)->default(0);
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['report_date', 'vessel_id', 'session_type'], 'vcd_date_vessel_session_unique');
            $table->index('report_date', 'vcd_report_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_consumption_daily');
    }
};
