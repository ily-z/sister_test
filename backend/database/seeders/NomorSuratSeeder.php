<?php

namespace Database\Seeders;

use App\Models\Surat;
use Illuminate\Database\Seeder;

class NomorSuratSeeder extends Seeder
{
    public function run(): void
    {
        $suratData = [
            [
                'jenis_dokumen' => 'Invoice Sewa Harian',
                'kode' => 'SH',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/SH/JRB/XXXX/XXXX',
            ],
            [
                'jenis_dokumen' => 'Invoice Sewa Kontrak',
                'kode' => 'SK',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/SK/JRB/XXXX/XXXX',
            ],
            [
                'jenis_dokumen' => 'Invoice Asuransi Kerusakan',
                'kode' => 'AK',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/AK/JRB/XXXX/XXXX',
            ],
            [
                'jenis_dokumen' => 'Invoice Bengkel',
                'kode' => 'BNG',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/BNG/JRB/XXXX/XXXX',
            ],
            [
                'jenis_dokumen' => 'Balik Nama',
                'kode' => 'BN',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/BN/JRB/XXXX/XXXX',
            ],
            [
                'jenis_dokumen' => 'Serah Terima',
                'kode' => 'ST',
                'subkategori' => null,
                'perusahaan' => 'Jaya Royal Bersama',
                'perusahaan_singkat' => 'JRB',
                'nomor_surat' => 'XXXX/SRT/ST/JRB/XXXX/XXXX',
            ],
        ];

        foreach ($suratData as $surat) {
            Surat::create($surat);
        }
    }
}
