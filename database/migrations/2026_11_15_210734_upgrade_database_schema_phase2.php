<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sanitasi data uang lama sebelum perubahan tipe kolom
        DB::statement("UPDATE `stok_hp` SET `harga_masuk` = REPLACE(REPLACE(TRIM(`harga_masuk`), '.', ''), ',', '') WHERE `harga_masuk` IS NOT NULL");
        DB::statement("UPDATE `stok_hp` SET `harga_jual` = REPLACE(REPLACE(TRIM(`harga_jual`), '.', ''), ',', '') WHERE `harga_jual` IS NOT NULL");
        DB::statement("UPDATE `penjualan` SET `harga_jual` = REPLACE(REPLACE(TRIM(`harga_jual`), '.', ''), ',', '') WHERE `harga_jual` IS NOT NULL");

        // 2. Modifikasi tabel stok_hp
        Schema::table('stok_hp', function (Blueprint $table) {
            $table->string('imei', 255)->nullable()->change();
            $table->decimal('harga_masuk', 15, 2)->default(0)->change();
            $table->decimal('harga_jual', 15, 2)->default(0)->change();

            if (!Schema::hasColumn('stok_hp', 'kategori')) {
                $table->enum('kategori', ['gadget', 'aksesoris', 'sparepart'])->default('gadget')->after('brand');
            }
            if (!Schema::hasColumn('stok_hp', 'kondisi')) {
                $table->enum('kondisi', ['baru', 'second', 'refurbished'])->default('baru')->after('warna');
            }
            if (!Schema::hasColumn('stok_hp', 'ram')) {
                $table->string('ram', 50)->nullable()->after('warna');
            }
            if (!Schema::hasColumn('stok_hp', 'storage')) {
                $table->string('storage', 50)->nullable()->after('ram');
            }
            if (!Schema::hasColumn('stok_hp', 'kelengkapan')) {
                $table->string('kelengkapan', 100)->nullable()->after('kondisi');
            }
            if (!Schema::hasColumn('stok_hp', 'garansi')) {
                $table->string('garansi', 100)->nullable()->after('kelengkapan');
            }
            if (!Schema::hasColumn('stok_hp', 'catatan')) {
                $table->text('catatan')->nullable()->after('status');
            }

            // Indexing untuk search kencang
            $table->index(['status', 'kategori'], 'idx_status_kategori');
            $table->index('imei', 'idx_imei');
        });

        // 3. Modifikasi tabel penjualan
        Schema::table('penjualan', function (Blueprint $table) {
            $table->decimal('harga_jual', 15, 2)->default(0)->change();
            $table->string('met_pem', 50)->default('Cash')->change();

            if (!Schema::hasColumn('penjualan', 'no_invoice')) {
                $table->string('no_invoice', 100)->nullable()->unique()->after('id_penjualan');
            }
            if (!Schema::hasColumn('penjualan', 'nama_pembeli')) {
                $table->string('nama_pembeli', 255)->nullable()->after('id_sales');
            }
            if (!Schema::hasColumn('penjualan', 'no_hp_pembeli')) {
                $table->string('no_hp_pembeli', 50)->nullable()->after('nama_pembeli');
            }
            if (!Schema::hasColumn('penjualan', 'catatan')) {
                $table->text('catatan')->nullable()->after('met_pem');
            }
            if (!Schema::hasColumn('penjualan', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('catatan');
            }
            if (!Schema::hasColumn('penjualan', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }

            $table->index('tanggal', 'idx_tanggal_penjualan');
        });

        // 4. Backfill no_invoice dan timestamps untuk data transaksi lama
        $existingSales = DB::table('penjualan')->orderBy('id_penjualan')->get();
        foreach ($existingSales as $sale) {
            $date = $sale->tanggal ? date('Ymd', strtotime($sale->tanggal)) : '20240901';
            $invoiceNumber = sprintf('INV/%s/%04d', $date, $sale->id_penjualan);
            $createdAt = $sale->tanggal ? $sale->tanggal . ' 12:00:00' : now();

            DB::table('penjualan')
                ->where('id_penjualan', $sale->id_penjualan)
                ->update([
                    'no_invoice' => $sale->no_invoice ?: $invoiceNumber,
                    'created_at' => $sale->created_at ?: $createdAt,
                    'updated_at' => $sale->updated_at ?: $createdAt,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stok_hp', function (Blueprint $table) {
            $table->dropIndex('idx_status_kategori');
            $table->dropIndex('idx_imei');
            $table->dropColumn(['kategori', 'kondisi', 'ram', 'storage', 'kelengkapan', 'garansi', 'catatan']);
            $table->string('harga_masuk', 255)->change();
            $table->string('harga_jual', 255)->change();
        });

        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropIndex('idx_tanggal_penjualan');
            $table->dropColumn(['no_invoice', 'nama_pembeli', 'no_hp_pembeli', 'catatan', 'created_at', 'updated_at']);
            $table->string('harga_jual', 255)->change();
        });
    }
};
