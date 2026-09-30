<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('surat_id')->nullable()->constrained('surat')->nullOnDelete();
            $table->string('jenis_invoice', 10)->nullable()->comment('SH, SK, AK, BNG');
            $table->foreignId('service_log_id')->nullable()->constrained('service_logs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['surat_id']);
            $table->dropForeign(['service_log_id']);
            $table->dropColumn('surat_id');
            $table->dropColumn('jenis_invoice');
            $table->dropColumn('service_log_id');
        });
    }
};
