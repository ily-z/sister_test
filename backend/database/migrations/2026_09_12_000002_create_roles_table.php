<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->string('slug', 100)->primary();
            $table->string('nama', 100);
            $table->unsignedInteger('level')->default(100);
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        foreach ([
            ['slug' => 'owner', 'nama' => 'Master/Owner', 'level' => 300, 'sort_order' => 300, 'is_system' => true],
            ['slug' => 'admin', 'nama' => 'Admin', 'level' => 200, 'sort_order' => 200, 'is_system' => true],
            ['slug' => 'reception', 'nama' => 'Receptionist Desk', 'level' => 100, 'sort_order' => 100, 'is_system' => true],
        ] as $role) {
            DB::table('roles')->insert(array_merge($role, ['created_at' => now(), 'updated_at' => now()]));
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY role VARCHAR(100) NOT NULL DEFAULT \'admin\'');
            DB::statement('ALTER TABLE role_permissions MODIFY role VARCHAR(100) NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};