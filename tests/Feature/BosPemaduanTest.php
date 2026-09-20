<?php

namespace Tests\Feature;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\PengajuanBarang;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BosPemaduanTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('gurus')) {
            Schema::create('gurus', function (Blueprint $table) {
                $table->id();
                $table->string('nip', 30)->nullable();
                $table->string('nik', 30)->nullable();
                $table->string('nuptk', 30)->nullable();
                $table->string('npwp', 30)->nullable();
                $table->string('name');
                $table->string('title_prefix', 30)->nullable();
                $table->string('title_suffix', 30)->nullable();
                $table->string('gender', 20)->nullable();
                $table->string('birth_place', 100)->nullable();
                $table->date('birth_date')->nullable();
                $table->string('religion', 50)->nullable();
                $table->string('employment_status', 50)->nullable();
                $table->string('pangkat_golongan', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_id')->nullable();
                $table->string('name');
                $table->string('username', 50)->nullable()->unique();
                $table->string('email')->unique();
                $table->string('role', 30)->default('bendahara');
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('telegram_chat_id', 50)->nullable()->unique();
                $table->string('telegram_username', 100)->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

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
                $table->unsignedBigInteger('utility_profile_id')->nullable();
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

        if (! Schema::hasTable('pengajuan_barangs')) {
            Schema::create('pengajuan_barangs', function (Blueprint $table) {
                $table->id();
                $table->integer('tahun_anggaran');
                $table->unsignedBigInteger('guru_id')->nullable();
                $table->unsignedBigInteger('pemohon_id')->nullable();
                $table->unsignedBigInteger('utility_profile_id')->nullable();
                $table->string('nama_barang');
                $table->text('spesifikasi')->nullable();
                $table->decimal('volume', 10, 2)->default(1);
                $table->string('satuan', 50)->default('unit');
                $table->decimal('harga', 15, 2)->default(0);
                $table->decimal('jumlah', 15, 2)->default(0);
                $table->string('kegiatan')->nullable();
                $table->string('kode_rekening', 50)->nullable();
                $table->string('bulan_pakai', 50)->nullable();
                $table->text('keterangan')->nullable();
                $table->boolean('realisasi')->default(false);
                $table->boolean('is_recurring')->default(false);
                $table->string('recurring_group_id', 50)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            if (! Schema::hasColumn('pengajuan_barangs', 'is_recurring')) {
                Schema::table('pengajuan_barangs', function (Blueprint $table) {
                    $table->boolean('is_recurring')->default(false);
                    $table->string('recurring_group_id', 50)->nullable();
                });
            }
            if (! Schema::hasColumn('pengajuan_barangs', 'kode_rekening')) {
                Schema::table('pengajuan_barangs', function (Blueprint $table) {
                    $table->string('kode_rekening', 50)->nullable();
                });
            }
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
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $this->user = User::factory()->create([
            'role' => 'bendahara',
        ]);
    }

    public function test_guest_cannot_access_pemaduan_index(): void
    {
        $response = $this->get(route('bos.pemaduan.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_pemaduan_index(): void
    {
        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026.xlsx',
            'file_path' => 'kertas_kerja_2026.xlsx',
            'is_active' => true,
        ]);

        $item = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Maret',
            'account_code' => '5.1.02.01.01.0024',
            'kegiatan' => 'Penilaian Akhir Semester',
            'description' => 'Pencetak / Alat Pengganda Dokumen',
            'unit' => 'Unit',
            'price' => 2500000,
            'volume' => 1,
            'total_amount' => 2500000,
        ]);

        $response = $this->actingAs($this->user)->get(route('bos.pemaduan.index', ['year' => 2026]));

        $response->assertStatus(200);
        $response->assertSee('Pemaduan RRKAS &amp; Kertas Kerja BOS', false);
        $response->assertSee('Pencetak / Alat Pengganda Dokumen');
        $response->assertSee('Belum Dipadukan');
    }

    public function test_available_pengajuan_endpoint_returns_json(): void
    {
        $pengajuan = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'pemohon_id' => $this->user->id,
            'nama_barang' => 'Printer Epson L3210 All-in-One',
            'spesifikasi' => 'Print, Scan, Copy EcoTank Original',
            'volume' => 1,
            'satuan' => 'Unit',
            'harga' => 2450000,
            'jumlah' => 2450000,
            'bulan_pakai' => 'Maret',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('bos.pemaduan.available-pengajuan', [
            'year' => 2026,
            'search' => 'Epson',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $response->assertSee('Printer Epson L3210 All-in-One');
    }

    public function test_can_pair_kertas_kerja_with_pengajuan_barang(): void
    {
        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026.xlsx',
            'file_path' => 'kertas_kerja_2026.xlsx',
            'is_active' => true,
        ]);

        $item = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Maret',
            'account_code' => '5.1.02.01.01.0024',
            'description' => 'Pencetak / Alat Pengganda Dokumen',
            'price' => 2500000,
            'volume' => 1,
            'total_amount' => 2500000,
        ]);

        $pengajuan = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'pemohon_id' => $this->user->id,
            'nama_barang' => 'Printer Epson L3210 All-in-One',
            'spesifikasi' => 'Print, Scan, Copy EcoTank Original',
            'volume' => 1,
            'satuan' => 'Unit',
            'harga' => 2450000,
            'jumlah' => 2450000,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('bos.pemaduan.pair'), [
            'bos_kertas_kerja_item_id' => $item->id,
            'pengajuan_barang_id' => $pengajuan->id,
            'notes' => 'Catatan belanja untuk TU',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('bos_kertas_kerja_item_pengajuans', [
            'bos_kertas_kerja_item_id' => $item->id,
            'pengajuan_barang_id' => $pengajuan->id,
            'notes' => 'Catatan belanja untuk TU',
        ]);

        // Verifikasi relasi Eloquent
        $item->refresh();
        $this->assertCount(1, $item->pengajuanBarangs);
        $this->assertEquals('Printer Epson L3210 All-in-One', $item->pengajuanBarangs->first()->nama_barang);
    }

    public function test_can_unpair_kertas_kerja_and_pengajuan_barang(): void
    {
        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026.xlsx',
            'file_path' => 'kertas_kerja_2026.xlsx',
            'is_active' => true,
        ]);

        $item = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Maret',
            'account_code' => '5.1.02.01.01.0024',
            'description' => 'Pencetak / Alat Pengganda Dokumen',
            'price' => 2500000,
            'volume' => 1,
            'total_amount' => 2500000,
        ]);

        $pengajuan = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'pemohon_id' => $this->user->id,
            'nama_barang' => 'Printer Epson L3210 All-in-One',
            'volume' => 1,
            'satuan' => 'Unit',
            'harga' => 2450000,
            'jumlah' => 2450000,
        ]);

        $item->pengajuanBarangs()->attach($pengajuan->id);
        $this->assertCount(1, $item->pengajuanBarangs);

        $response = $this->actingAs($this->user)->post(route('bos.pemaduan.unpair'), [
            'bos_kertas_kerja_item_id' => $item->id,
            'pengajuan_barang_id' => $pengajuan->id,
        ]);

        $response->assertSessionHas('success');
        $item->refresh();
        $this->assertCount(0, $item->pengajuanBarangs);
    }

    public function test_paired_pengajuan_appears_in_panduan_unrealized_data(): void
    {
        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026.xlsx',
            'file_path' => 'kertas_kerja_2026.xlsx',
            'is_active' => true,
        ]);

        $item = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Maret',
            'account_code' => '5.1.02.01.01.0024',
            'kegiatan' => 'Penilaian Akhir Semester',
            'description' => 'Pencetak / Alat Pengganda Dokumen',
            'unit' => 'Unit',
            'price' => 2500000,
            'volume' => 1,
            'total_amount' => 2500000,
        ]);

        $pengajuan = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'pemohon_id' => $this->user->id,
            'nama_barang' => 'Printer Epson L3210 All-in-One',
            'spesifikasi' => 'Print, Scan, Copy EcoTank Original',
            'volume' => 1,
            'satuan' => 'Unit',
            'harga' => 2450000,
            'jumlah' => 2450000,
        ]);

        $item->pengajuanBarangs()->attach($pengajuan->id);

        $response = $this->actingAs($this->user)->getJson(route('bos.panduan.unrealized-data', [
            'year' => 2026,
            'month' => 'Maret',
            'hide_planned' => 0,
        ]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertNotEmpty($json['data']);
        $this->assertEquals('Printer Epson L3210 All-in-One', $json['data'][0]['paired_pengajuans'][0]['nama_barang']);
        $this->assertEquals('Print, Scan, Copy EcoTank Original', $json['data'][0]['paired_pengajuans'][0]['spesifikasi']);
    }

    public function test_can_filter_pemaduan_by_recurring_character_and_grouped_mode(): void
    {
        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026.xlsx',
            'file_path' => 'kertas_kerja_2026.xlsx',
            'is_active' => true,
        ]);

        // 1 item berulang (Januari & Februari)
        BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.02.01.0061',
            'kegiatan' => 'Operasional Sekolah',
            'description' => 'Tagihan Listrik PLN Rutin',
            'price' => 1000,
            'volume' => 100,
            'total_amount' => 100000,
        ]);
        BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Februari',
            'account_code' => '5.1.02.02.01.0061',
            'kegiatan' => 'Operasional Sekolah',
            'description' => 'Tagihan Listrik PLN Rutin',
            'price' => 1000,
            'volume' => 100,
            'total_amount' => 100000,
        ]);

        // 1 item sekali (Hanya Maret)
        BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Maret',
            'account_code' => '5.1.02.01.01.0024',
            'kegiatan' => 'ATK',
            'description' => 'Spanduk Kegiatan Sekali',
            'price' => 50000,
            'volume' => 1,
            'total_amount' => 50000,
        ]);

        // Test filter berulang
        $resBerulang = $this->actingAs($this->user)->get(route('bos.pemaduan.index', [
            'year' => 2026,
            'karakter' => 'berulang',
            'view_mode' => 'single',
        ]));
        $resBerulang->assertOk();
        $resBerulang->assertSee('Tagihan Listrik PLN Rutin');
        $resBerulang->assertDontSee('Spanduk Kegiatan Sekali');

        // Test mode sekali
        $resSekali = $this->actingAs($this->user)->get(route('bos.pemaduan.index', [
            'year' => 2026,
            'view_mode' => 'sekali',
        ]));
        $resSekali->assertOk();
        $resSekali->assertSee('Tampilan Belanja Sekali (Insidental)');
        $resSekali->assertSee('Spanduk Kegiatan Sekali');
        $resSekali->assertDontSee('Tagihan Listrik PLN Rutin');

        // Test mode grouped
        $resGrouped = $this->actingAs($this->user)->get(route('bos.pemaduan.index', [
            'year' => 2026,
            'view_mode' => 'grouped',
        ]));
        $resGrouped->assertOk();
        $resGrouped->assertSee('Tampilan Terkelompok per Seri Belanja');
        $resGrouped->assertSee('Tagihan Listrik PLN Rutin');
        $resGrouped->assertDontSee('Spanduk Kegiatan Sekali');

        // Test mode single (semua rinci)
        $resSingle = $this->actingAs($this->user)->get(route('bos.pemaduan.index', [
            'year' => 2026,
            'view_mode' => 'single',
        ]));
        $resSingle->assertOk();
        $resSingle->assertSee('Tampilan Semua Data Rinci per Alokasi Bulan');
        $resSingle->assertSee('Tagihan Listrik PLN Rutin');
        $resSingle->assertSee('Spanduk Kegiatan Sekali');
    }

    public function test_batch_pair_and_batch_unpair_for_recurring_items(): void
    {
        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026.xlsx',
            'file_path' => 'kertas_kerja_2026.xlsx',
            'is_active' => true,
        ]);

        // Kertas kerja 2 bulan
        $kkJan = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.02.01.0061',
            'kegiatan' => 'Listrik',
            'description' => 'Tagihan Listrik PLN 5500VA',
            'price' => 1000,
            'volume' => 500,
            'total_amount' => 500000,
        ]);
        $kkFeb = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Februari',
            'account_code' => '5.1.02.02.01.0061',
            'kegiatan' => 'Listrik',
            'description' => 'Tagihan Listrik PLN 5500VA',
            'price' => 1000,
            'volume' => 500,
            'total_amount' => 500000,
        ]);

        // Pengajuan berulang 2 bulan
        $groupId = 'test-recurring-listrik-group';
        $pbJan = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Bayar Token Listrik Kampus 1',
            'volume' => 1,
            'satuan' => 'Bulan',
            'harga' => 500000,
            'jumlah' => 500000,
            'bulan_pakai' => 'Januari',
            'is_recurring' => true,
            'recurring_group_id' => $groupId,
        ]);
        $pbFeb = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Bayar Token Listrik Kampus 1',
            'volume' => 1,
            'satuan' => 'Bulan',
            'harga' => 500000,
            'jumlah' => 500000,
            'bulan_pakai' => 'Februari',
            'is_recurring' => true,
            'recurring_group_id' => $groupId,
        ]);

        // Check available recurring pengajuan endpoint
        $resList = $this->actingAs($this->user)->getJson(route('bos.pemaduan.available-recurring-pengajuan', [
            'year' => 2026,
        ]));
        $resList->assertOk();
        $this->assertNotEmpty($resList->json('data'));

        // Batch pair execution
        $resPair = $this->actingAs($this->user)->postJson(route('bos.pemaduan.batch-pair'), [
            'year' => 2026,
            'account_code' => '5.1.02.02.01.0061',
            'kegiatan' => 'Listrik',
            'description' => 'Tagihan Listrik PLN 5500VA',
            'price' => 1000,
            'pengajuan_group_key' => $groupId,
            'notes' => 'Paduan rutin listrik kampus',
        ]);
        $resPair->assertOk();
        $this->assertEquals(2, $resPair->json('paired_count'));

        $kkJan->refresh();
        $kkFeb->refresh();
        $this->assertCount(1, $kkJan->pengajuanBarangs);
        $this->assertCount(1, $kkFeb->pengajuanBarangs);
        $this->assertEquals($pbJan->id, $kkJan->pengajuanBarangs->first()->id);
        $this->assertEquals($pbFeb->id, $kkFeb->pengajuanBarangs->first()->id);

        // Batch unpair execution
        $resUnpair = $this->actingAs($this->user)->postJson(route('bos.pemaduan.batch-unpair'), [
            'year' => 2026,
            'account_code' => '5.1.02.02.01.0061',
            'kegiatan' => 'Listrik',
            'description' => 'Tagihan Listrik PLN 5500VA',
            'price' => 1000,
        ]);
        $resUnpair->assertOk();
        $this->assertEquals(2, $resUnpair->json('unpaired_count'));

        $kkJan->refresh();
        $kkFeb->refresh();
        $this->assertCount(0, $kkJan->pengajuanBarangs);
        $this->assertCount(0, $kkFeb->pengajuanBarangs);
    }

    public function test_grouped_mode_displays_paired_pengajuans_as_group_not_individually(): void
    {
        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026.xlsx',
            'file_path' => 'kertas_kerja_2026.xlsx',
            'is_active' => true,
        ]);

        // Kertas kerja 3 bulan
        $months = ['Januari', 'Februari', 'Maret'];
        $kkItems = [];
        foreach ($months as $m) {
            $kkItems[$m] = BosKertasKerjaItem::create([
                'bos_kertas_kerja_file_id' => $file->id,
                'year' => 2026,
                'month' => $m,
                'account_code' => '5.1.02.02.01.0061',
                'kegiatan' => 'Listrik Gedung',
                'description' => 'Pembayaran Daya Listrik PLN',
                'price' => 1000,
                'volume' => 750,
                'total_amount' => 750000,
            ]);
        }

        // Pengajuan berulang 3 bulan
        $groupId = 'test-recurring-display-group';
        foreach ($months as $m) {
            $pb = PengajuanBarang::create([
                'tahun_anggaran' => 2026,
                'nama_barang' => 'Listrik PLN SMK PGRI KRAS',
                'volume' => 1,
                'satuan' => 'Bulan',
                'harga' => 750000,
                'jumlah' => 750000,
                'bulan_pakai' => $m,
                'is_recurring' => true,
                'recurring_group_id' => $groupId,
            ]);

            // Hubungkan (pair)
            $kkItems[$m]->pengajuanBarangs()->attach($pb->id);
        }

        // Request tampilan grouped
        $res = $this->actingAs($this->user)->get(route('bos.pemaduan.index', [
            'year' => 2026,
            'view_mode' => 'grouped',
        ]));

        $res->assertOk();
        $res->assertSee('Tampilan Terkelompok per Seri Belanja');
        $res->assertSee('3 Bulan Terpadu Penuh');
        $res->assertSee('Listrik PLN SMK PGRI KRAS');
        $res->assertSee('3 Bulan Terpadu');
    }

    public function test_batch_pair_with_special_characters_in_description(): void
    {
        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026.xlsx',
            'file_path' => 'bos/kertas_kerja_2026.xlsx',
            'is_active' => true,
        ]);

        $kkItems = [];
        foreach (['Januari', 'Februari'] as $m) {
            $kkItems[] = BosKertasKerjaItem::create([
                'bos_kertas_kerja_file_id' => $file->id,
                'year' => 2026,
                'month' => $m,
                'account_code' => '5.1.02.02.01.0063',
                'kegiatan' => 'Pembayaran jasa internet',
                'description' => 'Langganan Internet + Telepon - Broadband Up to > 100 Mbps',
                'price' => 450000,
                'volume' => 1,
                'total_amount' => 450000,
            ]);
        }

        $recGroup = 'rec-group-internet-test';
        foreach (['Januari', 'Februari'] as $m) {
            PengajuanBarang::create([
                'tahun_anggaran' => 2026,
                'nama_barang' => 'Bayar INDIHOME SMK PGRI KRAS',
                'volume' => 1,
                'satuan' => 'Bulan',
                'harga' => 450000,
                'jumlah' => 450000,
                'bulan_pakai' => $m,
                'is_recurring' => true,
                'recurring_group_id' => $recGroup,
            ]);
        }

        // Test with reference item id & HTML entity encoded description
        $res = $this->actingAs($this->user)->post(route('bos.pemaduan.batch-pair'), [
            'bos_kertas_kerja_item_id' => $kkItems[0]->id,
            'year' => 2026,
            'account_code' => '5.1.02.02.01.0063',
            'kegiatan' => 'Pembayaran jasa internet',
            'description' => 'Langganan Internet + Telepon - Broadband Up to &gt; 100 Mbps',
            'price' => '450000',
            'pengajuan_group_key' => $recGroup,
        ]);

        $res->assertSessionHas('success');
        $this->assertCount(1, $kkItems[0]->fresh()->pengajuanBarangs);
        $this->assertCount(1, $kkItems[1]->fresh()->pengajuanBarangs);

        // Test unpair
        $unpairRes = $this->actingAs($this->user)->post(route('bos.pemaduan.batch-unpair'), [
            'bos_kertas_kerja_item_id' => $kkItems[0]->id,
            'year' => 2026,
            'account_code' => '5.1.02.02.01.0063',
            'kegiatan' => 'Pembayaran jasa internet',
            'description' => 'Langganan Internet + Telepon - Broadband Up to &gt; 100 Mbps',
            'price' => '450000',
        ]);

        $unpairRes->assertSessionHas('success');
        $this->assertCount(0, $kkItems[0]->fresh()->pengajuanBarangs);
        $this->assertCount(0, $kkItems[1]->fresh()->pengajuanBarangs);
    }

    public function test_available_endpoints_only_return_unpaired_references(): void
    {
        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_2026.xlsx',
            'file_path' => 'bos/kertas_kerja_2026.xlsx',
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.02.01.0063',
            'kegiatan' => 'Jasa Internet',
            'description' => 'Langganan Internet',
            'price' => 500000,
            'volume' => 1,
            'total_amount' => 500000,
        ]);

        $pbPaired = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Sudah Dipadukan PB',
            'volume' => 1,
            'satuan' => 'Bulan',
            'harga' => 500000,
            'jumlah' => 500000,
            'bulan_pakai' => 'Januari',
            'is_recurring' => true,
            'recurring_group_id' => 'group-paired-test',
        ]);

        $pbUnpaired = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Belum Dipadukan PB',
            'volume' => 1,
            'satuan' => 'Bulan',
            'harga' => 500000,
            'jumlah' => 500000,
            'bulan_pakai' => 'Januari',
            'is_recurring' => true,
            'recurring_group_id' => 'group-unpaired-test',
        ]);

        // Pair $pbPaired with $kkItem
        $kkItem->pengajuanBarangs()->attach($pbPaired->id);

        // 1. Check availablePengajuan endpoint
        $resSingle = $this->actingAs($this->user)->getJson(route('bos.pemaduan.available-pengajuan', [
            'year' => 2026,
        ]));
        $resSingle->assertOk();
        $resSingle->assertSee('Belum Dipadukan PB');
        $resSingle->assertDontSee('Sudah Dipadukan PB');

        // 2. Check availableRecurringPengajuan endpoint
        $resRecurring = $this->actingAs($this->user)->getJson(route('bos.pemaduan.available-recurring-pengajuan', [
            'year' => 2026,
        ]));
        $resRecurring->assertOk();
        $resRecurring->assertSee('Belum Dipadukan PB');
        $resRecurring->assertDontSee('Sudah Dipadukan PB');
    }
}
