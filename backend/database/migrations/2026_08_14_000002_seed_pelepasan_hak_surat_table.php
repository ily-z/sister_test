<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('surat')->insert([
            'jenis_dokumen' => 'Pelepasan Hak Kendaraan',
            'kode' => 'PHK',
            'subkategori' => null,
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'nomor_surat' => 'XXXX/SRT/PHK/JRB/XXXX/XXXX',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('surat')->where('kode', 'PHK')->delete();
    }
};
