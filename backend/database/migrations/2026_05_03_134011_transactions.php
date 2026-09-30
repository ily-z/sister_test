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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('tipe', 15)->comment('Pemasukan|Pengeluaran');
            $table->string('kategori', 30)->comment('sewa|servis|operasional_mobil|kantor');
            $table->decimal('jumlah', 12, 2);
            $table->date('tanggal');
            $table->text('deskripsi')->nullable();
            $table->string('referensi_type', 30)->nullable()->comment('booking|service_log|null');
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->foreignId('car_id')->nullable()->constrained('cars')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
