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

class BosRealisasiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_bos_realisasi_index_view_has_newest_order_default(): void
    {
        $years = [2026, 2025];
        $months = ['Januari', 'Februari', 'Maret'];

        $html = view('bos.realisasi.index', [
            'years' => $years,
            'months' => $months,
        ])->render();

        $this->assertStringContainsString("order: [[2, 'desc'], [1, 'desc']]", $html);
        $this->assertStringContainsString('TGL BKU', $html);
        $this->assertStringContainsString('NO BPU', $html);
    }

    public function test_bos_realisasi_ajax_orders_by_newest_transaction(): void
    {
        $user = new User(['id' => 999, 'name' => 'Admin']);

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
            'kegiatan' => 'Kegiatan Test',
            'account_code' => '5.1.02.01.01.0001',
            'description' => 'Barang Test',
            'price' => 50000,
            'volume' => 10,
            'unit' => 'pcs',
            'total_amount' => 500000,
        ]);

        $r1 = BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $item->id,
            'no_transaksi' => 'BPU001',
            'transaction_date' => '2026-02-10',
            'volume' => 1,
            'unit' => 'pcs',
            'price' => 50000,
            'total_amount' => 50000,
            'year' => 2026,
            'month' => 'Januari',
        ]);

        $r2 = BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $item->id,
            'no_transaksi' => 'BPU002',
            'transaction_date' => '2026-08-25', // Teranyar
            'volume' => 1,
            'unit' => 'pcs',
            'price' => 50000,
            'total_amount' => 50000,
            'year' => 2026,
            'month' => 'Januari',
        ]);

        $r3 = BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $item->id,
            'no_transaksi' => 'BPU003',
            'transaction_date' => '2026-05-15',
            'volume' => 1,
            'unit' => 'pcs',
            'price' => 50000,
            'total_amount' => 50000,
            'year' => 2026,
            'month' => 'Januari',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('bos.realisasi.index', [
                'year' => 2026,
                'order' => [
                    ['column' => '2', 'dir' => 'desc'],
                    ['column' => '1', 'dir' => 'desc'],
                ],
                'columns' => [
                    ['data' => 'no_nota_display', 'name' => 'no_nota'],
                    ['data' => 'no_transaksi', 'name' => 'no_transaksi'],
                    ['data' => 'transaction_date_display', 'name' => 'transaction_date'],
                    ['data' => 'receipt_date_display', 'name' => 'receipt_date'],
                ],
            ]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('data', $json);
        $data = $json['data'];
        $this->assertNotEmpty($data);

        // Baris pertama harus transaksi terbaru: 25/08/2026
        $firstRow = $data[0];
        $this->assertEquals('25/08/2026', $firstRow['transaction_date_display']);
        $this->assertEquals('BPU002', $firstRow['no_transaksi']);

        // Bersihkan data test
        $r1->delete();
        $r2->delete();
        $r3->delete();
    }

    public function test_bos_realisasi_combines_account_code_with_kegiatan_and_removes_separate_kode_rek_header(): void
    {
        $years = [2026, 2025];
        $months = ['Januari', 'Februari', 'Maret'];

        $html = view('bos.realisasi.index', [
            'years' => $years,
            'months' => $months,
            'selectedYear' => 2026,
        ])->render();

        $this->assertStringContainsString('KEGIATAN & KODE REK', $html);
        $this->assertStringContainsString('colspan="8"', $html);
        $this->assertStringNotContainsString('<th width="95" class="text-center">KODE REK</th>', $html);
        $this->assertStringContainsString('VOL.</th>', $html);
        $this->assertStringContainsString('SALDO BANK</th>', $html);
        $this->assertStringNotContainsString('SALDO REKENING</th>', $html);
    }

    public function test_bos_realisasi_shows_and_filters_by_realization_month_and_has_planning_month_badge(): void
    {
        $user = new User(['id' => 998, 'name' => 'Admin Test']);

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

        // Item dianggarkan pada bulan Januari (Bulan Perencanaan)
        $item = BosKertasKerjaItem::create([
            'year' => 2026,
            'month' => 'Januari',
            'kegiatan' => 'Pemeliharaan Gedung',
            'account_code' => '5.1.02.01.01.0024',
            'description' => 'Pipa Paralon 2 Inch',
            'price' => 75000,
            'volume' => 5,
            'unit' => 'batang',
            'total_amount' => 375000,
        ]);

        // Realisasi belanja terjadi pada tanggal 20 Maret 2026 (Bulan Realisasi: Maret)
        $realisasi = BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $item->id,
            'no_transaksi' => 'BPU-TEST-001',
            'transaction_date' => '2026-03-20',
            'volume' => 2,
            'unit' => 'batang',
            'price' => 75000,
            'total_amount' => 150000,
            'year' => 2026,
            'month' => 'Maret',
        ]);

        // Request DataTables tanpa filter bulan
        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('bos.realisasi.index', [
                'year' => 2026,
            ]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);

        $matchedRow = collect($json['data'])->firstWhere('no_transaksi', 'BPU-TEST-001');
        $this->assertNotNull($matchedRow);

        // Kolom bulan harus menampilkan bulan realisasi transaksi (Maret)
        $this->assertEquals('Maret', $matchedRow['month']);

        // Kolom uraian harus memiliki badge bulan perencanaan (Januari)
        $this->assertStringContainsString('Pipa Paralon 2 Inch', $matchedRow['description']);
        $this->assertStringContainsString('Januari', $matchedRow['description']);
        $this->assertStringNotContainsString('(Januari)', $matchedRow['description']);
        $this->assertStringContainsString('badge', $matchedRow['description']);

        // Request DataTables filter month=Maret (bulan realisasi) -> HARUS muncul
        $responseMar = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('bos.realisasi.index', [
                'year' => 2026,
                'month' => 'Maret',
            ]));
        $responseMar->assertStatus(200);
        $jsonMar = $responseMar->json();
        $this->assertTrue(collect($jsonMar['data'])->contains('no_transaksi', 'BPU-TEST-001'));

        // Request DataTables filter month=Januari (bukan bulan realisasi) -> TIDAK boleh muncul
        $responseJan = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('bos.realisasi.index', [
                'year' => 2026,
                'month' => 'Januari',
            ]));
        $responseJan->assertStatus(200);
        $jsonJan = $responseJan->json();
        $this->assertFalse(collect($jsonJan['data'])->contains('no_transaksi', 'BPU-TEST-001'));

        // Bersihkan
        $realisasi->delete();
        $item->delete();
    }

    public function test_bos_realisasi_running_balance_is_continuous_and_not_negative_when_filtered_by_month(): void
    {
        $user = new User(['id' => 997, 'name' => 'Admin Test Balance']);

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
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('bos_penerimaans')) {
            $migration = require database_path('migrations/2026_09_11_105536_create_bos_penerimaans_table.php');
            $migration->up();
        }

        // 1. Penerimaan di bulan Januari: Rp 50.000.000
        $penerimaan = BosPenerimaan::create([
            'year' => 2026,
            'transaction_date' => '2026-01-05',
            'amount' => 50000000,
            'category' => 'salur_tahap_1',
            'description' => 'Penyaluran BOSP Tahap 1',
            'created_by' => null,
        ]);

        // Item Kertas Kerja
        $item = BosKertasKerjaItem::create([
            'year' => 2026,
            'month' => 'Januari',
            'kegiatan' => 'Operasional Sekolah',
            'account_code' => '5.1.02.01.01.0001',
            'description' => 'Kertas HVS',
            'price' => 50000,
            'volume' => 100,
            'unit' => 'rim',
            'total_amount' => 5000000,
        ]);

        // 2. Realisasi Belanja di bulan Januari: Rp 10.000.000 -> Sisa Saldo: Rp 40.000.000
        $rJan = BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $item->id,
            'no_transaksi' => 'BPU-JAN-01',
            'transaction_date' => '2026-01-15',
            'volume' => 200,
            'unit' => 'rim',
            'price' => 50000,
            'total_amount' => 10000000,
            'year' => 2026,
            'month' => 'Januari',
        ]);

        // 3. Realisasi Belanja di bulan Maret: Rp 5.000.000 -> Saldo Kumulatif yang Benar: Rp 35.000.000
        $rMar = BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $item->id,
            'no_transaksi' => 'BPU-MAR-01',
            'transaction_date' => '2026-03-20',
            'volume' => 100,
            'unit' => 'rim',
            'price' => 50000,
            'total_amount' => 5000000,
            'year' => 2026,
            'month' => 'Maret',
        ]);

        // Filter DataTables khusus bulan Maret
        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('bos.realisasi.index', [
                'year' => 2026,
                'month' => 'Maret',
            ]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);

        $marRow = collect($json['data'])->firstWhere('no_transaksi', 'BPU-MAR-01');
        $this->assertNotNull($marRow);

        // Saldo Bank harus kumulatif dari transaksi sebelumnya (50jt - 10jt - 5jt = 35jt), BUKAN negatif (-5jt)
        $this->assertEquals(35000000, (float) $marRow['running_balance']);
        $this->assertStringContainsString('35.000.000', $marRow['saldo_display']);
        $this->assertStringNotContainsString('-Rp', $marRow['saldo_display']);
        $this->assertStringNotContainsString('text-danger', $marRow['saldo_display']);

        // Bersihkan
        $rMar->delete();
        $rJan->delete();
        $item->delete();
        $penerimaan->delete();
    }

    public function test_bos_realisasi_sorting_works_for_all_columns(): void
    {
        $user = new User(['id' => 999, 'name' => 'Admin']);

        $columns = [
            ['data' => 'no_nota_display', 'name' => 'no_nota'],
            ['data' => 'no_transaksi', 'name' => 'no_transaksi'],
            ['data' => 'transaction_date_display', 'name' => 'transaction_date'],
            ['data' => 'receipt_date_display', 'name' => 'receipt_date'],
            ['data' => 'month', 'name' => 'month'],
            ['data' => 'description', 'name' => 'description'],
            ['data' => 'volume_display', 'name' => 'volume'],
            ['data' => 'price_display', 'name' => 'price'],
            ['data' => 'total_amount_display', 'name' => 'total_amount'],
            ['data' => 'saldo_display', 'name' => 'saldo'],
            ['data' => 'kegiatan', 'name' => 'kegiatan'],
            ['data' => 'action', 'name' => 'action'],
        ];

        // Test sorting ASC and DESC on every data column
        for ($colIdx = 0; $colIdx <= 10; $colIdx++) {
            foreach (['asc', 'desc'] as $dir) {
                $response = $this->actingAs($user)
                    ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                    ->getJson(route('bos.realisasi.index', [
                        'year' => 2026,
                        'columns' => $columns,
                        'order' => [
                            ['column' => (string) $colIdx, 'dir' => $dir],
                        ],
                    ]));

                $response->assertStatus(200);
                $this->assertArrayHasKey('data', $response->json());
            }
        }
    }
}
