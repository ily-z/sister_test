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
            $table->string('parent_slug', 100)->nullable()->after('slug');
            $table->foreign('parent_slug')->references('slug')->on('roles')->nullOnDelete();
        });

        DB::table('roles')->where('slug', 'admin')->update(['parent_slug' => 'owner']);
        DB::table('roles')->where('slug', 'reception')->update(['parent_slug' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['parent_slug']);
            $table->dropColumn('parent_slug');
        });
    }
};