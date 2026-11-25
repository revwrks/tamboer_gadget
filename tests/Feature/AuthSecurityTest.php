<?php

namespace Tests\Feature;

use App\Models\MUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_login_returns_sanctum_token_without_password_hash(): void
    {
        $user = MUsers::create([
            'username' => 'owner1',
            'password' => 'secret123',
            'level' => 'owner',
            'nama' => 'Owner Satu',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'owner1',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'message', 'user' => ['id_user', 'username', 'level', 'nama']])
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id_user,
        ]);
    }

    public function test_invalid_credentials_returns_401(): void
    {
        MUsers::create([
            'username' => 'owner1',
            'password' => 'secret123',
            'level' => 'owner',
            'nama' => 'Owner Satu',
        ]);

        $this->postJson('/api/login', [
            'username' => 'owner1',
            'password' => 'wrongpass',
        ])->assertStatus(401);
    }

    public function test_login_is_rate_limited_after_repeated_attempts(): void
    {
        MUsers::create([
            'username' => 'owner1',
            'password' => 'secret123',
            'level' => 'owner',
            'nama' => 'Owner Satu',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', [
                'username' => 'owner1',
                'password' => 'wrong',
            ])->assertStatus(401);
        }

        $this->postJson('/api/login', [
            'username' => 'owner1',
            'password' => 'wrong',
        ])->assertStatus(429);
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
        $this->getJson('/api/stocks')->assertStatus(401);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = MUsers::create([
            'username' => 'owner1',
            'password' => 'secret123',
            'level' => 'owner',
            'nama' => 'Owner Satu',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_security_headers_are_present_and_excel_proxy_is_removed(): void
    {
        $response = $this->get('/up');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

        $this->postJson('/api/download-excel-proxy', [
            'filename' => 'test.xlsx',
            'base64' => base64_encode('hello'),
        ])->assertStatus(404);
    }
}
