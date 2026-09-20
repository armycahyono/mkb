<?php

namespace Tests\Feature;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\BosRealisasi;
use App\Models\BosRencanaBelanja;
use App\Models\BosRencanaBelanjaItem;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BosRencanaBelanjaConflictTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('bos_kertas_kerja_files')) {
            Schema::create('bos_kertas_kerja_files', function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('file_path');
                $table->integer('version')->default(1);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_items')) {
            Schema::create('bos_kertas_kerja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_file_id');
                $table->integer('year');
                $table->string('month');
                $table->string('account_code');
                $table->string('program_code')->nullable();
                $table->string('kegiatan')->nullable();
                $table->string('description');
                $table->string('unit')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('volume', 15, 2)->default(0);
                $table->decimal('total_volume', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanjas')) {
            Schema::create('bos_rencana_belanjas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('no_nota', 50)->nullable();
                $table->integer('year')->nullable();
                $table->string('month', 50)->nullable();
                $table->string('title', 255)->nullable();
                $table->string('supplier_name', 255)->nullable();
                $table->date('planned_date')->nullable();
                $table->string('status', 50)->default('draft');
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 50)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanja_items')) {
            Schema::create('bos_rencana_belanja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_rencana_belanja_id');
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->float('volume_plan')->default(0);
                $table->string('unit', 50)->nullable();
                $table->float('price')->default(0);
                $table->float('total_amount')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bos_realisasies')) {
            Schema::create('bos_realisasies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->unsignedBigInteger('bos_rencana_belanja_id')->nullable();
                $table->unsignedBigInteger('perjadin_id')->nullable();
                $table->string('no_transaksi', 50)->nullable();
                $table->string('no_nota', 50)->nullable();
                $table->integer('year')->nullable();
                $table->string('month', 50)->nullable();
                $table->date('transaction_date')->nullable();
                $table->date('receipt_date')->nullable();
                $table->float('volume')->default(0);
                $table->string('unit', 50)->nullable();
                $table->float('price')->default(0);
                $table->float('total_amount')->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('pengajuan_barangs')) {
            Schema::create('pengajuan_barangs', function (Blueprint $table) {
                $table->id();
                $table->integer('tahun_anggaran');
                $table->unsignedBigInteger('guru_id')->nullable();
                $table->unsignedBigInteger('pemohon_id')->nullable();
                $table->string('nama_barang');
                $table->text('spesifikasi')->nullable();
                $table->decimal('volume', 10, 2)->default(1);
                $table->string('satuan', 50)->default('unit');
                $table->decimal('harga', 15, 2)->default(0);
                $table->decimal('jumlah', 15, 2)->default(0);
                $table->string('kegiatan')->nullable();
                $table->string('bulan_pakai', 50)->nullable();
                $table->text('keterangan')->nullable();
                $table->boolean('realisasi')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_item_pengajuans')) {
            Schema::create('bos_kertas_kerja_item_pengajuans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_item_id');
                $table->unsignedBigInteger('pengajuan_barang_id');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    private function authenticateUser(): User
    {
        $user = new User([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->id = 1;
        $this->actingAs($user);

        return $user;
    }

    public function test_cannot_store_plan_exceeding_budget_volume(): void
    {
        $this->authenticateUser();

        $file = BosKertasKerjaFile::create([
            'filename' => 'kk_2026.pdf',
            'file_path' => 'bos/kk_2026.pdf',
            'version' => 1,
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'September',
            'account_code' => '5.1.02.01.01.0024',
            'kegiatan' => 'Pemeliharaan Gedung',
            'description' => 'Kawat Las 2.6mm',
            'unit' => 'roll',
            'price' => 126000,
            'volume' => 4,
            'total_volume' => 4,
            'total_amount' => 504000,
        ]);

        // Buat rencana pertama sebanyak 3 roll (dari total 4 roll)
        $res1 = $this->post(route('bos.panduan.store-group'), [
            'year' => 2026,
            'month' => 'September',
            'title' => 'Rencana 1 Kawat Las',
            'items' => [
                [
                    'bos_kertas_kerja_item_id' => $kkItem->id,
                    'volume_plan' => 3,
                    'price' => 126000,
                ],
            ],
        ]);
        $res1->assertStatus(200);

        // Coba buat rencana kedua sebanyak 2 roll (3 + 2 = 5 > 4) -> Harus ditolak (422)
        $res2 = $this->post(route('bos.panduan.store-group'), [
            'year' => 2026,
            'month' => 'September',
            'title' => 'Rencana 2 Kawat Las (Melebihi Anggaran)',
            'items' => [
                [
                    'bos_kertas_kerja_item_id' => $kkItem->id,
                    'volume_plan' => 2,
                    'price' => 126000,
                ],
            ],
        ]);
        $res2->assertStatus(422);
        $res2->assertJsonFragment([
            'message' => 'Volume untuk barang [Kawat Las 2.6mm] (2 roll) melebihi sisa anggaran yang tersedia! Sisa yang dapat dialokasikan: 1 roll (Anggaran: 4, Realisasi BKU: 0, Sudah Masuk Rencana: 3).',
        ]);

        // Buat rencana kedua sebanyak sisa yang pas (1 roll) -> Harus berhasil (200)
        $res3 = $this->post(route('bos.panduan.store-group'), [
            'year' => 2026,
            'month' => 'September',
            'title' => 'Rencana 2 Kawat Las (Sisa Pas)',
            'items' => [
                [
                    'bos_kertas_kerja_item_id' => $kkItem->id,
                    'volume_plan' => 1,
                    'price' => 126000,
                ],
            ],
        ]);
        $res3->assertStatus(200);
    }

    public function test_get_unpaid_rencana_notas_detects_conflict_when_already_realized(): void
    {
        $this->authenticateUser();

        $file = BosKertasKerjaFile::create([
            'filename' => 'kk_2026.pdf',
            'file_path' => 'bos/kk_2026.pdf',
            'version' => 1,
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'September',
            'account_code' => '5.1.02.01.01.0024',
            'kegiatan' => 'Pemeliharaan Gedung',
            'description' => 'Meteran 5m',
            'unit' => 'buah',
            'price' => 54000,
            'volume' => 4,
            'total_volume' => 4,
            'total_amount' => 216000,
        ]);

        // Simulasikan sudah ada Realisasi di BKU sebanyak 4 buah (100%)
        BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'no_nota' => 'OLD-001',
            'volume' => 4,
            'unit' => 'buah',
            'price' => 54000,
            'total_amount' => 216000,
        ]);

        // Buat rencana belanja yang belum masuk TNT
        $rb = BosRencanaBelanja::create([
            'no_nota' => 'RB26-999',
            'year' => 2026,
            'month' => 'September',
            'title' => 'Rencana Belanja Dobel Meteran',
            'status' => 'draft',
        ]);
        BosRencanaBelanjaItem::create([
            'bos_rencana_belanja_id' => $rb->id,
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'volume_plan' => 4,
            'unit' => 'buah',
            'price' => 54000,
            'total_amount' => 216000,
        ]);

        $response = $this->getJson(route('bos.api.unpaid-rencana-notas'));
        $response->assertStatus(200);

        $data = $response->json();
        $matched = collect($data)->firstWhere('no_nota', 'RB26-999');

        $this->assertNotNull($matched);
        $this->assertTrue($matched['has_conflict']);
        $this->assertStringContainsString('Meteran 5m (4 buah)', $matched['items_summary']);
        $this->assertStringContainsString('sudah direalisasikan penuh di BKU', $matched['conflict_messages'][0]);
    }

    public function test_unrealized_data_deducts_active_planned_volume_and_handles_hide_planned_filter(): void
    {
        $this->authenticateUser();

        $file = BosKertasKerjaFile::create([
            'filename' => 'kk_2026.pdf',
            'file_path' => 'bos/kk_2026.pdf',
            'version' => 1,
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'September',
            'account_code' => '5.1.02.01.01.0024',
            'kegiatan' => 'Pemeliharaan Gedung',
            'description' => 'Palu Besi',
            'unit' => 'buah',
            'price' => 75000,
            'volume' => 10,
            'total_volume' => 10,
            'total_amount' => 750000,
        ]);

        // Buat rencana belanja aktif untuk 10 buah Palu Besi (100% rencana)
        $rb = BosRencanaBelanja::create([
            'no_nota' => 'RB26-050',
            'year' => 2026,
            'month' => 'September',
            'title' => 'Rencana Belanja Palu',
            'status' => 'draft',
        ]);
        BosRencanaBelanjaItem::create([
            'bos_rencana_belanja_id' => $rb->id,
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'volume_plan' => 10,
            'unit' => 'buah',
            'price' => 75000,
            'total_amount' => 750000,
        ]);

        // Ketika hide_planned = 1 (default): item yang 100% masuk rencana tidak muncul
        $resHidden = $this->getJson(route('bos.panduan.unrealized-data', [
            'year' => 2026,
            'month' => 'September',
            'hide_planned' => 1,
        ]));
        $resHidden->assertStatus(200);
        $dataHidden = $resHidden->json('data');
        $this->assertEmpty(collect($dataHidden)->where('description', 'Palu Besi'));

        // Ketika hide_planned = 0: item muncul tapi checkbox terkunci (lock icon) dan sisa vol = 0
        $resShown = $this->getJson(route('bos.panduan.unrealized-data', [
            'year' => 2026,
            'month' => 'September',
            'hide_planned' => 0,
        ]));
        $resShown->assertStatus(200);
        $dataShown = $resShown->json('data');
        $paluRow = collect($dataShown)->firstWhere('description', 'Palu Besi');

        $this->assertNotNull($paluRow);
        $this->assertEquals(0, $paluRow['remaining_volume']);
        $this->assertFalse($paluRow['is_allocatable']);
        $this->assertStringContainsString('fa-lock', $paluRow['checkbox']);
        $this->assertContains('RB26-050', $paluRow['active_plans']);
    }

    public function test_rencana_belanja_unrealized_data_excludes_kegiatan_from_search(): void
    {
        $user = new User(['id' => 991, 'name' => 'Admin Rencana']);
        $this->actingAs($user);

        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_search_test.xlsx',
            'file_path' => 'kertas_kerja/search_test.xlsx',
            'version' => 1,
            'is_active' => true,
        ]);

        $item = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.01.01.0055',
            'program_code' => 'PROG-01',
            'kegiatan' => 'Kegiatan Robotika Unik',
            'description' => 'Spidol Marker Putih',
            'unit' => 'pcs',
            'price' => 12000,
            'volume' => 5,
            'total_volume' => 5,
            'total_amount' => 60000,
        ]);

        $columns = [
            ['data' => 'checkbox', 'searchable' => 'false'],
            ['data' => 'account_code', 'searchable' => 'true'],
            ['data' => 'program_code', 'searchable' => 'true'],
            ['data' => 'description', 'searchable' => 'true'],
            ['data' => 'remaining_volume', 'searchable' => 'true'],
            ['data' => 'unit', 'searchable' => 'true'],
            ['data' => 'price_display', 'searchable' => 'true'],
            ['data' => 'remaining_amount_display', 'searchable' => 'true'],
            ['data' => 'months_included', 'searchable' => 'true'],
            ['data' => 'kegiatan', 'searchable' => 'false'],
        ];

        // Pencarian dengan kata kunci kegiatan ("Robotika") harus dikecualikan (tidak boleh match)
        $resKegiatan = $this->getJson(route('bos.panduan.unrealized-data', [
            'year' => 2026,
            'month' => 'Januari',
            'columns' => $columns,
            'search' => ['value' => 'Robotika'],
        ]));
        $resKegiatan->assertStatus(200);
        $dataKegiatan = $resKegiatan->json('data');
        $this->assertEmpty(collect($dataKegiatan)->where('description', 'Spidol Marker Putih'));

        // Pencarian dengan kata kunci nama barang ("Spidol") harus match
        $resDesc = $this->getJson(route('bos.panduan.unrealized-data', [
            'year' => 2026,
            'month' => 'Januari',
            'columns' => $columns,
            'search' => ['value' => 'Spidol'],
        ]));
        $resDesc->assertStatus(200);
        $dataDesc = $resDesc->json('data');
        $this->assertNotEmpty(collect($dataDesc)->where('description', 'Spidol Marker Putih'));

        // Bersihkan
        $item->delete();
        $file->delete();
    }
}
