<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulations_qty', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('sum_unit_cost')->default(0);
            $table->unsignedBigInteger('sum_tarif_master')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->unsignedInteger('items_count')->default(0);
            $table->timestamps();
        });

        Schema::create('simulation_qty_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_qty_id');
            $table->unsignedBigInteger('layanan_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('kode');
            $table->string('jenis_pemeriksaan');
            $table->unsignedBigInteger('tarif_master')->default(0);
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->unsignedBigInteger('margin_value')->default(0);
            $table->unsignedInteger('margin_percentage')->default(0); // stored as percent
            $table->unsignedBigInteger('total_tarif')->default(0); // per unit total
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_qty_items');
        Schema::dropIfExists('simulations_qty');
    }
};


