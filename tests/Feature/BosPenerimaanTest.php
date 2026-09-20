<?php

namespace Tests\Feature;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\BosPenerimaan;
use App\Models\BosRealisasi;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BosPenerimaanTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            $userMigration = require database_path('migrations/0001_01_01_000000_create_users_table.php');
            $userMigration->up();
        }

        $this->user = User::create([
            'id' => 1,
            'name' => 'Bendahara BOS',
            'email' => 'bendahara@test.com',
            'password' => bcrypt('password'),
        ]);

        if (! Schema::hasTable('bos_kertas_kerja_files')) {
            Schema::create('bos_kertas_kerja_files', function (Blueprint $table) {
                $table->id();
                $table->integer('year');
                $table->string('month', 50);
                $table->string('version', 20)->default('v1.0');
                $table->string('filename');
                $table->string('original_filename');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (! Schema::hasTable('bos_kertas_kerja_items')) {
            Schema::create('bos_kertas_kerja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_file_id')->nullable();
                $table->integer('year');
                $table->string('month', 50);
                $table->string('kegiatan')->nullable();
                $table->string('account_code')->nullable();
                $table->string('description')->nullable();
                $table->float('price')->default(0);
                $table->float('volume')->default(0);
                $table->string('unit', 50)->nullable();
                $table->float('total_amount')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (! Schema::hasTable('bos_realisasies')) {
            Schema::create('bos_realisasies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->string('no_transaksi', 50)->nullable();
                $table->string('no_nota', 50)->nullable();
                $table->date('transaction_date')->nullable();
                $table->date('receipt_date')->nullable();
                $table->float('volume')->default(0);
                $table->string('unit', 50)->nullable();
                $table->float('price')->default(0);
                $table->float('total_amount')->default(0);
                $table->integer('year');
                $table->string('month', 50);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (! Schema::hasTable('bos_penerimaans')) {
            $migration = require database_path('migrations/2026_09_11_105536_create_bos_penerimaans_table.php');
            $migration->up();
        }
    }

    public function test_can_store_update_and_delete_penerimaan(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('bos.realisasi.penerimaan.store'), [
            'transaction_date' => '2026-01-20',
            'category' => 'salur_tahap_1',
            'reference_number' => 'BOS-IN-01',
            'description' => 'Salur BOSP Tahap 1 Gelombang 1',
            'amount' => '150.000.000',
            'notes' => 'Masuk bank Jatim',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Penerimaan dana BOS berhasil dicatat.',
            ]);

        $penerimaan = BosPenerimaan::where('reference_number', 'BOS-IN-01')->first();
        $this->assertNotNull($penerimaan);
        $this->assertEquals(150000000.0, (float) $penerimaan->amount);
        $this->assertEquals(2026, $penerimaan->year);

        // Test Update
        $updateResponse = $this->actingAs($this->user)->putJson(route('bos.realisasi.penerimaan.update', $penerimaan->id), [
            'transaction_date' => '2026-01-21',
            'category' => 'salur_tahap_1',
            'reference_number' => 'BOS-IN-01-REV',
            'description' => 'Revisi Salur BOSP Tahap 1',
            'amount' => '175.000.000',
            'notes' => 'Revisi catatan',
        ]);

        $updateResponse->assertStatus(200);
        $penerimaan->refresh();
        $this->assertEquals('BOS-IN-01-REV', $penerimaan->reference_number);
        $this->assertEquals(175000000.0, (float) $penerimaan->amount);

        // Test Delete
        $deleteResponse = $this->actingAs($this->user)->deleteJson(route('bos.realisasi.penerimaan.destroy', $penerimaan->id));
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('bos_penerimaans', ['id' => $penerimaan->id]);
    }

    public function test_summary_and_running_balance_calculation(): void
    {
        $file = new BosKertasKerjaFile;
        $file->year = 2026;
        $file->month = 'Januari';
        $file->version = 'v1.0';
        $file->filename = 'test.pdf';
        $file->original_filename = 'test.pdf';
        $file->is_active = true;
        $file->save();

        $item = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Januari',
            'kegiatan' => 'Operasional Sekolah',
            'account_code' => '5.1.02.01.01.0024',
            'description' => 'Kertas HVS A4',
            'price' => 50000,
            'volume' => 10,
            'unit' => 'rim',
            'total_amount' => 500000,
        ]);

        // Input Penerimaan
        BosPenerimaan::create([
            'funding_source' => 'BOS',
            'year' => 2026,
            'category' => 'salur_tahap_1',
            'transaction_date' => '2026-01-10',
            'reference_number' => 'SALUR-01',
            'description' => 'Penyaluran Tahap 1',
            'amount' => 1000000,
        ]);

        // Input Belanja
        BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $item->id,
            'no_transaksi' => 'BPU-001',
            'no_nota' => 'NOTA-001',
            'transaction_date' => '2026-01-15',
            'volume' => 2,
            'unit' => 'rim',
            'price' => 50000,
            'total_amount' => 100000,
            'year' => 2026,
            'month' => 'Januari',
        ]);

        // Test Summary
        $sumResponse = $this->actingAs($this->user)->getJson(route('bos.realisasi.summary', ['year' => 2026]));
        $sumResponse->assertStatus(200);
        $summaryData = $sumResponse->json();

        $this->assertEquals(1000000, $summaryData['total_dana_masuk']);
        $this->assertEquals(100000, $summaryData['total_realisasi']);
        $this->assertEquals(900000, $summaryData['saldo_bank']); // 1.000.000 - 100.000

        // Test Ajax Index with Saldo Rekening
        $ajaxResponse = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('bos.realisasi.index', ['year' => 2026]));

        $ajaxResponse->assertStatus(200);
        $data = $ajaxResponse->json('data');
        $this->assertCount(2, $data);
    }
}
