<?php

namespace Tests\Feature;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\BosRealisasi;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BosKertasKerjaDataFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('username')->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('admin');
                $table->rememberToken();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('bos_kertas_kerja_files')) {
            Schema::create('bos_kertas_kerja_files', function (Blueprint $table) {
                $table->id();
                $table->integer('year');
                $table->string('month', 50);
                $table->string('version', 20)->default('v1.0');
                $table->string('filename');
                $table->string('original_filename')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (! Schema::hasTable('bos_kertas_kerja_items')) {
            Schema::create('bos_kertas_kerja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_file_id')->nullable();
                $table->string('arkas_id')->nullable();
                $table->integer('year');
                $table->string('month', 50);
                $table->string('kegiatan')->nullable();
                $table->string('account_code')->nullable();
                $table->string('program_code')->nullable();
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
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_bos_data_index_view_renders_defaults_correctly(): void
    {
        $years = collect([2026, 2025]);
        $filesData = collect([
            [
                'id' => 1,
                'year' => 2026,
                'month' => 'Januari',
                'version' => 'v1.0',
                'is_active' => true,
                'filename' => 'kertas_kerja_2026_januari.pdf',
            ],
            [
                'id' => 2,
                'year' => 2026,
                'month' => 'Januari',
                'version' => 'v2.0',
                'is_active' => false,
                'filename' => 'kertas_kerja_2026_januari_v2.pdf',
            ],
        ]);

        $html = view('bos.data.index', [
            'years' => $years,
            'files' => collect([]),
            'filesData' => $filesData,
        ])->render();

        // 1. Year filter defaults to active year (2026)
        $this->assertStringContainsString('id="filter-year" value="2026"', $html);
        $this->assertStringContainsString('Tahun Anggaran 2026', $html);

        // 2. Month filter defaults to Januari
        $this->assertStringContainsString('<option value="Januari" selected>Januari</option>', $html);

        // 3. Version filter has active version as default
        $this->assertStringContainsString('<option value="active" selected>Aktif (Default)</option>', $html);
        $this->assertStringContainsString('kertasKerjaFiles', $html);
        $this->assertStringContainsString('updateVersionOptions', $html);

        // 4. Exhausted row styling and legend
        $this->assertStringContainsString('row-exhausted', $html);
        $this->assertStringContainsString('Teks Ungu: Terserap Habis', $html);
        $this->assertStringContainsString('Gembok: Ada Sisa', $html);
    }

    public function test_bos_item_realization_status_logic(): void
    {
        $item = new BosKertasKerjaItem([
            'year' => 2025,
            'month' => 'Februari',
            'volume' => 50,
            'price' => 10000,
            'total_amount' => 500000,
            'account_code' => '5.1.02.01.01',
            'kegiatan' => 'Kegiatan Belanja',
            'description' => 'Besi Beton',
            'unit' => 'biji',
        ]);

        $this->assertFalse($item->is_locked);
        $this->assertFalse($item->is_exhausted);
        $this->assertEquals('normal', $item->realization_status);
    }

    public function test_bos_data_ajax_datatables_response_is_fast_and_valid(): void
    {
        $user = User::first() ?? User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('bos.data.index', [
                'year' => 2026,
                'month' => 'Januari',
                'version_filter' => 'all',
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
            'grandTotal',
        ]);
    }

    public function test_bos_item_realization_status_uses_group_caching(): void
    {
        BosKertasKerjaItem::flushRealizationCaches();

        $file = BosKertasKerjaFile::create([
            'year' => 2026,
            'month' => 'Januari',
            'version' => 'v1.0',
            'filename' => 'test.pdf',
            'is_active' => true,
        ]);

        $item1 = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Januari',
            'volume' => 10,
            'price' => 50000,
            'total_amount' => 500000,
            'account_code' => '5.1.02.01.01',
            'kegiatan' => 'PPDB',
            'description' => 'Brosur PPDB',
            'unit' => 'lembar',
        ]);

        $item2 = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Februari',
            'volume' => 5,
            'price' => 50000,
            'total_amount' => 250000,
            'account_code' => '5.1.02.01.01',
            'kegiatan' => 'PPDB',
            'description' => 'Brosur PPDB',
            'unit' => 'lembar',
        ]);

        // When no realisasi exists, status is normal
        $this->assertEquals('normal', $item1->realization_status);
        $this->assertEquals('normal', $item2->realization_status);
        $this->assertFalse($item1->is_locked);
        $this->assertFalse($item2->is_locked);

        // Add realisasi on item1 for full volume (10)
        BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $item1->id,
            'year' => 2026,
            'month' => 'Januari',
            'volume' => 10,
            'price' => 50000,
            'total_amount' => 500000,
        ]);

        BosKertasKerjaItem::flushRealizationCaches();
        $freshItem1 = BosKertasKerjaItem::find($item1->id);
        $freshItem2 = BosKertasKerjaItem::find($item2->id);

        $this->assertEquals('exhausted', $freshItem1->realization_status);
        $this->assertTrue($freshItem1->is_exhausted);
        $this->assertTrue($freshItem1->is_locked);

        // Item2 in Februari is normal because Jan only absorbed 10
        $this->assertEquals('normal', $freshItem2->realization_status);
        $this->assertFalse($freshItem2->is_exhausted);
    }
}
