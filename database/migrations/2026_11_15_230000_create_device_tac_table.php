<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('device_tac')) {
            Schema::create('device_tac', function (Blueprint $table) {
                $table->id();
                $table->string('tac', 8)->unique();
                $table->string('brand', 50)->index();
                $table->string('model', 100);
                $table->string('storage', 50)->nullable();
                $table->string('ram', 50)->nullable();
                $table->string('kategori', 50)->default('gadget');
                $table->timestamps();
            });
        }

        // Seed popular Indonesian smartphone TACs
        $initialData = [
            // Apple iPhone 11 Series
            ['tac' => '35656010', 'brand' => 'Apple', 'model' => 'iPhone 11', 'storage' => '64GB', 'ram' => '4GB', 'kategori' => 'gadget'],
            ['tac' => '35656110', 'brand' => 'Apple', 'model' => 'iPhone 11', 'storage' => '128GB', 'ram' => '4GB', 'kategori' => 'gadget'],
            ['tac' => '35656210', 'brand' => 'Apple', 'model' => 'iPhone 11 Pro', 'storage' => '256GB', 'ram' => '4GB', 'kategori' => 'gadget'],
            ['tac' => '35656310', 'brand' => 'Apple', 'model' => 'iPhone 11 Pro Max', 'storage' => '256GB', 'ram' => '4GB', 'kategori' => 'gadget'],

            // Apple iPhone 12 Series
            ['tac' => '35304611', 'brand' => 'Apple', 'model' => 'iPhone 12 Mini', 'storage' => '128GB', 'ram' => '4GB', 'kategori' => 'gadget'],
            ['tac' => '35304711', 'brand' => 'Apple', 'model' => 'iPhone 12', 'storage' => '128GB', 'ram' => '4GB', 'kategori' => 'gadget'],
            ['tac' => '35304811', 'brand' => 'Apple', 'model' => 'iPhone 12 Pro', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '35304911', 'brand' => 'Apple', 'model' => 'iPhone 12 Pro Max', 'storage' => '256GB', 'ram' => '6GB', 'kategori' => 'gadget'],

            // Apple iPhone 13 Series
            ['tac' => '35875110', 'brand' => 'Apple', 'model' => 'iPhone 13', 'storage' => '128GB', 'ram' => '4GB', 'kategori' => 'gadget'],
            ['tac' => '35875210', 'brand' => 'Apple', 'model' => 'iPhone 13 Mini', 'storage' => '128GB', 'ram' => '4GB', 'kategori' => 'gadget'],
            ['tac' => '35875310', 'brand' => 'Apple', 'model' => 'iPhone 13 Pro', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '35875410', 'brand' => 'Apple', 'model' => 'iPhone 13 Pro Max', 'storage' => '256GB', 'ram' => '6GB', 'kategori' => 'gadget'],

            // Apple iPhone 14 Series
            ['tac' => '35626388', 'brand' => 'Apple', 'model' => 'iPhone 14', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '35626488', 'brand' => 'Apple', 'model' => 'iPhone 14 Plus', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '35626588', 'brand' => 'Apple', 'model' => 'iPhone 14 Pro', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '35626688', 'brand' => 'Apple', 'model' => 'iPhone 14 Pro Max', 'storage' => '256GB', 'ram' => '6GB', 'kategori' => 'gadget'],

            // Apple iPhone 15 Series
            ['tac' => '35418197', 'brand' => 'Apple', 'model' => 'iPhone 15', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '35418297', 'brand' => 'Apple', 'model' => 'iPhone 15 Plus', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '35418397', 'brand' => 'Apple', 'model' => 'iPhone 15 Pro', 'storage' => '128GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35418497', 'brand' => 'Apple', 'model' => 'iPhone 15 Pro Max', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],

            // Apple iPhone 16 Series
            ['tac' => '35728198', 'brand' => 'Apple', 'model' => 'iPhone 16', 'storage' => '128GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35728298', 'brand' => 'Apple', 'model' => 'iPhone 16 Pro', 'storage' => '128GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35728398', 'brand' => 'Apple', 'model' => 'iPhone 16 Pro Max', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],

            // Samsung Galaxy S Series
            ['tac' => '35807111', 'brand' => 'Samsung', 'model' => 'Galaxy S21 5G', 'storage' => '128GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35807211', 'brand' => 'Samsung', 'model' => 'Galaxy S21 Ultra 5G', 'storage' => '256GB', 'ram' => '12GB', 'kategori' => 'gadget'],
            ['tac' => '35287667', 'brand' => 'Samsung', 'model' => 'Galaxy S22 5G', 'storage' => '128GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35287767', 'brand' => 'Samsung', 'model' => 'Galaxy S22 Ultra 5G', 'storage' => '256GB', 'ram' => '12GB', 'kategori' => 'gadget'],
            ['tac' => '35398242', 'brand' => 'Samsung', 'model' => 'Galaxy S23 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35398342', 'brand' => 'Samsung', 'model' => 'Galaxy S23 Ultra 5G', 'storage' => '256GB', 'ram' => '12GB', 'kategori' => 'gadget'],
            ['tac' => '35987123', 'brand' => 'Samsung', 'model' => 'Galaxy S24 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35987223', 'brand' => 'Samsung', 'model' => 'Galaxy S24 Ultra 5G', 'storage' => '256GB', 'ram' => '12GB', 'kategori' => 'gadget'],

            // Samsung Galaxy A Series & Z Series
            ['tac' => '35789123', 'brand' => 'Samsung', 'model' => 'Galaxy A05', 'storage' => '64GB', 'ram' => '4GB', 'kategori' => 'gadget'],
            ['tac' => '35789223', 'brand' => 'Samsung', 'model' => 'Galaxy A05s', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '35467812', 'brand' => 'Samsung', 'model' => 'Galaxy A15 5G', 'storage' => '128GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35612345', 'brand' => 'Samsung', 'model' => 'Galaxy A25 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35890123', 'brand' => 'Samsung', 'model' => 'Galaxy A35 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35234567', 'brand' => 'Samsung', 'model' => 'Galaxy A54 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35234667', 'brand' => 'Samsung', 'model' => 'Galaxy A55 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35123456', 'brand' => 'Samsung', 'model' => 'Galaxy Z Flip 5', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '35765432', 'brand' => 'Samsung', 'model' => 'Galaxy Z Fold 5', 'storage' => '256GB', 'ram' => '12GB', 'kategori' => 'gadget'],

            // Xiaomi & Redmi Series
            ['tac' => '86498205', 'brand' => 'Xiaomi', 'model' => 'Redmi Note 10 Pro', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '86512305', 'brand' => 'Xiaomi', 'model' => 'Redmi Note 11', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '86623405', 'brand' => 'Xiaomi', 'model' => 'Redmi Note 12', 'storage' => '128GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86734505', 'brand' => 'Xiaomi', 'model' => 'Redmi Note 13 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86845605', 'brand' => 'Xiaomi', 'model' => 'Redmi 13C', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '86956705', 'brand' => 'Xiaomi', 'model' => 'POCO X6 Pro 5G', 'storage' => '512GB', 'ram' => '12GB', 'kategori' => 'gadget'],
            ['tac' => '86067805', 'brand' => 'Xiaomi', 'model' => 'POCO F6', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],

            // Oppo Series
            ['tac' => '86178905', 'brand' => 'Oppo', 'model' => 'Reno 8T', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86289005', 'brand' => 'Oppo', 'model' => 'Reno 10 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86390105', 'brand' => 'Oppo', 'model' => 'Reno 11 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86401205', 'brand' => 'Oppo', 'model' => 'A78 5G', 'storage' => '128GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86401305', 'brand' => 'Oppo', 'model' => 'A58', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],

            // Vivo Series
            ['tac' => '86512306', 'brand' => 'Vivo', 'model' => 'Y16', 'storage' => '64GB', 'ram' => '4GB', 'kategori' => 'gadget'],
            ['tac' => '86512406', 'brand' => 'Vivo', 'model' => 'Y27 5G', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '86512506', 'brand' => 'Vivo', 'model' => 'Y36', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86623406', 'brand' => 'Vivo', 'model' => 'V29 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86623506', 'brand' => 'Vivo', 'model' => 'V30 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],

            // Infinix Series
            ['tac' => '86734506', 'brand' => 'Infinix', 'model' => 'Hot 30', 'storage' => '128GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86734606', 'brand' => 'Infinix', 'model' => 'Hot 40 Pro', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86845606', 'brand' => 'Infinix', 'model' => 'Note 30', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86845706', 'brand' => 'Infinix', 'model' => 'Note 40 Pro', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86845806', 'brand' => 'Infinix', 'model' => 'GT 10 Pro', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],

            // Realme Series
            ['tac' => '86956706', 'brand' => 'Realme', 'model' => 'C53', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '86956806', 'brand' => 'Realme', 'model' => 'C55 NFC', 'storage' => '128GB', 'ram' => '6GB', 'kategori' => 'gadget'],
            ['tac' => '86067806', 'brand' => 'Realme', 'model' => 'C67', 'storage' => '128GB', 'ram' => '8GB', 'kategori' => 'gadget'],
            ['tac' => '86067906', 'brand' => 'Realme', 'model' => 'Realme 11 Pro+ 5G', 'storage' => '512GB', 'ram' => '12GB', 'kategori' => 'gadget'],
            ['tac' => '86068006', 'brand' => 'Realme', 'model' => 'Realme 12+ 5G', 'storage' => '256GB', 'ram' => '8GB', 'kategori' => 'gadget'],
        ];

        $now = now();
        foreach ($initialData as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        DB::table('device_tac')->insertOrIgnore($initialData);
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tac');
    }
};
