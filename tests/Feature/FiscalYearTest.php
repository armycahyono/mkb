<?php

namespace Tests\Feature;

use App\Helpers\FiscalYearHelper;
use App\Models\BosRencanaBelanja;
use App\Models\KasTunai;
use App\Models\PencairanBank;
use App\Models\Perjadin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FiscalYearTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTablesExist();
    }

    public function test_default_fiscal_year_is_initialized_in_session(): void
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        $this->assertEquals((int) date('Y'), session('fiscal_year'));
        $this->assertEquals((int) date('Y'), FiscalYearHelper::getActiveYear());
    }

    public function test_user_can_switch_fiscal_year_via_endpoint(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Switch via standard POST
        $res = $this->actingAs($user)->post(route('fiscal-year.switch'), [
            'year' => 2025,
        ]);
        $res->assertRedirect();
        $this->assertEquals(2025, session('fiscal_year'));
        $this->assertEquals(2025, FiscalYearHelper::getActiveYear());
        $this->assertFalse(FiscalYearHelper::isCurrentYear(2025));

        // 2. Switch via AJAX
        $ajaxRes = $this->actingAs($user)->postJson(route('fiscal-year.switch'), [
            'year' => 2026,
        ]);
        $ajaxRes->assertOk();
        $ajaxRes->assertJson([
            'success' => true,
            'year' => 2026,
            'is_current' => FiscalYearHelper::isCurrentYear(2026),
        ]);
        $this->assertEquals(2026, session('fiscal_year'));
    }

    public function test_kas_tunai_respects_session_fiscal_year(): void
    {
        $user = User::first() ?? User::factory()->create();

        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2025-05-10',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas 2025',
        ]);

        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-06-15',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 8000000,
            'description' => 'Droping Kas 2026',
        ]);

        // Sesi Tahun 2025
        $res2025 = $this->actingAs($user)
            ->withSession(['fiscal_year' => 2025])
            ->get(route('bos.kas-tunai.index'));

        $res2025->assertOk();
        $res2025->assertViewHas('selectedYear', '2025');
        $res2025->assertViewHas('totalMasuk', 5000000.0);

        // Sesi Tahun 2026
        $res2026 = $this->actingAs($user)
            ->withSession(['fiscal_year' => 2026])
            ->get(route('bos.kas-tunai.index'));

        $res2026->assertOk();
        $res2026->assertViewHas('selectedYear', '2026');
        $res2026->assertViewHas('totalMasuk', 8000000.0);
    }

    public function test_pencairan_bank_respects_session_fiscal_year(): void
    {
        $user = User::first() ?? User::factory()->create();

        PencairanBank::create([
            'funding_source' => 'BOSP',
            'letter_number' => 'TNT/2025/001',
            'letter_date' => '2025-04-10',
            'total_nominal' => 2000000,
        ]);

        PencairanBank::create([
            'funding_source' => 'BOSP',
            'letter_number' => 'TNT/2026/001',
            'letter_date' => '2026-04-10',
            'total_nominal' => 4000000,
        ]);

        $res = $this->actingAs($user)
            ->withSession(['fiscal_year' => 2025])
            ->get(route('bos.pencairan-bank.index'));

        $res->assertOk();
        $res->assertViewHas('selectedYear', '2025');
    }

    public function test_unpaid_rencana_notification_scoped_strictly_to_active_fiscal_year(): void
    {
        $user = User::first() ?? User::factory()->create();

        $rencana2025 = BosRencanaBelanja::create([
            'year' => 2025,
            'month' => 'Desember',
            'no_nota' => 'RB25-TEST-999',
            'title' => 'Rencana Belanja 2025 Test',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $rencana2026 = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Januari',
            'no_nota' => 'RB26-TEST-999',
            'title' => 'Rencana Belanja 2026 Test',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        // 1. When session fiscal_year is 2026
        $response2026 = $this->actingAs($user)
            ->withSession(['fiscal_year' => 2026])
            ->get(route('bos.pencairan-bank.index'));

        $response2026->assertOk();
        $response2026->assertSee('RB26-TEST-999');
        $response2026->assertDontSee('RB25-TEST-999');

        // 2. When session fiscal_year is 2025
        $response2025 = $this->actingAs($user)
            ->withSession(['fiscal_year' => 2025])
            ->get(route('bos.pencairan-bank.index'));

        $response2025->assertOk();
        $response2025->assertSee('RB25-TEST-999');
        $response2025->assertDontSee('RB26-TEST-999');

        // Clean up
        $rencana2025->forceDelete();
        $rencana2026->forceDelete();
    }

    public function test_perjadin_respects_session_fiscal_year(): void
    {
        $user = User::first() ?? User::factory()->create();

        Perjadin::create([
            'funding_source' => 'BOSP',
            'departure_date' => '2025-08-01',
            'activity_purpose' => 'Dinas 2025',
        ]);

        Perjadin::create([
            'funding_source' => 'BOSP',
            'departure_date' => '2026-08-01',
            'activity_purpose' => 'Dinas 2026',
        ]);

        $res = $this->actingAs($user)
            ->withSession(['fiscal_year' => 2025])
            ->get(route('bos.perjadin.index'));

        $res->assertOk();
        $res->assertViewHas('selectedYear', '2025');
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

        if (! Schema::hasTable('school_profiles')) {
            Schema::create('school_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('gurus')) {
            Schema::create('gurus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('title_ahead')->nullable();
                $table->string('title_behind')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('perjadins')) {
            Schema::create('perjadins', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->string('bku_number')->nullable();
                $table->string('activity_purpose')->nullable();
                $table->date('departure_date')->nullable();
                $table->date('payment_date')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('perjadin_items')) {
            Schema::create('perjadin_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('perjadin_id')->nullable();
                $table->foreignId('guru_id')->nullable();
                $table->string('receipt_number')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('net_amount', 15, 2)->default(0);
                $table->boolean('is_paid')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('pencairan_banks')) {
            Schema::create('pencairan_banks', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->string('letter_number')->nullable();
                $table->date('letter_date')->nullable();
                $table->decimal('total_nominal', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type', 20)->default('offline');
                $table->string('marketplace_name', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanjas')) {
            Schema::create('bos_rencana_belanjas', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->foreignId('supplier_id')->nullable();
                $table->foreignId('pencairan_bank_item_id')->nullable();
                $table->string('no_nota', 50)->nullable();
                $table->integer('year')->nullable();
                $table->string('month', 50)->nullable();
                $table->string('title')->nullable();
                $table->string('supplier_name')->nullable();
                $table->string('status', 30)->nullable();
                $table->date('planned_date')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('account_number')->nullable();
                $table->string('activity_name')->nullable();
                $table->string('item_name')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('total_price', 15, 2)->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('bos_rencana_belanjas', function (Blueprint $table) {
                if (! Schema::hasColumn('bos_rencana_belanjas', 'no_nota')) {
                    $table->string('no_nota', 50)->nullable();
                }
                if (! Schema::hasColumn('bos_rencana_belanjas', 'year')) {
                    $table->integer('year')->nullable();
                }
                if (! Schema::hasColumn('bos_rencana_belanjas', 'month')) {
                    $table->string('month', 50)->nullable();
                }
                if (! Schema::hasColumn('bos_rencana_belanjas', 'title')) {
                    $table->string('title')->nullable();
                }
                if (! Schema::hasColumn('bos_rencana_belanjas', 'supplier_id')) {
                    $table->foreignId('supplier_id')->nullable();
                }
                if (! Schema::hasColumn('bos_rencana_belanjas', 'supplier_name')) {
                    $table->string('supplier_name')->nullable();
                }
                if (! Schema::hasColumn('bos_rencana_belanjas', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
            });
        }

        if (! Schema::hasTable('bos_rencana_belanja_items')) {
            Schema::create('bos_rencana_belanja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_rencana_belanja_id');
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->string('nama_pasar', 255)->nullable();
                $table->decimal('volume_plan', 15, 2)->default(0);
                $table->string('unit')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('pencairan_bank_items')) {
            Schema::create('pencairan_bank_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pencairan_bank_id')->nullable();
                $table->string('item_name')->nullable();
                $table->decimal('nominal', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('kas_tunais')) {
            Schema::create('kas_tunais', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP')->index();
                $table->date('transaction_date')->index();
                $table->unsignedSmallInteger('year')->nullable();
                $table->unsignedInteger('sequence')->nullable();
                $table->string('transaction_type', 10)->index();
                $table->string('category', 50)->index();
                $table->decimal('amount', 15, 2)->default(0);
                $table->text('description');
                $table->string('person_name')->nullable();
                $table->foreignId('guru_id')->nullable();
                $table->foreignId('pencairan_bank_id')->nullable();
                $table->foreignId('bos_rencana_belanja_id')->nullable();
                $table->foreignId('perjadin_item_id')->nullable();
                $table->foreignId('parent_kas_id')->nullable();
                $table->string('panjar_status', 20)->default('none')->index();
                $table->string('evidence_number')->nullable();
                $table->string('receipt_file')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->foreignId('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
}
