<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
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

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore(array_merge($permission, ['created_at' => $now, 'updated_at' => $now]));
        }

        $ids = DB::table('permissions')->pluck('id', 'nama_permission');
        $allActionIds = collect($permissions)->map(fn (array $permission) => $ids[$permission['nama_permission']])->all();
        $viewIds = collect($permissions)->filter(fn (array $permission) => str_ends_with($permission['nama_permission'], '.view'))->map(fn (array $permission) => $ids[$permission['nama_permission']])->all();

        foreach ($allActionIds as $permissionId) {
            DB::table('role_permissions')->insertOrIgnore(['role' => 'admin', 'permission_id' => $permissionId]);
        }

        DB::table('role_permissions')->where('role', 'reception')->whereIn('permission_id', [$ids['manage_bookings'] ?? 0])->delete();
        foreach ($viewIds as $permissionId) {
            DB::table('role_permissions')->insertOrIgnore(['role' => 'reception', 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('nama_permission', [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.delete',
            'fleet.view', 'fleet.create', 'fleet.edit', 'fleet.delete',
            'finance.view', 'finance.create', 'finance.edit', 'finance.delete',
            'service.view', 'service.create', 'service.edit', 'service.delete', 'analytics.view',
        ])->delete();
    }
};