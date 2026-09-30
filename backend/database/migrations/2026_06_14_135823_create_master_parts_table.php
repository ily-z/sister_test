<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_parts', function (Blueprint $table) {
            $table->id();
            $table->string('nama_part', 100);
            $table->string('kategori', 50)->comment('Mesin|Kaki-kaki|Aksesoris|Interior');
            $table->string('icon', 50)->default('build')->comment('Material symbol icon name');
            $table->boolean('is_track_km')->default(true)->comment('True = hitung KM, False = Aksesoris statis');
            $table->unsignedInteger('default_lifespan_km')->nullable()->comment('Rekomendasi umur KM. Null jika is_track_km = false');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_parts');
    }
};