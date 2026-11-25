<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user')) {
            Schema::create('user', function (Blueprint $table) {
                $table->increments('id_user');
                $table->string('username')->unique();
                $table->string('password');
                $table->enum('level', ['owner', 'sales'])->default('sales');
                $table->string('nama');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('stok_hp')) {
            Schema::create('stok_hp', function (Blueprint $table) {
                $table->increments('id_stock');
                $table->date('tanggal')->nullable();
                $table->string('imei')->nullable();
                $table->string('brand')->nullable();
                $table->enum('kategori', ['gadget', 'aksesoris', 'sparepart'])->default('gadget');
                $table->string('nama')->nullable();
                $table->string('warna')->nullable();
                $table->string('ram', 50)->nullable();
                $table->string('storage', 50)->nullable();
                $table->enum('kondisi', ['baru', 'second', 'refurbished'])->default('baru');
                $table->string('kelengkapan', 100)->nullable();
                $table->string('garansi', 100)->nullable();
                $table->decimal('harga_masuk', 15, 2)->default(0);
                $table->decimal('harga_jual', 15, 2)->default(0);
                $table->unsignedInteger('id_sales')->nullable();
                $table->enum('status', ['ada', 'terjual'])->default('ada');
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->index(['status', 'kategori'], 'idx_status_kategori');
                $table->index('imei', 'idx_imei');
            });
        }

        if (!Schema::hasTable('penjualan')) {
            Schema::create('penjualan', function (Blueprint $table) {
                $table->increments('id_penjualan');
                $table->string('no_invoice', 100)->nullable();
                $table->date('tanggal')->nullable();
                $table->unsignedInteger('id_stock');
                $table->unsignedInteger('id_sales');
                $table->string('nama_pembeli')->nullable();
                $table->string('no_hp_pembeli', 50)->nullable();
                $table->decimal('harga_jual', 15, 2)->default(0);
                $table->decimal('diskon', 15, 2)->default(0);
                $table->string('met_pem', 50)->default('Cash');
                $table->decimal('uang_bayar', 15, 2)->default(0);
                $table->decimal('kembalian', 15, 2)->default(0);
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->index('no_invoice', 'idx_no_invoice');
                $table->index('tanggal', 'idx_tanggal_penjualan');
            });
        }

        if (!Schema::hasTable('laporan_bulanan')) {
            Schema::create('laporan_bulanan', function (Blueprint $table) {
                $table->increments('id_laporan');
                $table->string('bulan')->nullable();
                $table->integer('tahun')->nullable();
                $table->decimal('pemasukan', 15, 2)->default(0);
                $table->decimal('pengeluaran', 15, 2)->default(0);
                $table->integer('unit_masuk')->default(0);
                $table->integer('unit_keluar')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan');
        Schema::dropIfExists('stok_hp');
        Schema::dropIfExists('laporan_bulanan');
        Schema::dropIfExists('user');
    }
};
