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
        Schema::table('layanan', function (Blueprint $table) {
            // Hapus kolom margin dan tarif
            $table->dropColumn(['margin', 'tarif']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layanan', function (Blueprint $table) {
            // Tambah kembali kolom margin dan tarif jika rollback
            $table->decimal('margin', 8, 2)->default(0)->after('unit_cost');
            $table->decimal('tarif', 10, 2)->default(0)->after('margin');
        });
    }
};
