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
        Schema::create('layanan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->nullable()->unique();
            // nama_layanan dihapus sesuai kebutuhan terbaru
            $table->foreignId('kategori_id')->constrained('kategori')->onDelete('cascade');
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('margin', 5, 2)->default(0); // persentase margin
            $table->decimal('tarif', 15, 2);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layanan');
    }
};
