<?php

namespace Tests\Feature\Api\V1;

use App\Models\KasCategory;
use App\Models\KasTunai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'testbendahara',
            'role' => 'bendahara_bos',
        ]);

        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'logintest',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'login' => 'logintest',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@mkb.grik',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'login' => 'test@mkb.grik',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'login' => 'nonexistent',
            'password' => 'wrong',
        ]);

        $response->assertUnprocessable();
    }

    public function test_logout(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Berhasil logout.']);
    }

    public function test_get_authenticated_user(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/user');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'role']]);
    }

    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertUnauthorized();
    }

    public function test_dashboard_returns_saldo_data(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'saldo_kas',
                'total_panjar_pending',
                'kas_siap_pakai',
                'transaksi_hari_ini',
                'kasbon_count',
            ]);
    }

    public function test_saldo_returns_bosp_and_bpopp(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/saldo');

        $response->assertOk()
            ->assertJsonStructure([
                'bosp' => ['saldo_kas', 'total_panjar_pending', 'kas_siap_pakai'],
                'bpopp' => ['saldo_kas', 'total_panjar_pending', 'kas_siap_pakai'],
            ]);
    }

    public function test_kas_categories_returns_data(): void
    {
        KasCategory::create([
            'code' => 'test_masuk',
            'name' => 'Test Masuk',
            'type' => 'masuk',
            'icon' => '💰',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/kas-categories');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_kas_tunai_index(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/kas-tunai');

        $response->assertOk()
            ->assertJsonStructure(['data', 'summary']);
    }

    public function test_kas_tunai_store(): void
    {
        // First create inflow so we have saldo
        KasTunai::create([
            'transaction_date' => now(),
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping dana',
            'funding_source' => 'BOSP',
            'year' => now()->year,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/kas-tunai', [
                'transaction_date' => now()->toDateString(),
                'transaction_type' => 'keluar',
                'category' => 'belanja_tunai',
                'amount' => 250000,
                'description' => 'Beli ATK Sekolah',
                'funding_source' => 'BOSP',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.description', 'Beli ATK Sekolah');
    }

    public function test_kas_tunai_store_prevents_negative_balance(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/kas-tunai', [
                'transaction_date' => now()->toDateString(),
                'transaction_type' => 'keluar',
                'category' => 'belanja_tunai',
                'amount' => 99999999,
                'description' => 'Terlalu besar',
                'funding_source' => 'BOSP',
            ]);

        $response->assertUnprocessable();
    }

    public function test_fiscal_years(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/fiscal-years');

        $response->assertOk();
    }
}
