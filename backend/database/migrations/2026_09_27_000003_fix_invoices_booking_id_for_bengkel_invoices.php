<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaiki kolom invoices.booking_id.
     *
     * Migration asal (2026_05_03_133606) membuat booking_id NOT NULL + UNIQUE,
     * padahal InvoiceController::bengkel() membuat invoice bengkel (BNG) tanpa
     * booking_id sama sekali, dan InvoiceController::generate() mencari invoice
     * yang sudah ada per pasangan (booking_id, jenis_invoice).
     *
     * Akibatnya:
     *   - tombol "Invoice" di halaman bengkel selalu gagal dengan integrity error,
     *   - satu booking tidak bisa punya lebih dari satu jenis invoice (mis. SH lalu AK).
     *
     * Di MySQL index UNIQUE ikut dipakai oleh foreign key, jadi FK harus dilepas
     * lebih dulu sebelum index-nya bisa dibuang, lalu dipasang lagi dengan index biasa.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropUnique(['booking_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index('booking_id', 'invoices_booking_id_index');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Hanya bisa dikembalikan kalau tidak ada invoice bengkel (booking_id NULL).
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropIndex('invoices_booking_id_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable(false)->change();
            $table->unique('booking_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }
};
