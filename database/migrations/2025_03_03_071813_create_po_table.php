<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('po', function (Blueprint $table) {
            $table->id('id_Po'); // Primary Key (id_Po)
            $table->date('Tanggal')->useCurrent(); // Date of PO
            $table->string('No_PO'); // PO Number
            $table->string('Kode_Shiptos')->nullable(); // Ship-to Code
            $table->string('nama_Shiptos')->nullable(); // Ship-to Name
            $table->string('Kode_Plants')->nullable(); // Plant Code
            $table->string('nama_Plants')->nullable(); // Plant Name
            $table->string('kode_product')->nullable(); // Product Code
            $table->string('nama_product')->nullable(); // Product Name
            $table->integer('Qty_Sisa')->nullable(); // Remaining Quantity
            $table->string('Status')->nullable(); // PO Status
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('po');
    }
}