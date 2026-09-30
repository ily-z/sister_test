<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Membersihkan data dummy yang dibuat oleh DummyDataSeeder.
 *
 * PENTING:
 * - Hanya menghapus baris yang memakai penanda (marker) data dummy.
 *   Data milik user lain TIDAK AKAN tersentuh.
 * - Baris turunan (booking_car, booking_addons, booking_payments, car_parts,
 *   odometer_logs, service_log_details, invoice_verifications) ikut terhapus
 *   melalui cascade / karena induknya dihapus lebih dulu.
 * - master_parts TIDAK dihapus, karena berisi data referensi yang dipakai
 *   bersama dengan data asli.
 *
 * CARA JALANKAN:
 *   php artisan db:seed --class=DummyDataPurger
 */
class DummyDataPurger extends Seeder
{
    /** @var array<string, int> */
    private array $deleted = [];

    public function run(): void
    {
        $dummyCars = DB::table('cars')->where('no_mesin', 'like', 'DUMMY-%')->pluck('id')->all();
        $dummyBookings = DB::table('bookings')->where('nomor_pesanan', 'like', 'DUM-%')->pluck('id')->all();
        $dummyServices = DB::table('service_logs')
            ->whereIn('car_id', $dummyCars ?: [0])
            ->pluck('id')->all();
        $dummyUsers = DB::table('users')->where('email', 'like', '%@dummy.test')->pluck('id')->all();

        $this->command->info('Menghapus data dummy (hanya baris bertanda dummy)...');

        // 1. Invoice & verifikasinya (invoice_verifications cascade dari invoice,
        //    tapi dihapus eksplisit agar foreign key dari tabel lain aman).
        $dummyInvoices = DB::table('invoices')
            ->where('nomor_seri', 'like', 'INV-DUM-%')
            ->pluck('id')->all();

        $this->deleteWhere('invoice_verifications', fn ($q) => $q->whereIn('invoice_id', $dummyInvoices ?: [0]));
        $this->deleteWhere('invoices', fn ($q) => $q->whereIn('id', $dummyInvoices ?: [0]));

        // 2. Detail pesanan
        $this->deleteWhere('booking_payments', fn ($q) => $q->whereIn('booking_id', $dummyBookings ?: [0]));
        $this->deleteWhere('booking_addons', fn ($q) => $q->whereIn('booking_id', $dummyBookings ?: [0]));
        $this->deleteWhere('booking_car', fn ($q) => $q
            ->whereIn('booking_id', $dummyBookings ?: [0])
            ->orWhereIn('car_id', $dummyCars ?: [0]));

        // 3. Transaksi & aktivitas dummy
        $this->deleteWhere('transactions', fn ($q) => $q
            ->where('deskripsi', 'like', '[DUMMY]%')
            ->orWhere(function ($q) use ($dummyBookings) {
                $q->where('referensi_type', 'booking')->whereIn('referensi_id', $dummyBookings ?: [0]);
            })
            ->orWhere(function ($q) use ($dummyServices) {
                $q->where('referensi_type', 'service_log')->whereIn('referensi_id', $dummyServices ?: [0]);
            }));
        $this->deleteWhere('activities', fn ($q) => $q->where('description', 'like', '[DUMMY]%'));

        // 4. Pesanan dummy
        $this->deleteWhere('bookings', fn ($q) => $q->whereIn('id', $dummyBookings ?: [0]));

        // 5. Data armada dummy
        $this->deleteWhere('service_log_details', fn ($q) => $q->whereIn('service_log_id', $dummyServices ?: [0]));
        $this->deleteWhere('service_logs', fn ($q) => $q->whereIn('car_id', $dummyCars ?: [0]));
        $this->deleteWhere('car_parts', fn ($q) => $q->whereIn('car_id', $dummyCars ?: [0]));
        $this->deleteWhere('odometer_logs', fn ($q) => $q->whereIn('car_id', $dummyCars ?: [0]));
        $this->deleteWhere('cars', fn ($q) => $q->whereIn('id', $dummyCars ?: [0]));

        // 6. Nomor surat, template surat, dan user dummy
        $this->deleteWhere('surat', fn ($q) => $q->where('nomor_surat', 'like', 'DUM-%'));
        $this->deleteWhere('template_surats', fn ($q) => $q->where('judul', 'like', '[DUMMY]%'));
        $this->deleteWhere('users', fn ($q) => $q->whereIn('id', $dummyUsers ?: [0]));

        $this->report();
    }

    private function deleteWhere(string $table, callable $callback): void
    {
        $count = $callback(DB::table($table))->count();

        if ($count === 0) {
            return;
        }

        $callback(DB::table($table))->delete();
        $this->deleted[$table] = $count;
    }

    private function report(): void
    {
        if (! $this->deleted) {
            $this->command->info('Tidak ada data dummy untuk dibersihkan.');

            return;
        }

        $tables = array_keys($this->deleted);
        sort($tables);

        $this->command->newLine();
        $this->command->info('Data dummy berhasil dibersihkan:');
        $this->command->table(
            ['Tabel', 'Baris dihapus'],
            array_map(fn ($table) => [$table, $this->deleted[$table]], $tables)
        );
        $this->command->line('  master_parts tidak dihapus (data referensi bersama).');
    }
}
