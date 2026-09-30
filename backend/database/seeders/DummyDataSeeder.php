<?php

namespace Database\Seeders;

use App\Models\MasterPart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder data dummy (data uji) untuk seluruh fitur aplikasi.
 *
 * ATURAN PENTING:
 * 1. TIDAK PERNAH menghapus, truncate, atau menimpa data yang sudah ada.
 *    Data lama di database tetap utuh setelah seeder ini dijalankan.
 * 2. IDEMPOTENT - aman dijalankan berkali-kali. Data yang sudah pernah
 *    dibuat akan dilewati (skip), bukan diduplikasi.
 * 3. Identifier data dummy memakai penanda (marker) yang stabil, sehingga
 *    aman diulang walau dijalankan di tanggal yang berbeda:
 *      users           -> email berakhiran  @dummy.test
 *      cars            -> no_mesin diawali  DUMMY-
 *      bookings        -> nomor_pesanan diawali  DUM-
 *      surat           -> nomor_surat diawali  DUM-
 *      invoices        -> nomor_seri diawali   INV-DUM-
 *      transactions    -> deskripsi diawali   [DUMMY]
 *      activities      -> deskripsi diawali   [DUMMY]
 *      service_logs    -> catatan diawali     [DUMMY]
 *      template_surats -> judul diawali       [DUMMY]
 *      (odometer_logs, car_parts, service_log_details, booking_car,
 *       booking_addons, booking_payments, invoice_verifications mengikuti
 *       induknya dan otomatis terhapus saat induknya dihapus)
 *
 * CARA JALANKAN (aman, tidak menghapus data):
 *   php artisan db:seed --class=DummyDataSeeder
 *
 * CARA BERSIHKAN (hanya menghapus data dummy):
 *   php artisan db:seed --class=DummyDataPurger
 */
class DummyDataSeeder extends Seeder
{
    /** Password semua user dummy. */
    public const PASSWORD = 'password';

    /** Prefix penanda data dummy. */
    private const TAG = 'DUMMY';

    /** Versi dataset, dipakai agar nomor urut dummy stabil. */
    private const DATASET = '01';

    /** @var array<string, int> Penghitung baris per tabel. */
    private array $inserted = [];

    /** @var array<string, int> Penghitung baris yang dilewati. */
    private array $skipped = [];

    /** @var string[] Peringatan yang dikumpulkan selama seeding. */
    private array $warnings = [];

    public function run(): void
    {
        $users = $this->seedUsers();
        $cars = $this->seedCars();
        $this->seedCarParts($cars);
        $bookings = $this->seedBookings($cars, $users);
        $this->seedBookingCars($bookings, $cars);
        $this->seedBookingAddons($bookings);
        $this->seedBookingPayments($bookings, $users);
        $this->seedTransactions($bookings, $users);
        $this->seedOdometerLogs($cars);
        $services = $this->seedServiceLogs($cars);
        $this->seedServiceLogDetails($services, $users);
        $this->seedInvoices($bookings);
        $this->seedTemplateSurats();
        $this->seedActivities($bookings, $services, $users);
        $this->syncCarStatus($cars);

        $this->report();
    }

    // =====================================================================
    // 1. USERS
    // =====================================================================

    /**
     * @return array<string, int> peta role => user id
     */
    private function seedUsers(): array
    {
        $roles = DB::table('roles')->pluck('slug')->all();
        $fallbackRole = $roles[0] ?? null;

        $definitions = [
            ['key' => 'owner', 'nama' => 'Dummy Owner', 'email' => 'owner@dummy.test', 'role' => 'owner'],
            ['key' => 'admin', 'nama' => 'Dummy Admin', 'email' => 'admin@dummy.test', 'role' => 'admin'],
            ['key' => 'reception', 'nama' => 'Dummy Receptionist', 'email' => 'reception@dummy.test', 'role' => 'reception'],
        ];

        $users = ['any' => 0];

        foreach ($definitions as $def) {
            $role = in_array($def['role'], $roles, true) ? $def['role'] : $fallbackRole;

            if ($role === null) {
                $this->warnings[] = 'Tabel roles kosong, user dummy dilewati.';
                break;
            }

            $existing = DB::table('users')->where('email', $def['email'])->first();

            if ($existing) {
                $id = (int) $existing->id;
                $this->skipped('users');
            } else {
                $id = (int) DB::table('users')->insertGetId([
                    'nama' => $def['nama'],
                    'email' => $def['email'],
                    'password' => Hash::make(self::PASSWORD),
                    'role' => $role,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->inserted('users');
            }

            $users[$def['key']] = $users[$def['key']] ?? $id;
            $users['any'] = $users['any'] ?: $id;
        }

        // Kalau tidak ada user dummy sama sekali, pakai user yang sudah ada.
        if (! $users['any']) {
            $users['any'] = (int) (DB::table('users')->orderBy('id')->value('id') ?? 0);

            if (! $users['any']) {
                $this->warnings[] = 'Tidak ada user di database, data dummy yang butuh user dilewati.';
            }
        }

        return $users;
    }

    // =====================================================================
    // 2. CARS
    // =====================================================================

    /**
     * @return array<string, array{id:int, plat_nomor:string, kode_mobil:string, km_saat_ini:int, status:string}>
     */
    private function seedCars(): array
    {
        $definitions = $this->carDefinitions();
        $cars = [];

        foreach ($definitions as $def) {
            $noMesin = 'DUMMY-' . $def['kode'] . '-MESIN';
            $noRangka = 'DUMMYFRAME' . $def['kode'] . '0001';

            $existing = DB::table('cars')->where('no_mesin', $noMesin)->first();

            if ($existing) {
                $id = (int) $existing->id;
                $this->skipped('cars');
            } else {
                $id = (int) DB::table('cars')->insertGetId([
                    'plat_nomor' => $def['plat_nomor'],
                    'merk' => $def['merk'],
                    'model' => $def['model'],
                    'warna' => $def['warna'],
                    'tahun' => $def['tahun'],
                    'kapasitas' => $def['kapasitas'],
                    'harga_default' => $def['harga_default'],
                    'harga_kontrak' => $def['harga_kontrak'],
                    'no_mesin' => $noMesin,
                    'no_rangka' => $noRangka,
                    'km_saat_ini' => $def['km_saat_ini'],
                    'status' => $def['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->inserted('cars');
            }

            $cars[$def['kode']] = [
                'id' => $id,
                'plat_nomor' => $existing ? (string) $existing->plat_nomor : $def['plat_nomor'],
                'kode_mobil' => $def['kode_mobil'],
                'km_saat_ini' => $def['km_saat_ini'],
                'status' => $def['status'],
                'surat_id' => $existing ? $existing->surat_id : null,
            ];
        }

        // Setiap mobil punya dokumen Pelepasan Hak Kendaraan (PHK) sendiri,
        // karena form tambah mobil mewajibkan surat_id tersebut.
        $counter = [];
        $urut = 0;
        foreach ($cars as $kode => $car) {
            $urut++;

            if (! empty($car['surat_id'])) {
                continue;
            }

            $suratId = $this->attachPhkSurat(($urut % 12) + 1, $counter);

            if ($suratId) {
                DB::table('cars')
                    ->where('id', $car['id'])
                    ->where('no_mesin', 'like', 'DUMMY-%')
                    ->whereNull('surat_id')
                    ->update(['surat_id' => $suratId]);

                $cars[$kode]['surat_id'] = $suratId;
            }
        }

        return $cars;
    }

    /**
     * Nomor surat PHK dummy untuk sebuah mobil.
     */
    private function attachPhkSurat(int $bulan, array &$counter): int
    {
        $tahun = (string) now()->year;
        $key = 'PHK|' . $tahun;

        $counter[$key] ??= (int) (DB::table('surat')
            ->where('kode', 'PHK')
            ->where('tahun', $tahun)
            ->max('no_urut') ?? 0);
        $counter[$key]++;

        $noUrut = str_pad((string) $counter[$key], 3, '0', STR_PAD_LEFT);
        $bulanRomawi = $this->bulanRomawi($bulan);
        $nomorSurat = sprintf('DUM-%s/SRT/PHK/JRB/%s/%s', $noUrut, $bulanRomawi, $tahun);

        return $this->insertOnce('surat', ['nomor_surat' => $nomorSurat], [
            'jenis_dokumen' => 'Pelepasan Hak Kendaraan',
            'kode' => 'PHK',
            'subkategori' => null,
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => $noUrut,
            'bulan' => $bulanRomawi,
            'tahun' => $tahun,
            'nomor_surat' => $nomorSurat,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function carDefinitions(): array
    {
        return [
            ['kode' => 'C01', 'kode_mobil' => 'AVZ', 'plat_nomor' => 'B 9001 DUM', 'merk' => 'Toyota', 'model' => 'Avanza 1.3 G M/T', 'warna' => 'Silver', 'tahun' => 2021, 'kapasitas' => 7, 'harga_default' => 400000, 'harga_kontrak' => 280000, 'km_saat_ini' => 48250, 'status' => 'Tersedia'],
            ['kode' => 'C02', 'kode_mobil' => 'XEN', 'plat_nomor' => 'B 9002 DUM', 'merk' => 'Daihatsu', 'model' => 'Xenia 1.3 X CV', 'warna' => 'Putih', 'tahun' => 2022, 'kapasitas' => 7, 'harga_default' => 350000, 'harga_kontrak' => 245000, 'km_saat_ini' => 36100, 'status' => 'Tersedia'],
            ['kode' => 'C03', 'kode_mobil' => 'BRI', 'plat_nomor' => 'B 9003 DUM', 'merk' => 'Honda', 'model' => 'Brio Satya E CVT', 'warna' => 'Merah', 'tahun' => 2023, 'kapasitas' => 5, 'harga_default' => 300000, 'harga_kontrak' => 210000, 'km_saat_ini' => 18900, 'status' => 'Tersedia'],
            ['kode' => 'C04', 'kode_mobil' => 'INV', 'plat_nomor' => 'B 9004 DUM', 'merk' => 'Toyota', 'model' => 'Innova Venturer 2.4 G', 'warna' => 'Hitam', 'tahun' => 2022, 'kapasitas' => 7, 'harga_default' => 650000, 'harga_kontrak' => 455000, 'km_saat_ini' => 52300, 'status' => 'Tersedia'],
            ['kode' => 'C05', 'kode_mobil' => 'PJS', 'plat_nomor' => 'B 9005 DUM', 'merk' => 'Mitsubishi', 'model' => 'Pajero Sport Dakar', 'warna' => 'Putih', 'tahun' => 2023, 'kapasitas' => 7, 'harga_default' => 1300000, 'harga_kontrak' => 910000, 'km_saat_ini' => 27500, 'status' => 'Tersedia'],
            ['kode' => 'C06', 'kode_mobil' => 'ERT', 'plat_nomor' => 'B 9006 DUM', 'merk' => 'Suzuki', 'model' => 'Ertiga GX', 'warna' => 'Biru', 'tahun' => 2021, 'kapasitas' => 7, 'harga_default' => 400000, 'harga_kontrak' => 280000, 'km_saat_ini' => 61400, 'status' => 'Tersedia'],
            ['kode' => 'C07', 'kode_mobil' => 'FTN', 'plat_nomor' => 'B 9007 DUM', 'merk' => 'Toyota', 'model' => 'Fortuner VRZ 2.4', 'warna' => 'Hitam', 'tahun' => 2023, 'kapasitas' => 7, 'harga_default' => 1150000, 'harga_kontrak' => 805000, 'km_saat_ini' => 14900, 'status' => 'Tersedia'],
            ['kode' => 'C08', 'kode_mobil' => 'SIG', 'plat_nomor' => 'B 9008 DUM', 'merk' => 'Daihatsu', 'model' => 'Sigra 1.2 R', 'warna' => 'Abu-abu', 'tahun' => 2024, 'kapasitas' => 7, 'harga_default' => 300000, 'harga_kontrak' => 210000, 'km_saat_ini' => 7600, 'status' => 'Nonaktif'],
        ];
    }

    // =====================================================================
    // 3. CAR PARTS (komponen per mobil, diambil dari master_parts yang ada)
    // =====================================================================

    /**
     * @param  array<string, array>  $cars
     */
    private function seedCarParts(array $cars): void
    {
        $masterParts = $this->ensureMasterParts();

        if ($masterParts->isEmpty()) {
            return;
        }

        // Faktor pemakaian per komponen, sengaja disebar agar dashboard
        // menampilkan kondisi normal, warning (>=85%), dan kritis (>=100%).
        $factors = [0.25, 0.55, 0.88, 1.06, 0.4, 1.25, 0.7];

        foreach ($cars as $car) {
            foreach ($masterParts as $index => $part) {
                $lifespan = (int) ($part->default_lifespan_km ?? 0);
                $isTrack = (bool) $part->is_track_km && $lifespan > 0;

                $lastServiceKm = 0;
                $lastServiceDate = null;

                if ($isTrack) {
                    $terpakai = (int) round($lifespan * $factors[$index % count($factors)]);
                    $lastServiceKm = max(0, $car['km_saat_ini'] - $terpakai);
                    $lastServiceDate = now()->subMonths($index % 6)->startOfMonth()->format('Y-m-d');
                }

                $this->insertOnce('car_parts', [
                    'car_id' => $car['id'],
                    'master_part_id' => $part->id,
                ], [
                    'car_id' => $car['id'],
                    'master_part_id' => $part->id,
                    'nama_part' => $part->nama_part,
                    'icon' => $part->icon,
                    'is_track_km' => $isTrack ? 1 : 0,
                    'lifespan_km' => $lifespan > 0 ? $lifespan : null,
                    'last_service_km' => $lastServiceKm,
                    'last_service_date' => $lastServiceDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Master part dipakai sebagai sumber data. Kalau tabelnya kosong, isi
     * dengan komponen dasar (firstOrCreate, tidak menimpa yang ada).
     */
    private function ensureMasterParts()
    {
        $parts = MasterPart::query()->orderBy('id')->get();

        if ($parts->isNotEmpty()) {
            return $parts;
        }

        $this->warnings[] = 'master_parts kosong, dibuat 5 komponen dasar untuk data dummy.';

        $basics = [
            ['nama_part' => 'Oli Mesin (Engine Oil)', 'kategori' => 'Mesin', 'icon' => 'water_drop', 'is_track_km' => true, 'default_lifespan_km' => 10000, 'deskripsi' => 'Penggantian oli mesin rutin'],
            ['nama_part' => 'Filter Oli', 'kategori' => 'Mesin', 'icon' => 'filter_alt', 'is_track_km' => true, 'default_lifespan_km' => 10000, 'deskripsi' => 'Diganti bersamaan dengan oli mesin'],
            ['nama_part' => 'Filter Udara', 'kategori' => 'Mesin', 'icon' => 'air', 'is_track_km' => true, 'default_lifespan_km' => 20000, 'deskripsi' => 'Pembersihan filter udara'],
            ['nama_part' => 'Kampas Rem Depan (Brake Pad)', 'kategori' => 'Kaki-kaki', 'icon' => 'tire_repair', 'is_track_km' => true, 'default_lifespan_km' => 30000, 'deskripsi' => 'Penggantian kampas rem cakram depan'],
            ['nama_part' => 'Ban (Tire)', 'kategori' => 'Kaki-kaki', 'icon' => 'album', 'is_track_km' => true, 'default_lifespan_km' => 50000, 'deskripsi' => 'Penggantian ban aus / rotasi ban'],
        ];

        foreach ($basics as $part) {
            $this->insertOnce('master_parts', ['nama_part' => $part['nama_part']], $part + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return MasterPart::query()->orderBy('id')->get();
    }

    // =====================================================================
    // 4. BOOKINGS
    // =====================================================================

    /**
     * @return array<int, array> daftar booking dummy yang tersimpan
     */
    private function seedBookings(array $cars, array $users): array
    {
        $authorId = $this->userId($users, ['admin', 'reception', 'any']);
        $bookings = [];

        foreach ($this->bookingDefinitions() as $def) {
            $nomor = sprintf('DUM-%s-%03d', self::DATASET, $def['no']);
            $existing = DB::table('bookings')->where('nomor_pesanan', $nomor)->first();

            $car = $cars[$def['car']] ?? null;
            if (! $car) {
                $this->warnings[] = "Mobil {$def['car']} tidak tersedia, pesanan {$nomor} dilewati.";

                continue;
            }

            $mulai = $this->tanggal($def['bulan'], $def['hari']);
            $selesai = $mulai->copy()->addDays($def['durasi'] - 1);
            $harian = $def['tipe'] === 'kontrak' ? $this->hargaKontrak($car) : $this->hargaDefault($car);
            $totalHarga = $harian * $def['durasi'];
            $totalAddons = array_sum(array_column($this->addonRows($def), 'nominal'));

            $dpAmount = match ($def['bayar']) {
                'Lunas' => $totalHarga,
                'DP' => (int) round($totalHarga * 0.3),
                'Refunded' => (int) round($totalHarga * 0.3),
                default => 0,
            };

            $refundAmount = $def['bayar'] === 'Refunded' ? $dpAmount : 0;
            $createdAt = $mulai->copy()->subDays(3)->setTime(10, 0);

            $pickedUp = in_array($def['rental'], ['Aktif', 'Selesai', 'Dikembalikan'], true)
                ? $mulai->copy()->setTime(8, 0)
                : null;

            $returned = in_array($def['rental'], ['Selesai', 'Dikembalikan'], true)
                ? $selesai->copy()->setTime(17, 0)
                : null;

            $values = [
                'nomor_pesanan' => $nomor,
                'nama_pelanggan' => $def['pelanggan'],
                'kontak_pelanggan' => $def['kontak'],
                'email' => $def['email'],
                'tanggal_mulai' => $mulai->format('Y-m-d'),
                'tanggal_selesai' => $selesai->format('Y-m-d'),
                'lokasi_jemput' => $def['lokasi'],
                'lokasi_antar' => $def['lokasi'],
                'tipe_sewa' => $def['tipe'],
                'status_pembayaran' => $def['bayar'],
                'status_rental' => $def['rental'],
                'total_harga' => $totalHarga,
                'total_addons' => $totalAddons,
                'dp_amount' => $dpAmount,
                'refund_amount' => $refundAmount,
                'catatan' => $this->tag($def['catatan'] ?? 'Pesanan data dummy untuk pengujian'),
                'picked_up_at' => $pickedUp?->format('Y-m-d H:i:s'),
                'returned_at' => $returned?->format('Y-m-d H:i:s'),
                'created_by' => $authorId ?: null,
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'updated_at' => ($returned ?? $createdAt)->format('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $id = (int) $existing->id;
                $this->skipped('bookings');
            } else {
                $id = (int) DB::table('bookings')->insertGetId($values);
                $this->inserted('bookings');
            }

            $bookings[] = [
                'id' => $id,
                'no' => $def['no'],
                'nomor' => $nomor,
                'pelanggan' => $def['pelanggan'],
                'bayar' => $def['bayar'],
                'rental' => $def['rental'],
                'tipe' => $def['tipe'],
                'mulai' => $mulai,
                'selesai' => $selesai,
                'total' => $totalHarga,
                'dp' => $dpAmount,
                'car' => $car,
                'car2' => isset($def['car2']) ? ($cars[$def['car2']] ?? null) : null,
                'harian' => $harian,
            ];
        }

        return $bookings;
    }

    private function bookingDefinitions(): array
    {
        return [
            ['no' => 1, 'bulan' => 8, 'hari' => 6, 'durasi' => 3, 'car' => 'C01', 'tipe' => 'harian', 'bayar' => 'Lunas', 'rental' => 'Selesai', 'pelanggan' => 'Andi Pratama', 'kontak' => '081234560001', 'email' => 'andi.pratama@dummy.test', 'lokasi' => 'Jl. Merdeka No. 45, Jakarta Pusat', 'catatan' => 'Mobil dalam keadaan bersih'],
            ['no' => 2, 'bulan' => 7, 'hari' => 12, 'durasi' => 4, 'car' => 'C02', 'tipe' => 'harian', 'bayar' => 'Lunas', 'rental' => 'Selesai', 'pelanggan' => 'Budi Hartono', 'kontak' => '081234560002', 'email' => 'budi.hartono@dummy.test', 'lokasi' => 'Bandara Soekarno-Hatta Terminal 3', 'catatan' => 'Penjemputan di terminal 3'],
            ['no' => 3, 'bulan' => 6, 'hari' => 4, 'durasi' => 2, 'car' => 'C03', 'tipe' => 'harian', 'bayar' => 'Lunas', 'rental' => 'Selesai', 'pelanggan' => 'Cindy Wijaya', 'kontak' => '081234560003', 'email' => 'cindy.wijaya@dummy.test', 'lokasi' => 'Jl. Sudirman No. 10, Jakarta Selatan', 'catatan' => 'Minta mobil dalam kondisi terbaik'],
            ['no' => 4, 'bulan' => 5, 'hari' => 9, 'durasi' => 5, 'car' => 'C04', 'tipe' => 'harian', 'bayar' => 'DP', 'rental' => 'Selesai', 'pelanggan' => 'Denny Saputra', 'kontak' => '081234560004', 'email' => 'denny.saputra@dummy.test', 'lokasi' => 'Perumahan Citra Garden Blok A3 No. 5', 'catatan' => 'Perjalanan keluar kota'],
            ['no' => 5, 'bulan' => 4, 'hari' => 15, 'durasi' => 6, 'car' => 'C05', 'tipe' => 'harian', 'bayar' => 'Lunas', 'rental' => 'Selesai', 'pelanggan' => 'Eko Prasetyo', 'kontak' => '081234560005', 'email' => 'eko.prasetyo@dummy.test', 'lokasi' => 'Jl. Gatot Subroto Kav. 56, Jakarta Selatan', 'catatan' => 'Perjalanan ke Bandung'],
            ['no' => 6, 'bulan' => 3, 'hari' => 18, 'durasi' => 3, 'car' => 'C06', 'tipe' => 'harian', 'bayar' => 'Belum Bayar', 'rental' => 'Batal', 'pelanggan' => 'Fitriani', 'kontak' => '081234560006', 'email' => 'fitriani@dummy.test', 'lokasi' => 'Jl. Raya Bogor KM 28, Depok', 'catatan' => 'Dibatalkan oleh pelanggan'],
            ['no' => 7, 'bulan' => 2, 'hari' => 2, 'durasi' => 30, 'car' => 'C07', 'tipe' => 'kontrak', 'bayar' => 'Lunas', 'rental' => 'Selesai', 'pelanggan' => 'PT. Maju Jaya Abadi', 'kontak' => '021-5557890', 'email' => 'info@majujaya@dummy.test', 'lokasi' => 'Kantor PT. Maju Jaya, SCBD Lot 12', 'catatan' => 'Kontrak bulanan untuk direksi, termasuk supir'],
            ['no' => 8, 'bulan' => 1, 'hari' => 20, 'durasi' => 3, 'car' => 'C08', 'tipe' => 'harian', 'bayar' => 'Refunded', 'rental' => 'Batal', 'pelanggan' => 'Gilang Ramadhan', 'kontak' => '081234560008', 'email' => 'gilang.r@dummy.test', 'lokasi' => 'Jl. Kemang Raya No. 88, Jakarta Selatan', 'catatan' => 'Dibatalkan karena kecelakaan ringan sehari sebelumnya'],
            ['no' => 9, 'bulan' => 1, 'hari' => 24, 'durasi' => 4, 'car' => 'C01', 'car2' => 'C02', 'tipe' => 'harian', 'bayar' => 'Lunas', 'rental' => 'Selesai', 'pelanggan' => 'Hendra Kusuma', 'kontak' => '081234560009', 'email' => 'hendra.k@dummy.test', 'lokasi' => 'Kantor PT. Surya Abadi, Jl. Jend. Sudirman', 'catatan' => 'Dua unit mobil untuk tim operasional'],
            ['no' => 10, 'bulan' => 0, 'hari' => 6, 'durasi' => 5, 'car' => 'C03', 'tipe' => 'harian', 'bayar' => 'DP', 'rental' => 'Selesai', 'pelanggan' => 'Irma Lestari', 'kontak' => '081234560010', 'email' => 'irma.lestari@dummy.test', 'lokasi' => 'Jl. Puri Indah No. 21, Jakarta Barat', 'catatan' => 'DP 30% saat booking'],
            ['no' => 11, 'bulan' => 0, 'hari' => 12, 'durasi' => 3, 'car' => 'C05', 'tipe' => 'harian', 'bayar' => 'Lunas', 'rental' => 'Selesai', 'pelanggan' => 'Joko Susilo', 'kontak' => '081234560011', 'email' => 'joko.susilo@dummy.test', 'lokasi' => 'Hotel Grand Menteng, Jakarta Pusat', 'catatan' => 'Tamu bisnis / researcher'],
            ['no' => 12, 'bulan' => 0, 'hari' => 18, 'durasi' => 7, 'car' => 'C06', 'tipe' => 'harian', 'bayar' => 'Lunas', 'rental' => 'Aktif', 'pelanggan' => 'Kartika Sari', 'kontak' => '081234560012', 'email' => 'kartika.sari@dummy.test', 'lokasi' => 'Kantor Notariat 12, Jl. Sabang', 'catatan' => 'Sewa mingguan untuk entourage acara'],
            ['no' => 13, 'bulan' => 0, 'hari' => 20, 'durasi' => 30, 'car' => 'C05', 'tipe' => 'kontrak', 'bayar' => 'DP', 'rental' => 'Aktif', 'pelanggan' => 'PT. Samudera Biru', 'kontak' => '021-5557011', 'email' => 'finance@samuderabiru@dummy.test', 'lokasi' => 'Kantor PT. Samudera Biru, Jl. Asia Afrika', 'catatan' => 'Kontrak bulanan, DP 50%'],
            ['no' => 14, 'bulan' => 0, 'hari' => 25, 'durasi' => 2, 'car' => 'C07', 'tipe' => 'harian', 'bayar' => 'Belum Bayar', 'rental' => 'Menunggu', 'pelanggan' => 'Lina Marlina', 'kontak' => '081234560014', 'email' => 'lina.marlina@dummy.test', 'lokasi' => 'Stasiun Tugu, Yogyakarta', 'catatan' => 'Menunggu konfirmasi pembayaran'],
        ];
    }

    // =====================================================================
    // 5. BOOKING CARS (pivot)
    // =====================================================================

    private function seedBookingCars(array $bookings, array $cars): void
    {
        foreach ($bookings as $booking) {
            $rows = [[
                'booking_id' => $booking['id'],
                'car_id' => $booking['car']['id'],
                'quantity' => 1,
                'harga_dasar_per_hari' => $booking['harian'],
            ]];

            if ($booking['car2']) {
                $rows[] = [
                    'booking_id' => $booking['id'],
                    'car_id' => $booking['car2']['id'],
                    'quantity' => 1,
                    'harga_dasar_per_hari' => $this->hargaDefault($booking['car2']),
                ];
            }

            foreach ($rows as $row) {
                $this->insertOnce('booking_car', [
                    'booking_id' => $row['booking_id'],
                    'car_id' => $row['car_id'],
                ], $row + ['catatan_harga' => null, 'foto_kondisi' => null]);
            }
        }
    }

    // =====================================================================
    // 6. BOOKING ADDONS
    // =====================================================================

    private function seedBookingAddons(array $bookings): void
    {
        foreach ($bookings as $booking) {
            foreach ($this->addonRows($booking) as $addon) {
                $this->insertOnce('booking_addons', [
                    'booking_id' => $booking['id'],
                    'nama_biaya' => $addon['nama'],
                    'nominal' => $addon['nominal'],
                ], [
                    'booking_id' => $booking['id'],
                    'nama_biaya' => $addon['nama'],
                    'nominal' => $addon['nominal'],
                ]);
            }
        }
    }

    /**
     * @return array<int, array{nama:string, nominal:int}>
     */
    private function addonRows(array $source): array
    {
        // Dipakai oleh definition pesanan (memakai 'addons') maupun booking
        // hasil simpan (memakai 'addons' yang sudah diterjemahkan).
        if (isset($source['addons']) && is_array($source['addons'])) {
            return array_map(fn (array $a) => ['nama' => $a['nama'], 'nominal' => $a['nominal']], $source['addons']);
        }

        return match (true) {
            isset($source['no']) => $this->addonRowsByDefinition($source),
            default => [],
        };
    }

    private function addonRowsByDefinition(array $def): array
    {
        $rows = match ($def['no']) {
            1 => [['Jasa Supir', 250000]],
            2 => [['Antar Jemput Bandara', 150000]],
            3 => [['Jasa Supir', 200000]],
            4 => [],
            5 => [['Jasa Supir', 400000], ['Antar Jemput', 150000]],
            6 => [],
            7 => [['Jasa Supir', 9000000]],
            8 => [],
            9 => [['Jasa Supir', 900000], ['Antar Jemput', 200000]],
            10 => [['Antar Jemput', 100000]],
            11 => [],
            12 => [['Jasa Supir', 500000]],
            13 => [['Jasa Supir', 6000000]],
            14 => [['Antar Jemput', 175000]],
            default => [],
        };

        return array_map(fn (array $a) => ['nama' => $a[0], 'nominal' => $a[1]], $rows);
    }

    // =====================================================================
    // 7. BOOKING PAYMENTS
    // =====================================================================

    private function seedBookingPayments(array $bookings, array $users): void
    {
        $staffId = $this->userId($users, ['reception', 'admin', 'any']);

        foreach ($bookings as $booking) {
            $plans = match ($booking['bayar']) {
                'Lunas' => [[
                    'jumlah' => $booking['total'],
                    'dari' => 'Belum Bayar',
                    'sampai' => 'Lunas',
                    'metode' => 'transfer',
                    'catatan' => 'Transfer BCA',
                    'at' => $booking['mulai']->copy()->subDay()->setTime(9, 0),
                ]],
                'DP' => $booking['rental'] === 'Selesai'
                    ? [
                        [
                            'jumlah' => $booking['dp'],
                            'dari' => 'Belum Bayar',
                            'sampai' => 'DP',
                            'metode' => 'transfer',
                            'catatan' => 'DP 30% via transfer Mandiri',
                            'at' => $booking['mulai']->copy()->subDays(2)->setTime(14, 0),
                        ],
                        [
                            'jumlah' => $booking['total'] - $booking['dp'],
                            'dari' => 'DP',
                            'sampai' => 'Lunas',
                            'metode' => 'tunai',
                            'catatan' => 'Pelunasan saat ambil mobil',
                            'at' => $booking['mulai']->copy()->setTime(9, 30),
                        ],
                    ]
                    : [[
                        'jumlah' => $booking['dp'],
                        'dari' => 'Belum Bayar',
                        'sampai' => 'DP',
                        'metode' => 'transfer',
                        'catatan' => 'DP 50% via transfer perusahaan',
                        'at' => $booking['mulai']->copy()->subDays(2)->setTime(11, 0),
                    ]],
                'Refunded' => [
                    [
                        'jumlah' => $booking['dp'],
                        'dari' => 'Belum Bayar',
                        'sampai' => 'DP',
                        'metode' => 'tunai',
                        'catatan' => 'DP tunai di kasir',
                        'at' => $booking['mulai']->copy()->subDays(2)->setTime(10, 0),
                    ],
                    [
                        'jumlah' => $booking['dp'],
                        'dari' => 'DP',
                        'sampai' => 'Refunded',
                        'metode' => 'tunai',
                        'catatan' => 'Refund karena pembatalan',
                        'at' => $booking['mulai']->copy()->setTime(15, 0),
                    ],
                ],
                default => [],
            };

            foreach ($plans as $plan) {
                $this->insertOnce('booking_payments', [
                    'booking_id' => $booking['id'],
                    'jumlah' => $plan['jumlah'],
                    'status_sesudah' => $plan['sampai'],
                    'catatan' => $plan['catatan'],
                ], [
                    'booking_id' => $booking['id'],
                    'jumlah' => $plan['jumlah'],
                    'status_sebelum' => $plan['dari'],
                    'status_sesudah' => $plan['sampai'],
                    'metode_bayar' => $plan['metode'],
                    'catatan' => $plan['catatan'],
                    'created_by' => $staffId ?: null,
                    'created_at' => $plan['at']->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    // =====================================================================
    // 8. TRANSACTIONS
    // =====================================================================

    private function seedTransactions(array $bookings, array $users): void
    {
        $staffId = $this->userId($users, ['admin', 'reception', 'any']);

        // 8.1 Pemasukan dari pembayaran booking
        foreach ($bookings as $booking) {
            foreach (DB::table('booking_payments')->where('booking_id', $booking['id'])->orderBy('id')->get() as $payment) {
                if ($payment->status_sesudah === 'Refunded') {
                    continue;
                }

                $label = match ($payment->status_sesudah) {
                    'Lunas' => 'Pelunasan',
                    'DP' => 'DP',
                    default => 'Pembayaran',
                };

                $tanggal = Carbon::parse($payment->created_at);

                $this->insertTransaction([
                    'tipe' => 'Pemasukan',
                    'kategori' => 'sewa',
                    'jumlah' => $payment->jumlah,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'deskripsi' => $this->tag("{$label} pesanan {$booking['nomor']} - {$booking['pelanggan']}"),
                    'referensi_type' => 'booking',
                    'referensi_id' => $booking['id'],
                    'car_id' => $booking['car']['id'],
                    'user_id' => $staffId ?: null,
                    'created_at' => $payment->created_at,
                ], 'transactions');
            }
        }

        // 8.2 Pengeluaran kantor & operasional (tanpa mobil)
        $expenses = [
            ['bulan' => 8, 'hari' => 3, 'kategori' => 'kantor', 'jumlah' => 450000, 'desc' => 'Tagihan listrik & internet kantor'],
            ['bulan' => 7, 'hari' => 8, 'kategori' => 'kantor', 'jumlah' => 320000, 'desc' => 'ATK & printer toner'],
            ['bulan' => 6, 'hari' => 14, 'kategori' => 'operasional_mobil', 'jumlah' => 750000, 'desc' => 'Isi bensin armada 4 unit'],
            ['bulan' => 5, 'hari' => 6, 'kategori' => 'kantor', 'jumlah' => 280000, 'desc' => 'Sewa fotokopi & domain'],
            ['bulan' => 4, 'hari' => 21, 'kategori' => 'operasional_mobil', 'jumlah' => 500000, 'desc' => 'Cuci mobil armada 6 unit'],
            ['bulan' => 3, 'hari' => 11, 'kategori' => 'kantor', 'jumlah' => 600000, 'desc' => 'Gaji satpam & cleaning kantor'],
            ['bulan' => 2, 'hari' => 19, 'kategori' => 'operasional_mobil', 'jumlah' => 1200000, 'desc' => 'Penggantian ban 2 unit armada'],
            ['bulan' => 1, 'hari' => 25, 'kategori' => 'kantor', 'jumlah' => 350000, 'desc' => 'Perlengkapan kantor & souvenir'],
            ['bulan' => 0, 'hari' => 5, 'kategori' => 'kantor', 'jumlah' => 475000, 'desc' => 'Tagihan listrik, air & internet'],
            ['bulan' => 0, 'hari' => 15, 'kategori' => 'operasional_mobil', 'jumlah' => 850000, 'desc' => 'Paket asuransi armada bulanan'],
        ];

        foreach ($expenses as $expense) {
            $tanggal = $this->tanggal($expense['bulan'], $expense['hari']);

            $this->insertTransaction([
                'tipe' => 'Pengeluaran',
                'kategori' => $expense['kategori'],
                'jumlah' => $expense['jumlah'],
                'tanggal' => $tanggal->format('Y-m-d'),
                'deskripsi' => $this->tag($expense['desc'] . ' (' . $tanggal->format('d M Y') . ')'),
                'referensi_type' => null,
                'referensi_id' => null,
                'car_id' => null,
                'user_id' => $staffId ?: null,
                'created_at' => $tanggal->copy()->setTime(13, 0)->format('Y-m-d H:i:s'),
            ], 'transactions');
        }
    }

    // =====================================================================
    // 9. ODOMETER LOGS
    // =====================================================================

    private function seedOdometerLogs(array $cars): void
    {
        foreach ($cars as $car) {
            // Angka odometer diturunkan dari km_saat_ini mobil (tetap / stabil)
            // supaya riwayat selalu naik dan tidak pernah negatif.
            $total = max(3, (int) $car['km_saat_ini']);

            $readings = [
                ['km' => (int) round($total * 0.55), 'bulan' => 6, 'hari' => 10],
                ['km' => (int) round($total * 0.78), 'bulan' => 3, 'hari' => 12],
                ['km' => $total, 'bulan' => 0, 'hari' => 18],
            ];

            foreach ($readings as $reading) {
                $at = $this->tanggal($reading['bulan'], $reading['hari'])->setTime(8, 0);

                $this->insertOnce('odometer_logs', [
                    'car_id' => $car['id'],
                    'km_reading' => $reading['km'],
                ], [
                    'car_id' => $car['id'],
                    'km_reading' => $reading['km'],
                    'created_at' => $at->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    // =====================================================================
    // 10. SERVICE LOGS
    // =====================================================================

    /**
     * @return array<int, array> service log dummy yang tersimpan
     */
    private function seedServiceLogs(array $cars): array
    {
        $definitions = [
            ['car' => 'C04', 'bengkel' => 'Auto2000 Cab. Cempaka Putih', 'km' => 52300, 'bulan' => 0, 'hari' => 22, 'hari_keluar' => null, 'status' => 'open', 'catatan' => 'Servis rutin 50.000 km, ganti oli & filter'],
            ['car' => 'C01', 'bengkel' => 'Auto2000 Cab. Fatmawati', 'km' => 46250, 'bulan' => 3, 'hari' => 12, 'hari_keluar' => 12, 'status' => 'closed', 'catatan' => 'Spooring & balancing keempat ban'],
            ['car' => 'C02', 'bengkel' => 'Daihatsu Astra Motor Kemang', 'km' => 33900, 'bulan' => 4, 'hari' => 8, 'hari_keluar' => 8, 'status' => 'closed', 'catatan' => 'Servis rutin 35.000 km'],
            ['car' => 'C03', 'bengkel' => 'Honda Prospect Jl. MT Haryono', 'km' => 17100, 'bulan' => 2, 'hari' => 14, 'hari_keluar' => 14, 'status' => 'closed', 'catatan' => 'Ganti ban depan & cek rem'],
            ['car' => 'C05', 'bengkel' => 'Mitsubishi PLoos PlUIT', 'km' => 25200, 'bulan' => 5, 'hari' => 6, 'hari_keluar' => 7, 'status' => 'closed', 'catatan' => 'Servis 25.000 km & ganti filter AC'],
            ['car' => 'C06', 'bengkel' => 'Suzuki Authorized Service TB Simatupang', 'km' => 58900, 'bulan' => 6, 'hari' => 19, 'hari_keluar' => 19, 'status' => 'closed', 'catatan' => 'Ganti kampas rem belakang & oli mesin'],
            ['car' => 'C07', 'bengkel' => 'Auto2000 Jagorawi', 'km' => 13500, 'bulan' => 7, 'hari' => 9, 'hari_keluar' => 9, 'status' => 'closed', 'catatan' => 'Servis awal 15.000 km'],
            ['car' => 'C08', 'bengkel' => 'Daihatsu Astra Pongky', 'km' => 6100, 'bulan' => 8, 'hari' => 11, 'hari_keluar' => 11, 'status' => 'closed', 'catatan' => 'Cek kelengkapan & servis kecil'],
            ['car' => 'C04', 'bengkel' => 'Auto2000 Cab. Cempaka Putih', 'km' => 48700, 'bulan' => 7, 'hari' => 5, 'hari_keluar' => 5, 'status' => 'closed', 'catatan' => 'Ganti aki & busi'],
        ];

        $services = [];

        foreach ($definitions as $index => $def) {
            $car = $cars[$def['car']] ?? null;

            if (! $car) {
                continue;
            }

            $masuk = $this->tanggal($def['bulan'], $def['hari']);
            $keluar = $def['hari_keluar'] === null
                ? null
                : $this->tanggal($def['bulan'], $def['hari_keluar']);

            $values = [
                'car_id' => $car['id'],
                'nama_bengkel' => $def['bengkel'],
                'catatan' => $this->tag($def['catatan']),
                'km_saat_servis' => $def['km'],
                'tanggal_masuk' => $masuk->format('Y-m-d'),
                'tanggal_keluar' => $keluar?->format('Y-m-d'),
                'status_bengkel' => $def['status'],
                'created_at' => $masuk->copy()->setTime(8, 30)->format('Y-m-d H:i:s'),
                'updated_at' => ($keluar ?? $masuk)->copy()->setTime(15, 0)->format('Y-m-d H:i:s'),
            ];

            $existing = DB::table('service_logs')
                ->where('car_id', $car['id'])
                ->where('km_saat_servis', $def['km'])
                ->first();

            if ($existing) {
                $id = (int) $existing->id;
                $this->skipped('service_logs');
            } else {
                $id = (int) DB::table('service_logs')->insertGetId($values);
                $this->inserted('service_logs');
            }

            $services[] = [
                'id' => $id,
                'no' => $index + 1,
                'car' => $car,
                'bengkel' => $def['bengkel'],
                'km' => $def['km'],
                'masuk' => $masuk,
                'status' => $def['status'],
            ];
        }

        return $services;
    }

    // =====================================================================
    // 11. SERVICE LOG DETAILS
    // =====================================================================

    private function seedServiceLogDetails(array $services, array $users): void
    {
        $definitions = [
            1 => [['Oli Mesin (Engine Oil)', 'Ganti oli mesin 10W-40', 450000], ['Filter Oli', 'Ganti filter oli original', 150000], ['Filter AC (Cabin Filter)', 'Ganti filter AC kabin', 200000]],
            2 => [['Spooring & Balancing', 'Spooring & balancing keempat ban', 250000], ['Ban (Tire)', 'Rotasi ban depan-belakang', 100000]],
            3 => [['Oli Mesin (Engine Oil)', 'Ganti oli mesin 10W-40', 400000], ['Filter Oli', 'Ganti filter oli', 130000], ['Busi (Spark Plug)', 'Ganti busi 4 pcs', 320000]],
            4 => [['Kampas Rem Depan (Brake Pad)', 'Ganti kampas rem depan', 650000], ['Minyak Rem (Brake Fluid)', 'Ganti minyak rem', 180000]],
            5 => [['Oli Mesin (Engine Oil)', 'Ganti oli mesin 5W-30 full synthetic', 600000], ['Filter AC (Cabin Filter)', 'Ganti filter AC', 220000], ['Aki (Battery)', 'Tes aki, voltase drop', 0]],
            6 => [['Oli Mesin (Engine Oil)', 'Ganti oli mesin 10W-40', 450000], ['Kampas Rem Belakang (Brake Shoe)', 'Ganti kampas rem belakang', 520000]],
            7 => [['Oli Mesin (Engine Oil)', 'Servis awal oli mesin', 550000], ['Filter Udara', 'Ganti filter udara mesin', 250000]],
            8 => [['Wiper Kaca', 'Ganti wiper depan', 180000], ['Bohlam Lampu Rem / Sein', 'Ganti lampu rem', 95000]],
            9 => [['Busi (Spark Plug)', 'Ganti set busi', 300000], ['Aki (Battery)', 'Ganti aki baru', 900000]],
        ];

        $staffId = $this->userId($users, ['admin', 'reception', 'any']);

        foreach ($services as $service) {
            $rows = $definitions[$service['no']] ?? [];

            foreach ($rows as [$partName, $tindakan, $biaya]) {
                $carPartId = DB::table('car_parts')
                    ->where('car_id', $service['car']['id'])
                    ->where('nama_part', $partName)
                    ->value('id');

                $this->insertOnce('service_log_details', [
                    'service_log_id' => $service['id'],
                    'deskripsi_tindakan' => $tindakan,
                ], [
                    'service_log_id' => $service['id'],
                    'car_part_id' => $carPartId ?: null,
                    'deskripsi_tindakan' => $tindakan,
                    'biaya' => $biaya,
                    'created_at' => $service['masuk']->copy()->setTime(8, 30)->format('Y-m-d H:i:s'),
                    'updated_at' => $service['masuk']->copy()->setTime(15, 0)->format('Y-m-d H:i:s'),
                ]);
            }

            // Pengeluaran servis mengikuti detail yang benar-benar tersimpan.
            $total = (int) DB::table('service_log_details')
                ->where('service_log_id', $service['id'])
                ->sum('biaya');

            if ($total > 0) {
                $tanggal = $service['masuk']->copy();

                $this->insertTransaction([
                    'tipe' => 'Pengeluaran',
                    'kategori' => 'servis',
                    'jumlah' => $total,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'deskripsi' => $this->tag("Servis {$service['car']['plat_nomor']} - {$service['bengkel']} ({$service['km']} km)"),
                    'referensi_type' => 'service_log',
                    'referensi_id' => $service['id'],
                    'car_id' => $service['car']['id'],
                    'user_id' => $staffId ?: null,
                    'created_at' => $tanggal->copy()->setTime(16, 0)->format('Y-m-d H:i:s'),
                ], 'transactions');
            }
        }
    }

    // =====================================================================
    // 12. INVOICES + VERIFICATIONS (+ nomor surat)
    // =====================================================================

    private function seedInvoices(array $bookings): void
    {
        $jenisDokumen = [
            'SH' => 'Invoice Sewa Harian',
            'SK' => 'Invoice Sewa Kontrak',
            'AK' => 'Invoice Asuransi Kerusakan',
            'BNG' => 'Invoice Bengkel',
        ];

        $counter = [];

        // 12.1 Invoice dari booking (1 invoice per booking, sesuai unique constraint)
        foreach ([1, 3, 7, 10, 13] as $no) {
            $booking = collect($bookings)->firstWhere('no', $no);

            if (! $booking) {
                continue;
            }

            $jenis = match (true) {
                $booking['tipe'] === 'kontrak' => 'SK',
                $no === 3 => 'AK',
                default => 'SH',
            };

            $this->insertOnce('invoices', [
                'booking_id' => $booking['id'],
                'jenis_invoice' => $jenis,
            ], [
                'booking_id' => $booking['id'],
                'jenis_invoice' => $jenis,
                'nomor_seri' => 'INV-DUM-' . $jenis . '-' . str_pad((string) $booking['no'], 3, '0', STR_PAD_LEFT),
                'kode_mobil' => $booking['car']['kode_mobil'],
                'qr_code_string' => route('invoice.verify', $this->invoiceToken($jenis, 'booking-' . $booking['id'])),
                'issued_at' => $booking['mulai']->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'surat_id' => null,
                'service_log_id' => null,
                'created_at' => $booking['mulai']->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'updated_at' => $booking['mulai']->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
            ]);

            $invoice = DB::table('invoices')
                ->where('booking_id', $booking['id'])
                ->where('jenis_invoice', $jenis)
                ->first();

            if (! $invoice) {
                continue;
            }

            // Nomor surat dibuat terpisah (setelah invoice ada) agar tidak ada
            // baris surat yatim ketika seeder diulang.
            $this->attachSurat($invoice, $jenis, $jenisDokumen[$jenis], $booking['mulai'], $counter);

            $this->insertOnce('invoice_verifications', [
                'invoice_id' => $invoice->id,
            ], [
                'invoice_id' => $invoice->id,
                'token' => $this->invoiceToken($jenis, 'booking-' . $booking['id']),
                'is_verified' => $no % 2 === 0 ? 1 : 0,
                'verified_at' => $no % 2 === 0 ? $booking['mulai']->copy()->addDay()->setTime(11, 0)->format('Y-m-d H:i:s') : null,
            ], 'invoice_verifications');
        }

        // 12.2 Invoice bengkel (BNG) tidak dibuat di sini.
        // Kolom invoices.booking_id di database bersifat NOT NULL, sehingga
        // invoice bengkel wajib merujuk pada sebuah pesanan. Perbaiki dulu
        // InvoiceController::bengkel() bila ingin data invoice bengkel.
    }

    /**
     * Buat (atau pakai kembali) nomor surat dummy untuk sebuah invoice.
     */
    private function attachSurat(object $invoice, string $kode, string $jenisDokumen, Carbon $tanggal, array &$counter): void
    {
        if ($invoice->surat_id) {
            return;
        }

        $tahun = (string) $tanggal->year;
        $key = $kode . '|' . $tahun;

        // Lanjut dari nomor urut terakhir (logika sama dengan InvoiceController)
        // supaya nomor surat dummy tidak bentrok dengan yang sudah ada.
        $counter[$key] ??= (int) (DB::table('surat')
            ->where('kode', $kode)
            ->where('tahun', $tahun)
            ->max('no_urut') ?? 0);
        $counter[$key]++;

        $noUrut = str_pad((string) $counter[$key], 3, '0', STR_PAD_LEFT);
        $nomorSurat = sprintf('DUM-%s/SRT/%s/JRB/%s/%s', $noUrut, $kode, $this->bulanRomawi($tanggal->month), $tahun);

        $suratId = $this->insertOnce('surat', ['nomor_surat' => $nomorSurat], [
            'jenis_dokumen' => $jenisDokumen,
            'kode' => $kode,
            'subkategori' => null,
            'perusahaan' => 'Jaya Royal Bersama',
            'perusahaan_singkat' => 'JRB',
            'no_urut' => $noUrut,
            'bulan' => $this->bulanRomawi($tanggal->month),
            'tahun' => $tahun,
            'nomor_surat' => $nomorSurat,
            'created_at' => $tanggal->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'updated_at' => $tanggal->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
        ]);

        DB::table('invoices')
            ->where('id', $invoice->id)
            ->whereNull('surat_id')
            ->update(['surat_id' => $suratId ?: null]);
    }

    // =====================================================================
    // 13. TEMPLATE SURATS
    // =====================================================================

    private function seedTemplateSurats(): void
    {
        $templates = [
            [
                'judul' => '[DUMMY] Surat Pernyataan Pelepasan Hak Kendaraan',
                'jenis_surat' => 'Pelepasan Hak Kendaraan',
                'konten' => <<<'HTML'
                    Yang bertanda tangan di bawah ini:
                    Nama : {{ nama_pemilik }}
                    No. KTP : {{ no_ktp }}
                    Alamat : {{ alamat }}

                    Dengan ini menyatakan bahwa kendaraan dengan data:
                    No. Rangka : {{ no_rangka }}
                    No. Mesin : {{ no_mesin }}
                    No. Polis : {{ no_polis }}

                    Berhak dilepas dan dikembalikan dalam keadaan baik serta
                    dipergunakan khusus untuk keperluan Sewa Kendaraan atas nama
                    {{ nama_pelanggan }}.
                HTML,
            ],
            [
                'judul' => '[DUMMY] Surat Serah Terima Kendaraan',
                'jenis_surat' => 'Serah Terima',
                'konten' => <<<'HTML'
                    Pada hari ini, {{ tanggal }}, telah dilakukan serah terima
                    kendaraan {{ plat_nomor }} ({{ merk }} {{ model }}) dalam
                    keadaan {{ kondisi }} dengan odometer {{ odometer }} km.

                    Diterima oleh : {{ nama_pelanggan }}
                    Diterahkan oleh : {{ nama_penyerahan }} — {{ nama_perusahaan }}
                HTML,
            ],
            [
                'judul' => '[DUMMY] Invoice Sewa Kendaraan Harian',
                'jenis_surat' => 'Invoice Sewa Harian',
                'konten' => <<<'HTML'
                    {{ nama_perusahaan }}
                    No. Surat : {{ nomor_surat }}

                    Nama pelanggan : {{ nama_pelanggan }}
                    Kendaraan : {{ plat_nomor }} ({{ merk }} {{ model }})
                    Periode : {{ tanggal_mulai }} s/d {{ tanggal_selesai }}
                    Total : {{ total_harga }}

                    Pembayaran telah diterima dengan penuh.
                    Terima kasih atas kepercayaan Anda.
                HTML,
            ],
        ];

        foreach ($templates as $template) {
            $this->insertOnce('template_surats', ['judul' => $template['judul']], [
                'judul' => $template['judul'],
                'jenis_surat' => $template['jenis_surat'],
                'konten' => $template['konten'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // =====================================================================
    // 14. ACTIVITIES
    // =====================================================================

    private function seedActivities(array $bookings, array $services, array $users): void
    {
        $reception = DB::table('users')->where('email', 'reception@dummy.test')->value('nama')
            ?? DB::table('users')->where('email', 'admin@dummy.test')->value('nama')
            ?? 'Dummy User';
        $admin = DB::table('users')->where('email', 'admin@dummy.test')->value('nama') ?? $reception;

        $rows = [];

        // Aktivitas user
        foreach ([
            ['nama' => 'Dummy Admin', 'email' => 'admin@dummy.test', 'role' => 'Admin'],
            ['nama' => 'Dummy Receptionist', 'email' => 'reception@dummy.test', 'role' => 'Receptionist'],
        ] as $user) {
            $rows[] = [
                'user_id' => DB::table('users')->where('email', $user['email'])->value('id'),
                'type' => 'user',
                'icon' => 'person_add',
                'color' => 'purple',
                'title' => 'Akun user dibuat',
                'description' => $this->tag("Akun {$user['nama']} ({$user['email']}) dibuat dengan role {$user['role']}."),
                'user' => 'system',
                'subject_type' => null,
                'subject_id' => null,
                'created_at' => now()->subDays(20)->setTime(8, 0)->format('Y-m-d H:i:s'),
            ];
        }

        // Aktivitas booking
        foreach ($bookings as $booking) {
            $rows[] = [
                'user_id' => DB::table('users')->where('email', 'reception@dummy.test')->value('id'),
                'type' => 'booking',
                'icon' => 'add_circle',
                'color' => 'emerald',
                'title' => 'Pesanan Baru Dibuat',
                'description' => $this->tag("Pesanan #{$booking['nomor']} dibuat untuk {$booking['pelanggan']}"),
                'user' => $reception,
                'subject_type' => 'booking',
                'subject_id' => $booking['id'],
                'created_at' => $booking['mulai']->copy()->subDays(3)->setTime(10, 5)->format('Y-m-d H:i:s'),
            ];

            if ($booking['bayar'] !== 'Belum Bayar') {
                $rows[] = [
                    'user_id' => DB::table('users')->where('email', 'reception@dummy.test')->value('id'),
                    'type' => 'payment',
                    'icon' => 'payments',
                    'color' => 'blue',
                    'title' => 'Status Pembayaran Diubah',
                    'description' => $this->tag("Pembayaran pesanan #{$booking['nomor']} menjadi {$booking['bayar']}"),
                    'user' => $reception,
                    'subject_type' => 'booking',
                    'subject_id' => $booking['id'],
                    'created_at' => $booking['mulai']->copy()->subDays(2)->setTime(11, 30)->format('Y-m-d H:i:s'),
                ];
            }

            if ($booking['rental'] === 'Aktif') {
                $rows[] = [
                    'user_id' => DB::table('users')->where('email', 'admin@dummy.test')->value('id'),
                    'type' => 'booking',
                    'icon' => 'directions_car',
                    'color' => 'teal',
                    'title' => 'Mobil Diserahkan ke Pelanggan',
                    'description' => $this->tag("Mobil {$booking['car']['plat_nomor']} diserahkan ke {$booking['pelanggan']}"),
                    'user' => $admin,
                    'subject_type' => 'booking',
                    'subject_id' => $booking['id'],
                    'created_at' => $booking['mulai']->copy()->setTime(8, 5)->format('Y-m-d H:i:s'),
                ];
            }

            if ($booking['rental'] === 'Batal') {
                $rows[] = [
                    'user_id' => DB::table('users')->where('email', 'admin@dummy.test')->value('id'),
                    'type' => 'booking',
                    'icon' => 'cancel',
                    'color' => 'red',
                    'title' => 'Pesanan Dibatalkan',
                    'description' => $this->tag("Pesanan #{$booking['nomor']} atas nama {$booking['pelanggan']} dibatalkan"),
                    'user' => $admin,
                    'subject_type' => 'booking',
                    'subject_id' => $booking['id'],
                    'created_at' => $booking['mulai']->copy()->setTime(15, 0)->format('Y-m-d H:i:s'),
                ];
            }
        }

        // Aktivitas servis
        foreach ($services as $service) {
            $rows[] = $service['status'] === 'open'
                ? [
                    'user_id' => DB::table('users')->where('email', 'admin@dummy.test')->value('id'),
                    'type' => 'service',
                    'icon' => 'build',
                    'color' => 'amber',
                    'title' => 'Servis Mobil',
                    'description' => $this->tag("Mobil {$service['car']['plat_nomor']} masuk bengkel {$service['bengkel']}"),
                    'user' => $admin,
                    'subject_type' => 'service_log',
                    'subject_id' => $service['id'],
                    'created_at' => $service['masuk']->copy()->setTime(8, 35)->format('Y-m-d H:i:s'),
                ]
                : [
                    'user_id' => DB::table('users')->where('email', 'admin@dummy.test')->value('id'),
                    'type' => 'maintenance',
                    'icon' => 'check_circle',
                    'color' => 'green',
                    'title' => 'Servis Selesai',
                    'description' => $this->tag("Servis {$service['car']['plat_nomor']} selesai di {$service['bengkel']} ({$service['km']} km)"),
                    'user' => $admin,
                    'subject_type' => 'service_log',
                    'subject_id' => $service['id'],
                    'created_at' => $service['masuk']->copy()->setTime(15, 5)->format('Y-m-d H:i:s'),
                ];
        }

        foreach ($rows as $row) {
            $this->insertOnce('activities', ['description' => $row['description']], $row + [
                'updated_at' => $row['created_at'],
            ]);
        }
    }

    // =====================================================================
    // 15. STATUS MOBIL (hanya mobil dummy)
    // =====================================================================

    /**
     * Selaraskan status mobil dummy dengan booking aktif & servis yang masih
     * berjalan. Query dibatasi pada mobil dummy (no_mesin DUMMY-) sehingga
     * mobil milik user lain tidak tersentuh.
     */
    private function syncCarStatus(array $cars): void
    {
        $today = now()->toDateString();

        foreach ($cars as $car) {
            $sedangDisewa = DB::table('bookings')
                ->whereIn('status_rental', ['Aktif', 'Menunggu'])
                ->whereDate('tanggal_mulai', '<=', $today)
                ->whereDate('tanggal_selesai', '>=', $today)
                ->whereIn('id', function ($query) use ($car) {
                    $query->select('booking_id')->from('booking_car')->where('car_id', $car['id']);
                })
                ->exists();

            $diBengkel = DB::table('service_logs')
                ->where('car_id', $car['id'])
                ->where('status_bengkel', 'open')
                ->exists();

            $status = match (true) {
                $car['status'] === 'Nonaktif' => 'Nonaktif',
                $sedangDisewa => 'Sedang Disewa',
                $diBengkel => 'Masuk Bengkel',
                default => 'Tersedia',
            };

            DB::table('cars')
                ->where('id', $car['id'])
                ->where('no_mesin', 'like', 'DUMMY-%')
                ->update(['status' => $status, 'updated_at' => now()]);
        }
    }

    // =====================================================================
    // HELPER
    // =====================================================================

    /**
     * Sisipkan baris hanya kalau belum ada (tidak pernah menimpa data lama).
     *
     * @return int id baris yang tersimpan
     */
    private function insertOnce(string $table, array $match, array $values, ?string $counterKey = null): int
    {
        $counterKey ??= $table;
        $existing = DB::table($table)->where($match)->first();

        if ($existing) {
            $this->skipped($counterKey);

            return (int) ($existing->id ?? 0);
        }

        $id = (int) DB::table($table)->insertGetId($values);
        $this->inserted($counterKey);

        return $id;
    }

    /**
     * Transaksi selalu dicocokkan lewat deskripsi (unik per baris dummy).
     */
    private function insertTransaction(array $values, string $table): void
    {
        $this->insertOnce($table, ['deskripsi' => $values['deskripsi']], $values + [
            'updated_at' => $values['created_at'],
        ]);
    }

    private function inserted(string $key): void
    {
        $this->inserted[$key] = ($this->inserted[$key] ?? 0) + 1;
    }

    private function skipped(string $key): void
    {
        $this->skipped[$key] = ($this->skipped[$key] ?? 0) + 1;
    }

    private function tag(string $text): string
    {
        return '[' . self::TAG . '] ' . $text;
    }

    private function userId(array $users, array $keys): int
    {
        foreach ($keys as $key) {
            if (! empty($users[$key])) {
                return (int) $users[$key];
            }
        }

        return 0;
    }

    private function hargaDefault(array $car): int
    {
        return (int) (DB::table('cars')->where('id', $car['id'])->value('harga_default') ?? 0);
    }

    private function hargaKontrak(array $car): int
    {
        return (int) (DB::table('cars')->where('id', $car['id'])->value('harga_kontrak') ?? 0);
    }

    /**
     * Tanggal relatif: 0 = bulan ini, 1 = bulan lalu, dan seterusnya.
     */
    private function tanggal(int $bulan, int $hari): Carbon
    {
        $date = now()->startOfMonth()->subMonths($bulan);
        $maxHari = (int) $date->copy()->endOfMonth()->format('j');

        return $date->setDate((int) $date->format('Y'), (int) $date->format('m'), min($hari, $maxHari))->startOfDay();
    }

    private function bulanRomawi(int $month): string
    {
        return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month - 1] ?? 'I';
    }

    private function invoiceToken(string $jenis, string $ref): string
    {
        return md5('dummy-invoice|' . $jenis . '|' . $ref);
    }

    private function report(): void
    {
        $tables = array_values(array_unique(array_merge(array_keys($this->inserted), array_keys($this->skipped))));
        sort($tables);

        $this->command->newLine();
        $this->command->info('Data dummy selesai ditambahkan (tidak ada data lama yang dihapus).');
        $this->command->table(
            ['Tabel', 'Baris baru', 'Sudah ada (skip)'],
            array_map(fn ($table) => [$table, $this->inserted[$table] ?? 0, $this->skipped[$table] ?? 0], $tables)
        );

        if ($this->warnings) {
            $this->command->warn('Catatan:');

            foreach (array_unique($this->warnings) as $warning) {
                $this->command->line('  - ' . $warning);
            }
        }

        $this->command->line('  User dummy   : admin@dummy.test / reception@dummy.test / owner@dummy.test');
        $this->command->line('  Password     : ' . self::PASSWORD);
        $this->command->line('  Bersihkan   : php artisan db:seed --class=DummyDataPurger');
    }
}
