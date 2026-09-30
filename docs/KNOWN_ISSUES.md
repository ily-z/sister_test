# Known Issues — Precision Fleet

Catatan teknis yang sengaja tidak ikut dikerjakan saat pemisahan 3-server.
Setiap bagian menjelaskan **gejalanya, kenapa terjadi, dan apa akibatnya kalau
dibiarkan**, supaya tidak salah dibaca sebagai hal yang belum selesai saja.

Semua ini adalah warisan dari versi monolit dan **sengaja tidak diubah** saat pemisahan
server, karena mengubahnya berarti mengubah hasil yang sudah dilihat pengguna.
Kalau sebuah item dianggap perlu diperbaiki, ada penjelasan singkat di bagian
"Kalau Diperbaiki".

---

## 1. `cars.kode_mobil` tidak pernah ada di skema

**Gejala.** Setiap `kode_mobil` pada invoice bernilai `XXX`.

**Kenapa.** Kolom `kode_mobil` ada di tabel `invoices` (lihat migration
`2026_05_03_133606_invoices.php`), **bukan** di tabel `cars`. Versi Blade
menerima `kode_mobil` dari form Armada dan mengirimkannya ke `Car::create()`,
sehingga berakhir dengan SQL error *"Unknown column 'kode_mobil'"*.

Saat pemisahan API, penanganannya dihapus dari `store()` dan `update()` di
`Api/ArmadaController` supaya Armada bisa disimpan tanpa error. Efek sampingnya,
`InvoiceController` yang membaca `$car->kode_mobil` selalu dapat `null` dan
jatuh ke fallback `'XXX'`.

**Akibat.** Kolom `kode_mobil` di PDF dan halaman Arsip tidak informatif.
Nomor seri invoice sendiri tetap benar, jadi tidak ada data yang hilang —
hanya satu field yang tidak terisi.

**Kalau diperbaiki.** Tambah kolom `kode_mobil` di `cars` (3 huruf, unik per
armada), isi dari kode tiga huruf mobil tersebut, lalu hapus fallback `'XXX'` di
`Api/InvoiceController` dan `InvoiceController`.

---

## 2. Angka Analytics dan sebagian Dashboard masih data dummy

**Gejala.** Halaman Analytics menampilkan pendapatan Rp 125.000.000, 156
booking, dan grafik per bulan yang isinya tetap.

**Kenapa.** `Api/AnalyticsController::index()` mengembalikan angka yang ditulis
langsung di dalam method — identik dengan versi Blade-nya. `getAnalyticsData()`
di `Api/DashboardController` punya masalah serupa.

**Akibat.** Angka-angka ini tidak berasal dari database. Untuk menghindari
hindari menyesatkan, frontend **menampilkan spanduk peringatan** di halaman
Analytics, dan Dashboard **sengaja tidak merender** field pendapatan sama sekali
(meski backend mengirimkannya). Halaman lain yang benar-benar punya angka —
Pesanan, Keuangan, Laba Rugi — dihitung dari query asli dan tidak terpengaruh.

**Kalau diperbaiki.** Ganti return literal dengan agregasi: `SUM` untuk
pendapatan, `COUNT` untuk booking, dan rasio hari terpakai untuk utilisasi.
Tidak perlu perubahan di frontend karena bentuk datanya sudah sama.

---

## 3. Dropdown notifikasi di Topbar dihapus

**Gejala.** Tidak ada menu lonceng notifikasi di navigasi atas.

**Kenapa.** Di versi Blade, dropdown itu berisi data contoh yang dikeraskan.
Tidak ada endpoint, tabel, atau aturan yang menentukan kapan sebuah notifikasi
dibuat — jadi isinya benar-benar fiktif.

**Akibat.** Tidak ada fitur yang hilang, karena tidak ada yang bisa terjadi
sebenarnya. Yang lebih berbahaya justru kalau dibiarkan: operator melihat
pengingat servis fiktif dan mulai menelusuri, atau menganggap sistemnya sudah
memberi tahu padahal tidak ada yang memantau.

**Kalau diperbaiki.** Fitur baru: endpoint `/api/notifications`, tabelnya
sendiri, dan aturan kapan notifikasi dibuat (mis. servis jatuh tempo 500 km).

---

## 4. `APP_LOCALE` sengaja `en`; tanggal Indonesia dihitung di frontend

**Gejala.** Nama bulan dan hari di tampilan berbahasa Indonesia, padahal
`APP_LOCALE=en`.

**Kenapa.** Ini punya sebab yang perlu dijelaskan dengan benar, karena
penjelasan yang sering beredar ("Carbon gagal memuat locale `id`") **tidak
benar** — Carbon 3.13 memuat locale `id` dengan benar:

```php
Carbon::parse('2026-10-01')->translatedFormat('d F Y');  // "01 Oktober 2026"
Carbon::parse('-3 days')->diffForHumans();                // "3 hari yang lalu"
```

Masalahnya ada di **Laravel**, bukan Carbon. Paket ini hanya menyediakan satu
set terjemahan, yaitu `en`, dan proyek ini tidak punya folder `lang/` sama sekali.
Akibatnya, kalau locale dan fallback sama-sama `id`, setiap pesan validasi
tampil sebagai **kunci mentah** — bukan bahasa Inggris, dan bukan bahasa
Indonesia:

```
APP_LOCALE=en  APP_FALLBACK_LOCALE=en  →  "The email field must be a valid email address."
APP_LOCALE=id  APP_FALLBACK_LOCALE=en  →  "The email field must be a valid email address."
APP_LOCALE=id  APP_FALLBACK_LOCALE=id  →  "validation.email"     ← rusak
```

Baris ketiga adalah jebakan yang harus dihindari: pesan error berbahasa
Indonesia yang justru kita inginkan akan terlihat seperti galat sistem. Itu
jauh lebih membingungkan daripada bahasa Inggris.

**Akibat.** Nama bulan/hari Indonesia ditulis tangan di
`frontend/src/lib/format.js`. `monthLabel` yang dikirim backend (hasil
`translatedFormat()`) berbahasa Inggris dan **tidak dipakai** frontend — label
dihitung ulang dari string `YYYY-MM` supaya konsisten dengan halaman lain.

**Kalau diperbaiki.** Terbitkan terjemahan lewat
`php artisan lang:publish`, isi `lang/id/*.php`, baru set
`APP_LOCALE=id`. Setelah itu `format.js` masih boleh tetap dipakai sebagai
satu-satunya sumber label agar tampilan tidak berbeda antar halaman.

---

## 5. `signature_url` memakai URL bertanda tangan, bukan symlink

**Gejala.** Tidak ada yang salah di layar Arsip — ini sudah diperbaiki.

**Kenapa dulunya rusak.** `InvoiceSignature::getSignatureUrlAttribute()`
sekarang memakai `App\Support\FileUrl::public()`, bukan lagi
`asset('storage/...')`. Versi lama bergantung pada symlink `public/storage`,
yang (a) tidak boleh ada karena folder `public/` backend tidak di-expose ke
browser, (b) tidak ikut ter-deploy karena ada di `.gitignore`, dan (c) pernah
menunjuk ke path laptop sehingga langsung rusak begitu dipindah ke server.
Hasilnya `<img>` dengan URL yang 404.

Atribut ini masih dipakai oleh beberapa view Blade yang belum dihapus
(`invoices/print`, `surat-menyurat/arsip`, `components/invoice-sign-button`),
jadi bentuknya harus tetap berupa string — hanya sumbernya yang diganti.
Berkas yang tidak ada menghasilkan string kosong, bukan URL rusak.

**Kalau View Blade dihapus.** Atribut ini boleh ikut dihapus begitu
`routes/web.php` (94 route) dan view lamanya dibersihkan.

---

## 6. Dua aset gambar tidak ada di repositori

**Gejala.** Gambar diagram kendaraan (kolom "Kendaraan" di halaman Serah
Terima) dan logo pada halaman verifikasi invoice publik tidak muncul.

**Kenapa.** Dua berkas berikut tidak pernah masuk Git:

```
backend/storage/app/public/images/car-template.jpg
backend/storage/app/public/img/logo.png
```

Yang dirujuk di `resources/views/components/serah-terima.blade.php` dan
`Api/PublicInvoiceVerificationController`. Keduanya berada di `storage/`, yang
isinya diabaikan oleh `.gitignore`.

**Akibat.** Dua komponen tampil tanpa gambar. Fungsional tetap berjalan; hanya
tampilannya yang kosong.

**Kalau diperbaiki.** Taruh kedua berkas di repositori (atau ambil dari
instalasi lama), lalu jalankan ulang `php artisan storage:link` di server
backend.

---

## 7. `service.delete` ada di tabel permission tapi tidak dipakai route

**Gejala.** Ada permission "Hapus" untuk Bengkel & Servis di halaman
pengaturan, tapi tidak ada endpoint yang memeriksanya.

**Kenapa.** `service.delete` dibuat saat permission diperluas, tapi tidak ada
route hapus servis di `routes/api.php` — Bengkel dihapus lewat alur lain.

**Akibat.** Owner dan admin melihat tombol "Hapus" yang tidak melakukan apa
apa. Tidak ada risiko keamanan, hanya tombol yang tidak berguna.

**Kalau diperbaiki.** Tambahkan endpoint hapus servis dengan
`permission:service.delete`, atau keluarkan slug itu dari
`Api/PermissionController::permissionGroups()`.

---

## 8. `manage_users` tidak pernah ada di tabel permission

**Gejala.** Tidak ada, karena sudah dibersihkan — dicatat agar tidak
dimasukkan lagi.

**Kenapa.** `UserPolicy` dulu mengizinkan jalur alternatif lewat
`manage_users`, padahal slug itu tidak pernah ada di tabel `permissions`.
Pencantumannya tidak menambah akses apa pun — owner tetap lolos lewat
`hasPermission()` yang selalu `true` untuknya.

**Akibat.** Tidak ada. Frontend memakai slug apa adanya (`users.edit`,
`users.delete`), jadi tidak perlu tahu bahwa pernah ada nama lain.

---

## 9. Halaman Arsip tidak menampilkan nominal dan status pembayaran

**Gejala.** Kolom "Nominal" dan "Status" tidak ada di tabel Arsip.

**Kenapa.** Tabel `invoices` **tidak punya** kolom `total` maupun
`status_pembayaran` — hanya `id, booking_id, nomor_seri, kode_mobil,
qr_code_string, file_pdf_path, pdf_generated_at, document_hash, issued_at,
created_at, updated_at, surat_id, jenis_invoice, service_log_id`.

Angka pembayaran ada di `booking_payments`, dan itu tabel lain dengan
relasinya sendiri.

**Akibat.** Arsip menjalankan dokumen, bukan pembayarannya. Untuk angka
pembayaran, buka menu Pemesanan atau Keuangan.

**Kalau diperbaiki.** Tambahkan relasi `payment` di `Api/SuratMenyuratController::arsip()`
dan render nominal + status dari sana.

---

## 10. Drift antara dua bentuk data user

**Gejala.** Tidak terlihat oleh pengguna; dicatat karena mudah membuat bug
kalau tidak diketahui.

**Kenapa.** `UserController` sengaja tidak seragam:

| Endpoint   | `role` berisi      | fields tambahan              |
|------------|--------------------|------------------------------|
| `index`    | objek `Role`       | —                            |
| `edit`     | string slug        | `is_owner`, `role_name`, `permissions` |
| `me`       | string slug        | `is_owner`, `permissions`    |

**Akibat.** `index` cukup untuk tabel daftar (nama role sudah ada di dalam
objek), sedangkan `edit` butuh daftar permission untuk form. Dua bentuk ini
sengaja, bukan bug.

**Kalau dirapikan.** Kembalikan `role` dari `index` sebagai string juga, tapi
periksa dulu semua tempat yang membaca `user.role.name`.

---

## Yang BUKAN known issue (supaya tidak salah dibaca)

Beberapa hal yang sering dikira rusak, padahal memang disengaja:

- **Build Vite memunculkan peringatan `Error when using sourcemap`** untuk
  `PengeluaranKantorView.vue`. Itu peringatan, bukan kegagalan; build tetap
  menghasilkan `dist/`.
- **`php artisan test` mencetak banyak "deprecated".** Datang dari
  `PDO::MYSQL_ATTR_SSL_CA` di dalam vendor Laravel 11 saat dipakai di PHP 8.4+.
  Yang penting baris `Tests:` tidak mengandung `failed` atau `error`.
- **`.gitignore` mengabaikan `frontend/dist/` dan `database/dumps/*.sql`.**
  memang begitu: `dist` dibangun di server, dan dump berisi data pelanggan.
