# DEPLOY — Precision Fleet (3 Server)

Panduan menjalankan Precision Fleet di **tiga server terpisah**, supaya bisa
diakses dari IP address seadanya lewat **HTTP biasa** (tanpa HTTPS dan tanpa
domain).

```
  Server #1 (FRONTEND)          Server #2 (BACKEND)         Server #3 (DATABASE)
  ┌────────────────────┐         ┌────────────────────┐      ┌────────────────┐
  │ nginx              │         │ nginx              │      │ MySQL 8        │
  │  ├ dist/ (statis)  │  /api   │  └ PHP-FPM         │      │  car_rent      │
  │  └ proxy /api ─────┼────────>│     └ Laravel 11   │─────>│  27 tabel      │
  │                    │  :8000  │                      │3306 │                │
  └────────────────────┘         └────────────────────┘      └────────────────┘
   http://10.0.0.1:8080            http://10.0.0.2:8000        10.0.0.3
   (IP user-facing)                (tidak dibuka ke user)      (tidak dibuka ke user)
```

> **Ganti semua alamat contoh di dokumen ini** dengan IP asli sebelum
> menjalankan. Yang perlu diubah hanya di tiga tempat: konfigurasi nginx
> server #1 (`listen`, `server_name`, `proxy_pass`), konfigurasi nginx
> server #2, dan blok env di backend (`APP_URL`, `FRONTEND_URL`, `DB_HOST`).

Yang diakses user hanya **server #1**. Server #2 dan #3 tidak perlu
dibuka ke internet sama sekali.

---

## Daftar Isi

1. [Prasyarat](#prasyarat)
2. [Server #3 — Database](#server-3--database)
3. [Server #2 — Backend](#server-2--backend)
4. [Server #1 — Frontend](#server-1--frontend)
5. [Verifikasi](#verifikasi)
6. [Pembaruan Berkas](#pembaruan-berkas)
7. [Cadangan](#cadangan)
8. [Masalah yang Sering Muncul](#masalah-yang-sering-muncul)

---

## Prasyarat

| Kebutuhan     | Versi   | dippedakai di |
| -------------- | ------- | ------------- |
| PHP            | 8.2+    | server #2     |
| Composer       | 2.x     | server #2     |
| Node.js        | 20+     | server #1, #2 |
| MySQL          | 8.0+    | server #3     |
| nginx          | stable  | server #1, #2 |

Ekstensi PHP wajib: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`,
`dom`, `curl`, `gd`, `zip`, `fileinfo`. `gd` dipakai dompdf untuk merender
gambar ke PDF.

> **PHP 8.5 masih bisa dipakai.** Aplikasi ini sudah diuji di PHP 8.5.10.
> Kalau muncul peringatan `deprecated` saat `php artisan test`, itu datang dari
> vendor, bukan dari kode kita — lihat [docs/KNOWN_ISSUES.md](KNOWN_ISSUES.md).

Gunakan user khusus (bukan `root`) untuk PHP-FPM:

```bash
sudo adduser --system --group --home /var/www/pf-fpm pf-fpm
sudo chown -R pf-fpm:pf-fpm /var/www/backend
```

---

## Server #3 — Database

MySQL tidak perlu dibuka ke user. Batasi hanya ke jaringan internal.

### 1. Instalasi dan konfigurasi

```bash
sudo apt install mysql-server
```

Edit `/etc/mysql/mysql.conf.d/mysqld.cnf` supaya hanya mendengar di jaringan
internal dan **menolak** koneksi dari luar:

```ini
[mysqld]
bind-address            = 10.0.0.3
mysqlx-bind-address     = 10.0.0.3

# Hanya server #2 boleh melakukan koneksi.
skip-name-resolve
```

```bash
sudo systemctl restart mysql
```

### 2. Buat database dan user khusus

```sql
CREATE DATABASE car_rent
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- User HANYA untuk backend. Jangan pakai root.
CREATE USER 'pf_app'@'10.0.0.2' IDENTIFIED BY 'GANTI_DENGAN_SANDI_KUAT';
GRANT ALL PRIVILEGES ON car_rent.* TO 'pf_app'@'10.0.0.2';
FLUSH PRIVILEGES;
```

Kalau `php artisan test` juga dijalankan di server #2, tambahkan user
terpisah untuk database testing (lihat [Verifikasi](#verifikasi)):

```sql
CREATE DATABASE car_rent_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pf_test'@'10.0.0.2' IDENTIFIED BY 'GANTI_SANDI_LAIN';
GRANT ALL PRIVILEGES ON car_rent_testing.* TO 'pf_test'@'10.0.0.2';
```

### 3. Isi data

Cara tercepat dan paling aman: **migrate dari server #2**, bukan meng-import
dump lama. Ada dump warisan di `database/dumps/car_rent_legacy.sql`, tapi isinya
**tidak lengkap** — tidak punya tabel `roles`, `role_permissions`, `surat`,
`template_surats`, dan `invoice_signatures`.

```bash
# Jalankan di server #2, setelah .env terisi (lihat di bawah)
php artisan migrate --force
php artisan db:seed --class=MasterPartSeeder
php artisan db:seed --class=NomorSuratSeeder
```

> **Jangan pernah** menjalankan `php artisan db:seed` tanpa `--class=`.
> `DatabaseSeeder` melakukan TRUNCATE pada 19 tabel. Di database yang sudah
> berisi data asli, itu menghapus semuanya.

### 4. Buat user owner pertama

Tidak ada user yang bisa login sampai satu dibuat:

```bash
php artisan tinker
```

```php
App\Models\User::create([
    'nama'     => 'Admin',
    'email'    => 'admin@contoh.local',
    'password' => bcrypt('GANTI_SANDI_INI'),
    'role'     => 'owner',     // 'owner' selalu punya akses penuh
    'is_active' => true,
]);
```

Keluar dengan `exit`, lalu **ganti password itu lewat UI** setelah login
pertama.

---

## Server #2 — Backend

### 1. Ambil kode dan pasang dependensi

```bash
sudo mkdir -p /var/www && cd /var/www
git clone <URL_REPO> .

cd backend
composer install --no-dev --optimize-autoloader
npm ci && npm run build     # hanya untuk asset Blade & template PDF
```

### 2. File `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Isi **hanya** baris berikut; sisanya biarkan sesuai `.env.example`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://10.0.0.2:8000

# Origin server FRONTEND. Dipakai untuk link QR yang dicetak di PDF invoice.
FRONTEND_URL=http://10.0.0.1:8080

# Akses dari IP address seadanya.
FRONTEND_ORIGINS=*

DB_CONNECTION=mysql
DB_HOST=10.0.0.3
DB_PORT=3306
DB_DATABASE=car_rent
DB_USERNAME=pf_app
DB_PASSWORD=GANTI_DENGAN_SANDI_KUAT

# Kosongkan: percaya semua proxy (lihat catatan di bawah)
TRUSTED_PROXIES=
```

**Tiga baris yang wajib benar:**

| Baris             | Kenapa penting                                                          |
| ----------------- | ----------------------------------------------------------------------- |
| `APP_URL`         | Signed URL file (`/api/files/...`) dibangun relatif terhadap ini         |
| `FRONTEND_URL`    | Link QR yang **dicetak di kertas** dan dipindai orang luar              |
| `FRONTEND_ORIGINS`| Origin yang diizinkan CORS                                             |

> **`TRUSTED_PROXIES` dikosongkan dengan sengaja.** Nginx yangaledar di depan
> backend, jadi `X-Forwarded-For` adalah satu-satunya sumber IP asli. Kalau
> tidak dipercaya, `request()->ip()` selalu `127.0.0.1` — artinya rate limiter
> menganggap **semua user berasal dari satu IP** dan bisa memblokir akses
> bersama. Kalau nanti backend diexpose ke internet, isi IP proxy-nya:
> `TRUSTED_PROXIES=10.0.0.1`

### 3. Izin folder

```bash
chown -R pf-fpm:pf-fpm /var/www/backend/storage /var/www/backend/bootstrap/cache
chmod -R 775 /var/www/backend/storage /var/www/backend/bootstrap/cache
```

Folder `storage/app/public/invoices` harus ada (berisi PDF yang sudah
dibuat):

```bash
mkdir -p /var/www/backend/storage/app/public/invoices
```

### 4. Optimasi produksi

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> **Jangan** memakai `APP_DEBUG=true` di produksi. Pesan error yang lengkap
> membocorkan isi file dan struktur database ke whoever yang memicu error.

### 5. nginx + PHP-FPM

```bash
sudo nano /etc/nginx/sites-available/backend
```

```nginx
server {
    listen 8000;
    server_name 10.0.0.2;

    root /var/www/backend/public;
    index index.php;
    charset utf-8;

    # Backend tidak perlu dibuka lewat browser, tapi tetap perlu
    # menerima POST /api dari server #1.
    # Foto KTP dan PDF invoice bisa berukuran besar.
    client_max_body_size 32M;

    add_header X-Content-Type-Options nosniff always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT   $realpath_root;

        # X-Forwarded-* forwarded oleh Laravel lewat trustProxies
        fastcgi_param HTTP_X_FORWARDED_PROTO $scheme;

        # Generating PDF invoice bisa memakan waktu.
        fastcgi_read_timeout 120;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    # Folder public TIDAK boleh diakses langsung. Semua file lewat
    # endpoint bertanda tangan /api/files/{path}.
    location ~ /\. { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/backend /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## Server #1 — Frontend

Server ini hanya menyajikan file statis. Tidak ada PHP di sini.

> Bagian ini juga yang dirujuk oleh peringatan `vite` saat
> `VITE_API_BASE_URL` kosong.

### 1. Ambil kode dan build

```bash
sudo mkdir -p /var/www && cd /var/www
git clone <URL_REPO> .

cd frontend
npm ci
npm run build
```

Hasil build ada di `frontend/dist/`.

> **`VITE_API_BASE_URL` harus KOSONG saat build produksi.** Kalau diisi, SPA
> memakai URL absolut dan setiap request jadi lintas origin — lebih lambat,
> perlu preflight, dan bergantung pada konfigurasi CORS. Kalau kosong, axios
> memakai path relatif `/api/...` dan nginx di bawah yang meneruskannya.
>
> Kalau `VITE_API_BASE_URL` tidak diisi, Vite **mencetak peringatan** saat
> build. Itu peringatan, bukan error.

Cara paling bersih: set di `.env` server #1 lalu build ulang.

```bash
echo "VITE_API_BASE_URL=" > .env.production
npm run build
```

> **Nilai ini dibakar ke dalam hasil build.** Kalau nanti alamat backend
> berubah, `npm run build` **harus dijalankan ulang**. Mengganti `.env` saja
> tidak berpengaruh.

### 2. nginx

```bash
sudo nano /etc/nginx/sites-available/frontend
```

```nginx
server {
    listen 8080;
    server_name 10.0.0.1;

    root /var/www/frontend/dist;
    index index.html;

    # Asset ber-hash (mis. app-Bx7f9d.js) boleh di-cache lama.
    location /assets/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # index.html TIDAK boleh di-cache: di dalamnya ada referensi ke
    # asset ber-hash. Kalau user menahan file lama, asset baru tidak
    # pernah dimuat dan aplikasi terlihat rusak.
    location = /index.html {
        add_header Cache-Control "no-cache, no-store, must-revalidate";
    }

    # Routing SPA: semua path lain dilayani index.html, lalu vue-router
    # yang menentukannya di sisi browser. Tanpa baris ini, refresh di
    # /armada/123 akan 404.
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Teruskan API ke server #2. Browser tetap melihat satu origin,
    # jadi CORS tidak pernah tersentuh dan IP backend tidak terekspos.
    #
    # PENTING: alamat di sini adalah IP server BACKEND, bukan 127.0.0.1.
    # Nginx ini jalan di server #1; 127.0.0.1 akan menunjuk ke server #1
    # sendiri dan hasilnya 502.
    location /api {
        proxy_pass         http://10.0.0.2:8000;
        proxy_http_version 1.1;

        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        client_max_body_size 32M;
        # Generating PDF invoice bisa memakan waktu.
        proxy_read_timeout 120;
        proxy_send_timeout 120;
    }

    location ~ /\. { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/frontend /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

Buka port 8080 di firewall **hanya** untuk jaringan yang memang butuh:

```bash
sudo ufw allow from 10.0.0.0/24 to any port 8080 proto tcp
```

---

## Verifikasi

### Dari komputer Anda (client)

```bash
# 1. Halaman depan terbuka (status 200)
curl -I http://10.0.0.1:8080/

# 2. API hidup lewat proxy nginx
curl http://10.0.0.1:8080/api/public/watermark -o /dev/null -w '%{http_code}\n'
# harus: 200

# 3. Route SPA dilayani index.html, bukan 404 nginx
curl -o /dev/null -w '%{http_code}\n' http://10.0.0.1:8080/armada
# harus: 200
```

### Cek dari browser

| Yang diuji                                  | Hasil yang diharapkan                        |
| ------------------------------------------- | ------------------------------------------- |
| Buka `http://10.0.0.1:8080`                 | Dialihkan ke halaman login                  |
| Login dengan user owner                     | Masuk, menu lengkap terlihat                |
| Buka `http://10.0.0.1:8080/armada` langsung | Halaman tampil — **bukan** 404             |
| Refresh di halaman mana pun                 | Halaman tetap terbuka                        |
| Buka tab DevTools → Network                 | Tidak ada error CORS di console             |
| Daftar Armada → foto kendaraan              | Gambar muncul (bukan kotak rusak)           |

### Dari server #2

```bash
cd /var/www/backend

php artisan about                     # APP_ENV harus production
php artisan route:list --path=api | wc -l    # harus 95
php artisan migrate:status            # tidak boleh ada Pending
```

### Kalau menjalankan test

Test memakai `RefreshDatabase`, yang akan **DROP** database yang ditunjuk.
Pastikan tidak pernah mengarah ke `car_rent`.

```bash
cp .env .env.testing
```

```env
APP_ENV=testing
DB_HOST=10.0.0.3
DB_DATABASE=car_rent_testing
DB_USERNAME=pf_test
DB_PASSWORD=GANTI_SANDI_LAIN
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

```bash
php artisan test
# yang penting baris "Tests:" tidak mengandung failed atau error
```

---

## Pembaruan Berkas

### Hanya frontend berubah

```bash
cd /var/www/frontend
git pull
npm ci
npm run build
# selesai — tidak perlu restart apa pun
```

### Hanya backend berubah

```bash
cd /var/www/backend
git pull
composer install --no-dev --optimize-autoloader

# Kalau ada migration baru:
php artisan migrate --force

#WAJIB kalau .env atau kode config berubah:
php artisan config:cache

sudo systemctl reload php8.2-fpm
```

> **`php artisan config:cache` hanya membaca `.env` saat cache dibuat.**
> Kalau `.env` berubah dan cache tidak dibuat ulang, perubahan itu **tidak
> berlaku** — aplikasi diam-diam memakai nilai lama. Ini sumber bug
> "kenapa env ku sudah diganti tapi tidak berubah".

### Keduanya berubah

Kerjakan backend lebih dulu, baru frontend. Setelah backend selesai, build
frontend — **Vite membaca env-nya sendiri saat build**, jadi tidak ada
ketergantungan urutan lain.

---

## Cadangan

### Database (cron harian)

Buat `/usr/local/bin/backup-pf.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

TANGGAL=$(date +%F)
TUJUAN=/var/backups/precision-fleet
mkdir -p "$TUJUAN"

mysqldump \
  --host=10.0.0.3 --user=pf_app --password='GANTI_SANDI' \
  --single-transaction --quick --routines --triggers \
  car_rent | gzip > "$TUJUAN/car_rent_$TANGGAL.sql.gz"

# Simpan 30 hari terakhir
find "$TUJUAN" -name 'car_rent_*.sql.gz' -mtime +30 -delete

# Berkas upload (foto KTP, tanda tangan, PDF invoice)
tar czf "$TUJUAN/storage_$TANGGAL.tar.gz" -C /var/www/backend storage/app

find "$TUJUAN" -name 'storage_*.tar.gz' -mtime +30 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-pf.sh
sudo crontab -e
# backup tiap hari jam 02:15
15 2 * * * /usr/local/bin/backup-pf.sh >> /var/log/backup-pf.log 2>&1
```

`--single-transaction` itu penting: tanpa itu, `mysqldump` mengunci tabel
dan memblokir operasional saat jam sibuk.

### Berkas upload

Tidak ada di Git (`.gitignore`), jadi **wajib** dicadangkan terpisah. Isinya
berisi foto KTP penyewa.

---

## Masalah yang Sering Muncul

### Halaman 404 saat di-refresh di `/armada/123`

`try_files` di nginx server #1 belum ada. Tanpa itu, nginx mencari file
`armada/123` dan gagal — padahal `vue-router` yang seharusnya menanganinya di
browser.

```nginx
location / {
    try_files $uri $uri/ /index.html;
}
```

### Asset lama masih tampil setelah deploy

`index.html` ikut ter-cache oleh browser atau proxy. Halaman dirujuk oleh
`index.html` harus `no-cache`.

### Every CORS error di console

Hampir selalu berarti `VITE_API_BASE_URL` **terisi** saat build, padahal
`/api` sudah di-proxy nginx. Build ulang dengan nilainya kosong.

### Semua user terkunci setelah beberapa login

`TRUSTED_PROXIES` tidak dipercaya, jadi rate limiter melihat semua request
berasal dari `127.0.0.1`. Throttle login adalah `10,1` (10× per menit),
jadi 10 login dari user berbeda bisa saling memblokir. Biarkan kosong untuk
jaringan internal, atau isi IP proxy dengan benar.

### `storage:link` tidak boleh dibuat

Dulu symlink `public/storage` dipakai untuk menyajikan file. Sekarang **tidak
lagi** — semua file lewat endpoint bertanda tangan
`/api/files/{path}` yang punya masa berlaku pendek dan memeriksa permission
`file.view`. Symlink itu akan membocorkan foto KTP tanpa autentikasi.

Kalau `storage:link` terlanjur dibuat, hapus:

```bash
rm /var/www/backend/public/storage
```

### Login lambat sekali, atau timeout

`php artisan config:cache` belum dijalankan, jadi `.env` dibaca tiap request.
Atau `storage/` dan `bootstrap/cache/` salah izin sehingga tidak bisa ditulis.

### Pesan error berbahasa Inggris padahal UI Indonesia

Itu **normal**. `APP_LOCALE` sengaja `en` supaya pesan validasi tidak tampil
sebagai kunci mentah (`validation.email`). UI berbahasa Indonesia dihitung di
frontend. Penjelasan lengkap di [docs/KNOWN_ISSUES.md](KNOWN_ISSUES.md) bagian 4.

### Kamera QR tidak bisa dipakai di halaman pemindai

Browser hanya mengizinkan akses kamera di **HTTPS** atau `localhost`.
Lewat HTTP dengan IP address, `getUserMedia` ditolak browser — ini aturan
browser, bukan bug aplikasi. **Pindai dari berkas** tetap berfungsi di halaman
yang sama, jadi pemindai tetap berguna tanpa kamera. Butuh HTTPS? Lihat bagian
[HTTPS](#https-opsional) di bawah.

---

## HTTPS (opsional)

Aplikasi **dirancang untuk jalan tanpa HTTPS** dan tidak memerlukannya:

- Autentikasi memakai **Sanctum personal access token** lewat header
  `Authorization`, bukan cookie. Cookie lintas origin butuh
  `SameSite=None` + `Secure`, dan `Secure` hanya berlaku di HTTPS.
- File disajikan lewat **signed URL** berkedaluwarsa, bukan symlink publik.
- `FRONTEND_ORIGINS=*` aman justru karena kredensial tidak pernah dikirim
  otomatis lintas origin.

Kalau nanti sudah punya domain dan ingin HTTPS, dua hal yang **wajib**
diperiksa ulang:

1. Persempit `FRONTEND_ORIGINS` ke domain itu saja. Jangan tetap `*`.
2. Isi `TRUSTED_PROXIES` dengan IP proxy. Kalau backend diexpose langsung ke
   internet tanpa proxy, header `X-Forwarded-For` bisa dipalsukan user untuk
  _archive_menghindari rate limit.

Contoh membuat sertifikat dengan certbot:

```bash
sudo certbot --nginx -d app.contoh.local
# certbot mengubah konfigurasi nginx otomatis
```

Setelah HTTPS aktif, `http://` diarahkan ke `https://` supaya token yang
sudah tersimpan di `localStorage` tidak hilang saat origin berubah.
