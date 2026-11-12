<?php

namespace Tests\Feature;

use App\Jobs\ProcessUtilityReceiptOcrJob;
use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\User;
use App\Models\UtilityProfile;
use App\Models\UtilityReceipt;
use App\Services\UtilityReceiptLocalParserService;
use App\Services\UtilityReceiptOcrService;
use Database\Seeders\UtilityProfileSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UtilityReceiptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTablesExist();
        $this->seed(UtilityProfileSeeder::class);
    }

    private function ensureTablesExist(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('utility_profiles')) {
            Schema::create('utility_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->string('category', 50);
                $table->string('customer_name');
                $table->string('customer_number')->nullable();
                $table->string('meter_number')->nullable();
                $table->string('idpel')->nullable();
                $table->string('tariff_power')->nullable();
                $table->string('provider')->nullable();
                $table->string('payment_method')->nullable();
                $table->unsignedBigInteger('default_reimburse_guru_id')->nullable();
                $table->boolean('is_budgeted')->default(true);
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('utility_receipts')) {
            Schema::create('utility_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('utility_profile_id')->nullable();
                $table->foreignId('bos_kertas_kerja_item_id')->nullable();
                $table->foreignId('pencairan_bank_item_id')->nullable();
                $table->integer('fiscal_year')->index();
                $table->string('billing_month', 20)->index();
                $table->dateTime('transaction_date')->index();
                $table->string('receipt_number')->nullable();
                $table->string('reference_number')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_number')->nullable();
                $table->string('meter_number')->nullable();
                $table->string('tariff_power')->nullable();
                $table->decimal('kwh_amount', 12, 2)->nullable();
                $table->decimal('base_amount', 15, 2)->default(0);
                $table->decimal('tax_pbjt', 15, 2)->default(0);
                $table->decimal('tax_ppn', 15, 2)->default(0);
                $table->decimal('admin_fee', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('token_number')->nullable();
                $table->string('payment_merchant')->nullable();
                $table->string('receipt_file')->nullable();
                $table->string('ocr_status', 30)->default('completed')->index();
                $table->integer('ocr_attempts')->default(0);
                $table->text('ocr_error_message')->nullable();
                $table->timestamp('ocr_processed_at')->nullable();
                $table->json('ocr_raw_data')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_files')) {
            Schema::create('bos_kertas_kerja_files', function (Blueprint $table) {
                $table->id();
                $table->string('filename')->nullable();
                $table->string('original_filename')->nullable();
                $table->string('file_path')->nullable();
                $table->integer('year')->default(2026);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_items')) {
            Schema::create('bos_kertas_kerja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_file_id')->nullable();
                $table->unsignedBigInteger('utility_profile_id')->nullable();
                $table->integer('year')->default(2026);
                $table->string('month', 50)->nullable();
                $table->string('account_code', 50)->nullable();
                $table->string('program_code', 50)->nullable();
                $table->string('kegiatan')->nullable();
                $table->string('description')->nullable();
                $table->decimal('volume', 10, 2)->default(0);
                $table->string('unit', 50)->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_utility_profiles_seeded_count_and_accessors(): void
    {
        $count = UtilityProfile::count();
        $this->assertEquals(9, $count);

        $smkToken = UtilityProfile::where('code', 'token_smk_kras')->first();
        $this->assertNotNull($smkToken);
        $this->assertEquals('32118573479', $smkToken->meter_number);
        $this->assertEquals('Listrik Prabayar (Token)', $smkToken->category_label);
        $this->assertStringContainsString('badge-primary', $smkToken->category_badge);
    }

    public function test_ocr_smart_profile_matching(): void
    {
        $service = new UtilityReceiptOcrService;

        // 1. Match by meter number (Token SMK PGRI KRAS)
        $match1 = $service->matchProfile([
            'meter_number' => '32118573479',
            'customer_name' => 'SMK PGRI KRAS',
            'token_number' => '5001 0208 1701 5895 0864',
        ]);
        $this->assertNotNull($match1);
        $this->assertEquals('token_smk_kras', $match1->code);

        // 2. Match by IDPEL (Listrik STM)
        $match2 = $service->matchProfile([
            'customer_number' => '514031084530',
            'customer_name' => 'STM',
        ]);
        $this->assertNotNull($match2);
        $this->assertEquals('listrik_stm', $match2->code);

        // 3. Match by name (MASJID AN NUR)
        $match3 = $service->matchProfile([
            'customer_name' => 'PLN MASJID AN NUR',
        ]);
        $this->assertNotNull($match3);
        $this->assertEquals('listrik_masjid', $match3->code);

        // 4. Match by phone (Speedy IMAM SJADELI)
        $match4 = $service->matchProfile([
            'customer_number' => '0354-000479487',
            'customer_name' => 'IMAM SJADELI',
        ]);
        $this->assertNotNull($match4);
        $this->assertEquals('speedy_imam', $match4->code);

        // 5. Match by provider name (Menaksopal Link)
        $match5 = $service->matchProfile([
            'customer_name' => 'Tagihan Menaksopal Link Nusantara',
        ]);
        $this->assertNotNull($match5);
        $this->assertEquals('internet_menaksopal', $match5->code);
    }

    public function test_user_can_access_utility_receipts_index_page(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['fiscal_year' => 2026])
            ->get(route('bos.utilitas.index', ['year' => 2026, 'month' => '07']));

        $response->assertStatus(200);
        $response->assertSee('Struk Utilitas');
        $response->assertSee('Status 9 Tagihan Rutin Bulanan');
        $response->assertSee('SMK PGRI KRAS');
        $response->assertSee('MASJID AN NUR');
        $response->assertSee('IMAM SJADELI');
    }

    public function test_user_can_store_and_view_utility_receipt(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        $profile = UtilityProfile::where('code', 'token_smk_kras')->first();

        // 1. Store receipt via AJAX
        $postData = [
            'utility_profile_id' => $profile->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Juli',
            'transaction_date' => '2026-07-07 13:43:38',
            'receipt_number' => '64172-03/2026/805315',
            'reference_number' => '1064172053199980619385540000',
            'customer_name' => 'SMK PGRI KRAS',
            'customer_number' => '514031897945',
            'meter_number' => '32118573479',
            'tariff_power' => 'S1/5500 VA',
            'kwh_amount' => 1010.2,
            'base_amount' => 909090,
            'tax_pbjt' => 90910,
            'admin_fee' => 3500,
            'total_amount' => 1003500,
            'token_number' => '5001 0208 1701 5895 0864',
            'payment_merchant' => 'PT POS INDONESIA (PERSERO) Kpc. Kras 64172',
            'notes' => 'Pembelian token listrik pos',
        ];

        $storeResponse = $this->actingAs($user)->postJson(route('bos.utilitas.store'), $postData);
        $storeResponse->assertStatus(200);
        $storeResponse->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('utility_receipts', [
            'meter_number' => '32118573479',
            'total_amount' => 1003500,
            'token_number' => '5001 0208 1701 5895 0864',
        ]);

        $receipt = UtilityReceipt::where('meter_number', '32118573479')->first();
        $this->assertNotNull($receipt);
        $this->assertEquals('50010208170158950864', $receipt->clean_token_number);
        $this->assertEquals('Rp 1.003.500', $receipt->formatted_total_amount);

        // 2. View details
        $showResponse = $this->actingAs($user)->getJson(route('bos.utilitas.show', $receipt->id));
        $showResponse->assertStatus(200);
        $showResponse->assertJsonFragment(['clean_token' => '50010208170158950864']);

        // 3. Delete receipt
        $deleteResponse = $this->actingAs($user)->deleteJson(route('bos.utilitas.destroy', $receipt->id));
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJsonFragment(['success' => true]);
        $this->assertSoftDeleted('utility_receipts', ['id' => $receipt->id]);
    }

    public function test_ocr_service_parses_token_pln_locally_without_gemini(): void
    {
        Http::preventStrayRequests();

        $rawText = '
            PT POS INDONESIA Kpc. Kras 64172
            STRUK PEMBELIAN LISTRIK PRABAYAR
            NO METER : 32118573479
            NAMA     : SMK PGRI KRAS
            TOTAL RP : RP 1.003.500
            STROOM / TOKEN : 5001 0208 1701 5895 0864
            TGL : 07/07/2026 13:43:38
        ';

        $service = new UtilityReceiptOcrService;
        $result = $service->scan('dummy-path', $rawText, 'local');

        $this->assertTrue($result['success']);
        $this->assertEquals('token_smk_kras', $result['data']['matched_profile_code']);
        $this->assertEquals(1003500, $result['data']['total_amount']);
        $this->assertEquals('5001 0208 1701 5895 0864', $result['data']['token_number']);
        $this->assertEquals('32118573479', $result['data']['meter_number']);
    }

    public function test_ocr_scans_and_matches_atm_transfer_menaksopal_receipt(): void
    {
        Http::preventStrayRequests();

        $rawText = '
            9843-BRI UNIT KRAS (Link)
            RESI : 07205
            TGL : 07/07/26 13:38:18
            REKENING TUJUAN : 3243258444
            NAMA : MENAKSOPAL LINK NUSANTAR
            JUMLAH : RP 1.800.000
            TOTAL : RP 1.800.000
        ';

        $service = new UtilityReceiptOcrService;
        $result = $service->scan('dummy-path', $rawText, 'local');

        $this->assertTrue($result['success']);
        $this->assertEquals('internet_menaksopal', $result['data']['matched_profile_code']);
        $this->assertEquals('3243258444', $result['data']['customer_number']);
        $this->assertEquals('MENAKSOPAL LINK NUSANTAR', $result['data']['customer_name']);
        $this->assertEquals('2026-07-07 13:38:18', $result['data']['transaction_date']);
        $this->assertEquals(2026, $result['data']['fiscal_year']);
        $this->assertEquals('Juli', $result['data']['billing_month']);
        $this->assertEquals(1800000, $result['data']['total_amount']);
    }

    public function test_user_can_batch_upload_utility_receipts_and_queue_is_dispatched(): void
    {
        Queue::fake();
        Storage::fake('public');

        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        $file1 = UploadedFile::fake()->image('struk_listrik1.jpg', 600, 800);
        $file2 = UploadedFile::fake()->image('struk_speedy.jpg', 600, 800);

        $response = $this->actingAs($user)->post(route('bos.utilitas.batch-upload'), [
            'fiscal_year' => 2026,
            'billing_month' => '07',
            'receipt_files' => [$file1, $file2],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'total_uploaded' => 2,
        ]);

        $this->assertDatabaseCount('utility_receipts', 2);
        $this->assertDatabaseHas('utility_receipts', [
            'ocr_status' => 'pending',
            'fiscal_year' => 2026,
            'billing_month' => '07',
        ]);

        Queue::assertPushed(ProcessUtilityReceiptOcrJob::class, 2);
    }

    public function test_background_ocr_job_processes_receipt_and_updates_model(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();

        $fakeImagePath = 'utility_receipts/dummy_receipt.jpg';
        Storage::disk('public')->put($fakeImagePath, 'dummy-image-content');

        $rawText = '
            PT POS INDONESIA Kpc. Kras 64172
            STRUK PEMBELIAN LISTRIK PRABAYAR
            NO METER : 32118573479
            NAMA : SMK PGRI KRAS
            TARIF/DAYA : S1/5500 VA
            JML KWH : 1010.2
            RP BAYAR : RP 909.090
            PPN/PBJT : RP 90.910
            BIAYA ADM : RP 3.500
            TOTAL BAYAR : RP 1.003.500
            STROOM / TOKEN : 5001 0208 1701 5895 0864
            TGL : 07/07/2026 13:43:38
        ';

        $receipt = UtilityReceipt::create([
            'fiscal_year' => 2026,
            'billing_month' => '07',
            'transaction_date' => now(),
            'total_amount' => 0,
            'receipt_file' => $fakeImagePath,
            'ocr_raw_data' => $rawText,
            'ocr_status' => 'pending',
        ]);

        $ocrService = app(UtilityReceiptOcrService::class);
        $job = new ProcessUtilityReceiptOcrJob($receipt);
        $job->handle($ocrService);

        $receipt->refresh();
        $this->assertEquals('completed', $receipt->ocr_status);
        $this->assertEquals('32118573479', $receipt->meter_number);
        $this->assertEquals('5001 0208 1701 5895 0864', $receipt->token_number);
        $this->assertEquals(1003500, $receipt->total_amount);
        $this->assertNotNull($receipt->utility_profile_id);
    }

    public function test_artisan_command_process_pending_utility_receipts(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();

        $fakeImagePath = 'utility_receipts/dummy_speedy.jpg';
        Storage::disk('public')->put($fakeImagePath, 'dummy-image-content');

        $rawText = '
            PT POS INDONESIA
            PEMBAYARAN TAGIHAN TELKOM / SPEEDY
            NO TELP / ID : 0354-000479487
            NAMA : IMAM SJADELI
            TAGIHAN : RP 350.000
            BIAYA ADMIN : RP 2.500
            TOTAL BAYAR : RP 352.500
            TGL : 07/07/2026 10:15:00
        ';

        $receipt = UtilityReceipt::create([
            'fiscal_year' => 2026,
            'billing_month' => '07',
            'transaction_date' => now(),
            'total_amount' => 0,
            'receipt_file' => $fakeImagePath,
            'ocr_raw_data' => $rawText,
            'ocr_status' => 'pending',
        ]);

        $exitCode = Artisan::call('utilitas:process-pending', ['--sync' => true]);
        $this->assertEquals(0, $exitCode);

        $receipt->refresh();
        $this->assertEquals('completed', $receipt->ocr_status);
        $this->assertEquals(352500, $receipt->total_amount);
        $this->assertEquals('IMAM SADELI', $receipt->customer_name);
    }

    public function test_poll_status_endpoint_returns_pending_counts_and_matrix_summary(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        UtilityReceipt::create([
            'fiscal_year' => 2026,
            'billing_month' => '07',
            'transaction_date' => '2026-07-01 10:00:00',
            'total_amount' => 500000,
            'ocr_status' => 'completed',
        ]);

        UtilityReceipt::create([
            'fiscal_year' => 2026,
            'billing_month' => '07',
            'transaction_date' => now(),
            'total_amount' => 0,
            'ocr_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson(route('bos.utilitas.poll-status', [
            'year' => 2026,
            'month' => '07',
        ]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'has_pending' => true,
            'pending_count' => 1,
        ]);
        $this->assertArrayHasKey('matrix', $response->json());
    }

    public function test_retry_ocr_endpoint_resets_receipt_and_dispatches_job(): void
    {
        Queue::fake();
        Storage::fake('public');

        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        $fakeImagePath = 'utility_receipts/failed_receipt.jpg';
        Storage::disk('public')->put($fakeImagePath, 'dummy-content');

        $receipt = UtilityReceipt::create([
            'fiscal_year' => 2026,
            'billing_month' => '07',
            'transaction_date' => now(),
            'total_amount' => 0,
            'receipt_file' => $fakeImagePath,
            'ocr_status' => 'failed',
            'ocr_error_message' => '503 Service Unavailable',
        ]);

        $response = $this->actingAs($user)->postJson(route('bos.utilitas.retry-ocr', $receipt->id));
        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);

        $receipt->refresh();
        $this->assertEquals('pending', $receipt->ocr_status);
        $this->assertNull($receipt->ocr_error_message);

        Queue::assertPushed(ProcessUtilityReceiptOcrJob::class);
    }

    public function test_average_and_projected_annual_calculation_for_all_months_and_all_years(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        $profile = UtilityProfile::where('code', 'token_smk_kras')->first();

        // Buat data pembayaran 3 bulan berbeda
        UtilityReceipt::create([
            'utility_profile_id' => $profile->id,
            'fiscal_year' => 2026,
            'billing_month' => '07',
            'transaction_date' => '2026-07-07 10:00:00',
            'total_amount' => 1000000,
            'ocr_status' => 'completed',
        ]);

        UtilityReceipt::create([
            'utility_profile_id' => $profile->id,
            'fiscal_year' => 2026,
            'billing_month' => '08',
            'transaction_date' => '2026-08-07 10:00:00',
            'total_amount' => 1200000,
            'ocr_status' => 'completed',
        ]);

        UtilityReceipt::create([
            'utility_profile_id' => $profile->id,
            'fiscal_year' => 2026,
            'billing_month' => '09',
            'transaction_date' => '2026-09-07 10:00:00',
            'total_amount' => 1100000,
            'ocr_status' => 'completed',
        ]);

        // 1. Akses halaman index dengan month=all
        $responseAllMonths = $this->actingAs($user)
            ->get(route('bos.utilitas.index', ['year' => 2026, 'month' => 'all']));
        $responseAllMonths->assertStatus(200);
        $responseAllMonths->assertSee('Semua Bulan');
        // Total = 3.300.000, Rata-rata = 1.100.000 (Acuan RKA 12 bln dihilangkan dari kartu status)
        $responseAllMonths->assertSee('1.100.000');
        $responseAllMonths->assertDontSee('Acuan RKA');

        // 2. Akses halaman index dengan year=all & month=all
        $responseAllYears = $this->actingAs($user)
            ->get(route('bos.utilitas.index', ['year' => 'all', 'month' => 'all']));
        $responseAllYears->assertStatus(200);
        $responseAllYears->assertSee('Semua Tahun');

        // 3. Data endpoint Datatables dengan year=all & month=all
        $responseData = $this->actingAs($user)
            ->getJson(route('bos.utilitas.data', ['year' => 'all', 'month' => 'all']));
        $responseData->assertStatus(200);
        $this->assertEquals(3, $responseData->json('recordsTotal'));

        // 4. Poll status endpoint dengan year=all & month=all
        $responsePoll = $this->actingAs($user)
            ->getJson(route('bos.utilitas.poll-status', ['year' => 'all', 'month' => 'all']));
        $responsePoll->assertStatus(200);
        $responsePoll->assertJsonFragment(['success' => true]);
        $this->assertEquals(3300000, $responsePoll->json('matrix.month_total_raw'));
    }

    public function test_local_parser_extracts_token_smk_kras_from_pos_receipt_text(): void
    {
        $rawText = '
            PT POS INDONESIA (PERSERO)
            Kpc. Kras 64172
            BUKTI PEMBELIAN LISTRIK PRABAYAR
            NO. RESI : 64172-03/2026/805315
            TGL TRANS : 07/07/26 13:43:38
            NO. METER : 32118573479
            IDPEL     : 514031897945
            NAMA      : SMK PGRI KRAS
            TARIF/DAYA: S1/5500 VA
            JML KWH   : 1010.2
            RP TOKEN  : RP 909.090
            PBJT-TL   : RP 90.910
            ADMIN POS : RP 3.500
            TOTAL RP  : RP 1.003.500
            STROOM / TOKEN : 5001 0208 1701 5895 0864
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('local_regex_parser', $result['engine']);
        $this->assertEquals('token_smk_kras', $result['data']['matched_profile_code']);
        $this->assertEquals('32118573479', $result['data']['meter_number']);
        $this->assertEquals('5001 0208 1701 5895 0864', $result['data']['token_number']);
        $this->assertEquals(1003500, $result['data']['total_amount']);
        $this->assertEquals(909090, $result['data']['base_amount']);
        $this->assertEquals(3500, $result['data']['admin_fee']);
        $this->assertEquals(90910, $result['data']['tax_pbjt']);
        $this->assertEquals('2026-07-07 13:43:38', $result['data']['transaction_date']);
    }

    public function test_local_parser_extracts_internet_menaksopal_from_atm_transfer_text(): void
    {
        $rawText = '
            9843-BRI UNIT KRAS
            ATM ID : 91461
            TGL : 07/07/26  13:38:18
            NO. REF : 07205
            TRANSFER KE : BCA
            REKENING : 3243258444
            NAMA : MENAKSOPAL LINK NUSANTAR
            JUMLAH : RP 1.800.000
            TOTAL : RP 1.800.000
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('internet_menaksopal', $result['data']['matched_profile_code']);
        $this->assertEquals('3243258444', $result['data']['customer_number']);
        $this->assertEquals('MENAKSOPAL LINK NUSANTAR', $result['data']['customer_name']);
        $this->assertEquals(1800000, $result['data']['total_amount']);
        $this->assertEquals('2026-07-07 13:38:18', $result['data']['transaction_date']);
    }

    public function test_local_parser_extracts_postpaid_pln_stm_from_receipt_text(): void
    {
        $rawText = '
            PT POS INDONESIA
            STRUK PEMBAYARAN TAGIHAN LISTRIK
            IDPEL : 514031084530
            NAMA  : STM
            TARIF : S1/5500 VA
            BL/TH : JUL26
            TAGIHAN PLN : RP 450.000
            ADMIN POS   : RP 3.000
            TOTAL BAYAR : RP 453.000
            TGL : 05/07/2026 09:12:00
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('listrik_stm', $result['data']['matched_profile_code']);
        $this->assertEquals('514031084530', $result['data']['customer_number']);
        $this->assertEquals(453000, $result['data']['total_amount']);
        $this->assertEquals(3000, $result['data']['admin_fee']);
    }

    public function test_local_parser_extracts_telkom_speedy_from_standard_pos_receipt_template(): void
    {
        $rawText = '
            POS
            IND
            Logistik Indonedsia
            PT PTOS INDONESIA (PERSERO)
            Kpc. Kras 64172

            TANDA TERIMA
            TELKOM SPEEDY
            ...........
            Tanggal : 07/07/2026 13:42:31
            No Resi : 64172-03/2026/805310
            Petugas : 805310
            ...........
            NOMOR TELEPON : 0354-004740750
            NAMA PELANGGA : SMK PGRI KRAS
            DIVRE : 5
            DATEL : KDR
            BULAN JUL26 : 
            TAGIHAN : 421.800
            ADMIN : 2.500
            JUMLAH TAGIHAN : 424.300
            BUKTI PEMBAYARAN INI ADALAH SAH JIKA SUDAH DIBUBUHI CAP TANGGAL POS 
            NPWP : 00.000.000.0-000.000
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('local_regex_parser', $result['engine']);
        $this->assertEquals('telkom_speedy', $result['data']['utility_type']);
        $this->assertEquals('speedy_smk_kras', $result['data']['matched_profile_code']);
        $this->assertEquals('0354-004740750', $result['data']['customer_number']);
        $this->assertEquals('SMK PGRI KRAS', $result['data']['customer_name']);
        $this->assertEquals('64172-03/2026/805310', $result['data']['receipt_number']);
        $this->assertEquals('805310', $result['data']['reference_number']);
        $this->assertEquals(424300, $result['data']['total_amount']);
        $this->assertEquals(421800, $result['data']['base_amount']);
        $this->assertEquals(2500, $result['data']['admin_fee']);
        $this->assertEquals('Juli', $result['data']['billing_month']);
        $this->assertEquals(2026, $result['data']['fiscal_year']);
        $this->assertStringContainsString('Kras 64172', $result['data']['payment_merchant']);
    }

    public function test_ocr_service_dual_engine_uses_local_parser_and_avoids_gemini_api(): void
    {
        // Pastikan tidak ada request HTTP ke Gemini sama sekali saat rawText cocok secara lokal
        Http::preventStrayRequests();

        $service = app(UtilityReceiptOcrService::class);

        $rawText = '
            PT POS INDONESIA
            BUKTI PEMBELIAN LISTRIK PRABAYAR
            NO. METER : 32118573479
            NAMA : SMK PGRI KRAS
            TOTAL RP : RP 1.003.500
            STROOM : 5001 0208 1701 5895 0864
            TGL : 07/07/2026 13:43:00
        ';

        $result = $service->scan('dummy-image-path', $rawText, 'auto');

        $this->assertTrue($result['success']);
        $this->assertEquals('local_regex_parser', $result['engine']);
        $this->assertEquals('token_smk_kras', $result['data']['matched_profile_code']);
        $this->assertEquals(1003500, $result['data']['total_amount']);
    }

    public function test_scan_endpoint_with_local_ocr_raw_text(): void
    {
        Http::preventStrayRequests();

        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        $rawText = '
            ATM BRI UNIT KRAS
            TGL : 07/07/26 13:38:18
            REKENING : 3243258444
            NAMA : MENAKSOPAL LINK NUSANTAR
            TOTAL : RP 1.800.000
        ';

        $response = $this->actingAs($user)->postJson(route('bos.utilitas.scan-receipt'), [
            'raw_text' => $rawText,
            'engine' => 'local',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'engine' => 'local_regex_parser',
            'matched_profile_code' => 'internet_menaksopal',
        ]);
    }

    public function test_duplicate_receipt_detection_prevents_double_store(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        $postData = [
            'fiscal_year' => 2026,
            'billing_month' => 'Februari',
            'transaction_date' => '2026-02-05 10:00:00',
            'receipt_number' => 'RESI-DUP-001',
            'total_amount' => 500000,
            'token_number' => '1111 2222 3333 4444 5555',
        ];

        // First store succeeds
        $first = $this->actingAs($user)->postJson(route('bos.utilitas.store'), $postData);
        $first->assertStatus(200);
        $first->assertJsonFragment(['success' => true]);

        // Second store with identical token/receipt should be rejected as 422 duplicate
        $second = $this->actingAs($user)->postJson(route('bos.utilitas.store'), $postData);
        $second->assertStatus(422);
        $second->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('DUPLIKAT', $second->json('message'));
    }

    public function test_scan_receipt_flags_duplicate_when_already_exists_in_db(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        $profile = UtilityProfile::where('code', 'internet_menaksopal')->first();

        UtilityReceipt::create([
            'utility_profile_id' => $profile ? $profile->id : null,
            'fiscal_year' => 2026,
            'billing_month' => 'Maret',
            'transaction_date' => '2026-03-05 11:00:00',
            'receipt_number' => 'RESI-MAR-009',
            'total_amount' => 1800000,
            'customer_number' => '3243258444',
            'ocr_status' => 'completed',
        ]);

        $rawText = '
            ATM BRI UNIT KRAS
            TGL : 05/03/2026 11:00:00
            NO RESI : RESI-MAR-009
            REKENING : 3243258444
            NAMA : MENAKSOPAL LINK NUSANTAR
            TOTAL : RP 1.800.000
        ';

        $response = $this->actingAs($user)->postJson(route('bos.utilitas.scan-receipt'), [
            'raw_text' => $rawText,
            'engine' => 'local',
        ]);

        $response->assertStatus(200);
        $this->assertArrayHasKey('duplicate', $response->json());
        $this->assertEquals('Maret', $response->json('duplicate.billing_month'));
    }

    public function test_it_renders_index_page(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_BENDAHARA_BOS,
        ]);

        $response = $this->actingAs($user)->get(route('bos.utilitas.index'));
        $response->assertStatus(200);
        $response->assertSee('Struk Utilitas');
        $response->assertSee('Upload Struk OCR');
        $response->assertSee('Upload Batch');
    }

    public function test_it_returns_datatable_json(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_BENDAHARA_BOS,
        ]);

        $response = $this->actingAs($user)->getJson(route('bos.utilitas.data'));
        $response->assertStatus(200);
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_rendered_index_page_scripts_syntax(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_BENDAHARA_BOS,
        ]);

        $response = $this->actingAs($user)->get(route('bos.utilitas.index'));
        $content = $response->getContent();

        // Extract scripts
        preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $content, $matches);
        $this->assertNotEmpty($matches[1]);

        $tempJs = storage_path('framework/testing/test_rendered_script.js');
        if (! is_dir(dirname($tempJs))) {
            mkdir(dirname($tempJs), 0777, true);
        }

        $allScripts = '';
        foreach ($matches[1] as $idx => $script) {
            $trimmed = trim($script);
            if (! empty($trimmed)) {
                $allScripts .= "\n// --- SCRIPT {$idx} ---\n".$trimmed."\n";
            }
        }

        file_put_contents($tempJs, $allScripts);
        $output = [];
        $returnCode = 0;
        exec("node --check \"{$tempJs}\" 2>&1", $output, $returnCode);

        $this->assertEquals(0, $returnCode, "JavaScript syntax error in rendered index page:\n".implode("\n", $output));
    }

    public function test_local_parser_disambiguates_ocr_errors_in_prabayar_token_and_meter(): void
    {
        // Struk PLN Prabayar dengan karakter OCR khas thermal (5 jadi S, 0 jadi O, 1 jadi I)
        $rawText = '
            PT POS INDONESIA
            BUKTI PEMBELIAN LISTRIK PRABAYAR
            NO METER : 32118S73479
            IDPEL    : 514O31897945
            NAMA     : SMK PGR1 KRAS
            TARIF/DAYA : S1/550O VA
            RUPIAH TOKEN : RP 9O9.O9O
            ADMIN BANK   : RP 3.SOO
            TOTAL BAYAR  : RP 1.OO3.SOO
            STROOM/TOKEN : 5OO1-O2O8-I7O1-5895-O864
            JML KWH      : 30,4 KWH
            TGL : 07/07/26 13:43:38
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('listrik_pra', $result['data']['utility_type']);
        $this->assertEquals('token_smk_kras', $result['data']['matched_profile_code']);
        $this->assertEquals('32118573479', $result['data']['meter_number']);
        $this->assertEquals('5001 0208 1701 5895 0864', $result['data']['token_number']);
        $this->assertEquals(1003500, $result['data']['total_amount']);
        $this->assertEquals(909090, $result['data']['base_amount']);
        $this->assertEquals(3500, $result['data']['admin_fee']);
        $this->assertEquals(30.4, $result['data']['kwh_amount']);
        $this->assertEquals('2026-07-07 13:43:38', $result['data']['transaction_date']);
    }

    public function test_local_parser_enforces_pascabayar_structure_and_null_token(): void
    {
        // Struk PLN Pascabayar: Wajib TIDAK ADA TOKEN meskipun ada barcode/resi 20 digit
        $rawText = '
            PT POS INDONESIA
            STRUK PEMBAYARAN TAGIHAN LISTRIK
            IDPEL : 514O31O8453O
            NAMA  : STM
            TARIF : S1/5500 VA
            BL/TH : JUL26
            STAND METER : O1452O-O1563O
            TAGIHAN PLN : RP 4SO.OOO
            ADMIN POS   : RP 3.OOO
            TOTAL BAYAR : RP 453.OOO
            BARCODE NO  : 12345678901234567890
            TGL : 05/07/2026 09:12:00
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('listrik_pasca', $result['data']['utility_type']);
        $this->assertEquals('listrik_stm', $result['data']['matched_profile_code']);
        $this->assertEquals('514031084530', $result['data']['customer_number']);
        $this->assertNull($result['data']['token_number'], 'PLN Pascabayar tidak boleh memiliki token');
        $this->assertEquals('Juli', $result['data']['billing_month']);
        $this->assertEquals(2026, $result['data']['fiscal_year']);
        $this->assertEquals('14520-15630', $result['data']['stand_meter']);
        $this->assertEquals(1110.0, $result['data']['kwh_amount']);
        $this->assertEquals(453000, $result['data']['total_amount']);
        $this->assertEquals(450000, $result['data']['base_amount']);
        $this->assertEquals(3000, $result['data']['admin_fee']);
    }

    public function test_template_1_atm_transfer_menaksopal_internet(): void
    {
        $rawText = '
            TANGGAL : 10/07/2026        WAKTU   : 10:15:22 
            ATM ID  : 07205             NO.REF  : 2026071000984
            LOKASI  : ATM BRI UNIT KRAS
            NO.KARTU : 522184******1234

            TRANSFER ATM

            DARI
            BANK    : BANK BRI
            NAMA    : BENDAHARA BOSP
            KEPADA 
            BANK    : BANK BCA
            NAMA    : MENAKSOPAL LINK NUSANTAR
            REKENING: 3243258444
            NO REF  : 2026071000984

            JUMLAH  : RP 1.500.000
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('internet', $result['data']['utility_type']);
        $this->assertEquals('internet_menaksopal', $result['data']['matched_profile_code']);
        $this->assertEquals('MENAKSOPAL LINK NUSANTAR', $result['data']['customer_name']);
        $this->assertEquals('3243258444', $result['data']['customer_number']);
        $this->assertEquals(1500000, $result['data']['total_amount']);
        $this->assertNull($result['data']['token_number']);
        $this->assertEquals('2026-07-10 10:15:22', $result['data']['transaction_date']);
        $this->assertEquals('2026071000984', $result['data']['reference_number']);
    }

    public function test_template_2_telkom_speedy_smk_pgri_kras(): void
    {
        $rawText = '
            PT PTOS INDONESIA (PERSERO)
            Kpc. Kras 64172

            TANDA TERIMA
            TELKOM SPEEDY
            ------------
            Tanggal : 07/07/2026 13:42:31
            No Resi : 64172-03/2026/805310
            Petugas : 805310
            ------------
            NOMOR TELEPON   : 0354-004740750
            NAMA PELANGGAN  : SMK PGRI KRAS
            DIVRE       : 5
            DATEL       : KDR
            BULAN JUL26     : 
            TAGIHAN     : RP. 421.800
            ADMIN       : RP. 2.500
            JUMLAH TAGIHAN  : RP. 424.300
            BUKTI PEMBAYARAN INI ADALAH SAH JIKA SUDAH DIBUBUHI CAP TANGGAL POS 
            NPWP : 00.000.000.0-000.000
            ------------
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('telkom_speedy', $result['data']['utility_type']);
        $this->assertEquals('speedy_smk_kras', $result['data']['matched_profile_code']);
        $this->assertEquals('SMK PGRI KRAS', $result['data']['customer_name']);
        $this->assertEquals('0354-004740750', $result['data']['customer_number']);
        $this->assertEquals(424300, $result['data']['total_amount']);
        $this->assertEquals(421800, $result['data']['base_amount']);
        $this->assertEquals(2500, $result['data']['admin_fee']);
        $this->assertNull($result['data']['token_number']);
        $this->assertEquals('Juli', $result['data']['billing_month']);
        $this->assertEquals(2026, $result['data']['fiscal_year']);
    }

    public function test_template_3_telkom_speedy_imam_sadeli(): void
    {
        $rawText = '
            PT PTOS INDONESIA (PERSERO)
            Kpc. Kras 64172

            TANDA TERIMA
            TELKOM SPEEDY
            ------------
            Tanggal : 08/07/2026 10:11:05
            No Resi : 64172-03/2026/805312
            Petugas : 805312
            ------------
            NOMOR TELEPON : 0354-000479487
            NAMA PELANGGAN : IMAM SADELI
            DIVRE       : 5
            DATEL       : KDR
            BULAN JUL26     : 
            TAGIHAN     : RP. 382.500
            ADMIN       : RP. 2.500
            JUMLAH TAGIHAN  : RP. 385.000
            BUKTI PEMBAYARAN INI ADALAH SAH JIKA SUDAH DIBUBUHI CAP TANGGAL POS 
            NPWP :
            ------------
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('telkom_speedy', $result['data']['utility_type']);
        $this->assertEquals('speedy_imam', $result['data']['matched_profile_code']);
        $this->assertEquals('IMAM SADELI', $result['data']['customer_name']);
        $this->assertEquals('0354-000479487', $result['data']['customer_number']);
        $this->assertEquals(385000, $result['data']['total_amount']);
        $this->assertEquals(382500, $result['data']['base_amount']);
        $this->assertEquals(2500, $result['data']['admin_fee']);
        $this->assertNull($result['data']['token_number']);
    }

    public function test_template_4_listrik_postpaid_stm(): void
    {
        $rawText = '
            PT PTOS INDONESIA (PERSERO)
            Kpc. Kras 64172

            TANDA TERIMA
            TAGIHAN PLN POSTPAID
            ------------
            Tanggal : 05/07/2026 09:12:00
            No Resi : 64172-03/2026/805112
            Petugas : 805112
            ------------
            STRUK PEMBAYARAN TAGIHAN LISTRIK
            IDPEL       : 514031084530
            NAMA        : STM
            TARIF/DAYA  : S1/16500 VA
            BL/TH       : JUL26
            STAND METER     : 014520-015630
            RP TAG PLN  : RP 850.000
            NO REF      : 
            PLN menyatakan struk ini sebagai bukti pembayaran yang sah.
            ADMIN POS   : RP 3.000
            TOTAL BAYAR     : RP 853.000
            ------------
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('listrik_pasca', $result['data']['utility_type']);
        $this->assertEquals('listrik_stm', $result['data']['matched_profile_code']);
        $this->assertEquals('STM', $result['data']['customer_name']);
        $this->assertEquals('514031084530', $result['data']['customer_number']);
        $this->assertEquals('S1/16500 VA', $result['data']['tariff_power']);
        $this->assertEquals(853000, $result['data']['total_amount']);
        $this->assertEquals(850000, $result['data']['base_amount']);
        $this->assertEquals(3000, $result['data']['admin_fee']);
        $this->assertNull($result['data']['token_number']);
        $this->assertEquals('Juli', $result['data']['billing_month']);
        $this->assertEquals(2026, $result['data']['fiscal_year']);
    }

    public function test_template_5_listrik_postpaid_masjid_an_nur(): void
    {
        $rawText = '
            PT PTOS INDONESIA (PERSERO)
            Kpc. Kras 64172

            TANDA TERIMA
            TAGIHAN PLN POSTPAID
            ------------
            Tanggal : 06/07/2026 08:30:15
            No Resi : 64172-03/2026/805118
            Petugas : 805118
            ------------
            STRUK PEMBAYARAN TAGIHAN LISTRIK
            IDPEL       : 514030561937
            NAMA        : MASJID AN NUR
            TARIF/DAYA  : R1/450 VA
            BL/TH       : JUL26
            STAND METER     : 001200-001280
            RP TAG PLN  : RP 45.000
            NO REF      : 
            PLN menyatakan struk ini sebagai bukti pembayaran yang sah.
            ADMIN POS   : RP 3.000
            TOTAL BAYAR     : RP 48.000
            ------------
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('listrik_pasca', $result['data']['utility_type']);
        $this->assertEquals('listrik_masjid', $result['data']['matched_profile_code']);
        $this->assertEquals('MASJID AN NUR', $result['data']['customer_name']);
        $this->assertEquals('514030561937', $result['data']['customer_number']);
        $this->assertEquals('R1/450 VA', $result['data']['tariff_power']);
        $this->assertEquals(48000, $result['data']['total_amount']);
        $this->assertNull($result['data']['token_number']);
    }

    public function test_template_6_listrik_postpaid_smp_pgri(): void
    {
        $rawText = '
            PT PTOS INDONESIA (PERSERO)
            Kpc. Kras 64172

            TANDA TERIMA
            TAGIHAN PLN POSTPAID
            ------------
            Tanggal : 06/07/2026 08:35:00
            No Resi : 64172-03/2026/805119
            Petugas : 805119
            ------------
            STRUK PEMBAYARAN TAGIHAN LISTRIK
            IDPEL       : 514030561945
            NAMA        : SMP PGRI
            TARIF/DAYA  : S1/900 VA
            BL/TH       : JUL26
            STAND METER     : 004500-004650
            RP TAG PLN  : RP 122.000
            NO REF      : 
            PLN menyatakan struk ini sebagai bukti pembayaran yang sah.
            ADMIN POS   : RP 3.000
            TOTAL BAYAR     : RP 125.000
            ------------
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('listrik_pasca', $result['data']['utility_type']);
        $this->assertEquals('listrik_smp', $result['data']['matched_profile_code']);
        $this->assertEquals('SMP PGRI', $result['data']['customer_name']);
        $this->assertEquals('514030561945', $result['data']['customer_number']);
        $this->assertEquals('S1/900 VA', $result['data']['tariff_power']);
        $this->assertEquals(125000, $result['data']['total_amount']);
        $this->assertNull($result['data']['token_number']);
    }

    public function test_template_7_listrik_prepaid_stm_pgri(): void
    {
        $rawText = '
            PT PTOS INDONESIA (PERSERO)
            Kpc. Kras 64172

            TANDA TERIMA
            TAGIHAN PLN PREPAID
            ------------
            Tanggal : 07/07/2026 11:20:00
            No Resi : 64172-03/2026/805220
            Petugas : 805220
            ------------
            STRUK PEMBELIAN LISTRIK PRABAYAR
            NO METER    : 45038124785
            IDPEL       : 514031736766
            NAMA        : STM PGRI
            TARIF/DAYA  : S1/5500 VA
            NO REF      : 

            RP BAYAR    : RP. 1.003.500
            METERAI     : RP. 0
            PPN         : RP. 0
            PBJT-TL     : RP. 90.910
            ANGSURAN    : RP. 0
            RP STROOM/TOKEN : RP. 909.090
            JML KWH     : 30.4
            ADMIN POS   : RP. 3.500
            STROOM / TOKEN  : 5001 0208 1701 5895 0864
            ------------
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('listrik_pra', $result['data']['utility_type']);
        $this->assertEquals('token_stm_pgri', $result['data']['matched_profile_code']);
        $this->assertEquals('STM PGRI', $result['data']['customer_name']);
        $this->assertEquals('45038124785', $result['data']['meter_number']);
        $this->assertEquals('514031736766', $result['data']['customer_number']);
        $this->assertEquals('S1/5500 VA', $result['data']['tariff_power']);
        $this->assertEquals('5001 0208 1701 5895 0864', $result['data']['token_number']);
        $this->assertEquals(1003500, $result['data']['total_amount']);
        $this->assertEquals(909090, $result['data']['base_amount']);
        $this->assertEquals(90910, $result['data']['tax_pbjt']);
        $this->assertEquals(3500, $result['data']['admin_fee']);
        $this->assertEquals(30.4, $result['data']['kwh_amount']);
    }

    public function test_template_8_listrik_prepaid_smk_pgri_kras(): void
    {
        $rawText = '
            PT PTOS INDONESIA (PERSERO)
            Kpc. Kras 64172

            TANDA TERIMA
            TAGIHAN PLN PREPAID
            ------------
            Tanggal : 07/07/2026 13:43:38
            No Resi : 64172-03/2026/805315
            Petugas : 805315
            ------------
            STRUK PEMBELIAN LISTRIK PRABAYAR
            NO METER    : 32118573479
            IDPEL       : 514031897945
            NAMA        : SMK PGRI KRAS
            TARIF/DAYA  : S1/5500 VA
            NO REF      : 

            RP BAYAR    : RP. 2.003.500
            METERAI     : RP. 0
            PPN         : RP. 0
            PBJT-TL     : RP. 181.820
            ANGSURAN    : RP. 0
            RP STROOM/TOKEN : RP. 1.818.180
            JML KWH     : 60.8
            ADMIN POS   : RP. 3.500
            STROOM / TOKEN  : 1234 5678 9012 3456 7890
            ------------
        ';

        $localParser = new UtilityReceiptLocalParserService;
        $result = $localParser->parse($rawText);

        $this->assertTrue($result['success']);
        $this->assertEquals('listrik_pra', $result['data']['utility_type']);
        $this->assertEquals('token_smk_kras', $result['data']['matched_profile_code']);
        $this->assertEquals('SMK PGRI KRAS', $result['data']['customer_name']);
        $this->assertEquals('32118573479', $result['data']['meter_number']);
        $this->assertEquals('514031897945', $result['data']['customer_number']);
        $this->assertEquals('S1/5500 VA', $result['data']['tariff_power']);
        $this->assertEquals('1234 5678 9012 3456 7890', $result['data']['token_number']);
        $this->assertEquals(2003500, $result['data']['total_amount']);
        $this->assertEquals(1818180, $result['data']['base_amount']);
        $this->assertEquals(181820, $result['data']['tax_pbjt']);
        $this->assertEquals(3500, $result['data']['admin_fee']);
        $this->assertEquals(60.8, $result['data']['kwh_amount']);
        $this->assertTrue($result['data']['is_amount_verified']);
    }

    public function test_pln_prabayar_rp_bayar_is_strictly_total_amount(): void
    {
        $localParser = new UtilityReceiptLocalParserService;

        // Kasus 1: Struk Asli PT POS Indonesia (RP BAYAR: 503.500, Admin: 3.500, Pokok: 454.545, PBJT: 45.455)
        $rawTextPos = '
            PT POS INDONESIA (PERSERO) Kpc. Kras 64172
            TANDA TERIMA TAGIHAN PLN PREPAID
            Tanggal : 07-07-2026 13:43:57
            STRUK PEMBELIAN LISTRIK PRABAYAR
            NO METER : 45038124785
            IDPEL : 514031736766
            NAMA : STM PGRI
            TARIF/DAYA : S1/5500 VA
            RP BAYAR : RP. 503.500
            METERAI : RP. 0,00
            PPN : RP. 0,00
            PBJT-TL : RP. 45.455,00
            RP STROOM/TOKEN : RP. 454.545,00
            ADMIN POS : RP. 3.500
            STROOM / TOKEN : 6916 1521 8654 5119 2117
        ';

        $res1 = $localParser->parse($rawTextPos);
        $this->assertEquals(503500, $res1['data']['total_amount'], 'RP BAYAR harus menjadi total_amount, TIDAK ditambahkan admin lagi!');
        $this->assertEquals(454545, $res1['data']['base_amount']);
        $this->assertEquals(3500, $res1['data']['admin_fee']);
        $this->assertEquals(45455, $res1['data']['tax_pbjt']);
        $this->assertTrue($res1['data']['is_amount_verified']);

        // Kasus 2: Struk Token Prabayar dengan RP BAYAR bulat (500.000)
        $rawTextBulat = '
            STRUK PEMBELIAN TOKEN LISTRIK PRABAYAR
            NO METER : 32118573479
            IDPEL : 514031897945
            NAMA : SMK PGRI KRAS
            RP BAYAR : RP. 500.000
            ADMIN : RP. 3.000
            PBJT-TL : RP. 45.182
            RP STROOM : RP. 451.818
            TOKEN : 1234 5678 9012 3456 7890
        ';

        $res2 = $localParser->parse($rawTextBulat);
        $this->assertEquals(500000, $res2['data']['total_amount'], 'RP BAYAR 500.000 adalah total bayar, bukan 503.000');
        $this->assertEquals(451818, $res2['data']['base_amount']);
        $this->assertEquals(3000, $res2['data']['admin_fee']);
        $this->assertEquals(45182, $res2['data']['tax_pbjt']);
        $this->assertTrue($res2['data']['is_amount_verified']);
    }

    public function test_amount_verification_and_reconciliation()
    {
        $localParser = new UtilityReceiptLocalParserService;

        // Kasus 1: Sesuai (Tagihan 750.000 + Admin 3.000 = Total 753.000)
        $textMatching = '
            TANDA TERIMA TELKOM SPEEDY
            TAGIHAN : RP. 750.000
            ADMIN : RP. 3.000
            JUMLAH TAGIHAN : RP. 753.000
        ';
        $res1 = $localParser->parse($textMatching);
        $this->assertTrue($res1['data']['is_amount_verified']);
        $this->assertEquals(753000, $res1['data']['total_amount']);
        $this->assertEquals(753000, $res1['data']['expected_total']);
        $this->assertEquals(0, $res1['data']['amount_discrepancy']);

        // Kasus 2: Selisih (Total di struk tertulis berbeda dengan Tagihan + Admin)
        $textMismatch = '
            TANDA TERIMA TELKOM SPEEDY
            TAGIHAN : RP. 500.000
            ADMIN : RP. 3.000
            JUMLAH DIBAYAR : RP. 510.000
        ';
        $res2 = $localParser->parse($textMismatch);
        $this->assertFalse($res2['data']['is_amount_verified']);
        $this->assertEquals(510000, $res2['data']['total_amount']);
        $this->assertEquals(503000, $res2['data']['expected_total']);
        $this->assertEquals(7000, $res2['data']['amount_discrepancy']);
    }

    public function test_store_and_update_with_formatted_thousand_separators(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        $profile = UtilityProfile::first();

        // 1. Store with dot thousand separators in string format
        $postData = [
            'utility_profile_id' => $profile->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Agustus',
            'transaction_date' => '2026-08-01 10:00:00',
            'customer_name' => 'SMK PGRI KRAS',
            'base_amount' => '1.500.000',
            'admin_fee' => '3.500',
            'tax_pbjt' => '150.000',
            'total_amount' => '1.653.500',
            'token_number' => '1234-5678-9012-3456-7890',
            'kwh_amount' => '120,5',
        ];

        $storeResponse = $this->actingAs($user)->postJson(route('bos.utilitas.store'), $postData);
        $storeResponse->assertStatus(200);
        $storeResponse->assertJsonFragment(['success' => true]);

        $receipt = UtilityReceipt::where('billing_month', 'Agustus')->where('fiscal_year', 2026)->first();
        $this->assertNotNull($receipt);
        $this->assertEquals(1500000, (float) $receipt->base_amount);
        $this->assertEquals(3500, (float) $receipt->admin_fee);
        $this->assertEquals(150000, (float) $receipt->tax_pbjt);
        $this->assertEquals(1653500, (float) $receipt->total_amount);
        $this->assertEquals(120.5, (float) $receipt->kwh_amount);
        $this->assertEquals('12345678901234567890', $receipt->clean_token_number);

        // 2. Update with formatted separators
        $updateData = [
            'utility_profile_id' => $profile->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Agustus',
            'transaction_date' => '2026-08-01 10:00:00',
            'customer_name' => 'SMK PGRI KRAS (Updated)',
            'base_amount' => '2.000.000',
            'admin_fee' => '5.000',
            'tax_pbjt' => '200.000',
            'total_amount' => '2.205.000',
            'token_number' => '9999-8888-7777-6666-5555',
            'kwh_amount' => '155.75',
        ];

        $updateResponse = $this->actingAs($user)->putJson(route('utility-receipts.update', $receipt->id), $updateData);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonFragment(['success' => true]);

        $receipt->refresh();
        $this->assertEquals(2000000, (float) $receipt->base_amount);
        $this->assertEquals(5000, (float) $receipt->admin_fee);
        $this->assertEquals(200000, (float) $receipt->tax_pbjt);
        $this->assertEquals(2205000, (float) $receipt->total_amount);
        $this->assertEquals(155.75, (float) $receipt->kwh_amount);
        $this->assertEquals('99998888777766665555', $receipt->clean_token_number);
    }

    public function test_edit_receipt_can_update_bos_kertas_kerja_item(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        $profile = UtilityProfile::where('is_budgeted', true)->first();

        $receipt = UtilityReceipt::create([
            'utility_profile_id' => $profile->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Agustus',
            'transaction_date' => '2026-08-01 10:00:00',
            'customer_name' => 'SMK PGRI KRAS',
            'base_amount' => 500000,
            'admin_fee' => 3500,
            'tax_pbjt' => 0,
            'total_amount' => 503500,
            'bos_kertas_kerja_item_id' => null,
        ]);

        $kkFile = BosKertasKerjaFile::create([
            'year' => 2026,
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Agustus',
            'kegiatan' => 'Pembayaran daya listrik',
            'program_code' => '07.06.01.',
            'description' => 'Tarif Listrik Golongan Rumah Tangga',
            'total_amount' => 503500,
            'price' => 503500,
            'volume' => 1,
        ]);

        $updateData = [
            'utility_profile_id' => $profile->id,
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Agustus',
            'transaction_date' => '2026-08-01 10:00:00',
            'customer_name' => 'SMK PGRI KRAS',
            'base_amount' => '500.000',
            'admin_fee' => '3.500',
            'tax_pbjt' => '0',
            'total_amount' => '503.500',
        ];

        $response = $this->actingAs($user)->put(route('bos.utilitas.update', $receipt->id), $updateData);
        $response->assertStatus(200);

        $receipt->refresh();
        $this->assertEquals($kkItem->id, $receipt->bos_kertas_kerja_item_id);
    }

    public function test_table_displays_remaining_budget_and_over_budget_warning_for_linked_kertas_kerja(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Bendahara BOSP',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_BENDAHARA_BOS,
        ]);

        $profile = UtilityProfile::where('code', 'listrik_stm')->first();
        if (! $profile) {
            $profile = UtilityProfile::create([
                'code' => 'listrik_stm',
                'name' => 'Pembayaran Listrik STM',
                'category' => 'listrik_pasca',
                'customer_name' => 'STM',
                'is_budgeted' => true,
                'is_active' => true,
            ]);
        }

        $kkFile = BosKertasKerjaFile::create([
            'year' => 2026,
            'is_active' => true,
        ]);

        // Alokasi Jan: 500.000, Feb: 500.000 (Total s.d. Feb: 1.000.000)
        BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Januari',
            'kegiatan' => 'Pembayaran daya listrik',
            'program_code' => '07.06.01.',
            'description' => 'Listrik Kampus STM',
            'utility_profile_id' => $profile->id,
            'total_amount' => 500000,
            'price' => 500000,
            'volume' => 1,
        ]);

        $kkFeb = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Februari',
            'kegiatan' => 'Pembayaran daya listrik',
            'program_code' => '07.06.01.',
            'description' => 'Listrik Kampus STM',
            'utility_profile_id' => $profile->id,
            'total_amount' => 500000,
            'price' => 500000,
            'volume' => 1,
        ]);

        // Kasus 1: Struk dengan pembayaran 1.200.000 (melebihi anggaran s.d. Feb sebesar 200.000)
        $overReceipt = UtilityReceipt::create([
            'utility_profile_id' => $profile->id,
            'bos_kertas_kerja_item_id' => $kkFeb->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Februari',
            'transaction_date' => '2026-02-15 10:00:00',
            'total_amount' => 1200000,
            'ocr_status' => 'completed',
        ]);

        $response = $this->actingAs($user)->getJson(route('bos.utilitas.data', [
            'year' => 2026,
            'month' => '02',
            'profile_id' => $profile->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $rowHtml = $data[0]['token_display'];
        $this->assertStringContainsString('Over:', $rowHtml);
        $this->assertStringContainsString('-Rp 200.000', $rowHtml);

        // Kasus 2: Update struk menjadi 800.000 (masih aman dengan sisa anggaran 200.000)
        $overReceipt->update(['total_amount' => 800000]);

        $responseSafe = $this->actingAs($user)->getJson(route('bos.utilitas.data', [
            'year' => 2026,
            'month' => '02',
            'profile_id' => $profile->id,
        ]));

        $dataSafe = $responseSafe->json('data');
        $rowHtmlSafe = $dataSafe[0]['token_display'];
        $this->assertStringContainsString('Sisa Anggaran:', $rowHtmlSafe);
        $this->assertStringContainsString('Rp 200.000', $rowHtmlSafe);
    }
}
