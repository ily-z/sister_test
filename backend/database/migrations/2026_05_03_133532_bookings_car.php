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
        Schema::create('booking_car', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('car_id')->constrained('cars');
            $table->unsignedTinyInteger('quantity')->default(1);
            $table->decimal('harga_dasar_per_hari', 12, 2)->default(0.00);
            $table->string('catatan_harga', 255)->nullable();
            $table->json('foto_kondisi')->nullable()->comment('Menyimpan array path foto kondisi awal mobil');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
