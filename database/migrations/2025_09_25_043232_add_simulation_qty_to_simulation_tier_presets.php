<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulation_tier_presets', function (Blueprint $table) {
            $table->unsignedInteger('simulation_qty')->default(1)->after('tiers');
        });
    }

    public function down(): void
    {
        Schema::table('simulation_tier_presets', function (Blueprint $table) {
            $table->dropColumn('simulation_qty');
        });
    }
};