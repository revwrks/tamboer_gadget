<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('penjualan', function (Blueprint $table) {
            if (Schema::hasIndex('penjualan', 'penjualan_no_invoice_unique')) {
                $table->dropUnique('penjualan_no_invoice_unique');
            }
            if (!Schema::hasIndex('penjualan', 'idx_no_invoice')) {
                $table->index('no_invoice', 'idx_no_invoice');
            }

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

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
    }
};
