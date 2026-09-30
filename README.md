<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<h1 align="center">Precision Fleet - Enterprise ERP</h1>

<p align="center">
  Sistem Informasi Manajemen Armada, Penyewaan Kendaraan, dan Pemantauan Servis Bengkel berbasis Laravel.
</p>

---

## 🚀 Tentang Project

**Precision Fleet** adalah sistem ERP berbasis Laravel yang dirancang untuk membantu manajemen operasional kendaraan secara terintegrasi. Sistem ini mencakup:

* **Manajemen Penyewaan**
  Perekaman data booking, kontrak, status pembayaran, dan riwayat transaksi.

* **Manajemen Armada**
  Pendataan kendaraan, status ketersediaan unit, spesifikasi kendaraan, dan monitoring kondisi armada.

* **Servis & Bengkel**
  Pelacakan keausan komponen secara spesifik (*Granular Part Tracking*) berbasis Odometer (KM).

* **Keuangan & Laporan**
  Pencatatan pemasukan dari penyewaan dan pengeluaran dari servis/perbaikan kendaraan.

---

## 🛠️ Persiapan & Instalasi Dasar

Ikuti langkah-langkah berikut untuk setup project di komputer lokal.

### 1. Install Dependencies

Pastikan sudah menginstall:

* PHP
* Composer
* Node.js
* MySQL / MariaDB

Lalu jalankan:

```bash
composer install
npm install
```

---

### 2. Konfigurasi Environment

Salin file environment:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Lalu sesuaikan konfigurasi database di file `.env`:

```env
DB_DATABASE=precision_fleet
DB_USERNAME=root
DB_PASSWORD=
```

---

## 🗄️ Database Migration & Seeder

Jalankan migrasi database:

```bash
php artisan migrate
```

Kemudian jalankan seeder untuk mengisi template Master Part:

```bash
php artisan db:seed --class=MasterPartSeeder
```

---

## 🔑 Membuat Akun Admin / Owner

Masuk ke Laravel Tinker:

```bash
php artisan tinker
```

Lalu jalankan:

```php
App\Models\User::create([
    'nama' => 'admin',
    'email' => 'admin@gmail.com',
    'password' => bcrypt('admin'),
    'role' => 'owner',
    'is_active' => 1
]);
```

Keluar dari Tinker:

```bash
exit
```

---

## 💻 Menjalankan Project

Project menggunakan **Vite** untuk build asset frontend.

Jalankan 2 terminal:

### Terminal 1 — Menjalankan Laravel Server

```bash
php artisan serve
```

---

### Terminal 2 — Menjalankan Vite

```bash
npm run dev
```

---

Aplikasi dapat diakses di:

```text
http://localhost:8000
```

---

## 🔐 Default Login

| Role  | Email                                     | Password |
| ----- | ----------------------------------------- | -------- |
| Owner | [admin@gmail.com](mailto:admin@gmail.com) | admin    |

---

## 🧪 Testing

Project ini menggunakan **PHPUnit / `php artisan test`** untuk automated Feature test, dengan environment terpisah dari aplikasi utama (tidak menyentuh database `precision_fleet`).

### 1. Buat file `.env.testing`

Copy `.env` ke `.env.testing`, lalu sesuaikan bagian berikut (biarkan sisanya sama):

```env
APP_ENV=testing
DB_DATABASE=car_rent_testing
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

> ⚠️ `.env.testing` **tidak di-commit ke Git** (sudah masuk `.gitignore`). Setiap kontributor perlu bikin file ini sendiri secara lokal.

### 2. Buat database testing

```sql
CREATE DATABASE car_rent_testing;
```

### 3. Jalankan test

```bash
php artisan test
```

Untuk jalankan test dari satu modul tertentu saja:

```bash
php artisan test --filter=AuthTest
```

### Catatan

* Test pakai `RefreshDatabase`, jadi setiap kali dijalankan, database `car_rent_testing` akan di-migrate ulang dari nol — **jangan pernah** arahkan `.env.testing` ke database `precision_fleet` / production.
* Struktur test ada di `tests/Feature` (test end-to-end lewat HTTP request) dan `tests/Unit` (test logic murni di Model/class).

---

## 📌 Teknologi yang Digunakan

* Laravel
* MySQL
* Tailwind CSS
* Vite

---

## 📄 License

Project ini dikembangkan menggunakan Laravel Framework yang berlisensi **MIT License**.