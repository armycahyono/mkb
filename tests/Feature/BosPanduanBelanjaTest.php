<?php

namespace Tests\Feature;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\BosRencanaBelanja;
use App\Models\BosRencanaBelanjaItem;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BosPanduanBelanjaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_files')) {
            Schema::create('bos_kertas_kerja_files', function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('file_path')->nullable();
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
                $table->unsignedBigInteger('bos_kertas_kerja_file_id')->nullable();
                $table->integer('year');
                $table->string('month');
                $table->string('account_code');
                $table->string('program_code')->nullable();
                $table->string('kegiatan')->nullable();
                $table->string('description');
                $table->string('unit')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('volume', 15, 2)->default(0);
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
                $table->string('title')->nullable();
                $table->string('supplier_name')->nullable();
                $table->date('planned_date')->nullable();
                $table->string('status', 30)->default('draft');
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('account_number')->nullable();
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
                $table->string('nama_pasar', 255)->nullable();
                $table->text('spesifikasi')->nullable();
                $table->decimal('volume_plan', 15, 2)->default(0);
                $table->string('unit')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        } elseif (! Schema::hasColumn('bos_rencana_belanja_items', 'nama_pasar')) {
            Schema::table('bos_rencana_belanja_items', function (Blueprint $table) {
                $table->string('nama_pasar', 255)->nullable();
                $table->text('spesifikasi')->nullable();
            });
        }

        if (! Schema::hasTable('pengajuan_barangs')) {
            Schema::create('pengajuan_barangs', function (Blueprint $table) {
                $table->id();
                $table->year('tahun_anggaran')->nullable();
                $table->unsignedBigInteger('guru_id')->nullable();
                $table->unsignedBigInteger('pemohon_id')->nullable();
                $table->string('nama_barang');
                $table->text('spesifikasi')->nullable();
                $table->decimal('volume', 12, 2)->default(1);
                $table->string('satuan')->nullable();
                $table->decimal('harga', 15, 2)->default(0);
                $table->decimal('jumlah', 15, 2)->default(0);
                $table->string('kegiatan')->nullable();
                $table->string('bulan_pakai')->nullable();
                $table->text('keterangan')->nullable();
                $table->string('realisasi')->nullable();
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

        if (! Schema::hasTable('bos_realisasies')) {
            Schema::create('bos_realisasies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_item_id');
                $table->string('no_transaksi')->nullable();
                $table->string('no_nota')->nullable();
                $table->unsignedBigInteger('bos_rencana_belanja_id')->nullable();
                $table->integer('year')->nullable();
                $table->string('month')->nullable();
                $table->date('transaction_date')->nullable();
                $table->date('receipt_date')->nullable();
                $table->decimal('volume', 15, 2)->default(0);
                $table->string('unit')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_groups_data_places_unrealized_items_at_the_top(): void
    {
        $user = new User(['id' => 996, 'name' => 'Tester Panduan']);

        // 1. Buat rencana yang SUDAH direalisasikan dengan ID lebih tinggi / baru
        $realizedGroup = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Januari',
            'no_nota' => 'RB26-999',
            'title' => 'Rencana Sudah Realisasi',
            'status' => 'realized',
            'created_by' => 1,
        ]);

        // 2. Buat rencana yang BELUM direalisasikan (status draft) dengan ID lebih rendah / lebih lama
        $draftGroup = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Januari',
            'no_nota' => 'RB26-001',
            'title' => 'Rencana Belum Realisasi (Draft)',
            'status' => 'draft',
            'created_by' => 1,
        ]);

        // 3. Buat rencana yang diajukan ke bank (belum realisasi juga)
        $tntGroup = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Februari',
            'no_nota' => 'RB26-002',
            'title' => 'Rencana Diajukan ke TNT',
            'status' => 'diajukan_ke_bank',
            'created_by' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('bos.panduan.groups-data', [
                'year' => 2026,
            ]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);

        $titles = collect($json['data'])->pluck('title')->toArray();

        // Rencana yang belum direalisasikan (draft & diajukan_ke_bank) harus berada sebelum realized
        $indexOfDraft = array_search('Rencana Belum Realisasi (Draft)', $titles);
        $indexOfTnt = array_search('Rencana Diajukan ke TNT', $titles);
        $indexOfRealized = array_search('Rencana Sudah Realisasi', $titles);

        $this->assertNotFalse($indexOfDraft, 'Draft group not found in response');
        $this->assertNotFalse($indexOfTnt, 'TNT group not found in response');
        $this->assertNotFalse($indexOfRealized, 'Realized group not found in response');

        $this->assertLessThan($indexOfRealized, $indexOfDraft, 'Draft (belum realisasi) must be placed above realized');
        $this->assertLessThan($indexOfRealized, $indexOfTnt, 'Diajukan ke TNT (belum realisasi) must be placed above realized');

        // Bersihkan
        $realizedGroup->delete();
        $draftGroup->delete();
        $tntGroup->delete();
    }

    public function test_groups_data_search(): void
    {
        $user = new User(['id' => 995, 'name' => 'Tester Search']);

        $group = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Januari',
            'no_nota' => 'RB26-SEARCH-01',
            'title' => 'Pengadaan Komputer Lab',
            'supplier_name' => 'CV Sumber Rejeki',
            'status' => 'draft',
            'created_by' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('bos.panduan.groups-data', [
                'year' => 2026,
                'search' => ['value' => 'Sumber Rejeki'],
            ]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertNotEmpty($json['data']);
        $this->assertEquals('RB26-SEARCH-01', $json['data'][0]['no_nota']);

        // Test search by item description inside the group
        $kkItem = BosKertasKerjaItem::create([
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.01.01.0025',
            'description' => 'Proyektor Epson EB-X500',
            'price' => 6000000,
            'volume' => 1,
            'total_amount' => 6000000,
        ]);

        $itemRencana = BosRencanaBelanjaItem::create([
            'bos_rencana_belanja_id' => $group->id,
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'volume_plan' => 1,
            'unit' => 'unit',
            'price' => 6000000,
            'total_amount' => 6000000,
        ]);

        $responseItem = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('bos.panduan.groups-data', [
                'year' => 2026,
                'search' => ['value' => 'Proyektor Epson'],
            ]));

        $responseItem->assertStatus(200);
        $jsonItem = $responseItem->json();
        $this->assertNotEmpty($jsonItem['data']);
        $this->assertEquals('RB26-SEARCH-01', $jsonItem['data'][0]['no_nota']);

        $itemRencana->delete();
        $kkItem->delete();
        $group->delete();
    }

    public function test_store_and_update_group_with_nama_pasar_and_spesifikasi(): void
    {
        $user = new User(['id' => 994, 'name' => 'Tester SIPLah']);

        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026.xlsx',
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Februari',
            'account_code' => '5.1.02.01.01.0024',
            'program_code' => '03.02',
            'kegiatan' => 'Pengadaan Alat Tulis Kantor',
            'description' => 'Kertas HVS A4 70gr (Sesuai Kertas Kerja)',
            'unit' => 'rim',
            'price' => 55000,
            'volume' => 10,
            'total_amount' => 550000,
        ]);

        // 1. Test Store Group
        $storeResponse = $this->actingAs($user)
            ->postJson(route('bos.panduan.store-group'), [
                'title' => 'Belanja ATK SIPLah',
                'no_nota' => 'RB26-SIP-01',
                'year' => 2026,
                'month' => 'Februari',
                'items' => [
                    [
                        'bos_kertas_kerja_item_id' => $kkItem->id,
                        'nama_pasar' => 'Sinar Dunia PaperOne HVS A4 75gr',
                        'spesifikasi' => '1 Rim isi 500 lembar warna putih',
                        'volume_plan' => 5,
                        'price' => 54000,
                    ],
                ],
            ]);

        $storeResponse->assertStatus(200);
        $storeResponse->assertJsonStructure(['message']);

        $group = BosRencanaBelanja::where('no_nota', 'RB26-SIP-01')->first();
        $this->assertNotNull($group);

        $savedItem = $group->items()->first();
        $this->assertNotNull($savedItem);
        $this->assertEquals('Sinar Dunia PaperOne HVS A4 75gr', $savedItem->nama_pasar);
        $this->assertEquals('1 Rim isi 500 lembar warna putih', $savedItem->spesifikasi);
        $this->assertEquals('Sinar Dunia PaperOne HVS A4 75gr', $savedItem->effective_description);

        // 2. Test Update Group
        $updateResponse = $this->actingAs($user)
            ->putJson(route('bos.panduan.update-group', $group->id), [
                'title' => 'Belanja ATK SIPLah Diperbarui',
                'items' => [
                    [
                        'id' => $savedItem->id,
                        'nama_pasar' => 'PaperOne HVS A4 80gr Super White',
                        'spesifikasi' => 'Dus isi 5 rim',
                        'volume_plan' => 6,
                        'price' => 56000,
                    ],
                ],
            ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonStructure(['message']);

        $savedItem->refresh();
        $this->assertEquals('PaperOne HVS A4 80gr Super White', $savedItem->nama_pasar);
        $this->assertEquals('Dus isi 5 rim', $savedItem->spesifikasi);
        $this->assertEquals('PaperOne HVS A4 80gr Super White', $savedItem->effective_description);

        // Bersihkan
        $savedItem->delete();
        $group->delete();
        $kkItem->delete();
        $file->delete();
    }

    public function test_print_group_renders_hierarchical_grouping(): void
    {
        $user = new User(['id' => 993, 'name' => 'Tester Print']);

        $kk1 = BosKertasKerjaItem::create([
            'year' => 2026,
            'month' => 'Maret',
            'account_code' => '5.1.02.01.01.0024',
            'program_code' => '03.01',
            'kegiatan' => 'Kegiatan Administrasi Kantor',
            'description' => 'Buku Tulis Ekspedisi',
            'price' => 25000,
            'volume' => 5,
            'total_amount' => 125000,
        ]);

        $kk2 = BosKertasKerjaItem::create([
            'year' => 2026,
            'month' => 'Maret',
            'account_code' => '5.1.02.01.01.0025',
            'program_code' => '03.02',
            'kegiatan' => 'Kegiatan Pembelajaran',
            'description' => 'Spidol Whiteboard Hitam',
            'price' => 10000,
            'volume' => 20,
            'total_amount' => 200000,
        ]);

        $group = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Maret',
            'no_nota' => 'RB26-PRINT-01',
            'title' => 'Rencana PO Multi Kegiatan & Multi Rekening',
            'status' => 'draft',
            'created_by' => 1,
        ]);

        BosRencanaBelanjaItem::create([
            'bos_rencana_belanja_id' => $group->id,
            'bos_kertas_kerja_item_id' => $kk1->id,
            'nama_pasar' => 'Buku Ekspedisi Hardcover 100 lbr',
            'spesifikasi' => 'Ukuran Folio',
            'volume_plan' => 5,
            'unit' => 'buah',
            'price' => 25000,
            'total_amount' => 125000,
        ]);

        BosRencanaBelanjaItem::create([
            'bos_rencana_belanja_id' => $group->id,
            'bos_kertas_kerja_item_id' => $kk2->id,
            'nama_pasar' => null, // default ke description arkas
            'spesifikasi' => null,
            'volume_plan' => 20,
            'unit' => 'buah',
            'price' => 10000,
            'total_amount' => 200000,
        ]);

        $printResponse = $this->actingAs($user)
            ->get(route('bos.panduan.print', $group->id));

        $printResponse->assertStatus(200);
        $printResponse->assertSee('RB26-PRINT-01');
        $printResponse->assertSee('Kegiatan Administrasi Kantor');
        $printResponse->assertSee('Kegiatan Pembelajaran');
        $printResponse->assertSee('5.1.02.01.01.0024');
        $printResponse->assertSee('5.1.02.01.01.0025');
        $printResponse->assertSee('Buku Ekspedisi Hardcover 100 lbr');
        $printResponse->assertSee('Spidol Whiteboard Hitam');
        $printResponse->assertSee('Sub-total Rekening');
        $printResponse->assertSee('page-sheet');
        $printResponse->assertSee('no-print-bar');

        // Bersihkan
        $group->items()->delete();
        $group->delete();
        $kk1->delete();
        $kk2->delete();
    }

    public function test_toggle_order_status_and_filtering(): void
    {
        $user = User::firstOrCreate(
            ['id' => 1],
            ['name' => 'Bendahara BOS', 'email' => 'bendahara@test.com']
        );

        $kk = BosKertasKerjaItem::create([
            'year' => 2026,
            'month' => 'April',
            'account_code' => '5.1.02.01.01.0024',
            'program_code' => '03.01',
            'kegiatan' => 'Kegiatan Administrasi Kantor',
            'description' => 'Kertas A4 70gr',
            'price' => 50000,
            'volume' => 10,
            'total_amount' => 500000,
        ]);

        $group = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'April',
            'no_nota' => 'RB26-TEST-ORDER',
            'title' => 'Rencana Belanja Kertas A4',
            'status' => 'draft',
            'created_by' => 1,
        ]);

        BosRencanaBelanjaItem::create([
            'bos_rencana_belanja_id' => $group->id,
            'bos_kertas_kerja_item_id' => $kk->id,
            'volume_plan' => 5,
            'unit' => 'rim',
            'price' => 50000,
            'total_amount' => 250000,
        ]);

        // 1. Toggle status to 'ordered'
        $toggleResponse = $this->actingAs($user)
            ->post(route('bos.panduan.toggle-order', $group->id), [
                'status' => 'ordered',
            ]);

        $toggleResponse->assertStatus(200);
        $toggleResponse->assertJsonFragment(['status' => 'ordered']);

        $group->refresh();
        $this->assertEquals('ordered', $group->status);

        // 2. Test groupsData returns 'ordered' badge and action
        $groupsDataResponse = $this->actingAs($user)
            ->get(route('bos.panduan.groups-data', ['year' => 2026, 'status' => 'ordered']));

        $groupsDataResponse->assertStatus(200);
        $groupsDataResponse->assertSee('Order');
        $groupsDataResponse->assertDontSee('Sudah Diorder (SIPLah)');
        $groupsDataResponse->assertSee('RB26-TEST-ORDER');

        // 3. Test filtering groupsData with status='draft' excludes this ordered group
        $draftDataResponse = $this->actingAs($user)
            ->get(route('bos.panduan.groups-data', ['year' => 2026, 'status' => 'draft']));
        $draftDataResponse->assertStatus(200);
        $this->assertStringNotContainsString('RB26-TEST-ORDER', $draftDataResponse->getContent());

        // 4. Test toggle back to 'draft'
        $toggleDraftResponse = $this->actingAs($user)
            ->post(route('bos.panduan.toggle-order', $group->id), [
                'status' => 'draft',
            ]);
        $toggleDraftResponse->assertStatus(200);
        $group->refresh();
        $this->assertEquals('draft', $group->status);

        // 5. Test converting ordered plan to realisasi
        $group->update(['status' => 'ordered']);
        $convertResponse = $this->actingAs($user)
            ->post(route('bos.panduan.convert', $group->id), [
                'transaction_date' => '2026-04-15',
            ]);
        $convertResponse->assertStatus(200);
        $group->refresh();
        $this->assertEquals('realized', $group->status);

        // Clean up
        $group->realisasis()->forceDelete();
        $group->items()->delete();
        $group->delete();
        $kk->delete();
    }

    public function test_update_group_removes_omitted_item(): void
    {
        $user = User::firstOrCreate(
            ['id' => 1],
            ['name' => 'Bendahara BOS', 'email' => 'bendahara@test.com']
        );

        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026_mei.xlsx',
            'is_active' => true,
        ]);

        $kk1 = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Mei',
            'account_code' => '5.1.02.01.01.0024',
            'program_code' => '03.01',
            'kegiatan' => 'Kegiatan Administrasi Kantor',
            'description' => 'Barang A',
            'price' => 10000,
            'volume' => 10,
            'total_amount' => 100000,
        ]);

        $kk2 = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Mei',
            'account_code' => '5.1.02.01.01.0024',
            'program_code' => '03.01',
            'kegiatan' => 'Kegiatan Administrasi Kantor',
            'description' => 'Barang B',
            'price' => 20000,
            'volume' => 10,
            'total_amount' => 200000,
        ]);

        $group = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Mei',
            'no_nota' => 'RB26-TEST-DELETE-ITEM',
            'title' => 'Rencana Belanja Uji Hapus Item',
            'status' => 'draft',
            'created_by' => 1,
        ]);

        $item1 = BosRencanaBelanjaItem::create([
            'bos_rencana_belanja_id' => $group->id,
            'bos_kertas_kerja_item_id' => $kk1->id,
            'volume_plan' => 2,
            'unit' => 'pcs',
            'price' => 10000,
            'total_amount' => 20000,
        ]);

        $item2 = BosRencanaBelanjaItem::create([
            'bos_rencana_belanja_id' => $group->id,
            'bos_kertas_kerja_item_id' => $kk2->id,
            'volume_plan' => 3,
            'unit' => 'pcs',
            'price' => 20000,
            'total_amount' => 60000,
        ]);

        $this->assertEquals(2, $group->items()->count());

        // Update group by only keeping item1 (omitting item2)
        $response = $this->actingAs($user)
            ->put(route('bos.panduan.update-group', $group->id), [
                'title' => 'Rencana Belanja Setelah Item Dihapus',
                'items' => [
                    [
                        'id' => $item1->id,
                        'volume_plan' => 2,
                        'price' => 10000,
                        'nama_pasar' => 'Barang A Revisi',
                    ],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Rencana belanja berhasil diperbarui.']);

        $group->refresh();
        $this->assertEquals(1, $group->items()->count());
        $this->assertTrue($group->items()->where('id', $item1->id)->exists());
        $this->assertFalse($group->items()->where('id', $item2->id)->exists());

        // Clean up
        $group->items()->delete();
        $group->delete();
        $kk1->delete();
        $kk2->delete();
        $file->delete();
    }

    public function test_export_unrealized_excel(): void
    {
        $user = User::firstOrCreate(
            ['id' => 1],
            ['name' => 'Bendahara BOS', 'email' => 'bendahara@test.com']
        );

        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026_export.xlsx',
            'is_active' => true,
        ]);

        $kk = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.01.01.0024',
            'program_code' => '03.01',
            'kegiatan' => 'Kegiatan Administrasi Kantor',
            'description' => 'Barang Export Test',
            'price' => 15000,
            'volume' => 10,
            'total_amount' => 150000,
        ]);

        $response = $this->actingAs($user)
            ->get(route('bos.panduan.export-unrealized', [
                'year' => 2026,
                'month' => 'Januari',
                'hide_planned' => 1,
            ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('filename=Barang_Belum_Belanja_2026_Januari.xlsx', (string) $response->headers->get('content-disposition'));

        $kk->delete();
        $file->delete();
    }

    public function test_export_groups_excel(): void
    {
        $user = User::firstOrCreate(
            ['id' => 1],
            ['name' => 'Bendahara BOS', 'email' => 'bendahara@test.com']
        );

        $group = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Januari',
            'no_nota' => 'RB26-TEST-EXP',
            'title' => 'Rencana Belanja Export Test',
            'status' => 'draft',
            'created_by' => 1,
        ]);

        $kk = BosKertasKerjaItem::create([
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.01.01.0024',
            'description' => 'Barang RB Export',
            'price' => 20000,
            'volume' => 5,
            'total_amount' => 100000,
        ]);

        $item = BosRencanaBelanjaItem::create([
            'bos_rencana_belanja_id' => $group->id,
            'bos_kertas_kerja_item_id' => $kk->id,
            'volume_plan' => 2,
            'unit' => 'pcs',
            'price' => 20000,
            'total_amount' => 40000,
        ]);

        $response = $this->actingAs($user)
            ->get(route('bos.panduan.export-groups', [
                'year' => 2026,
                'status' => 'draft',
            ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('filename=Daftar_Rencana_Belanja_2026_DRAFT.xlsx', (string) $response->headers->get('content-disposition'));

        $item->delete();
        $group->delete();
        $kk->delete();
    }
}
