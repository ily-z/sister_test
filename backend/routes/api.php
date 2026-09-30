<?php

/*
|--------------------------------------------------------------------------
| API Routes -- Precision Fleet
|--------------------------------------------------------------------------
|
| Prefix: /api
|
| Frontend (Vue SPA) berada di server terpisah, jadi semua request ke
| sini lintas origin. Yang membuatnya jalan:
|   1. config/cors.php mengizinkan origin manapun.
|   2. Autentikasi memakai Sanctum BEARER TOKEN (header Authorization),
|      bukan cookie -- jadi tidak butuh SameSite/None dan tidak butuh
|      HTTPS. Ini syarat agar aplikasi bisa diakses dari IP address
|      seadanya.
|
| Otorisasi tetap di backend: middleware `permission:` di bawah adalah
| satu-satunya penentu hak akses. Permission yang dikirim ke frontend
| lewat /api/auth/me hanya untuk Enhance UX (sembunyikan menu).
|
*/

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\PublicInvoiceVerificationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ArmadaController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\KeuanganController;
use App\Http\Controllers\Api\SuratMenyuratController;
use App\Http\Controllers\Api\TemplateSuratController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public -- tanpa auth
|--------------------------------------------------------------------------
|
| Dipakai oleh QR di PDF invoice. Seseorang yang memindai QR invoice di
| kertas (kuria, tender, CHECK karos) belum tentu punya akun sistem, jadi
| endpoint ini harus terbuka.
|
*/

Route::prefix('public')->name('api.public.')->group(function () {
    Route::get('invoices/verify/{token}', [PublicInvoiceVerificationController::class, 'verify'])
        ->name('invoices.verify');

    // Watermark/logo untuk pratinjau dokumen.
    Route::get('watermark', [PublicInvoiceVerificationController::class, 'watermark'])
        ->name('watermark');
});

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->name('api.auth.')->group(function () {
    // Rate limit ketat: endpoint ini yang paling menarik untuk di-brute-force.
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::post('password', [AuthController::class, 'updatePassword'])->name('password.update');
    });
});

/*
|--------------------------------------------------------------------------
| File
|--------------------------------------------------------------------------
|
| Signed URL dipakai supaya tag <img src> di frontend bisa memuat file
| tanpa perlu header Authorization. URL dibuat di server lewat
| App\Support\FileUrl, berlaku 10 menit (config filesystems.file_url_ttl).
|
*/

Route::middleware(['auth:sanctum', 'signed', 'permission:file.view'])
    ->get('files/{path}', [FileController::class, 'show'])
    ->where('path', '.*')
    ->name('api.files.show');

/*
|--------------------------------------------------------------------------
| Terlindungi -- butuh bearer token
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // ── Dashboard & laporan ────────────────────────────────────────────
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('analytics', [AnalyticsController::class, 'index'])
        ->middleware('permission:analytics.view')->name('analytics');
    Route::get('activity', [ActivityController::class, 'index'])
        ->middleware('permission:analytics.view')->name('activity');

    // ── Penyewaan / booking ────────────────────────────────────────────
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->middleware('permission:bookings.view')->name('index');
        Route::get('create', [BookingController::class, 'create'])->middleware('permission:bookings.create')->name('create');
        Route::post('/', [BookingController::class, 'store'])->middleware('permission:bookings.create')->name('store');
        Route::get('calendar', [BookingController::class, 'calendar'])->middleware('permission:bookings.view')->name('calendar');

        Route::get('{booking}', [BookingController::class, 'show'])->middleware('permission:bookings.view')->name('show');
        Route::get('{booking}/edit', [BookingController::class, 'edit'])->middleware('permission:bookings.edit')->name('edit');
        Route::put('{booking}', [BookingController::class, 'update'])->middleware('permission:bookings.edit')->name('update');
        Route::delete('{booking}', [BookingController::class, 'destroy'])->middleware('permission:bookings.delete')->name('destroy');

        Route::post('{booking}/addon', [BookingController::class, 'storeAddon'])->middleware('permission:bookings.edit')->name('addon.store');
        Route::delete('{booking}/addon/{addonId}', [BookingController::class, 'destroyAddon'])->middleware('permission:bookings.edit')->name('addon.destroy');
        Route::post('{booking}/update-odometer', [BookingController::class, 'updateOdometer'])->middleware('permission:bookings.edit')->name('odometer.update');
        Route::post('{booking}/lock', [BookingController::class, 'lockBooking'])->middleware('permission:bookings.edit')->name('lock');
        Route::post('{booking}/unlock', [BookingController::class, 'unlockBooking'])->middleware('permission:bookings.edit')->name('unlock');
        Route::post('{booking}/update-status', [BookingController::class, 'updateStatus'])->middleware('permission:bookings.edit')->name('status.update');
        Route::post('{booking}/refund', [BookingController::class, 'processRefund'])->middleware('permission:finance.edit')->name('refund');
    });

    // ── Armada ─────────────────────────────────────────────────────────
    Route::prefix('armada')->name('armada.')->group(function () {
        Route::get('/', [ArmadaController::class, 'index'])->middleware('permission:fleet.view')->name('index');
        Route::get('create', [ArmadaController::class, 'create'])->middleware('permission:fleet.create')->name('create');
        Route::post('dokumen-phk/{surat}', [ArmadaController::class, 'dokumenPhk'])->middleware('permission:fleet.view')->name('dokumen-phk');
        Route::post('create-dokumen', [ArmadaController::class, 'createDokumen'])->middleware('permission:fleet.create')->name('create-dokumen');
        Route::post('/', [ArmadaController::class, 'store'])->middleware('permission:fleet.create')->name('store');

        // Bengkel & sale-template harus SEBELUM {armada} supaya tidak
        // tertangkap oleh route parameter.
        Route::get('sale-template', [ArmadaController::class, 'saleTemplate'])->middleware('permission:fleet.view')->name('sale-template');
        Route::post('sale-template/save', [ArmadaController::class, 'saveSaleTemplate'])->middleware('permission:fleet.edit')->name('sale-template.save');
        Route::get('bengkel', [ArmadaController::class, 'bengkel'])->middleware('permission:service.view')->name('bengkel');

        Route::get('{armada}', [ArmadaController::class, 'show'])->whereNumber('armada')->middleware('permission:fleet.view')->name('show');
        Route::get('{armada}/edit', [ArmadaController::class, 'edit'])->whereNumber('armada')->middleware('permission:fleet.edit')->name('edit');
        Route::put('{armada}', [ArmadaController::class, 'update'])->whereNumber('armada')->middleware('permission:fleet.edit')->name('update');
        Route::delete('{armada}', [ArmadaController::class, 'destroy'])->whereNumber('armada')->middleware('permission:fleet.delete')->name('destroy');
        Route::post('{armada}/km', [ArmadaController::class, 'updateKm'])->whereNumber('armada')->middleware('permission:fleet.edit')->name('km.update');

        Route::post('{armada}/part', [ArmadaController::class, 'storePart'])->whereNumber('armada')->middleware('permission:fleet.edit')->name('part.store');
        Route::get('{armada}/bengkel/create', [ArmadaController::class, 'createBengkel'])->whereNumber('armada')->middleware('permission:service.create')->name('bengkel.create');
        Route::post('{armada}/bengkel/store', [ArmadaController::class, 'storeBengkel'])->whereNumber('armada')->middleware('permission:service.create')->name('bengkel.store');
        Route::post('{armada}/send-to-workshop', [ArmadaController::class, 'sendToWorkshop'])->whereNumber('armada')->middleware('permission:service.edit')->name('send-to-workshop');
    });

    // Komponen kendaraan (di luar prefix armada agar path tidak ambigu)
    Route::delete('armada/part/{part}', [ArmadaController::class, 'destroyPart'])
        ->middleware('permission:fleet.delete')->name('armada.part.destroy');
    Route::put('armada/part/{part}', [ArmadaController::class, 'updatePart'])
        ->middleware('permission:fleet.edit')->name('armada.part.update');

    // ── Keuangan ───────────────────────────────────────────────────────
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::get('/', [KeuanganController::class, 'index'])->middleware('permission:finance.view')->name('index');
        Route::get('data', [KeuanganController::class, 'data'])->middleware('permission:finance.view')->name('data');
        Route::get('per-mobil', [KeuanganController::class, 'perMobil'])->middleware('permission:finance.view')->name('per-mobil');
        Route::get('per-mobil/data', [KeuanganController::class, 'perMobilData'])->middleware('permission:finance.view')->name('per-mobil.data');
        Route::get('pengeluaran-kantor', [KeuanganController::class, 'pengeluaranKantor'])->middleware('permission:finance.view')->name('pengeluaran-kantor');
        Route::get('pengeluaran-kantor/data', [KeuanganController::class, 'pengeluaranKantorData'])->middleware('permission:finance.view')->name('pengeluaran-kantor.data');
        Route::get('pengeluaran-kantor/create', [KeuanganController::class, 'createPengeluaranKantor'])->middleware('permission:finance.create')->name('pengeluaran-kantor.create');
        Route::post('pengeluaran-kantor', [KeuanganController::class, 'storePengeluaranKantor'])->middleware('permission:finance.create')->name('pengeluaran-kantor.store');
        Route::get('pengeluaran-kantor/{transaction}/edit', [KeuanganController::class, 'editPengeluaranKantor'])->middleware('permission:finance.edit')->name('pengeluaran-kantor.edit');
        Route::put('pengeluaran-kantor/{transaction}', [KeuanganController::class, 'updatePengeluaranKantor'])->middleware('permission:finance.edit')->name('pengeluaran-kantor.update');
        Route::delete('pengeluaran-kantor/{transaction}', [KeuanganController::class, 'destroyPengeluaranKantor'])->middleware('permission:finance.delete')->name('pengeluaran-kantor.destroy');
    });

    // ── Surat menyurat ─────────────────────────────────────────────────
    Route::prefix('surat-menyurat')->name('surat-menyurat.')->group(function () {
        Route::get('/', [SuratMenyuratController::class, 'index'])->middleware('permission:bookings.view')->name('index');
        Route::get('create', [SuratMenyuratController::class, 'create'])->middleware('permission:bookings.edit')->name('create');
        Route::post('/', [SuratMenyuratController::class, 'store'])->middleware('permission:bookings.edit')->name('store');
        Route::post('store-sub', [SuratMenyuratController::class, 'storeSub'])->middleware('permission:bookings.edit')->name('store-sub');
        Route::delete('bulk-delete', [SuratMenyuratController::class, 'bulkDestroy'])->middleware('permission:bookings.delete')->name('bulk-delete');
        Route::get('arsip', [SuratMenyuratController::class, 'arsip'])->middleware('permission:bookings.view')->name('arsip');
        Route::post('balik-nama/{car}', [SuratMenyuratController::class, 'balikNama'])->middleware('permission:fleet.edit')->name('balik-nama');
        Route::post('serah-terima/{booking}', [SuratMenyuratController::class, 'serahTerima'])->middleware('permission:bookings.edit')->name('serah-terima');
    });

    // ── Template surat ────────────────────────────────────────────────
    Route::prefix('surat-menyurat/template')->name('surat-menyurat.template.')->group(function () {
        Route::get('/', [TemplateSuratController::class, 'index'])->middleware('permission:bookings.view')->name('index');
        Route::get('create', [TemplateSuratController::class, 'create'])->middleware('permission:bookings.edit')->name('create');
        Route::post('/', [TemplateSuratController::class, 'store'])->middleware('permission:bookings.edit')->name('store');
        Route::get('{template}/edit', [TemplateSuratController::class, 'edit'])->middleware('permission:bookings.edit')->name('edit');
        Route::put('{template}', [TemplateSuratController::class, 'update'])->middleware('permission:bookings.edit')->name('update');
        Route::delete('{template}', [TemplateSuratController::class, 'destroy'])->middleware('permission:bookings.delete')->name('destroy');
        Route::get('{template}/print', [TemplateSuratController::class, 'print'])->middleware('permission:bookings.view')->name('print');
    });

    // ── Invoice ───────────────────────────────────────────────────────
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::post('generate/{booking}/{jenis}', [InvoiceController::class, 'generate'])->middleware('permission:finance.create')->name('generate');
        Route::post('bengkel/{serviceLog}', [InvoiceController::class, 'bengkel'])->middleware('permission:finance.create')->name('bengkel');
        Route::get('{invoice}/print', [InvoiceController::class, 'print'])->middleware('permission:finance.view')->name('print');
        Route::get('{invoice}/print-bengkel', [InvoiceController::class, 'printBengkel'])->middleware('permission:finance.view')->name('print-bengkel');
        Route::post('{invoice}/signature', [InvoiceController::class, 'storeSignature'])->middleware('permission:finance.create')->name('signature.store');
        Route::delete('{invoice}/signature/{role}', [InvoiceController::class, 'destroySignature'])->middleware('permission:finance.create')->name('signature.destroy');
        Route::get('{invoice}/download', [InvoiceController::class, 'download'])->middleware('permission:finance.view')->name('download');
        Route::get('{invoice}/preview', [InvoiceController::class, 'preview'])->middleware('permission:finance.view')->name('preview');

        // QR sebagai SVG, bukan JSON. Dipakai <img src> langsung di halaman
        // cetak invoice. Lihat InvoiceController::qr() untuk alasannya.
        Route::get('{invoice}/qr', [InvoiceController::class, 'qr'])->middleware('permission:finance.view')->name('qr');
    });

    // ── User ──────────────────────────────────────────────────────────
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('{user}', [UserController::class, 'update'])->name('update');
        Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::patch('{user}/role', [UserController::class, 'updateRole'])->name('role.update');
    });

    // ── Permission & role ─────────────────────────────────────────────
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->middleware('permission:manage_permissions')->name('index');
        Route::get('data', [PermissionController::class, 'data'])->middleware('permission:manage_permissions')->name('data');
        Route::put('/', [PermissionController::class, 'update'])->middleware('permission:manage_permissions')->name('update');
        Route::post('roles', [PermissionController::class, 'storeRole'])->middleware('permission:manage_permissions')->name('roles.store');
        Route::patch('roles/{role}', [PermissionController::class, 'updateRole'])->middleware('permission:manage_permissions')->name('roles.update');
        Route::delete('roles/{role}', [PermissionController::class, 'destroyRole'])->middleware('permission:manage_permissions')->name('roles.destroy');
        Route::patch('roles/{role}/color', [PermissionController::class, 'updateColor'])->middleware('permission:manage_permissions')->name('roles.color');
        Route::post('reorder', [PermissionController::class, 'reorder'])->middleware('permission:manage_permissions')->name('reorder');
    });

});
