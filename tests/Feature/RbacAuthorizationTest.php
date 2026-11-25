<?php

namespace Tests\Feature;

use App\Models\MPenjualan;
use App\Models\MStock;
use App\Models\MUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private MUsers $owner;
    private MUsers $sales;
    private MStock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = MUsers::create([
            'username' => 'owner_test',
            'password' => 'password123',
            'level' => 'owner',
            'nama' => 'Owner Test',
        ]);

        $this->sales = MUsers::create([
            'username' => 'sales_test',
            'password' => 'password123',
            'level' => 'sales',
            'nama' => 'Sales Test',
        ]);

        $this->stock = MStock::create([
            'tanggal' => '2026-11-25',
            'imei' => '354184970099887',
            'brand' => 'Apple',
            'kategori' => 'gadget',
            'nama' => 'iPhone 15 Pro Max',
            'harga_masuk' => 18000000,
            'harga_jual' => 21000000,
            'id_sales' => $this->owner->id_user,
            'status' => 'ada',
        ]);
    }

    public function test_sales_cannot_access_owner_only_endpoints(): void
    {
        Sanctum::actingAs($this->sales);

        $this->getJson('/api/users')->assertStatus(403);
        $this->postJson('/api/users', [
            'username' => 'newguy',
            'password' => 'password123',
            'level' => 'sales',
            'nama' => 'New Guy',
        ])->assertStatus(403);

        $this->putJson("/api/stocks/{$this->stock->id_stock}", ['harga_jual' => 19000000])->assertStatus(403);
        $this->deleteJson("/api/stocks/{$this->stock->id_stock}")->assertStatus(403);

        $sale = MPenjualan::create([
            'no_invoice' => 'INV/20261125/0001',
            'tanggal' => '2026-11-25',
            'id_stock' => $this->stock->id_stock,
            'id_sales' => $this->sales->id_user,
            'harga_jual' => 21000000,
            'met_pem' => 'Cash',
        ]);

        $this->putJson("/api/penjualan/{$sale->id_penjualan}", ['met_pem' => 'QRIS'])->assertStatus(403);
        $this->deleteJson("/api/penjualan/{$sale->id_penjualan}")->assertStatus(403);

        $this->getJson('/api/report/monthly')->assertStatus(403);
        $this->getJson('/api/sales-performance')->assertStatus(403);
        $this->getJson('/api/sales-details?month=11&year=2026')->assertStatus(403);
        $this->getJson('/api/available-dates')->assertStatus(403);
    }

    public function test_sales_never_receives_cost_or_profit_fields(): void
    {
        Sanctum::actingAs($this->sales);

        $stocksRes = $this->getJson('/api/stocks')->assertStatus(200);
        $this->assertArrayNotHasKey('harga_masuk', $stocksRes->json('0'));

        MPenjualan::create([
            'no_invoice' => 'INV/20261125/0001',
            'tanggal' => '2026-11-25',
            'id_stock' => $this->stock->id_stock,
            'id_sales' => $this->sales->id_user,
            'harga_jual' => 21000000,
            'met_pem' => 'Cash',
        ]);

        $salesRes = $this->getJson('/api/penjualan')->assertStatus(200);
        $this->assertArrayNotHasKey('harga_masuk', $salesRes->json('0.stok_hp'));

        $dashRes = $this->getJson('/api/analytics/dashboard')->assertStatus(200);
        $this->assertArrayNotHasKey('profit_this_month', $dashRes->json('cards'));
        $this->assertArrayNotHasKey('revenue_this_month', $dashRes->json('cards'));
        $this->assertArrayNotHasKey('ready_asset_value', $dashRes->json('cards'));
        $this->assertNull($dashRes->json('trend'));
    }

    public function test_owner_cannot_delete_self_or_demote_last_owner(): void
    {
        Sanctum::actingAs($this->owner);

        $this->deleteJson("/api/users/{$this->owner->id_user}")->assertStatus(422);

        $this->putJson("/api/users/{$this->owner->id_user}", [
            'username' => $this->owner->username,
            'nama' => $this->owner->nama,
            'level' => 'sales',
        ])->assertStatus(422);
    }
}
