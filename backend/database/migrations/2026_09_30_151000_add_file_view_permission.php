<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambahkan permission `file.view` untuk endpoint /api/files/{path}.
 *
 * KENAPA PERMISSION BARU
 * ----------------------
 * Dulu file diakses lewat symlink public/storage tanpa otentikasi
 * sama sekali, jadi tidak ada permission untuk itu. Sekarang file
 * disajikan lewat endpoint backend yang memuat foto KTP penyewa, foto
 * kondisi kendaraan, dan tanda tangan. Setiap user yang punya akses ke
 * data booking/armada otomatis berhak melihat file yang menyertainya,
 * jadi permission ini diberikan ke semua role yang sudah ada.
 *
 * Role `owner` sudah otomatis bypass semua permission
 * (lihat App\Models\User::hasPermission).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->insertOrIgnore([
            [
                'nama_permission' => 'file.view',
                'deskripsi' => 'Melihat file unggahan (foto KTP, foto kondisi, dokumen kendaraan, tanda tangan, PDF invoice)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $permissionId = DB::table('permissions')
            ->where('nama_permission', 'file.view')
            ->value('id');

        if ($permissionId === null) {
            return;
        }

        // Berikan ke setiap role yang ada, supaya tidak ada role yang
        // tiba-tiba tidak bisa membuka foto di halaman detail.
        $roles = DB::table('role_permissions')->distinct()->pluck('role');

        foreach ($roles as $role) {
            DB::table('role_permissions')->insertOrIgnore([
                'role' => $role,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('nama_permission', 'file.view')
            ->value('id');

        if ($permissionId === null) {
            return;
        }

        DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
