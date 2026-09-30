<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_dokumen');
            $table->string('kode');
            $table->string('subkategori')->nullable();
            $table->string('perusahaan')->default('Jaya Royal Bersama');
            $table->string('perusahaan_singkat')->default('JRB');
            $table->string('no_urut')->nullable();
            $table->string('bulan')->nullable();
            $table->string('tahun')->nullable();
            $table->string('nomor_surat')->default('XXXX/SRT/XX/JRB/XXXX/XXXX');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};
