<?php

namespace Tests\Feature;

use App\Models\MDeviceTac;
use App\Models\MStock;
use App\Models\MUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosCheckoutAndReportTest extends TestCase
{
    use RefreshDatabase;

    private MUsers $owner;
    private MUsers $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = MUsers::create([
            'username' => 'owner_pos',
            'password' => 'password123',
            'level' => 'owner',
            'nama' => 'Owner POS',
        ]);

        $this->sales = MUsers::create([
            'username' => 'sales_pos',
            'password' => 'password123',
            'level' => 'sales',
            'nama' => 'Kasir POS',
        ]);
    }

    public function test_multi_item_checkout_computes_change_server_side_and_returns_store_metadata(): void
    {
        Sanctum::actingAs($this->sales);

        $s1 = MStock::create([
            'tanggal' => '2026-11-25',
            'brand' => 'Apple',
            'kategori' => 'gadget',
            'nama' => 'iPhone 15',
            'harga_masuk' => 10000000,
            'harga_jual' => 12000000,
            'status' => 'ada',
        ]);

        $s2 = MStock::create([
            'tanggal' => '2026-11-25',
            'brand' => 'Anker',
            'kategori' => 'aksesoris',
            'nama' => 'Charger 20W',
            'harga_masuk' => 150000,
            'harga_jual' => 300000,
            'status' => 'ada',
        ]);

        $res = $this->postJson('/api/penjualan/checkout', [
            'tanggal' => '2026-11-25',
            'items' => [
                ['id_stock' => $s1->id_stock, 'harga_jual' => 12000000],
                ['id_stock' => $s2->id_stock, 'harga_jual' => 300000],
            ],
            'met_pem' => 'Cash',
            'diskon' => 300000,
            'uang_bayar' => 12500000,
            'kembalian' => 99999999, // Tampered client value must be ignored
            'nama_pembeli' => 'Budi',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.total', 12000000)
            ->assertJsonPath('data.kembalian', 500000);

        $noInvoice = $res->json('data.no_invoice');
        $this->assertEquals('terjual', $s1->fresh()->status);
        $this->assertEquals('terjual', $s2->fresh()->status);

        $invoiceRes = $this->getJson('/api/penjualan/invoice/' . urlencode($noInvoice));
        $invoiceRes->assertStatus(200)
            ->assertJsonPath('total', 12000000)
            ->assertJsonPath('kembalian', 500000)
            ->assertJsonPath('store.name', 'Tamboer Gadget');
    }

    public function test_checkout_rejects_underpaid_excess_discount_duplicate_and_sold_items(): void
    {
        Sanctum::actingAs($this->sales);

        $s1 = MStock::create([
            'tanggal' => '2026-11-25',
            'brand' => 'Samsung',
            'kategori' => 'gadget',
            'nama' => 'Galaxy A55',
            'harga_masuk' => 4500000,
            'harga_jual' => 5500000,
            'status' => 'ada',
        ]);

        // Underpaid cash
        $this->postJson('/api/penjualan/checkout', [
            'tanggal' => '2026-11-25',
            'items' => [['id_stock' => $s1->id_stock, 'harga_jual' => 5500000]],
            'met_pem' => 'Cash',
            'uang_bayar' => 5000000,
        ])->assertStatus(422);

        // Discount > subtotal
        $this->postJson('/api/penjualan/checkout', [
            'tanggal' => '2026-11-25',
            'items' => [['id_stock' => $s1->id_stock, 'harga_jual' => 5500000]],
            'met_pem' => 'QRIS',
            'diskon' => 6000000,
        ])->assertStatus(422);

        // Duplicate item in cart
        $this->postJson('/api/penjualan/checkout', [
            'tanggal' => '2026-11-25',
            'items' => [
                ['id_stock' => $s1->id_stock, 'harga_jual' => 5500000],
                ['id_stock' => $s1->id_stock, 'harga_jual' => 5500000],
            ],
            'met_pem' => 'QRIS',
        ])->assertStatus(422);

        // Valid checkout
        $this->postJson('/api/penjualan/checkout', [
            'tanggal' => '2026-11-25',
            'items' => [['id_stock' => $s1->id_stock, 'harga_jual' => 5500000]],
            'met_pem' => 'QRIS',
        ])->assertStatus(201);

        // Already sold
        $this->postJson('/api/penjualan/checkout', [
            'tanggal' => '2026-11-25',
            'items' => [['id_stock' => $s1->id_stock, 'harga_jual' => 5500000]],
            'met_pem' => 'QRIS',
        ])->assertStatus(422);
    }

    public function test_delete_sale_restores_stock_preserves_invoice_and_avoids_invoice_collision(): void
    {
        Sanctum::actingAs($this->owner);

        $s1 = MStock::create([
            'tanggal' => '2026-11-25',
            'brand' => 'Apple',
            'kategori' => 'gadget',
            'nama' => 'iPhone 14',
            'harga_masuk' => 8000000,
            'harga_jual' => 9500000,
            'status' => 'ada',
        ]);
        $s2 = MStock::create([
            'tanggal' => '2026-11-25',
            'brand' => 'Apple',
            'kategori' => 'aksesoris',
            'nama' => 'Case MagSafe',
            'harga_masuk' => 100000,
            'harga_jual' => 250000,
            'status' => 'ada',
        ]);

        $res1 = $this->postJson('/api/penjualan/checkout', [
            'tanggal' => '2026-11-25',
            'items' => [
                ['id_stock' => $s1->id_stock, 'harga_jual' => 9500000],
                ['id_stock' => $s2->id_stock, 'harga_jual' => 250000],
            ],
            'met_pem' => 'Cash',
            'diskon' => 50000,
            'uang_bayar' => 9800000,
        ])->assertStatus(201);

        $firstSaleId = $res1->json('data.items.0.id_penjualan');
        $inv1 = $res1->json('data.no_invoice');

        // Delete first item from invoice
        $this->deleteJson("/api/penjualan/{$firstSaleId}")->assertStatus(200);
        $this->assertEquals('ada', $s1->fresh()->status);

        // Remaining sibling item keeps invoice discount
        $invRes = $this->getJson('/api/penjualan/invoice/' . urlencode($inv1))->assertStatus(200);
        $this->assertEquals(50000, $invRes->json('diskon'));
        $this->assertEquals(200000, $invRes->json('total'));

        // Next checkout does not collide with INV/20261125/0001
        $res2 = $this->postJson('/api/penjualan/checkout', [
            'tanggal' => '2026-11-25',
            'items' => [['id_stock' => $s1->id_stock, 'harga_jual' => 9500000]],
            'met_pem' => 'Transfer',
        ])->assertStatus(201);

        $this->assertNotEquals($inv1, $res2->json('data.no_invoice'));
    }

    public function test_reports_subtract_discounts_and_return_category_breakdown(): void
    {
        Sanctum::actingAs($this->owner);

        $s1 = MStock::create([
            'tanggal' => '2026-11-25',
            'brand' => 'Apple',
            'kategori' => 'gadget',
            'nama' => 'iPhone 15',
            'harga_masuk' => 10000000,
            'harga_jual' => 12000000,
            'status' => 'ada',
        ]);

        $this->postJson('/api/penjualan/checkout', [
            'tanggal' => '2026-11-25',
            'items' => [['id_stock' => $s1->id_stock, 'harga_jual' => 12000000]],
            'met_pem' => 'Transfer',
            'diskon' => 500000,
        ])->assertStatus(201);

        $report = $this->getJson('/api/report/monthly?month=11&year=2026')->assertStatus(200);
        $this->assertEquals(12000000, $report->json('gross_income'));
        $this->assertEquals(500000, $report->json('total_discount'));
        $this->assertEquals(11500000, $report->json('total_income'));
        $this->assertEquals(1500000, $report->json('profit'));

        $dash = $this->getJson('/api/analytics/dashboard?month=11&year=2026')->assertStatus(200);
        $this->assertEquals(11500000, $dash->json('cards.revenue_this_month'));
        $this->assertEquals(1500000, $dash->json('cards.profit_this_month'));
        $this->assertEquals(['Smartphone'], $dash->json('category_breakdown.labels'));
        $this->assertEquals([1], $dash->json('category_breakdown.data'));
    }

    public function test_tac_lookup_matches_seeded_and_self_learned_tac(): void
    {
        Sanctum::actingAs($this->sales);

        // Seeded from migration 2026_11_15_230000
        $this->getJson('/api/stocks/lookup-tac/354184971234567')
            ->assertStatus(200)
            ->assertJsonPath('found', true)
            ->assertJsonPath('brand', 'Apple')
            ->assertJsonPath('nama', 'iPhone 15 Pro Max');

        // Self-learn new TAC on stock create
        $this->postJson('/api/stocks', [
            'tanggal' => '2026-11-25',
            'imei' => '998877661234567',
            'brand' => 'Nothing',
            'kategori' => 'gadget',
            'nama' => 'Phone (2)',
            'ram' => '12GB',
            'storage' => '256GB',
            'harga_jual' => 8500000,
        ])->assertStatus(201);

        $this->assertDatabaseHas('device_tac', ['tac' => '99887766', 'brand' => 'Nothing']);

        $this->getJson('/api/stocks/lookup-tac/998877669999999')
            ->assertStatus(200)
            ->assertJsonPath('found', true)
            ->assertJsonPath('brand', 'Nothing')
            ->assertJsonPath('nama', 'Phone (2)');
    }
}
