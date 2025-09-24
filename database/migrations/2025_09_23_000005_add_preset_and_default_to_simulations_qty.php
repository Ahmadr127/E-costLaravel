<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulations_qty', function (Blueprint $table) {
            $table->unsignedBigInteger('tier_preset_id')->nullable()->after('user_id');
            $table->unsignedInteger('default_margin_percent')->default(0)->after('tier_preset_id');
        });
    }

    public function down(): void
    {
        Schema::table('simulations_qty', function (Blueprint $table) {
            $table->dropColumn(['tier_preset_id', 'default_margin_percent']);
        });
    }
};


