<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            // Nomor surat Pelepasan Hak Kendaraan yang menyertai mobil ini
            $table->unsignedBigInteger('surat_id')->nullable()->after('foto_dokumen');
            $table->foreign('surat_id')->references('id')->on('surat')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropForeign(['surat_id']);
            $table->dropColumn('surat_id');
        });
    }
};
