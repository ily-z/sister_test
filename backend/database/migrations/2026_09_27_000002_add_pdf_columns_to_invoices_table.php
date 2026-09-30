<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Kolom file_pdf_path sudah ada sebelumnya (migration 2026_05_03_133606) tapi belum pernah dipakai.
            if (!Schema::hasColumn('invoices', 'pdf_generated_at')) {
                $table->timestamp('pdf_generated_at')->nullable()->after('file_pdf_path');
            }
            if (!Schema::hasColumn('invoices', 'document_hash')) {
                $table->string('document_hash', 64)->nullable()->after('pdf_generated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['pdf_generated_at', 'document_hash']);
        });
    }
};
