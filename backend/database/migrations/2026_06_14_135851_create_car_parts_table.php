<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained('cars')->onDelete('cascade');
            $table->foreignId('master_part_id')->nullable()->constrained('master_parts')->onDelete('set null');
            
            // Kolom ini di-copy dari master_parts agar admin bisa "nyetting sendiri" per mobil
            $table->string('nama_part', 100);
            $table->string('icon', 50)->default('build');
            $table->boolean('is_track_km')->default(true);
            $table->unsignedInteger('lifespan_km')->nullable()->comment('Target umur KM part ini pada mobil ini');
            
            // Tracking
            $table->unsignedInteger('last_service_km')->default(0)->comment('Posisi Odometer saat part ini dipasang/diservis');
            $table->date('last_service_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_parts');
    }
};