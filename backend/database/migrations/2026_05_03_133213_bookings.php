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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pesanan', 20)->unique()->comment('Auto-generated: YYYYMMDD-0001');
            $table->string('nama_pelanggan', 100);
            $table->string('kontak_pelanggan', 50);
            $table->string('email', 150)->nullable(); 
            $table->json('foto_ktp')->nullable();
            $table->json('foto_penyewa')->nullable(); 
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('lokasi_jemput', 255)->nullable();
            $table->string('lokasi_antar', 255)->nullable();
            $table->string('tipe_sewa', 10)->comment('harian|kontrak');
            $table->string('status_pembayaran', 20)->default('Belum Bayar')->comment('Belum Bayar|DP|Belum Lunas|Lunas|Refund Pending|Refunded');
            $table->string('status_rental', 20)->default('Menunggu')->comment('Menunggu|Aktif|Selesai|Batal');
            $table->decimal('total_harga', 12, 2)->nullable()->comment('Override total untuk kontrak');
            $table->decimal('total_addons', 12, 2)->default(0.00);
            $table->decimal('dp_amount', 12, 2)->default(0); 
            $table->decimal('refund_amount', 12, 2)->default(0)->comment('Total dana yang sudah direfund');
            $table->string('foto_pendukung', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['picked_up_at', 'returned_at', 'dp_amount', 'created_by']);
        });
    }
};
