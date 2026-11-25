<?php

namespace Database\Seeders;

use App\Models\MStock;
use App\Models\MUsers;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = MUsers::firstOrCreate(
            ['username' => 'revworks'],
            [
                'password' => 'admin123',
                'level' => 'owner',
                'nama' => 'Rev',
            ]
        );

        MUsers::firstOrCreate(
            ['username' => 'revwerks'],
            [
                'password' => 'admin123',
                'level' => 'sales',
                'nama' => 'Reva',
            ]
        );

        if (MStock::count() === 0) {
            $today = now()->toDateString();

            MStock::create([
                'tanggal' => $today,
                'imei' => '354184970011223',
                'brand' => 'Apple',
                'kategori' => 'gadget',
                'nama' => 'iPhone 15 Pro Max',
                'warna' => 'Natural Titanium',
                'ram' => '8GB',
                'storage' => '256GB',
                'kondisi' => 'baru',
                'kelengkapan' => 'Fullset',
                'garansi' => 'Resmi iBox 1 Tahun',
                'harga_masuk' => 19500000,
                'harga_jual' => 21499000,
                'id_sales' => $owner->id_user,
                'status' => 'ada',
            ]);

            MStock::create([
                'tanggal' => $today,
                'imei' => '359872230044556',
                'brand' => 'Samsung',
                'kategori' => 'gadget',
                'nama' => 'Galaxy S24 Ultra 5G',
                'warna' => 'Titanium Gray',
                'ram' => '12GB',
                'storage' => '256GB',
                'kondisi' => 'second',
                'kelengkapan' => 'Fullset',
                'garansi' => 'Garansi Toko 1 Bulan',
                'harga_masuk' => 14200000,
                'harga_jual' => 15750000,
                'id_sales' => $owner->id_user,
                'status' => 'ada',
            ]);
        }
    }
}
