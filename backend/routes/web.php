<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ArmadaController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\SuratMenyuratController;
use App\Http\Controllers\TemplateSuratController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PermissionController;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'index'])->name('login');
    Route::post('/', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');    Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('permission:analytics.view')->name('admin.analytics');
    Route::get('/activity', [ActivityController::class, 'index'])->middleware('permission:analytics.view')->name('admin.activity');

    Route::get('/bookings', [BookingController::class, 'index'])->middleware('permission:bookings.view')->name('admin.bookings');
    Route::get('/bookings/create', [BookingController::class, 'create'])->middleware('permission:bookings.create')->name('admin.bookings.create');
    Route::post('/bookings/store', [BookingController::class, 'store'])->middleware('permission:bookings.create')->name('admin.bookings.store');
    Route::get('/bookings/calendar', [BookingController::class, 'calendar'])->middleware('permission:bookings.view')->name('admin.bookings.calendar');
    Route::get('/bookings/{id}/edit', [BookingController::class, 'edit'])->middleware('permission:bookings.edit')->name('admin.bookings.edit');
    Route::put('/bookings/{id}', [BookingController::class, 'update'])->middleware('permission:bookings.edit')->name('admin.bookings.update');
    Route::get('/bookings/{id}', [BookingController::class, 'show'])->middleware('permission:bookings.view')->name('admin.bookings.show');
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])->middleware('permission:bookings.delete')->name('admin.bookings.destroy');
    Route::post('/bookings/{id}/addon', [BookingController::class, 'storeAddon'])->middleware('permission:bookings.edit')->name('admin.bookings.addon.store');
    Route::delete('/bookings/{id}/addon/{addonId}', [BookingController::class, 'destroyAddon'])->middleware('permission:bookings.edit')->name('admin.bookings.addon.destroy');
    Route::post('/admin/bookings/{id}/update-odometer', [BookingController::class, 'updateOdometer'])->middleware('permission:bookings.edit')->name('admin.bookings.update_odometer');
    Route::post('/admin/bookings/{id}/lock', [BookingController::class, 'lockBooking'])->middleware('permission:bookings.edit')->name('admin.bookings.lock');
    Route::post('/admin/bookings/{id}/unlock', [BookingController::class, 'unlockBooking'])->middleware('permission:bookings.edit')->name('admin.bookings.unlock');
    Route::post('/bookings/{id}/refund', [BookingController::class, 'processRefund'])->middleware('permission:finance.edit')->name('admin.bookings.process_refund');
    Route::post('/bookings/{id}/update-status', [BookingController::class, 'updateStatus'])->middleware('permission:bookings.edit')->name('admin.bookings.update_status');

    Route::get('/armada', [ArmadaController::class, 'index'])->middleware('permission:fleet.view')->name('admin.armada');
    Route::get('/armada/create', [ArmadaController::class, 'create'])->middleware('permission:fleet.create')->name('admin.armada.create');
    Route::post('/armada/create-dokumen', [ArmadaController::class, 'createDokumen'])->middleware('permission:fleet.create')->name('admin.armada.create-dokumen');
    Route::get('/armada/dokumen-phk/{surat}', [ArmadaController::class, 'dokumenPhk'])->middleware('permission:fleet.view')->name('admin.armada.dokumen-phk');
    Route::post('/armada/store', [ArmadaController::class, 'store'])->middleware('permission:fleet.create')->name('admin.armada.store');
    Route::get('/armada/{id}/edit', [ArmadaController::class, 'edit'])->middleware('permission:fleet.edit')->name('admin.armada.edit');
    Route::put('/armada/{id}', [ArmadaController::class, 'update'])->middleware('permission:fleet.edit')->name('admin.armada.update');
    Route::delete('/armada/{id}', [ArmadaController::class, 'destroy'])->middleware('permission:fleet.delete')->name('admin.armada.destroy');
    Route::get('/armada/{id}/show', [ArmadaController::class, 'show'])->middleware('permission:fleet.view')->name('admin.armada.show');
    Route::post('/armada/{id}/km', [ArmadaController::class, 'updateKm'])->middleware('permission:fleet.edit')->name('admin.armada.updateKm');
    Route::post('/armada/{id}/part', [ArmadaController::class, 'storePart'])->middleware('permission:fleet.edit')->name('admin.armada.part.store');
    Route::get('/armada/sale-template', [ArmadaController::class, 'saleTemplate'])->middleware('permission:fleet.view')->name('admin.armada.sale-template');
    Route::post('/armada/sale-template/save', [ArmadaController::class, 'saveSaleTemplate'])->middleware('permission:fleet.edit')->name('admin.armada.sale-template.save');
    Route::delete('/armada/part/{id}', [ArmadaController::class, 'destroyPart'])->middleware('permission:fleet.delete')->name('admin.armada.part.destroy');
    Route::put('/armada/part/{id}', [ArmadaController::class, 'updatePart'])->middleware('permission:fleet.edit')->name('admin.armada.part.update');
    Route::get('/armada/bengkel', [ArmadaController::class, 'bengkel'])->middleware('permission:service.view')->name('admin.armada.bengkel');
    Route::get('/armada/{id}/bengkel/create', [ArmadaController::class, 'createBengkel'])->middleware('permission:service.create')->name('admin.armada.bengkel.create');
    Route::post('/armada/{id}/bengkel/store', [ArmadaController::class, 'storeBengkel'])->middleware('permission:service.create')->name('admin.armada.bengkel.store');
    Route::post('/armada/{id}/send-to-workshop', [ArmadaController::class, 'sendToWorkshop'])->middleware('permission:service.edit')->name('admin.armada.send_to_workshop');

    Route::get('/keuangan', [KeuanganController::class, 'index'])->middleware('permission:finance.view')->name('admin.keuangan');
    Route::get('/keuangan/data', [KeuanganController::class, 'data'])->middleware('permission:finance.view')->name('admin.keuangan.data');
    Route::get('/keuangan/per-mobil', [KeuanganController::class, 'perMobil'])->middleware('permission:finance.view')->name('admin.keuangan.per-mobil');
    Route::get('/keuangan/per-mobil/data', [KeuanganController::class, 'perMobilData'])->middleware('permission:finance.view')->name('admin.keuangan.per-mobil.data');
    Route::get('/keuangan/pengeluaran-kantor', [KeuanganController::class, 'pengeluaranKantor'])->middleware('permission:finance.view')->name('admin.keuangan.pengeluaran-kantor');
    Route::get('/keuangan/pengeluaran-kantor/data', [KeuanganController::class, 'pengeluaranKantorData'])->middleware('permission:finance.view')->name('admin.keuangan.pengeluaran-kantor.data');
    Route::get('/keuangan/pengeluaran-kantor/create', [KeuanganController::class, 'createPengeluaranKantor'])->middleware('permission:finance.create')->name('admin.keuangan.pengeluaran-kantor.create');
    Route::post('/keuangan/pengeluaran-kantor', [KeuanganController::class, 'storePengeluaranKantor'])->middleware('permission:finance.create')->name('admin.keuangan.pengeluaran-kantor.store');
    Route::get('/keuangan/pengeluaran-kantor/{id}/edit', [KeuanganController::class, 'editPengeluaranKantor'])->middleware('permission:finance.edit')->name('admin.keuangan.pengeluaran-kantor.edit');
    Route::put('/keuangan/pengeluaran-kantor/{id}', [KeuanganController::class, 'updatePengeluaranKantor'])->middleware('permission:finance.edit')->name('admin.keuangan.pengeluaran-kantor.update');
    Route::delete('/keuangan/pengeluaran-kantor/{id}', [KeuanganController::class, 'destroyPengeluaranKantor'])->middleware('permission:finance.delete')->name('admin.keuangan.pengeluaran-kantor.destroy');

    Route::get('/watermark-page', function () {
        $logoPath = public_path('storage/img/logo.png');
        if (! file_exists($logoPath)) {
            return response('<svg xmlns="http://www.w3.org/2000/svg" width="554" height="1123"/>', 200)->header('Content-Type', 'image/svg+xml');
        }
        $logoData = base64_encode(file_get_contents($logoPath));
        $size = getimagesize($logoPath);
        $ratio = ($size[0] ?? 1000) / ($size[1] ?? 1000);
        $logoW = 554;
        $logoH = round($logoW / $ratio);
        $y = round((1123 - $logoH) / 2);
        $svg = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="554" height="1123"><image href="data:image/png;base64,%s" x="0" y="%d" width="%d" height="%d" opacity="0.45" preserveAspectRatio="xMidYMid meet"/></svg>', $logoData, $y, $logoW, $logoH);
        return response($svg, 200)->header('Content-Type', 'image/svg+xml')->header('Cache-Control', 'public, max-age=86400');
    })->name('watermark.page');

    Route::get('/surat-menyurat', [SuratMenyuratController::class, 'index'])->middleware('permission:bookings.view')->name('admin.surat-menyurat');
    Route::get('/surat-menyurat/create', [SuratMenyuratController::class, 'create'])->middleware('permission:bookings.edit')->name('admin.surat-menyurat.create');
    Route::post('/surat-menyurat/store', [SuratMenyuratController::class, 'store'])->middleware('permission:bookings.edit')->name('admin.surat-menyurat.store');
    Route::post('/surat-menyurat/store-sub', [SuratMenyuratController::class, 'storeSub'])->middleware('permission:bookings.edit')->name('admin.surat-menyurat.store-sub');
    Route::delete('/surat-menyurat/bulk-delete', [SuratMenyuratController::class, 'bulkDestroy'])->middleware('permission:bookings.delete')->name('admin.surat-menyurat.bulk-delete');
    Route::prefix('surat-menyurat')->name('admin.surat-menyurat.')->group(function () {
        Route::get('/template', [TemplateSuratController::class, 'index'])->middleware('permission:bookings.view')->name('template');
        Route::get('/template/create', [TemplateSuratController::class, 'create'])->middleware('permission:bookings.edit')->name('template.create');
        Route::post('/template/store', [TemplateSuratController::class, 'store'])->middleware('permission:bookings.edit')->name('template.store');
        Route::get('/template/{id}/edit', [TemplateSuratController::class, 'edit'])->middleware('permission:bookings.edit')->name('template.edit');
        Route::put('/template/{id}', [TemplateSuratController::class, 'update'])->middleware('permission:bookings.edit')->name('template.update');
        Route::delete('/template/{id}', [TemplateSuratController::class, 'destroy'])->middleware('permission:bookings.delete')->name('template.destroy');
        Route::get('/template/{id}/print', [TemplateSuratController::class, 'print'])->middleware('permission:bookings.view')->name('template.print');
    });

    Route::post('/invoices/generate/{booking}/{jenis}', [InvoiceController::class, 'generate'])->middleware('permission:finance.create')->name('admin.invoice.generate');
    Route::post('/invoices/bengkel/{serviceLog}', [InvoiceController::class, 'bengkel'])->middleware('permission:finance.create')->name('admin.invoice.bengkel');
    Route::get('/invoices/{id}/print', [InvoiceController::class, 'print'])->middleware('permission:finance.view')->name('admin.invoice.print');
    Route::get('/invoices/{id}/print-bengkel', [InvoiceController::class, 'printBengkel'])->middleware('permission:finance.view')->name('admin.invoice.print-bengkel');

    // Tanda tangan invoice. PDF hanya boleh diunduh setelah kedua blok terisi.
    Route::post('/invoices/{id}/signature', [InvoiceController::class, 'storeSignature'])->middleware('permission:finance.create')->name('admin.invoice.signature.store');
    Route::delete('/invoices/{id}/signature/{role}', [InvoiceController::class, 'destroySignature'])->middleware('permission:finance.create')->name('admin.invoice.signature.destroy');
    Route::get('/invoices/{id}/download', [InvoiceController::class, 'download'])->middleware('permission:finance.view')->name('admin.invoice.download');
    Route::get('/invoices/{id}/preview', [InvoiceController::class, 'preview'])->middleware('permission:finance.view')->name('admin.invoice.preview');
    Route::get('/surat-menyurat/arsip', [SuratMenyuratController::class, 'arsip'])->middleware('permission:bookings.view')->name('admin.surat-menyurat.arsip');
    Route::post('/surat-menyurat/balik-nama/{car}', [SuratMenyuratController::class, 'balikNama'])->middleware('permission:fleet.edit')->name('admin.surat-menyurat.balik-nama');
    Route::post('/surat-menyurat/serah-terima/{booking}', [SuratMenyuratController::class, 'serahTerima'])->middleware('permission:bookings.edit')->name('admin.surat-menyurat.serah-terima');
    Route::get('/qr-scanner', fn () => view('qr-scanner.index'))->middleware('permission:bookings.view')->name('admin.qr-scanner');

    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('admin.users.role');

    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:manage_permissions')->name('admin.permissions.index');
    Route::get('/permissions/data', [PermissionController::class, 'data'])->middleware('permission:manage_permissions')->name('admin.permissions.data');
    Route::put('/permissions', [PermissionController::class, 'update'])->middleware('permission:manage_permissions')->name('admin.permissions.update');
    Route::post('/permissions/roles', [PermissionController::class, 'storeRole'])->middleware('permission:manage_permissions')->name('admin.permissions.roles.store');
    Route::patch('/permissions/roles/{role}', [PermissionController::class, 'updateRole'])->middleware('permission:manage_permissions')->name('admin.permissions.roles.update');
    Route::delete('/permissions/roles/{role}', [PermissionController::class, 'destroyRole'])->middleware('permission:manage_permissions')->name('admin.permissions.roles.destroy');
    Route::patch('/permissions/roles/{role}/color', [PermissionController::class, 'updateColor'])->middleware('permission:manage_permissions')->name('admin.permissions.roles.color');
    Route::post('/permissions/reorder', [PermissionController::class, 'reorder'])->middleware('permission:manage_permissions')->name('admin.permissions.reorder');
});

Route::get('/verify/{token}', [InvoiceController::class, 'verify'])->name('invoice.verify');
