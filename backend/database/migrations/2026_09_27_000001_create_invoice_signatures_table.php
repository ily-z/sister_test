<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('role', 20)->comment('pelanggan, finance');
            $table->string('signer_name', 100);
            $table->string('signer_title', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signature_path', 255)->comment('Path PNG pada disk public');
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('document_hash', 64)->nullable()->comment('sha256 PDF saat ditandatangani');
            $table->timestamps();

            $table->unique(['invoice_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_signatures');
    }
};
