<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $suratData = [
            [
                'jenis_dokumen' => 'Invoice Sewa Harian',
                'kode' => 'SH',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/SH/JRB/XXXX/XXXX',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'jenis_dokumen' => 'Invoice Sewa Kontrak',
                'kode' => 'SK',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/SK/JRB/XXXX/XXXX',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'jenis_dokumen' => 'Invoice Asuransi Kerusakan',
                'kode' => 'AK',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/AK/JRB/XXXX/XXXX',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'jenis_dokumen' => 'Invoice Bengkel',
                'kode' => 'BNG',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/BNG/JRB/XXXX/XXXX',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'jenis_dokumen' => 'Balik Nama',
                'kode' => 'BN',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/BN/JRB/XXXX/XXXX',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'jenis_dokumen' => 'Serah Terima',
                'kode' => 'ST',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/ST/JRB/XXXX/XXXX',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('surat')->insert($suratData);
    }

    public function down(): void
    {
        DB::table('surat')->whereIn('jenis_dokumen', [
            'Invoice Sewa Harian',
            'Invoice Sewa Kontrak',
            'Invoice Asuransi Kerusakan',
            'Invoice Bengkel',
            'Balik Nama',
            'Serah Terima',
        ])->delete();
    }
};
