<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_log_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_log_id')->constrained('service_logs')->onDelete('cascade');
            
            // Relasi ke part mobil yang sedang diservis (opsional jika servisnya bersifat umum/cuci mobil)
            $table->foreignId('car_part_id')->nullable()->constrained('car_parts')->onDelete('set null');
            
            $table->string('deskripsi_tindakan', 255)->comment('Contoh: Ganti Oli Shell 10W-40');
            $table->decimal('biaya', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_log_details');
    }
};