<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceiptOcrTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTablesExist();
    }

    private function ensureTablesExist(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    public function test_scan_receipt_requires_file(): void
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('bos.kas-tunai.scan-receipt'), []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'fallback_to_offline' => true,
        ]);
    }

    public function test_scan_receipt_falls_back_when_no_api_key(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Ensure api key is null
        config(['services.gemini.api_key' => null]);

        $file = UploadedFile::fake()->image('nota_toko.jpg', 600, 800);

        $response = $this->actingAs($user)->post(route('bos.kas-tunai.scan-receipt'), [
            'receipt_file' => $file,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'fallback_to_offline' => true,
        ]);
    }

    public function test_scan_receipt_successfully_parses_gemini_vision_response(): void
    {
        $user = User::first() ?? User::factory()->create();

        config(['services.gemini.api_key' => 'fake-test-key']);

        $mockGeminiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'store_name' => 'Toko Alat Tulis Barokah',
                                    'date' => '2026-09-10',
                                    'evidence_number' => 'NOTA-889',
                                    'total_amount' => 350000,
                                    'items' => [
                                        [
                                            'item_name' => 'Kertas HVS A4 80gr',
                                            'quantity' => 5,
                                            'unit' => 'rim',
                                            'price' => 55000,
                                            'total_price' => 275000,
                                        ],
                                        [
                                            'item_name' => 'Spidol Whiteboard Hitam',
                                            'quantity' => 10,
                                            'unit' => 'pcs',
                                            'price' => 7500,
                                            'total_price' => 75000,
                                        ],
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockGeminiResponse, 200),
        ]);

        $file = UploadedFile::fake()->image('nota_belanja.jpg', 600, 800);

        // Test BOS route
        $response = $this->actingAs($user)->post(route('bos.kas-tunai.scan-receipt'), [
            'receipt_file' => $file,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'engine' => 'gemini',
            'data' => [
                'store_name' => 'Toko Alat Tulis Barokah',
                'date' => '2026-09-10',
                'evidence_number' => 'NOTA-889',
                'total_amount' => 350000,
            ],
        ]);

        $this->assertEquals(2, count($response->json('data.items')));

        // Test BPOPP route
        $responseBpopp = $this->actingAs($user)->post(route('bpopp.kas-tunai.scan-receipt'), [
            'receipt_file' => $file,
        ]);
        $responseBpopp->assertOk();
        $responseBpopp->assertJson(['success' => true]);
    }
}
