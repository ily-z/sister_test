<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Car;
use App\Models\Booking;
use App\Models\BookingCar;
use App\Models\BookingAddon;
use App\Models\BookingPayment;
use App\Models\Permission;
use App\Models\Transaction;
use App\Models\OdometerLog;
use App\Models\ServiceLog;
use App\Models\ServiceLogDetail;
use App\Models\CarPart;
use App\Models\MasterPart;
use App\Models\Activity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * PERINGATAN: seeder ini MENGHAPUS SELURUH DATA pada 19 tabel (truncate)
     * sebelum mengisi ulang. Jangan dipakai pada database yang sudah berisi
     * data asli.
     *
     * Untuk mengisi data uji tanpa menghapus data yang ada, gunakan:
     *   php artisan db:seed --class=DummyDataSeeder
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate all tables
        $tables = [
            'invoice_verifications', 'invoices', 'booking_payments', 'booking_addons',
            'booking_car', 'service_log_details', 'service_logs', 'car_parts',
            'odometer_logs', 'transactions', 'user_permissions', 'activities',
            'bookings', 'permissions', 'cars', 'master_parts', 'users', 'surat', 'template_surats',
        ];
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ==========================================
        // 1. USERS
        // ==========================================
        $users = [
            ['nama' => 'Budi Santoso', 'email' => 'owner@example.com', 'password' => Hash::make('password'), 'role' => 'owner', 'is_active' => true],
            ['nama' => 'Siti Rahmawati', 'email' => 'siti@example.com', 'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true],
            ['nama' => 'Dian Pratama', 'email' => 'dian@example.com', 'password' => Hash::make('password'), 'role' => 'reception', 'is_active' => true],
            ['nama' => 'Rudi Hermawan', 'email' => 'rudi@example.com', 'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true],
        ];
        foreach ($users as $u) {
            User::create($u);
        }

        // ==========================================
        // 2. PERMISSIONS
        // ==========================================
        $permissions = [
            ['nama_permission' => 'manage_users', 'deskripsi' => 'Mengelola akun pengguna'],
            ['nama_permission' => 'manage_bookings', 'deskripsi' => 'Mengelola pesanan rental'],
            ['nama_permission' => 'manage_fleet', 'deskripsi' => 'Mengelola armada kendaraan'],
            ['nama_permission' => 'manage_finance', 'deskripsi' => 'Mengelola keuangan'],
            ['nama_permission' => 'view_analytics', 'deskripsi' => 'Melihat halaman analitik'],
            ['nama_permission' => 'manage_settings', 'deskripsi' => 'Mengelola pengaturan aplikasi'],
            ['nama_permission' => 'manage_permissions', 'deskripsi' => 'Mengatur permission role di bawahnya'],
            ['nama_permission' => 'manage_service', 'deskripsi' => 'Mengelola bengkel & servis'],
            ['nama_permission' => 'manage_parts', 'deskripsi' => 'Mengelola data sparepart'],
            ['nama_permission' => 'users.view', 'deskripsi' => 'Melihat data pengguna'],
            ['nama_permission' => 'users.create', 'deskripsi' => 'Menambah pengguna'],
            ['nama_permission' => 'users.edit', 'deskripsi' => 'Mengubah pengguna'],
            ['nama_permission' => 'users.delete', 'deskripsi' => 'Menghapus pengguna'],
            ['nama_permission' => 'bookings.view', 'deskripsi' => 'Melihat data pesanan'],
            ['nama_permission' => 'bookings.create', 'deskripsi' => 'Menambah pesanan'],
            ['nama_permission' => 'bookings.edit', 'deskripsi' => 'Mengubah pesanan'],
            ['nama_permission' => 'bookings.delete', 'deskripsi' => 'Menghapus pesanan'],
            ['nama_permission' => 'fleet.view', 'deskripsi' => 'Melihat data armada'],
            ['nama_permission' => 'fleet.create', 'deskripsi' => 'Menambah armada'],
            ['nama_permission' => 'fleet.edit', 'deskripsi' => 'Mengubah armada'],
            ['nama_permission' => 'fleet.delete', 'deskripsi' => 'Menghapus armada'],
            ['nama_permission' => 'finance.view', 'deskripsi' => 'Melihat data keuangan'],
            ['nama_permission' => 'finance.create', 'deskripsi' => 'Menambah transaksi'],
            ['nama_permission' => 'finance.edit', 'deskripsi' => 'Mengubah transaksi'],
            ['nama_permission' => 'finance.delete', 'deskripsi' => 'Menghapus transaksi'],
            ['nama_permission' => 'service.view', 'deskripsi' => 'Melihat data servis'],
            ['nama_permission' => 'service.create', 'deskripsi' => 'Menambah servis'],
            ['nama_permission' => 'service.edit', 'deskripsi' => 'Mengubah servis'],
            ['nama_permission' => 'service.delete', 'deskripsi' => 'Menghapus servis'],
            ['nama_permission' => 'analytics.view', 'deskripsi' => 'Melihat analitik'],
        ];
        foreach ($permissions as $p) {
            Permission::create($p);
        }

        $permissionIds = Permission::pluck('id', 'nama_permission');
        DB::table('role_permissions')->insert([
            ['role' => 'admin', 'permission_id' => $permissionIds['manage_users']],
            ['role' => 'admin', 'permission_id' => $permissionIds['manage_permissions']],
            ['role' => 'admin', 'permission_id' => $permissionIds['manage_bookings']],
            ['role' => 'reception', 'permission_id' => $permissionIds['manage_bookings']],
        ]);

        $actionPermissionNames = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.delete',
            'fleet.view', 'fleet.create', 'fleet.edit', 'fleet.delete',
            'finance.view', 'finance.create', 'finance.edit', 'finance.delete',
            'service.view', 'service.create', 'service.edit', 'service.delete', 'analytics.view',
        ];
        foreach ($actionPermissionNames as $permissionName) {
            DB::table('role_permissions')->insertOrIgnore([
                'role' => 'admin',
                'permission_id' => $permissionIds[$permissionName],
            ]);
        }

        foreach (array_filter($actionPermissionNames, fn (string $name) => str_ends_with($name, '.view')) as $permissionName) {
            DB::table('role_permissions')->insertOrIgnore([
                'role' => 'reception',
                'permission_id' => $permissionIds[$permissionName],
            ]);
        }
        DB::table('role_permissions')->where('role', 'reception')->where('permission_id', $permissionIds['manage_bookings'])->delete();

        // ==========================================
        // 3. MASTER PARTS
        // ==========================================
        $this->call(MasterPartSeeder::class);

        // ==========================================
        // 4. NOMOR SURAT
        // ==========================================
        $this->call(NomorSuratSeeder::class);

        // ==========================================
        // 4. CARS
        // ==========================================
        $cars = [
            [
                'plat_nomor' => 'B 1234 CDE', 'merk' => 'Toyota', 'model' => 'Avanza 1.3 E',
                'warna' => 'Silver', 'tahun' => 2019, 'kapasitas' => 7,
                'harga_default' => 350000, 'harga_kontrak' => 250000,
                'no_mesin' => '1NRF6391820', 'no_rangka' => 'MHFDE3BM5KZ123456',
                'km_saat_ini' => 45230, 'status' => 'Tersedia',
            ],
            [
                'plat_nomor' => 'B 5678 FGH', 'merk' => 'Daihatsu', 'model' => 'Xenia 1.3 R',
                'warna' => 'Putih', 'tahun' => 2020, 'kapasitas' => 7,
                'harga_default' => 300000, 'harga_kontrak' => 210000,
                'no_mesin' => '1NRF6391821', 'no_rangka' => 'MHFDE3BM5LZ234567',
                'km_saat_ini' => 38910, 'status' => 'Tersedia',
            ],
            [
                'plat_nomor' => 'B 9101 IJK', 'merk' => 'Honda', 'model' => 'Brio Satya E',
                'warna' => 'Merah', 'tahun' => 2021, 'kapasitas' => 5,
                'harga_default' => 250000, 'harga_kontrak' => 175000,
                'no_mesin' => 'L12B3492001', 'no_rangka' => 'MHFDE3BM5MZ345678',
                'km_saat_ini' => 21500, 'status' => 'Tersedia',
            ],
            [
                'plat_nomor' => 'B 1123 LMN', 'merk' => 'Toyota', 'model' => 'Innova Venturer 2.4 G',
                'warna' => 'Hitam', 'tahun' => 2022, 'kapasitas' => 7,
                'harga_default' => 500000, 'harga_kontrak' => 350000,
                'no_mesin' => '2TRFG7293845', 'no_rangka' => 'MHFDE3BM5NZ456789',
                'km_saat_ini' => 18750, 'status' => 'Tersedia',
            ],
            [
                'plat_nomor' => 'B 1415 OPQ', 'merk' => 'Mitsubishi', 'model' => 'Pajero Sport Dakar',
                'warna' => 'Putih', 'tahun' => 2023, 'kapasitas' => 7,
                'harga_default' => 1200000, 'harga_kontrak' => 850000,
                'no_mesin' => '4N15TAG2810', 'no_rangka' => 'MHFDE3BM5OZ567890',
                'km_saat_ini' => 12800, 'status' => 'Sedang Disewa',
            ],
            [
                'plat_nomor' => 'B 1617 RST', 'merk' => 'Suzuki', 'model' => 'Ertiga GX',
                'warna' => 'Biru', 'tahun' => 2020, 'kapasitas' => 7,
                'harga_default' => 350000, 'harga_kontrak' => 250000,
                'no_mesin' => 'K15B3982710', 'no_rangka' => 'MHFDE3BM5PZ678901',
                'km_saat_ini' => 41500, 'status' => 'Tersedia',
            ],
            [
                'plat_nomor' => 'B 1819 UVW', 'merk' => 'Toyota', 'model' => 'Fortuner VRZ 2.4',
                'warna' => 'Hitam', 'tahun' => 2022, 'kapasitas' => 7,
                'harga_default' => 1000000, 'harga_kontrak' => 700000,
                'no_mesin' => '2GDTTV38192', 'no_rangka' => 'MHFDE3BM5QZ789012',
                'km_saat_ini' => 22300, 'status' => 'Tersedia',
            ],
            [
                'plat_nomor' => 'B 2021 XYZ', 'merk' => 'Daihatsu', 'model' => 'Sigra 1.2 R',
                'warna' => 'Abu-abu', 'tahun' => 2023, 'kapasitas' => 7,
                'harga_default' => 250000, 'harga_kontrak' => 175000,
                'no_mesin' => '3NRVGF48103', 'no_rangka' => 'MHFDE3BM5RZ890123',
                'km_saat_ini' => 9800, 'status' => 'Tersedia',
            ],
            [
                'plat_nomor' => 'B 2223 ABC', 'merk' => 'Honda', 'model' => 'Civic RS 1.5 Turbo',
                'warna' => 'Hitam', 'tahun' => 2021, 'kapasitas' => 5,
                'harga_default' => 450000, 'harga_kontrak' => 315000,
                'no_mesin' => 'L15C7392045', 'no_rangka' => 'MHFDE3BM5SZ901234',
                'km_saat_ini' => 19600, 'status' => 'Masuk Bengkel',
            ],
            [
                'plat_nomor' => 'B 2425 DEF', 'merk' => 'Toyota', 'model' => 'Alphard 3.5 V6',
                'warna' => 'Putih', 'tahun' => 2023, 'kapasitas' => 7,
                'harga_default' => 2500000, 'harga_kontrak' => 1750000,
                'no_mesin' => '2GRFKS849201', 'no_rangka' => 'MHFDE3BM5TZ012345',
                'km_saat_ini' => 15600, 'status' => 'Sedang Disewa',
            ],
        ];
        foreach ($cars as $c) {
            Car::create($c);
        }

        // ==========================================
        // 5. CAR PARTS
        // ==========================================
        $masterParts = MasterPart::all();
        $allCars = Car::all();
        foreach ($allCars as $car) {
            foreach ($masterParts as $mp) {
                $lastServiceKm = 0;
                $lastServiceDate = null;
                if ($car->km_saat_ini > 10000 && $mp->is_track_km && $mp->default_lifespan_km) {
                    $ratio = $car->km_saat_ini / $mp->default_lifespan_km;
                    $serviceCount = floor($ratio);
                    if ($serviceCount > 0) {
                        $lastServiceKm = ($serviceCount - 1) * $mp->default_lifespan_km + rand(0, 2000);
                        $lastServiceDate = now()->subMonths(rand(1, 6))->format('Y-m-d');
                    }
                }
                CarPart::create([
                    'car_id' => $car->id,
                    'master_part_id' => $mp->id,
                    'nama_part' => $mp->nama_part,
                    'icon' => $mp->icon,
                    'is_track_km' => $mp->is_track_km,
                    'lifespan_km' => $mp->default_lifespan_km,
                    'last_service_km' => $lastServiceKm,
                    'last_service_date' => $lastServiceDate,
                ]);
            }
        }

        // ==========================================
        // 6. BOOKINGS
        // ==========================================
        $now = now();
        $bookings = [
            [
                'nomor_pesanan' => '20260601-0001',
                'nama_pelanggan' => 'Andi Pratama',
                'kontak_pelanggan' => '081234567891',
                'email' => 'andi.pratama@gmail.com',
                'tanggal_mulai' => '2026-06-01',
                'tanggal_selesai' => '2026-06-03',
                'lokasi_jemput' => 'Jl. Merdeka No. 45, Jakarta Pusat',
                'lokasi_antar' => 'Jl. Merdeka No. 45, Jakarta Pusat',
                'tipe_sewa' => 'harian',
                'status_pembayaran' => 'Lunas',
                'status_rental' => 'Selesai',
                'total_harga' => 1050000,
                'total_addons' => 200000,
                'dp_amount' => 0,
                'catatan' => 'Mobil dalam keadaan bersih',
                'picked_up_at' => '2026-06-01 08:00:00',
                'returned_at' => '2026-06-03 17:30:00',
                'created_by' => 2,
                'created_at' => '2026-05-25 10:00:00',
                'updated_at' => '2026-06-03 17:30:00',
            ],
            [
                'nomor_pesanan' => '20260605-0001',
                'nama_pelanggan' => 'Budi Hartono',
                'kontak_pelanggan' => '081234567892',
                'email' => 'budi.hartono@yahoo.com',
                'tanggal_mulai' => '2026-06-05',
                'tanggal_selesai' => '2026-06-08',
                'lokasi_jemput' => 'Bandara Soekarno-Hatta',
                'lokasi_antar' => 'Bandara Soekarno-Hatta',
                'tipe_sewa' => 'harian',
                'status_pembayaran' => 'Lunas',
                'status_rental' => 'Selesai',
                'total_harga' => 1500000,
                'total_addons' => 0,
                'dp_amount' => 500000,
                'catatan' => 'Penjemputan di terminal 3',
                'picked_up_at' => '2026-06-05 10:30:00',
                'returned_at' => '2026-06-08 14:00:00',
                'created_by' => 2,
                'created_at' => '2026-06-01 09:00:00',
                'updated_at' => '2026-06-08 14:00:00',
            ],
            [
                'nomor_pesanan' => '20260615-0001',
                'nama_pelanggan' => 'Cindy Wijaya',
                'kontak_pelanggan' => '081234567893',
                'email' => 'cindy.wijaya@gmail.com',
                'tanggal_mulai' => '2026-06-15',
                'tanggal_selesai' => '2026-06-20',
                'lokasi_jemput' => 'Jl. Sudirman No. 10, Jakarta Selatan',
                'lokasi_antar' => 'Jl. Sudirman No. 10, Jakarta Selatan',
                'tipe_sewa' => 'harian',
                'status_pembayaran' => 'DP',
                'status_rental' => 'Aktif',
                'total_harga' => 6000000,
                'total_addons' => 500000,
                'dp_amount' => 2000000,
                'catatan' => 'Minta supir profesional',
                'picked_up_at' => '2026-06-15 07:00:00',
                'returned_at' => null,
                'created_by' => 2,
                'created_at' => '2026-06-10 11:00:00',
                'updated_at' => '2026-06-15 07:00:00',
            ],
            [
                'nomor_pesanan' => '20260618-0001',
                'nama_pelanggan' => 'Denny Saputra',
                'kontak_pelanggan' => '081234567894',
                'email' => 'denny.saputra@gmail.com',
                'tanggal_mulai' => '2026-06-18',
                'tanggal_selesai' => '2026-06-21',
                'lokasi_jemput' => 'Perumahan Citra Garden Blok A3 No. 5',
                'lokasi_antar' => 'Perumahan Citra Garden Blok A3 No. 5',
                'tipe_sewa' => 'harian',
                'status_pembayaran' => 'Lunas',
                'status_rental' => 'Aktif',
                'total_harga' => 750000,
                'total_addons' => 0,
                'dp_amount' => 0,
                'catatan' => null,
                'picked_up_at' => '2026-06-18 09:00:00',
                'returned_at' => null,
                'created_by' => 3,
                'created_at' => '2026-06-14 14:00:00',
                'updated_at' => '2026-06-18 09:00:00',
            ],
            [
                'nomor_pesanan' => '20260625-0001',
                'nama_pelanggan' => 'Eko Prasetyo',
                'kontak_pelanggan' => '081234567895',
                'email' => 'eko.prasetyo@gmail.com',
                'tanggal_mulai' => '2026-06-25',
                'tanggal_selesai' => '2026-06-28',
                'lokasi_jemput' => 'Jl. Gatot Subroto Kav. 56, Jakarta Selatan',
                'lokasi_antar' => 'Jl. Gatot Subroto Kav. 56, Jakarta Selatan',
                'tipe_sewa' => 'harian',
                'status_pembayaran' => 'DP',
                'status_rental' => 'Menunggu',
                'total_harga' => 3000000,
                'total_addons' => 0,
                'dp_amount' => 1000000,
                'catatan' => 'Perjalanan ke Bandung',
                'picked_up_at' => null,
                'returned_at' => null,
                'created_by' => 2,
                'created_at' => '2026-06-20 08:00:00',
                'updated_at' => '2026-06-20 08:00:00',
            ],
            [
                'nomor_pesanan' => '20260627-0001',
                'nama_pelanggan' => 'Fitriani',
                'kontak_pelanggan' => '081234567896',
                'email' => 'fitriani@outlook.com',
                'tanggal_mulai' => '2026-06-27',
                'tanggal_selesai' => '2026-06-30',
                'lokasi_jemput' => 'Jl. Raya Bogor KM 28, Depok',
                'lokasi_antar' => 'Jl. Raya Bogor KM 28, Depok',
                'tipe_sewa' => 'harian',
                'status_pembayaran' => 'Belum Bayar',
                'status_rental' => 'Menunggu',
                'total_harga' => 1050000,
                'total_addons' => 150000,
                'dp_amount' => 0,
                'catatan' => 'Mobil harap diisi BBM full',
                'picked_up_at' => null,
                'returned_at' => null,
                'created_by' => 3,
                'created_at' => '2026-06-22 15:00:00',
                'updated_at' => '2026-06-22 15:00:00',
            ],
            [
                'nomor_pesanan' => '20260601-0002',
                'nama_pelanggan' => 'PT. Maju Jaya Abadi',
                'kontak_pelanggan' => '021-5557890',
                'email' => 'info@majujaya.co.id',
                'tanggal_mulai' => '2026-06-01',
                'tanggal_selesai' => '2026-06-30',
                'lokasi_jemput' => 'Kantor PT. Maju Jaya, SCBD Lot 12',
                'lokasi_antar' => 'Kantor PT. Maju Jaya, SCBD Lot 12',
                'tipe_sewa' => 'kontrak',
                'status_pembayaran' => 'Lunas',
                'status_rental' => 'Aktif',
                'total_harga' => 52500000,
                'total_addons' => 0,
                'dp_amount' => 26250000,
                'catatan' => 'Kontrak bulanan untuk direksi, termasuk supir',
                'picked_up_at' => '2026-06-01 06:00:00',
                'returned_at' => null,
                'created_by' => 2,
                'created_at' => '2026-05-20 09:00:00',
                'updated_at' => '2026-06-01 06:00:00',
            ],
            [
                'nomor_pesanan' => '20260610-0001',
                'nama_pelanggan' => 'Gilang Ramadhan',
                'kontak_pelanggan' => '081234567897',
                'email' => 'gilang.r@gmail.com',
                'tanggal_mulai' => '2026-06-10',
                'tanggal_selesai' => '2026-06-12',
                'lokasi_jemput' => 'Jl. Kemang Raya No. 88, Jakarta Selatan',
                'lokasi_antar' => 'Jl. Kemang Raya No. 88, Jakarta Selatan',
                'tipe_sewa' => 'harian',
                'status_pembayaran' => 'Refunded',
                'status_rental' => 'Batal',
                'total_harga' => 600000,
                'total_addons' => 0,
                'dp_amount' => 200000,
                'catatan' => 'Dibatalkan karena kecelakaan ringan sehari sebelumnya',
                'picked_up_at' => null,
                'returned_at' => null,
                'created_by' => 2,
                'created_at' => '2026-06-07 10:00:00',
                'updated_at' => '2026-06-09 08:00:00',
            ],
        ];
        foreach ($bookings as $b) {
            Booking::create($b);
        }

        // ==========================================
        // 7. BOOKING CARS (Pivot)
        // ==========================================
        $bookingCars = [
            ['booking_id' => 1, 'car_id' => 1, 'quantity' => 1, 'harga_dasar_per_hari' => 350000],
            ['booking_id' => 2, 'car_id' => 4, 'quantity' => 1, 'harga_dasar_per_hari' => 500000],
            ['booking_id' => 3, 'car_id' => 5, 'quantity' => 1, 'harga_dasar_per_hari' => 1200000],
            ['booking_id' => 4, 'car_id' => 3, 'quantity' => 1, 'harga_dasar_per_hari' => 250000],
            ['booking_id' => 5, 'car_id' => 7, 'quantity' => 1, 'harga_dasar_per_hari' => 1000000],
            ['booking_id' => 6, 'car_id' => 6, 'quantity' => 1, 'harga_dasar_per_hari' => 350000],
            ['booking_id' => 7, 'car_id' => 10, 'quantity' => 1, 'harga_dasar_per_hari' => 1750000],
            ['booking_id' => 8, 'car_id' => 2, 'quantity' => 1, 'harga_dasar_per_hari' => 300000],
        ];
        foreach ($bookingCars as $bc) {
            BookingCar::create($bc);
        }

        // ==========================================
        // 8. BOOKING ADDONS
        // ==========================================
        $addons = [
            ['booking_id' => 1, 'nama_biaya' => 'Jasa Supir', 'nominal' => 200000],
            ['booking_id' => 3, 'nama_biaya' => 'Jasa Supir', 'nominal' => 500000],
            ['booking_id' => 6, 'nama_biaya' => 'Antar Jemput', 'nominal' => 150000],
            ['booking_id' => 7, 'nama_biaya' => 'Jasa Supir', 'nominal' => 3000000],
        ];
        foreach ($addons as $a) {
            BookingAddon::create($a);
        }

        // ==========================================
        // 9. BOOKING PAYMENTS
        // ==========================================
        $payments = [
            ['booking_id' => 1, 'jumlah' => 1050000, 'status_sebelum' => 'Belum Bayar', 'status_sesudah' => 'Lunas', 'metode_bayar' => 'transfer', 'catatan' => 'Transfer BCA', 'created_by' => 2, 'created_at' => '2026-06-01 07:00:00'],
            ['booking_id' => 2, 'jumlah' => 500000, 'status_sebelum' => 'Belum Bayar', 'status_sesudah' => 'DP', 'metode_bayar' => 'transfer', 'catatan' => 'DP via BCA', 'created_by' => 2, 'created_at' => '2026-06-01 09:00:00'],
            ['booking_id' => 2, 'jumlah' => 1000000, 'status_sebelum' => 'DP', 'status_sesudah' => 'Lunas', 'metode_bayar' => 'tunai', 'catatan' => 'Pelunasan saat ambil mobil', 'created_by' => 2, 'created_at' => '2026-06-05 10:00:00'],
            ['booking_id' => 3, 'jumlah' => 2000000, 'status_sebelum' => 'Belum Bayar', 'status_sesudah' => 'DP', 'metode_bayar' => 'transfer', 'catatan' => 'DP Mandiri', 'created_by' => 2, 'created_at' => '2026-06-10 12:00:00'],
            ['booking_id' => 4, 'jumlah' => 750000, 'status_sebelum' => 'Belum Bayar', 'status_sesudah' => 'Lunas', 'metode_bayar' => 'tunai', 'catatan' => 'Bayar tunai di tempat', 'created_by' => 3, 'created_at' => '2026-06-18 08:00:00'],
            ['booking_id' => 5, 'jumlah' => 1000000, 'status_sebelum' => 'Belum Bayar', 'status_sesudah' => 'DP', 'metode_bayar' => 'transfer', 'catatan' => 'DP BCA', 'created_by' => 2, 'created_at' => '2026-06-20 08:00:00'],
            ['booking_id' => 7, 'jumlah' => 26250000, 'status_sebelum' => 'Belum Bayar', 'status_sesudah' => 'DP', 'metode_bayar' => 'transfer', 'catatan' => 'DP 50% via transfer perusahaan', 'created_by' => 2, 'created_at' => '2026-05-20 10:00:00'],
            ['booking_id' => 7, 'jumlah' => 26250000, 'status_sebelum' => 'DP', 'status_sesudah' => 'Lunas', 'metode_bayar' => 'transfer', 'catatan' => 'Pelunasan kontrak bulan Juni', 'created_by' => 2, 'created_at' => '2026-06-01 07:00:00'],
            ['booking_id' => 8, 'jumlah' => 200000, 'status_sebelum' => 'Belum Bayar', 'status_sesudah' => 'DP', 'metode_bayar' => 'tunai', 'catatan' => 'DP tunai', 'created_by' => 2, 'created_at' => '2026-06-07 10:00:00'],
            ['booking_id' => 8, 'jumlah' => 200000, 'status_sebelum' => 'DP', 'status_sesudah' => 'Refunded', 'metode_bayar' => 'tunai', 'catatan' => 'Refund karena pembatalan', 'created_by' => 2, 'created_at' => '2026-06-09 08:00:00'],
        ];
        foreach ($payments as $p) {
            BookingPayment::create($p);
        }

        // ==========================================
        // 10. TRANSACTIONS
        // ==========================================
        $transactions = [
            // Juni 2026 - Pemasukan
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 1050000, 'tanggal' => '2026-06-01', 'deskripsi' => 'Pembayaran Lunas - Andi Pratama (20260601-0001)', 'referensi_type' => 'booking', 'referensi_id' => 1, 'car_id' => 1, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 52500000, 'tanggal' => '2026-06-01', 'deskripsi' => 'Pembayaran Kontrak Juni - PT. Maju Jaya', 'referensi_type' => 'booking', 'referensi_id' => 7, 'car_id' => 10, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 800000, 'tanggal' => '2026-06-02', 'deskripsi' => 'Pembayaran Rental - Hendra Kusuma', 'referensi_type' => 'booking', 'referensi_id' => 1, 'car_id' => 2, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 1500000, 'tanggal' => '2026-06-05', 'deskripsi' => 'Pembayaran Lunas - Budi Hartono (20260605-0001)', 'referensi_type' => 'booking', 'referensi_id' => 2, 'car_id' => 4, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 600000, 'tanggal' => '2026-06-06', 'deskripsi' => 'Pembayaran Rental - Siti Nurhaliza', 'referensi_type' => 'booking', 'referensi_id' => 2, 'car_id' => 3, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 2000000, 'tanggal' => '2026-06-10', 'deskripsi' => 'DP Pemesanan - Cindy Wijaya (20260615-0001)', 'referensi_type' => 'booking', 'referensi_id' => 3, 'car_id' => 5, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 900000, 'tanggal' => '2026-06-11', 'deskripsi' => 'Pembayaran Rental - Roni Hermawan', 'referensi_type' => 'booking', 'referensi_id' => 3, 'car_id' => 6, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 1200000, 'tanggal' => '2026-06-14', 'deskripsi' => 'Pembayaran Rental - Toni Wijaya', 'referensi_type' => 'booking', 'referensi_id' => 4, 'car_id' => 4, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 750000, 'tanggal' => '2026-06-18', 'deskripsi' => 'Pembayaran Lunas - Denny Saputra (20260618-0001)', 'referensi_type' => 'booking', 'referensi_id' => 4, 'car_id' => 3, 'user_id' => 3],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 1000000, 'tanggal' => '2026-06-20', 'deskripsi' => 'DP Pemesanan - Eko Prasetyo (20260625-0001)', 'referensi_type' => 'booking', 'referensi_id' => 5, 'car_id' => 7, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 2500000, 'tanggal' => '2026-06-22', 'deskripsi' => 'Pembayaran Rental - Suryanto Putra', 'referensi_type' => 'booking', 'referensi_id' => 5, 'car_id' => 5, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 1800000, 'tanggal' => '2026-06-25', 'deskripsi' => 'Pembayaran Rental - Dwi Santoso', 'referensi_type' => 'booking', 'referensi_id' => 6, 'car_id' => 2, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 1100000, 'tanggal' => '2026-06-28', 'deskripsi' => 'Pembayaran Rental - Vicky Andrianto', 'referensi_type' => 'booking', 'referensi_id' => 7, 'car_id' => 6, 'user_id' => 2],
            ['tipe' => 'Pemasukan', 'kategori' => 'sewa', 'jumlah' => 700000, 'tanggal' => '2026-06-30', 'deskripsi' => 'Pembayaran Rental - Widi Pratama', 'referensi_type' => 'booking', 'referensi_id' => 8, 'car_id' => 1, 'user_id' => 2],
            
            // Juni 2026 - Pengeluaran
            ['tipe' => 'Pengeluaran', 'kategori' => 'kantor', 'jumlah' => 250000, 'tanggal' => '2026-06-01', 'deskripsi' => 'Isi ulang air galon & ATK', 'car_id' => null, 'user_id' => 2],
            ['tipe' => 'Pengeluaran', 'kategori' => 'servis', 'jumlah' => 850000, 'tanggal' => '2026-06-02', 'deskripsi' => 'Servis rutin Avanza B 1234 CDE - Ganti Oli & Filter', 'referensi_type' => 'service_log', 'referensi_id' => 1, 'car_id' => 1, 'user_id' => 2],
            ['tipe' => 'Pengeluaran', 'kategori' => 'operasional_mobil', 'jumlah' => 300000, 'tanggal' => '2026-06-05', 'deskripsi' => 'Beli bensin & oli untuk armada', 'car_id' => null, 'user_id' => 2],
            ['tipe' => 'Pengeluaran', 'kategori' => 'kantor', 'jumlah' => 200000, 'tanggal' => '2026-06-07', 'deskripsi' => 'Beli supplies kantor & printer ink', 'car_id' => null, 'user_id' => 2],
            ['tipe' => 'Pengeluaran', 'kategori' => 'servis', 'jumlah' => 1250000, 'tanggal' => '2026-06-12', 'deskripsi' => 'Servis Civic B 2223 ABC - Ganti kampas rem depan', 'referensi_type' => 'service_log', 'referensi_id' => 2, 'car_id' => 9, 'user_id' => 2],
            ['tipe' => 'Pengeluaran', 'kategori' => 'operasional_mobil', 'jumlah' => 500000, 'tanggal' => '2026-06-15', 'deskripsi' => 'Cuci mobil armada 5 unit', 'car_id' => null, 'user_id' => 2],
            ['tipe' => 'Pengeluaran', 'kategori' => 'kantor', 'jumlah' => 150000, 'tanggal' => '2026-06-19', 'deskripsi' => 'Beli pulsa internet & listrik', 'car_id' => null, 'user_id' => 2],
            ['tipe' => 'Pengeluaran', 'kategori' => 'operasional_mobil', 'jumlah' => 400000, 'tanggal' => '2026-06-21', 'deskripsi' => 'Servis berkala & penggantian filter AC', 'car_id' => null, 'user_id' => 2],
            ['tipe' => 'Pengeluaran', 'kategori' => 'kantor', 'jumlah' => 350000, 'tanggal' => '2026-06-25', 'deskripsi' => 'Pembayaran listrik bulanan & internet', 'car_id' => null, 'user_id' => 2],
            ['tipe' => 'Pengeluaran', 'kategori' => 'operasional_mobil', 'jumlah' => 600000, 'tanggal' => '2026-06-28', 'deskripsi' => 'Asuransi kendaraan bulanan', 'car_id' => null, 'user_id' => 2],
        ];
        foreach ($transactions as $t) {
            Transaction::create($t);
        }

        // ==========================================
        // 11. ODOMETER LOGS
        // ==========================================
        $odoLogs = [
            ['car_id' => 1, 'km_reading' => 40000, 'created_at' => '2026-01-15 08:00:00'],
            ['car_id' => 1, 'km_reading' => 42500, 'created_at' => '2026-03-20 10:00:00'],
            ['car_id' => 1, 'km_reading' => 45000, 'created_at' => '2026-05-10 09:00:00'],
            ['car_id' => 1, 'km_reading' => 45230, 'created_at' => '2026-06-20 07:00:00'],
            ['car_id' => 2, 'km_reading' => 35000, 'created_at' => '2026-02-10 08:00:00'],
            ['car_id' => 2, 'km_reading' => 37000, 'created_at' => '2026-04-15 10:00:00'],
            ['car_id' => 2, 'km_reading' => 38910, 'created_at' => '2026-06-18 09:00:00'],
            ['car_id' => 3, 'km_reading' => 18000, 'created_at' => '2026-03-01 08:00:00'],
            ['car_id' => 3, 'km_reading' => 20000, 'created_at' => '2026-05-10 09:00:00'],
            ['car_id' => 3, 'km_reading' => 21500, 'created_at' => '2026-06-15 11:00:00'],
            ['car_id' => 4, 'km_reading' => 15000, 'created_at' => '2026-02-20 08:00:00'],
            ['car_id' => 4, 'km_reading' => 17000, 'created_at' => '2026-04-25 10:00:00'],
            ['car_id' => 4, 'km_reading' => 18750, 'created_at' => '2026-06-10 09:00:00'],
            ['car_id' => 5, 'km_reading' => 10000, 'created_at' => '2026-03-15 08:00:00'],
            ['car_id' => 5, 'km_reading' => 12000, 'created_at' => '2026-05-20 10:00:00'],
            ['car_id' => 5, 'km_reading' => 12800, 'created_at' => '2026-06-18 07:00:00'],
            ['car_id' => 6, 'km_reading' => 38000, 'created_at' => '2026-01-25 08:00:00'],
            ['car_id' => 6, 'km_reading' => 40000, 'created_at' => '2026-04-10 10:00:00'],
            ['car_id' => 6, 'km_reading' => 41500, 'created_at' => '2026-06-12 09:00:00'],
            ['car_id' => 7, 'km_reading' => 19000, 'created_at' => '2026-02-28 08:00:00'],
            ['car_id' => 7, 'km_reading' => 21000, 'created_at' => '2026-05-05 10:00:00'],
            ['car_id' => 7, 'km_reading' => 22300, 'created_at' => '2026-06-17 09:00:00'],
            ['car_id' => 8, 'km_reading' => 5000, 'created_at' => '2026-03-20 08:00:00'],
            ['car_id' => 8, 'km_reading' => 7500, 'created_at' => '2026-05-15 10:00:00'],
            ['car_id' => 8, 'km_reading' => 9800, 'created_at' => '2026-06-19 09:00:00'],
            ['car_id' => 9, 'km_reading' => 16000, 'created_at' => '2026-02-10 08:00:00'],
            ['car_id' => 9, 'km_reading' => 18000, 'created_at' => '2026-04-20 10:00:00'],
            ['car_id' => 9, 'km_reading' => 19600, 'created_at' => '2026-06-10 09:00:00'],
            ['car_id' => 10, 'km_reading' => 10000, 'created_at' => '2026-03-01 08:00:00'],
            ['car_id' => 10, 'km_reading' => 13000, 'created_at' => '2026-05-01 10:00:00'],
            ['car_id' => 10, 'km_reading' => 15600, 'created_at' => '2026-06-18 07:00:00'],
        ];
        foreach ($odoLogs as $o) {
            OdometerLog::create($o);
        }

        // ==========================================
        // 12. SERVICE LOGS
        // ==========================================
        $serviceLogs = [
            [
                'car_id' => 1, 'nama_bengkel' => 'Auto2000 Cab. Cempaka Putih',
                'catatan' => 'Servis rutin 40.000 km', 'km_saat_servis' => 40230,
                'tanggal_masuk' => '2026-01-16', 'tanggal_keluar' => '2026-01-16',
                'status_bengkel' => 'closed',
                'created_at' => '2026-01-16 10:00:00', 'updated_at' => '2026-01-16 15:00:00',
            ],
            [
                'car_id' => 9, 'nama_bengkel' => 'Honda Prospect Jl. MT Haryono',
                'catatan' => 'Ganti kampas rem depan dan rotasi ban', 'km_saat_servis' => 18000,
                'tanggal_masuk' => '2026-04-21', 'tanggal_keluar' => '2026-04-21',
                'status_bengkel' => 'closed',
                'created_at' => '2026-04-21 09:00:00', 'updated_at' => '2026-04-21 14:00:00',
            ],
            [
                'car_id' => 9, 'nama_bengkel' => 'Honda Prospect Jl. MT Haryono',
                'catatan' => 'Perbaikan AC dan ganti filter kabin', 'km_saat_servis' => 19200,
                'tanggal_masuk' => '2026-06-10', 'tanggal_keluar' => null,
                'status_bengkel' => 'open',
                'created_at' => '2026-06-10 08:00:00', 'updated_at' => '2026-06-10 08:00:00',
            ],
            [
                'car_id' => 6, 'nama_bengkel' => 'Bengkel Suzuki Jl. TB Simatupang',
                'catatan' => 'Servis rutin 40.000 km', 'km_saat_servis' => 39800,
                'tanggal_masuk' => '2026-04-11', 'tanggal_keluar' => '2026-04-11',
                'status_bengkel' => 'closed',
                'created_at' => '2026-04-11 09:00:00', 'updated_at' => '2026-04-11 16:00:00',
            ],
            [
                'car_id' => 7, 'nama_bengkel' => 'Auto2000 Cab. Fatmawati',
                'catatan' => 'Servis rutin 20.000 km', 'km_saat_servis' => 20100,
                'tanggal_masuk' => '2026-05-06', 'tanggal_keluar' => '2026-05-06',
                'status_bengkel' => 'closed',
                'created_at' => '2026-05-06 08:00:00', 'updated_at' => '2026-05-06 14:00:00',
            ],
        ];
        foreach ($serviceLogs as $sl) {
            ServiceLog::create($sl);
        }

        // ==========================================
        // 13. SERVICE LOG DETAILS
        // ==========================================
        $parts1 = CarPart::where('car_id', 1)->get();
        $oliPart1 = $parts1->firstWhere('nama_part', 'Oli Mesin (Engine Oil)');
        $filterOliPart1 = $parts1->firstWhere('nama_part', 'Filter Oli');
        $spooringPart1 = $parts1->firstWhere('nama_part', 'Spooring & Balancing');

        $parts9 = CarPart::where('car_id', 9)->get();
        $kampasDepan9 = $parts9->firstWhere('nama_part', 'Kampas Rem Depan (Brake Pad)');
        $ban9 = $parts9->firstWhere('nama_part', 'Ban (Tire)');
        $filterAC9 = $parts9->firstWhere('nama_part', 'Filter AC (Cabin Filter)');

        $parts6 = CarPart::where('car_id', 6)->get();
        $oliPart6 = $parts6->firstWhere('nama_part', 'Oli Mesin (Engine Oil)');
        $filterOliPart6 = $parts6->firstWhere('nama_part', 'Filter Oli');
        $busi6 = $parts6->firstWhere('nama_part', 'Busi (Spark Plug)');

        $parts7 = CarPart::where('car_id', 7)->get();
        $oliPart7 = $parts7->firstWhere('nama_part', 'Oli Mesin (Engine Oil)');
        $filterOliPart7 = $parts7->firstWhere('nama_part', 'Filter Oli');
        $filterUdara7 = $parts7->firstWhere('nama_part', 'Filter Udara');

        $details = [
            ['service_log_id' => 1, 'car_part_id' => $oliPart1?->id, 'deskripsi_tindakan' => 'Ganti Oli Mesin Toyota Genuine Oil 10W-40', 'biaya' => 450000],
            ['service_log_id' => 1, 'car_part_id' => $filterOliPart1?->id, 'deskripsi_tindakan' => 'Ganti Filter Oli Original Toyota', 'biaya' => 150000],
            ['service_log_id' => 1, 'car_part_id' => $spooringPart1?->id, 'deskripsi_tindakan' => 'Spooring & Balancing keempat ban', 'biaya' => 250000],
            ['service_log_id' => 2, 'car_part_id' => $kampasDepan9?->id, 'deskripsi_tindakan' => 'Ganti Kampas Rem Depan Honda Genuine Parts', 'biaya' => 650000],
            ['service_log_id' => 2, 'car_part_id' => $ban9?->id, 'deskripsi_tindakan' => 'Rotasi Ban Depan-Belakang', 'biaya' => 100000],
            ['service_log_id' => 3, 'car_part_id' => $filterAC9?->id, 'deskripsi_tindakan' => 'Ganti Filter AC Kabin', 'biaya' => 200000],
            ['service_log_id' => 3, 'car_part_id' => null, 'deskripsi_tindakan' => 'Servis AC - Isi Freon', 'biaya' => 350000],
            ['service_log_id' => 4, 'car_part_id' => $oliPart6?->id, 'deskripsi_tindakan' => 'Ganti Oli Mesin Suzuki 10W-40', 'biaya' => 400000],
            ['service_log_id' => 4, 'car_part_id' => $filterOliPart6?->id, 'deskripsi_tindakan' => 'Ganti Filter Oli Original Suzuki', 'biaya' => 130000],
            ['service_log_id' => 4, 'car_part_id' => $busi6?->id, 'deskripsi_tindakan' => 'Ganti Busi Iridium 4 pcs', 'biaya' => 320000],
            ['service_log_id' => 5, 'car_part_id' => $oliPart7?->id, 'deskripsi_tindakan' => 'Ganti Oli Mesin Toyota 5W-30 Full Synthetic', 'biaya' => 600000],
            ['service_log_id' => 5, 'car_part_id' => $filterOliPart7?->id, 'deskripsi_tindakan' => 'Ganti Filter Oli Original Toyota', 'biaya' => 180000],
            ['service_log_id' => 5, 'car_part_id' => $filterUdara7?->id, 'deskripsi_tindakan' => 'Ganti Filter Udara Mesin', 'biaya' => 250000],
        ];
        foreach ($details as $d) {
            ServiceLogDetail::create($d);
        }

        // ==========================================
        // 14. ACTIVITIES
        // ==========================================
        $activities = [
            ['user_id' => null, 'type' => 'user', 'icon' => 'person_add', 'color' => 'purple', 'title' => 'Akun user dibuat', 'description' => 'Akun Siti Rahmawati (siti@example.com) dibuat dengan role Admin.', 'user' => 'system', 'created_at' => '2026-05-01 08:00:00'],
            ['user_id' => null, 'type' => 'user', 'icon' => 'person_add', 'color' => 'purple', 'title' => 'Akun user dibuat', 'description' => 'Akun Dian Pratama (dian@example.com) dibuat dengan role Receptionist.', 'user' => 'system', 'created_at' => '2026-05-01 08:05:00'],
            ['user_id' => null, 'type' => 'user', 'icon' => 'person_add', 'color' => 'purple', 'title' => 'Akun user dibuat', 'description' => 'Akun Rudi Hermawan (rudi@example.com) dibuat dengan role Admin.', 'user' => 'system', 'created_at' => '2026-05-02 09:00:00'],
            ['user_id' => null, 'type' => 'booking', 'icon' => 'add_circle', 'color' => 'emerald', 'title' => 'Pesanan Baru Dibuat', 'description' => 'Siti membuat pesanan #20260601-0001 untuk Andi Pratama', 'user' => 'Siti Rahmawati', 'subject_type' => 'booking', 'subject_id' => 1, 'created_at' => '2026-05-25 10:00:00'],
            ['user_id' => null, 'type' => 'booking', 'icon' => 'sync_alt', 'color' => 'blue', 'title' => 'Status Pesanan Diubah', 'description' => 'Pembayaran: Belum Bayar ➔ Lunas | Operasional: Menunggu ➔ Aktif', 'user' => 'Siti Rahmawati', 'subject_type' => 'booking', 'subject_id' => 1, 'created_at' => '2026-06-01 07:30:00'],
            ['user_id' => null, 'type' => 'booking', 'icon' => 'add_circle', 'color' => 'emerald', 'title' => 'Pesanan Baru Dibuat', 'description' => 'Siti membuat pesanan #20260605-0001 untuk Budi Hartono', 'user' => 'Siti Rahmawati', 'subject_type' => 'booking', 'subject_id' => 2, 'created_at' => '2026-06-01 09:00:00'],
            ['user_id' => null, 'type' => 'booking', 'icon' => 'add_circle', 'color' => 'emerald', 'title' => 'Pesanan Baru Dibuat', 'description' => 'Siti membuat pesanan #20260615-0001 untuk Cindy Wijaya', 'user' => 'Siti Rahmawati', 'subject_type' => 'booking', 'subject_id' => 3, 'created_at' => '2026-06-10 11:00:00'],
            ['user_id' => null, 'type' => 'booking', 'icon' => 'add_circle', 'color' => 'emerald', 'title' => 'Pesanan Baru Dibuat', 'description' => 'Dian membuat pesanan #20260618-0001 untuk Denny Saputra', 'user' => 'Dian Pratama', 'subject_type' => 'booking', 'subject_id' => 4, 'created_at' => '2026-06-14 14:00:00'],
            ['user_id' => null, 'type' => 'booking', 'icon' => 'add_circle', 'color' => 'emerald', 'title' => 'Pesanan Baru Dibuat', 'description' => 'Siti membuat pesanan #20260625-0001 untuk Eko Prasetyo', 'user' => 'Siti Rahmawati', 'subject_type' => 'booking', 'subject_id' => 5, 'created_at' => '2026-06-20 08:00:00'],
            ['user_id' => null, 'type' => 'booking', 'icon' => 'add_circle', 'color' => 'emerald', 'title' => 'Pesanan Baru Dibuat', 'description' => 'Siti membuat pesanan kontrak #20260601-0002 untuk PT. Maju Jaya Abadi', 'user' => 'Siti Rahmawati', 'subject_type' => 'booking', 'subject_id' => 7, 'created_at' => '2026-05-20 09:00:00'],
            ['user_id' => null, 'type' => 'booking', 'icon' => 'cancel', 'color' => 'red', 'title' => 'Pesanan Dibatalkan', 'description' => 'Pesanan #20260610-0001 atas nama Gilang Ramadhan dibatalkan. Refund DP Rp200.000', 'user' => 'Siti Rahmawati', 'subject_type' => 'booking', 'subject_id' => 8, 'created_at' => '2026-06-09 08:00:00'],
            ['user_id' => null, 'type' => 'service', 'icon' => 'build', 'color' => 'amber', 'title' => 'Servis Mobil', 'description' => 'Servis rutin Avanza B 1234 CDE di Auto2000 (40.000 km)', 'user' => 'Siti Rahmawati', 'subject_type' => 'service_log', 'subject_id' => 1, 'created_at' => '2026-01-16 15:00:00'],
            ['user_id' => null, 'type' => 'service', 'icon' => 'build', 'color' => 'amber', 'title' => 'Servis Mobil', 'description' => 'Servis Civic B 2223 ABC masuk bengkel - Perbaikan AC', 'user' => 'Siti Rahmawati', 'subject_type' => 'service_log', 'subject_id' => 3, 'created_at' => '2026-06-10 08:00:00'],
        ];
        foreach ($activities as $a) {
            Activity::create($a);
        }

        // ==========================================
        // 15. INVOICES & VERIFICATIONS
        // ==========================================
        $invoiceData = [
            ['booking_id' => 1, 'nomor_seri' => 'INV-202606-0001', 'kode_mobil' => 'AVZ', 'qr_code_string' => 'ROYAL|AVZ|20260601-0001|202606|' . md5('20260601-0001'), 'issued_at' => '2026-06-01 08:00:00'],
            ['booking_id' => 2, 'nomor_seri' => 'INV-202606-0002', 'kode_mobil' => 'INV', 'qr_code_string' => 'ROYAL|INV|20260605-0001|202606|' . md5('20260605-0001'), 'issued_at' => '2026-06-05 10:00:00'],
            ['booking_id' => 4, 'nomor_seri' => 'INV-202606-0003', 'kode_mobil' => 'BRI', 'qr_code_string' => 'ROYAL|BRI|20260618-0001|202606|' . md5('20260618-0001'), 'issued_at' => '2026-06-18 09:00:00'],
            ['booking_id' => 7, 'nomor_seri' => 'INV-202606-0004', 'kode_mobil' => 'ALP', 'qr_code_string' => 'ROYAL|ALP|20260601-0002|202606|' . md5('20260601-0002'), 'issued_at' => '2026-06-01 06:00:00'],
        ];

        foreach ($invoiceData as $inv) {
            $invoiceId = DB::table('invoices')->insertGetId($inv);

            DB::table('invoice_verifications')->insert([
                'invoice_id' => $invoiceId,
                'token' => md5($inv['nomor_seri'] . $inv['booking_id'] . 'royaltour'),
                'is_verified' => false,
                'verified_at' => null,
            ]);
        }

        $this->command->info('Database seeded successfully with realistic data!');
    }
}
