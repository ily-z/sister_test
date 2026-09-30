<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Nama database yang boleh dipakai oleh test.
     *
     * Feature test memakai RefreshDatabase, yang menjalankan migrate:fresh.
     * Migrate fresh menghapus SELURUH tabel, jadi test yang tidak sengaja
     * menunjuk database development akan menghapus semua data asli.
     */
    private const SAFE_TEST_DATABASES = [
        'car_rent_testing',
        ':memory:',
    ];

    /**
     * Dipanggil sebelum setUpTraits(), yaitu sebelum RefreshDatabase
     * menjalankan migrate:fresh. Jadi guard di sini masih sempat
     * menghentikan test sebelum data development tersentuh.
     */
    public function createApplication()
    {
        $this->guardAgainstNonTestDatabase();

        return parent::createApplication();
    }

    /**
     * Gagal cepat sebelum test sempat menghapus data.
     */
    private function guardAgainstNonTestDatabase(): void
    {
        $connection = $this->rawEnv('DB_CONNECTION') ?: 'mysql';

        if ($connection === 'sqlite') {
            return;
        }

        $database = trim((string) $this->rawEnv('DB_DATABASE'));

        if (in_array($database, self::SAFE_TEST_DATABASES, true)) {
            return;
        }

        $shown = $database === '' ? '(kosong)' : $database;

        throw new \RuntimeException(
            "Test dibatalkan demi keamanan data.\n"
            ."RefreshDatabase akan menjalankan migrate:fresh, yang menghapus SELURUH tabel\n"
            ."pada database \"{$shown}\". Itu database development, bukan database test.\n"
            ."\n"
            ."Perbaiki konfigurasi test:\n"
            ."  - phpunit.xml      : <env name=\"DB_DATABASE\" value=\"car_rent_testing\"/>\n"
            ."  - atau .env.testing dengan DB_DATABASE=car_rent_testing\n"
            ."\n"
            ."Buat dulu database test-nya:\n"
            ."  CREATE DATABASE car_rent_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n"
            ."\n"
            ."Data development Anda tidak disentuh."
        );
    }

    /**
     * Baca environment variable mentah, tanpa lewat config(),
     * karena aplikasi belum dibuat saat guard ini berjalan.
     */
    private function rawEnv(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return ($value === false || $value === null) ? null : (string) $value;
    }
}
