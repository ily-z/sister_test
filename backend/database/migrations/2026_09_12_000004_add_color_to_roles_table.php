<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('warna', 7)->default('#2563eb')->after('nama');
        });

        DB::table('roles')->where('slug', 'owner')->update(['warna' => '#f59e0b']);
        DB::table('roles')->where('slug', 'admin')->update(['warna' => '#2563eb']);
        DB::table('roles')->where('slug', 'reception')->update(['warna' => '#10b981']);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('warna');
        });
    }
};