<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel sessions untuk driver SESSION_DRIVER=database.
 *
 * Kenapa perlu tabel ini:
 *   1. Kalau SESSION_DRIVER=file, data session tersimpan di disk server
 *      backend dan hilang begitu server di-restart. Setelah backend
 *      dipisah ke server sendiri, itu jadi sumber masalah.
 *   2. Konsisten dengan CACHE_STORE dan QUEUE_CONNECTION yang sudah
 *      lebih dulu diarahkan ke database.
 *   3. Menyiapkan transisi penuh ke Sanctum bearer token tanpa jedanya,
 *      karena Blade legacy masih memakai session selama Fase 3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
