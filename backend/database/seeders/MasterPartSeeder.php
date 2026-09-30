<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterPart;
use Illuminate\Support\Facades\DB;

class MasterPartSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Matikan proteksi foreign key sementara
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        // 2. Kosongkan tabel (sekarang dijamin aman dan tidak error)
        \Illuminate\Support\Facades\DB::table('master_parts')->truncate();

        // 3. Nyalakan proteksi foreign key kembali
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $parts = [
            // ==========================================
            // KATEGORI MESIN & CAIRAN (Track KM = True)
            // ==========================================
            ['nama_part' => 'Oli Mesin (Engine Oil)', 'kategori' => 'Mesin', 'icon' => 'water_drop', 'is_track_km' => true, 'default_lifespan_km' => 10000, 'deskripsi' => 'Penggantian oli mesin rutin (10W-40 / 5W-30)'],
            ['nama_part' => 'Filter Oli', 'kategori' => 'Mesin', 'icon' => 'filter_alt', 'is_track_km' => true, 'default_lifespan_km' => 10000, 'deskripsi' => 'Diganti bersamaan dengan Oli Mesin'],
            ['nama_part' => 'Filter Udara', 'kategori' => 'Mesin', 'icon' => 'air', 'is_track_km' => true, 'default_lifespan_km' => 20000, 'deskripsi' => 'Pembersihan / Penggantian filter udara'],
            ['nama_part' => 'Filter AC (Cabin Filter)', 'kategori' => 'Mesin', 'icon' => 'ac_unit', 'is_track_km' => true, 'default_lifespan_km' => 10000, 'deskripsi' => 'Penggantian filter kabin agar AC tetap dingin & bersih'],
            ['nama_part' => 'Filter Bensin (Fuel Filter)', 'kategori' => 'Mesin', 'icon' => 'local_gas_station', 'is_track_km' => true, 'default_lifespan_km' => 40000, 'deskripsi' => 'Penggantian filter bahan bakar'],
            ['nama_part' => 'Busi (Spark Plug)', 'kategori' => 'Mesin', 'icon' => 'electric_bolt', 'is_track_km' => true, 'default_lifespan_km' => 20000, 'deskripsi' => 'Penggantian set busi pengapian'],
            ['nama_part' => 'Oli Transmisi (Matic/Manual)', 'kategori' => 'Mesin', 'icon' => 'settings', 'is_track_km' => true, 'default_lifespan_km' => 40000, 'deskripsi' => 'Kuras dan ganti oli transmisi'],
            ['nama_part' => 'Air Radiator (Coolant)', 'kategori' => 'Mesin', 'icon' => 'water_drop', 'is_track_km' => true, 'default_lifespan_km' => 40000, 'deskripsi' => 'Kuras dan ganti air radiator'],
            ['nama_part' => 'Timing Belt / V-Belt', 'kategori' => 'Mesin', 'icon' => 'settings', 'is_track_km' => true, 'default_lifespan_km' => 80000, 'deskripsi' => 'Penggantian sabuk penggerak mesin'],

            // ==========================================
            // KATEGORI KAKI-KAKI & REM (Track KM = True)
            // ==========================================
            ['nama_part' => 'Kampas Rem Depan (Brake Pad)', 'kategori' => 'Kaki-kaki', 'icon' => 'tire_repair', 'is_track_km' => true, 'default_lifespan_km' => 30000, 'deskripsi' => 'Penggantian kampas rem cakram depan'],
            ['nama_part' => 'Kampas Rem Belakang (Brake Shoe)', 'kategori' => 'Kaki-kaki', 'icon' => 'tire_repair', 'is_track_km' => true, 'default_lifespan_km' => 40000, 'deskripsi' => 'Penggantian kampas rem tromol/cakram belakang'],
            ['nama_part' => 'Minyak Rem (Brake Fluid)', 'kategori' => 'Kaki-kaki', 'icon' => 'water_drop', 'is_track_km' => true, 'default_lifespan_km' => 40000, 'deskripsi' => 'Kuras dan ganti minyak rem'],
            ['nama_part' => 'Ban (Tire)', 'kategori' => 'Kaki-kaki', 'icon' => 'album', 'is_track_km' => true, 'default_lifespan_km' => 50000, 'deskripsi' => 'Penggantian ban aus / rotasi ban'],
            ['nama_part' => 'Shockbreaker', 'kategori' => 'Kaki-kaki', 'icon' => 'hardware', 'is_track_km' => true, 'default_lifespan_km' => 60000, 'deskripsi' => 'Penggantian peredam kejut depan/belakang'],
            ['nama_part' => 'Spooring & Balancing', 'kategori' => 'Kaki-kaki', 'icon' => 'tire_repair', 'is_track_km' => true, 'default_lifespan_km' => 10000, 'deskripsi' => 'Penyelarasan roda dan keseimbangan ban'],

            // ==========================================
            // KATEGORI KELISTRIKAN (Track KM = True/False)
            // ==========================================
            ['nama_part' => 'Aki (Battery)', 'kategori' => 'Kelistrikan', 'icon' => 'battery_charging_full', 'is_track_km' => true, 'default_lifespan_km' => 30000, 'deskripsi' => 'Pengecekan tegangan atau ganti aki'],
            ['nama_part' => 'Bohlam Lampu Utama (Headlamp)', 'kategori' => 'Kelistrikan', 'icon' => 'lightbulb', 'is_track_km' => false, 'default_lifespan_km' => null, 'deskripsi' => 'Ganti lampu depan putus/redup'],
            ['nama_part' => 'Bohlam Lampu Rem / Sein', 'kategori' => 'Kelistrikan', 'icon' => 'lightbulb', 'is_track_km' => false, 'default_lifespan_km' => null, 'deskripsi' => 'Ganti lampu rem atau lampu sein'],

            // ==========================================
            // KATEGORI AKSESORIS / INTERIOR (Track KM = False)
            // ==========================================
            ['nama_part' => 'Kaca Film', 'kategori' => 'Aksesoris', 'icon' => 'wb_shade', 'is_track_km' => false, 'default_lifespan_km' => null, 'deskripsi' => 'Pemasangan kaca film anti panas'],
            ['nama_part' => 'Karpet Dasar', 'kategori' => 'Interior', 'icon' => 'layers', 'is_track_km' => false, 'default_lifespan_km' => null, 'deskripsi' => 'Pemasangan karpet karet/dasar'],
            ['nama_part' => 'Sarung Jok (Seat Cover)', 'kategori' => 'Interior', 'icon' => 'chair', 'is_track_km' => false, 'default_lifespan_km' => null, 'deskripsi' => 'Pemasangan sarung jok pelindung'],
            ['nama_part' => 'Wiper Kaca', 'kategori' => 'Aksesoris', 'icon' => 'wiper', 'is_track_km' => false, 'default_lifespan_km' => null, 'deskripsi' => 'Ganti karet wiper depan/belakang'],
            ['nama_part' => 'APAR (Pemadam Api)', 'kategori' => 'Aksesoris', 'icon' => 'fire_extinguisher', 'is_track_km' => false, 'default_lifespan_km' => null, 'deskripsi' => 'Tabung pemadam api ringan di dalam kabin'],
            ['nama_part' => 'Dongkrak & Toolkit', 'kategori' => 'Aksesoris', 'icon' => 'handyman', 'is_track_km' => false, 'default_lifespan_km' => null, 'deskripsi' => 'Perlengkapan kunci ban dan dongkrak standar'],
        ];

        foreach ($parts as $part) {
            MasterPart::create($part);
        }
    }
}