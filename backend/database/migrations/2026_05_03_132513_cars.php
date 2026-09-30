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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('plat_nomor', 15)->unique();
            $table->string('merk', 50);
            $table->string('model', 100)->nullable();
            $table->string('warna', 50)->nullable();
            $table->year('tahun');
            $table->unsignedTinyInteger('kapasitas')->comment('jumlah penumpang');
            $table->decimal('harga_default', 12, 2)->default(0)->comment('Harga sewa dasar per hari');
            $table->decimal('harga_kontrak', 12, 2)->default(0)->comment('Harga sewa per hari khusus kontrak');
            $table->string('foto', 255)->nullable();
            $table->string('no_mesin', 50)->nullable()->unique();
            $table->string('no_rangka', 50)->nullable()->unique();
            $table->unsignedInteger('km_saat_ini')->default(0);
            $table->string('status', 20)->default('Tersedia')->comment('Tersedia|Sedang Disewa|Masuk Bengkel|Nonaktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // BUG FIX: Diganti dari users menjadi cars
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('harga_kontrak');
        });
    }
};