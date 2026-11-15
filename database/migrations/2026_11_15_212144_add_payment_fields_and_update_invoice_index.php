<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            // Drop unique agar 1 nomor invoice bisa menampung banyak item barang (multi-item cart)
            $table->dropUnique('penjualan_no_invoice_unique');
            $table->index('no_invoice', 'idx_no_invoice');

            if (!Schema::hasColumn('penjualan', 'diskon')) {
                $table->decimal('diskon', 15, 2)->default(0)->after('harga_jual');
            }
            if (!Schema::hasColumn('penjualan', 'uang_bayar')) {
                $table->decimal('uang_bayar', 15, 2)->default(0)->after('met_pem');
            }
            if (!Schema::hasColumn('penjualan', 'kembalian')) {
                $table->decimal('kembalian', 15, 2)->default(0)->after('uang_bayar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropIndex('idx_no_invoice');
            $table->unique('no_invoice', 'penjualan_no_invoice_unique');
            $table->dropColumn(['diskon', 'uang_bayar', 'kembalian']);
        });
    }
};
