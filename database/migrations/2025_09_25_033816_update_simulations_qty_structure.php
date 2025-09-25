<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add simulation-level qty and margin fields to simulations_qty table
        Schema::table('simulations_qty', function (Blueprint $table) {
            $table->unsignedInteger('simulation_quantity')->default(1)->after('default_margin_percent');
            $table->unsignedInteger('simulation_margin_percent')->default(0)->after('simulation_quantity');
            $table->unsignedBigInteger('total_unit_cost')->default(0)->after('simulation_margin_percent');
            $table->unsignedBigInteger('total_margin_value')->default(0)->after('total_unit_cost');
        });

        // Remove individual qty and margin fields from simulation_qty_items table
        Schema::table('simulation_qty_items', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'margin_value', 'margin_percentage', 'total_tarif']);
        });
    }

    public function down(): void
    {
        // Remove simulation-level fields
        Schema::table('simulations_qty', function (Blueprint $table) {
            $table->dropColumn(['simulation_quantity', 'simulation_margin_percent', 'total_unit_cost', 'total_margin_value']);
        });

        // Restore individual qty and margin fields
        Schema::table('simulation_qty_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('layanan_id');
            $table->unsignedBigInteger('margin_value')->default(0)->after('unit_cost');
            $table->unsignedInteger('margin_percentage')->default(0)->after('margin_value');
            $table->unsignedBigInteger('total_tarif')->default(0)->after('margin_percentage');
        });
    }
};