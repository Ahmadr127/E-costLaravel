<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained('simulations')->cascadeOnDelete();
            $table->foreignId('layanan_id')->constrained('layanan')->restrictOnDelete();
            $table->string('kode');
            $table->string('jenis_pemeriksaan');
            $table->unsignedInteger('tarif_master')->default(0);
            $table->unsignedInteger('unit_cost')->default(0);
            $table->unsignedInteger('margin_value')->default(0);
            $table->unsignedSmallInteger('margin_percentage')->default(0); // store as percentage (e.g., 10 => 10%)
            $table->unsignedInteger('total_tarif')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_items');
    }
};


