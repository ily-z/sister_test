<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->string('role', 20);
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->primary(['role', 'permission_id']);
        });

        DB::table('permissions')->insertOrIgnore([
            ['nama_permission' => 'manage_permissions', 'deskripsi' => 'Mengatur permission role di bawahnya', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $permissionIds = DB::table('permissions')->whereIn('nama_permission', [
            'manage_users', 'manage_permissions', 'manage_bookings',
        ])->pluck('id', 'nama_permission');

        $defaults = [
            ['role' => 'admin', 'permission_id' => $permissionIds['manage_users'] ?? null],
            ['role' => 'admin', 'permission_id' => $permissionIds['manage_permissions'] ?? null],
            ['role' => 'admin', 'permission_id' => $permissionIds['manage_bookings'] ?? null],
            ['role' => 'reception', 'permission_id' => $permissionIds['manage_bookings'] ?? null],
        ];

        DB::table('role_permissions')->insertOrIgnore(array_values(array_filter($defaults, fn (array $row) => $row['permission_id'] !== null)));
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};