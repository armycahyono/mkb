<?php

namespace Tests\Feature;

use App\Models\ArkasBarang;
use App\Models\ArkasKegiatan;
use App\Models\BosPaguAnggaran;
use App\Models\Guru;
use App\Models\KegiatanSekolah;
use App\Models\PengajuanBarang;
use App\Models\User;
use App\Models\UtilityProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RencanaRkasTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('gurus')) {
            Schema::create('gurus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
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
                $table->string('email')->unique();
                $table->string('role', 30)->default('bendahara');
                $table->string('password');
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
                $table->string('kegiatan_sekolah')->nullable();
                $table->unsignedBigInteger('kegiatan_sekolah_id')->nullable();
                $table->string('kode_rekening', 50)->nullable();
                $table->string('bulan_pakai', 50)->nullable();
                $table->text('keterangan')->nullable();
                $table->boolean('realisasi')->default(false);
                $table->boolean('is_recurring')->default(false);
                $table->string('recurring_group_id', 36)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            if (! Schema::hasColumn('pengajuan_barangs', 'is_recurring')) {
                Schema::table('pengajuan_barangs', function (Blueprint $table) {
                    $table->boolean('is_recurring')->default(false);
                    $table->string('recurring_group_id', 36)->nullable();
                });
            }
            if (! Schema::hasColumn('pengajuan_barangs', 'utility_profile_id')) {
                Schema::table('pengajuan_barangs', function (Blueprint $table) {
                    $table->unsignedBigInteger('utility_profile_id')->nullable();
                });
            }
            if (! Schema::hasColumn('pengajuan_barangs', 'kode_rekening')) {
                Schema::table('pengajuan_barangs', function (Blueprint $table) {
                    $table->string('kode_rekening', 50)->nullable();
                });
            }
            if (! Schema::hasColumn('pengajuan_barangs', 'kegiatan_sekolah')) {
                Schema::table('pengajuan_barangs', function (Blueprint $table) {
                    $table->string('kegiatan_sekolah')->nullable();
                    $table->unsignedBigInteger('kegiatan_sekolah_id')->nullable();
                });
            }
        }

        if (! Schema::hasTable('kegiatan_sekolahs')) {
            Schema::create('kegiatan_sekolahs', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('kode', 50)->nullable();
                $table->string('arkas_kegiatan_kode', 50)->nullable();
                $table->string('color', 20)->default('#4f46e5');
                $table->boolean('is_active')->default(true);
                $table->integer('urutan')->default(0);
                $table->text('keterangan')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('utility_profiles')) {
            Schema::create('utility_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable();
                $table->string('name');
                $table->string('category', 50)->default('listrik_pra');
                $table->string('customer_name')->nullable();
                $table->string('customer_number')->nullable();
                $table->string('meter_number')->nullable();
                $table->string('idpel')->nullable();
                $table->string('tariff_power')->nullable();
                $table->string('provider')->nullable();
                $table->string('payment_method')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('default_reimburse_guru_id')->nullable();
                $table->boolean('is_budgeted')->default(true);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(1);
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            if (! Schema::hasColumn('utility_profiles', 'customer_name')) {
                Schema::table('utility_profiles', function (Blueprint $table) {
                    $table->string('customer_name')->nullable();
                    $table->string('customer_number')->nullable();
                    $table->string('meter_number')->nullable();
                    $table->string('idpel')->nullable();
                    $table->string('tariff_power')->nullable();
                    $table->string('provider')->nullable();
                    $table->string('payment_method')->nullable();
                    $table->text('description')->nullable();
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

        if (! Schema::hasTable('bos_pagu_anggarans')) {
            Schema::create('bos_pagu_anggarans', function (Blueprint $table) {
                $table->id();
                $table->integer('year');
                $table->string('tipe', 20)->default('bosp');
                $table->decimal('amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('arkas_kegiatans')) {
            Schema::create('arkas_kegiatans', function (Blueprint $table) {
                $table->id();
                $table->string('snp')->nullable();
                $table->string('komponen')->nullable();
                $table->string('kode_kegiatan', 50)->nullable();
                $table->string('kegiatan')->nullable();
                $table->text('definisi_operasional')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('arkas_barangs')) {
            Schema::create('arkas_barangs', function (Blueprint $table) {
                $table->id();
                $table->string('nama_barang');
                $table->string('satuan', 50)->nullable();
                $table->decimal('harga_min', 15, 2)->default(0);
                $table->decimal('harga_max', 15, 2)->default(0);
                $table->string('kode_rekening', 50)->nullable();
                $table->string('kode_belanja', 50)->nullable();
                $table->string('jenis_barang', 50)->nullable();
                $table->timestamps();
            });
        }

        $this->user = User::firstOrCreate(
            ['email' => 'bendahara_test@test.com'],
            [
                'name' => 'Bendahara Test',
                'role' => 'bendahara',
                'password' => bcrypt('password'),
            ]
        );
    }

    public function test_guest_cannot_access_rencana_rkas_index(): void
    {
        $response = $this->get(route('bos.rkas.index'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_rencana_rkas_index(): void
    {
        BosPaguAnggaran::create([
            'year' => 2026,
            'tipe' => 'bosp',
            'amount' => 500000000,
            'notes' => 'Pagu reguler',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('bos.rkas.index', ['year' => 2026]));

        $response->assertOk();
        $response->assertSee('Rencana RKAS BOSP');
        $response->assertSee('500.000.000');
    }

    public function test_user_can_save_pagu_anggaran(): void
    {
        $response = $this->actingAs($this->user)->post(route('bos.rkas.pagu.save'), [
            'year' => 2026,
            'amount' => 450000000,
            'notes' => '500 siswa x 900.000',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bos_pagu_anggarans', [
            'year' => 2026,
            'tipe' => 'bosp',
            'amount' => 450000000,
            'notes' => '500 siswa x 900.000',
        ]);
    }

    public function test_user_can_store_single_pengajuan_barang(): void
    {
        $guru = Guru::firstOrCreate(['name' => 'Pak Budi Guru']);

        $response = $this->actingAs($this->user)->post(route('bos.rkas.store'), [
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Laptop Asus Vivobook 14',
            'spesifikasi' => 'Core i5 8GB SSD 512GB',
            'kegiatan' => 'Pemeliharaan Sarana TI',
            'guru_id' => $guru->id,
            'volume' => 1,
            'satuan' => 'Unit',
            'harga' => 9500000,
            'karakter' => 'sekali',
            'bulan_pakai' => 'Maret',
            'keterangan' => 'Untuk lab komputer',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pengajuan_barangs', [
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Laptop Asus Vivobook 14',
            'volume' => 1,
            'harga' => 9500000,
            'jumlah' => 9500000,
            'bulan_pakai' => 'Maret',
            'is_recurring' => false,
            'recurring_group_id' => null,
        ]);
    }

    public function test_user_can_store_recurring_pengajuan_barang(): void
    {
        $response = $this->actingAs($this->user)->post(route('bos.rkas.store'), [
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Pembayaran Rekening Listrik PLN Pascabayar',
            'spesifikasi' => 'Daya 11.000 VA',
            'kegiatan' => 'Langganan Daya dan Jasa',
            'volume' => 1,
            'satuan' => 'Bulan',
            'harga' => 1500000,
            'karakter' => 'berulang',
            'months' => ['Januari', 'Februari', 'Maret', 'April'],
        ]);

        $response->assertRedirect();

        $items = PengajuanBarang::where('nama_barang', 'Pembayaran Rekening Listrik PLN Pascabayar')->get();
        $this->assertCount(4, $items);

        $firstItem = $items->first();
        $this->assertTrue((bool) $firstItem->is_recurring);
        $this->assertNotNull($firstItem->recurring_group_id);

        $this->assertEquals(
            4,
            PengajuanBarang::where('recurring_group_id', $firstItem->recurring_group_id)->count()
        );
    }

    public function test_user_can_update_single_item(): void
    {
        $item = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Kertas HVS A4',
            'kegiatan' => 'Administrasi Kantor',
            'volume' => 10,
            'satuan' => 'Rim',
            'harga' => 50000,
            'jumlah' => 500000,
            'bulan_pakai' => 'Januari',
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($this->user)->put(route('bos.rkas.update', $item->id), [
            'nama_barang' => 'Kertas HVS A4 80gr',
            'kegiatan' => 'Administrasi Kantor',
            'volume' => 12,
            'satuan' => 'Rim',
            'harga' => 55000,
            'bulan_pakai' => 'Januari',
        ]);

        $response->assertRedirect();
        $item->refresh();

        $this->assertEquals('Kertas HVS A4 80gr', $item->nama_barang);
        $this->assertEquals(12, (float) $item->volume);
        $this->assertEquals(660000, (float) $item->jumlah);
    }

    public function test_user_can_update_all_recurring_siblings(): void
    {
        $groupId = 'group-uuid-123';
        $months = ['Januari', 'Maret', 'Oktober'];

        foreach ($months as $m) {
            PengajuanBarang::create([
                'tahun_anggaran' => 2026,
                'nama_barang' => 'Kertas F4 Sinar Dunia',
                'kegiatan' => 'Ujian Siswa',
                'volume' => 20,
                'satuan' => 'Rim',
                'harga' => 58000,
                'jumlah' => 1160000,
                'bulan_pakai' => $m,
                'is_recurring' => true,
                'recurring_group_id' => $groupId,
            ]);
        }

        $itemToUpdate = PengajuanBarang::where('recurring_group_id', $groupId)->first();

        $response = $this->actingAs($this->user)->put(route('bos.rkas.update', $itemToUpdate->id), [
            'nama_barang' => 'Kertas F4 Sinar Dunia 75gr Revisi',
            'kegiatan' => 'Penggandaan Ujian',
            'volume' => 25,
            'satuan' => 'Rim',
            'harga' => 60000,
            'bulan_pakai' => $itemToUpdate->bulan_pakai,
            'update_all_recurring' => 1,
        ]);

        $response->assertRedirect();

        $updatedItems = PengajuanBarang::where('recurring_group_id', $groupId)->get();
        $this->assertCount(3, $updatedItems);

        foreach ($updatedItems as $it) {
            $this->assertEquals('Kertas F4 Sinar Dunia 75gr Revisi', $it->nama_barang);
            $this->assertEquals('Penggandaan Ujian', $it->kegiatan);
            $this->assertEquals(25, (float) $it->volume);
            $this->assertEquals(60000, (float) $it->harga);
            $this->assertEquals(1500000, (float) $it->jumlah);
        }
    }

    public function test_user_can_delete_single_item(): void
    {
        $item = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Spidol Whiteboard',
            'kegiatan' => 'KBM',
            'volume' => 5,
            'satuan' => 'Kotak',
            'harga' => 100000,
            'jumlah' => 500000,
            'bulan_pakai' => 'Januari',
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($this->user)->delete(route('bos.rkas.destroy', $item->id));

        $response->assertRedirect();
        $this->assertSoftDeleted('pengajuan_barangs', ['id' => $item->id]);
    }

    public function test_user_can_delete_all_recurring_siblings(): void
    {
        $groupId = 'group-uuid-delete-test';
        foreach (['Januari', 'Februari', 'Maret'] as $m) {
            PengajuanBarang::create([
                'tahun_anggaran' => 2026,
                'nama_barang' => 'BBM Genset',
                'kegiatan' => 'Sarpras',
                'volume' => 10,
                'satuan' => 'Liter',
                'harga' => 14000,
                'jumlah' => 140000,
                'bulan_pakai' => $m,
                'is_recurring' => true,
                'recurring_group_id' => $groupId,
            ]);
        }

        $oneItem = PengajuanBarang::where('recurring_group_id', $groupId)->first();

        $response = $this->actingAs($this->user)->delete(route('bos.rkas.destroy', $oneItem->id), [
            'delete_all_recurring' => 1,
        ]);

        $response->assertRedirect();
        $this->assertEquals(0, PengajuanBarang::where('recurring_group_id', $groupId)->count());
    }

    public function test_rencana_rkas_filters_by_month_and_karakter(): void
    {
        PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Kertas Januari Sekali',
            'kegiatan' => 'ATK',
            'volume' => 1,
            'satuan' => 'Rim',
            'harga' => 50000,
            'jumlah' => 50000,
            'bulan_pakai' => 'Januari',
            'is_recurring' => false,
        ]);

        PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Listrik Februari Rutin',
            'kegiatan' => 'Daya',
            'volume' => 1,
            'satuan' => 'Bulan',
            'harga' => 100000,
            'jumlah' => 100000,
            'bulan_pakai' => 'Februari',
            'is_recurring' => true,
        ]);

        $responseJan = $this->actingAs($this->user)->get(route('bos.rkas.index', ['year' => 2026, 'month' => 'Januari']));
        $responseJan->assertSee('Kertas Januari Sekali');
        $responseJan->assertDontSee('Listrik Februari Rutin');

        $responseRutin = $this->actingAs($this->user)->get(route('bos.rkas.index', ['year' => 2026, 'karakter' => 'berulang']));
        $responseRutin->assertSee('Listrik Februari Rutin');
        $responseRutin->assertDontSee('Kertas Januari Sekali');
    }

    public function test_store_recurring_item_with_empty_spesifikasi(): void
    {
        $payload = [
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Pembayaran Listrik SMK PGRI KRAS',
            'spesifikasi' => '', // kosong
            'kegiatan' => 'Listrik',
            'volume' => '1.005',
            'satuan' => 'Bulan',
            'harga' => '1.000',
            'karakter' => 'berulang',
            'months' => ['Januari', 'Februari'],
        ];

        $response = $this->actingAs($this->user)->post(route('bos.rkas.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Pembayaran Listrik SMK PGRI KRAS',
            'bulan_pakai' => 'Januari',
            'is_recurring' => true,
        ]);
        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Pembayaran Listrik SMK PGRI KRAS',
            'bulan_pakai' => 'Februari',
            'is_recurring' => true,
        ]);
    }

    public function test_store_multi_month_with_dynamic_cards_json(): void
    {
        $monthsCards = [
            ['id' => 1, 'month' => 'Januari', 'volume' => 5, 'unit' => 'Rim'],
            ['id' => 2, 'month' => 'Februari', 'volume' => 3, 'unit' => 'Rim'],
            ['id' => 3, 'month' => 'Maret', 'volume' => 2, 'unit' => 'Rim'],
        ];

        $payload = [
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Kertas HVS F4 75gr PaperOne',
            'spesifikasi' => 'PaperOne 75gr Rim',
            'kegiatan' => '02.02.01 - Kegiatan perpustakaan',
            'kode_rekening' => '5.1.02.01.01.0029',
            'harga' => '60.000',
            'months_json' => json_encode($monthsCards),
        ];

        $response = $this->actingAs($this->user)->post(route('bos.rkas.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Kertas HVS F4 75gr PaperOne',
            'kode_rekening' => '5.1.02.01.01.0029',
            'bulan_pakai' => 'Januari',
            'volume' => 5,
            'satuan' => 'Rim',
            'harga' => 60000,
            'jumlah' => 300000,
            'is_recurring' => true,
        ]);

        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Kertas HVS F4 75gr PaperOne',
            'bulan_pakai' => 'Februari',
            'volume' => 3,
            'satuan' => 'Rim',
            'harga' => 60000,
            'jumlah' => 180000,
            'is_recurring' => true,
        ]);

        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Kertas HVS F4 75gr PaperOne',
            'bulan_pakai' => 'Maret',
            'volume' => 2,
            'satuan' => 'Rim',
            'harga' => 60000,
            'jumlah' => 120000,
            'is_recurring' => true,
        ]);
    }

    public function test_search_kegiatan_endpoint(): void
    {
        ArkasKegiatan::create([
            'kode_kegiatan' => '02.02.01',
            'kegiatan' => 'Kegiatan perpustakaan minat baca',
            'komponen' => 'Pengembangan Perpustakaan',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('bos.rkas.search-kegiatan', ['q' => 'perpus']));
        $response->assertStatus(200);
        $response->assertJsonFragment(['kode_kegiatan' => '02.02.01']);

        // Jika kurang dari 2 karakter harus return array kosong
        $responseShort = $this->actingAs($this->user)->getJson(route('bos.rkas.search-kegiatan', ['q' => 'p']));
        $responseShort->assertStatus(200);
        $this->assertCount(0, $responseShort->json());
    }

    public function test_search_barang_endpoint(): void
    {
        ArkasBarang::create([
            'nama_barang' => 'Kertas HVS F4 75gr',
            'satuan' => 'Rim',
            'harga_min' => 50000,
            'harga_max' => 65000,
            'kode_rekening' => '5.1.02.01.01.0029',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('bos.rkas.search-barang', ['q' => 'kertas']));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'nama_barang' => 'Kertas HVS F4 75gr',
            'satuan' => 'Rim',
            'harga_max' => 65000,
        ]);
    }

    public function test_search_rekening_endpoint(): void
    {
        ArkasBarang::create([
            'nama_barang' => 'Buku Referensi',
            'satuan' => 'Eksemplar',
            'kode_rekening' => '5.1.02.01.01.0034 - Belanja Bahan Cetak',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('bos.rkas.search-rekening', ['q' => '5.1.02']));
        $response->assertStatus(200);
        $this->assertContains('5.1.02.01.01.0034 - Belanja Bahan Cetak', $response->json());
    }

    public function test_download_template_excel_endpoint(): void
    {
        $response = $this->actingAs($this->user)->get(route('bos.rkas.template', ['year' => 2026]));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=Template_Rencana_RKAS_2026.xlsx');
    }

    public function test_import_excel_with_guru_and_arkas_matching(): void
    {
        $guru = Guru::create([
            'name' => 'ARMY WIDHI CAHYONO',
            'is_active' => true,
        ]);

        $arkasKegiatan = ArkasKegiatan::create([
            'kode_kegiatan' => '02.02.01',
            'kegiatan' => 'Penyelenggaraan Asesmen dan Evaluasi Pembelajaran',
            'snp' => 'Standar Penilaian',
            'komponen' => 'Asesmen',
        ]);

        $arkasBarang = ArkasBarang::create([
            'nama_barang' => 'Kertas HVS F4 75gr',
            'satuan' => 'Rim',
            'harga_min' => 50000,
            'harga_max' => 65000,
            'kode_rekening' => '5.1.02.01.01.0029',
        ]);

        $csvContent = implode("\n", [
            'No,Kegiatan,Bulan,Nama Barang,Spesifikasi,Volume,Satuan,Harga,Total,Pemohon,Keterangan',
            '1,02.02.01,Januari,Kertas HVS F4 75gr,Merk SiDU,10,Rim,60000,600000,army,Cetak lembar ujian',
            '2,02.02.01,Februari,Kertas HVS F4 75gr,Merk SiDU,5,Rim,60000,300000,army,Cadangan ulangan',
        ]);

        $file = UploadedFile::fake()->createWithContent('rencana.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('bos.rkas.import'), [
            'file' => $file,
            'tahun_anggaran' => 2026,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pengajuan_barangs', [
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Kertas HVS F4 75gr',
            'guru_id' => $guru->id,
            'kode_rekening' => '5.1.02.01.01.0029',
            'bulan_pakai' => 'Januari',
            'is_recurring' => true,
        ]);

        $this->assertDatabaseHas('pengajuan_barangs', [
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Kertas HVS F4 75gr',
            'guru_id' => $guru->id,
            'kode_rekening' => '5.1.02.01.01.0029',
            'bulan_pakai' => 'Februari',
            'is_recurring' => true,
        ]);
    }

    public function test_import_excel_with_manual_fallback(): void
    {
        $csvContent = implode("\n", [
            'No,Kegiatan,Bulan,Nama Barang,Spesifikasi,Volume,Satuan,Harga,Total,Pemohon,Keterangan',
            '1,Pengecatan Pagar Sekolah,Maret,Bongkahan Batu Bata,Merah lokal,2,Truk,800000,1600000,Pak Tukang,Pondasi pagar keliling',
        ]);

        $file = UploadedFile::fake()->createWithContent('manual_rkas.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('bos.rkas.import'), [
            'file' => $file,
            'tahun_anggaran' => 2026,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pengajuan_barangs', [
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Bongkahan Batu Bata',
            'kegiatan' => 'Pengecatan Pagar Sekolah',
            'guru_id' => null,
            'volume' => 2,
            'satuan' => 'Truk',
            'harga' => 800000,
            'bulan_pakai' => 'Maret',
            'is_recurring' => false,
        ]);
    }

    public function test_user_can_store_item_with_kegiatan_sekolah(): void
    {
        $ks = KegiatanSekolah::create([
            'nama' => 'Pramuka',
            'kode' => 'PRA',
            'arkas_kegiatan_kode' => '03.03.06',
            'color' => '#16a34a',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('bos.rkas.store'), [
            'tahun_anggaran' => 2026,
            'kegiatan_sekolah_id' => $ks->id,
            'kegiatan_sekolah' => 'Pramuka',
            'kegiatan' => '03.03.06 - Pelaksanaan Kegiatan Kepramukaan',
            'nama_barang' => 'Tenda Regu Pramuka 6x4',
            'spesifikasi' => 'Bahan parasut tebal',
            'harga' => '1.200.000',
            'months_json' => json_encode([
                ['id' => 1, 'month' => 'April', 'volume' => 2, 'unit' => 'Set'],
            ]),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pengajuan_barangs', [
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Tenda Regu Pramuka 6x4',
            'kegiatan_sekolah' => 'Pramuka',
            'kegiatan_sekolah_id' => $ks->id,
            'kegiatan' => '03.03.06 - Pelaksanaan Kegiatan Kepramukaan',
            'bulan_pakai' => 'April',
            'volume' => 2,
            'satuan' => 'Set',
            'harga' => 1200000,
            'jumlah' => 2400000,
        ]);
    }

    public function test_filter_by_kegiatan_sekolah(): void
    {
        $ksPramuka = KegiatanSekolah::create([
            'nama' => 'Pramuka',
            'kode' => 'PRA',
            'is_active' => true,
        ]);

        $ksOsis = KegiatanSekolah::create([
            'nama' => 'OSIS',
            'kode' => 'OSIS',
            'is_active' => true,
        ]);

        PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Tali Pramuka',
            'kegiatan_sekolah' => 'Pramuka',
            'kegiatan_sekolah_id' => $ksPramuka->id,
            'kegiatan' => '03.03.06 - Kepramukaan',
            'volume' => 10,
            'satuan' => 'Roll',
            'harga' => 25000,
            'jumlah' => 250000,
            'bulan_pakai' => 'Mei',
            'is_recurring' => false,
        ]);

        PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Spanduk LDKS OSIS',
            'kegiatan_sekolah' => 'OSIS',
            'kegiatan_sekolah_id' => $ksOsis->id,
            'kegiatan' => '03.03.07 - OSIS',
            'volume' => 1,
            'satuan' => 'Lembar',
            'harga' => 150000,
            'jumlah' => 150000,
            'bulan_pakai' => 'Juni',
            'is_recurring' => false,
        ]);

        $responsePramuka = $this->actingAs($this->user)->get(route('bos.rkas.index', [
            'year' => 2026,
            'kegiatan_sekolah_id' => $ksPramuka->id,
        ]));

        $responsePramuka->assertOk();
        $responsePramuka->assertSee('Tali Pramuka');
        $responsePramuka->assertDontSee('Spanduk LDKS OSIS');
    }

    public function test_import_excel_with_kegiatan_sekolah_auto_mapping(): void
    {
        ArkasKegiatan::create([
            'kode_kegiatan' => '03.03.06',
            'kegiatan' => 'Pelaksanaan Kegiatan Kepramukaan',
        ]);

        $ks = KegiatanSekolah::create([
            'nama' => 'Pramuka',
            'kode' => 'PRA',
            'arkas_kegiatan_kode' => '03.03.06',
            'color' => '#16a34a',
            'is_active' => true,
        ]);

        $csvContent = implode("\n", [
            'No,Kegiatan,Bulan,Nama Barang,Spesifikasi,Volume,Satuan,Harga,Total,Pemohon,Keterangan',
            '1,Pramuka,Agustus,Pluit Morse Pramuka,Bahan logam,15,Buah,15000,225000,Sekolah,Latihan semaphore & morse',
        ]);

        $file = UploadedFile::fake()->createWithContent('kegiatan_sekolah_test.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('bos.rkas.import'), [
            'file' => $file,
            'tahun_anggaran' => 2026,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pengajuan_barangs', [
            'tahun_anggaran' => 2026,
            'nama_barang' => 'Pluit Morse Pramuka',
            'kegiatan_sekolah' => 'Pramuka',
            'kegiatan_sekolah_id' => $ks->id,
            'kegiatan' => '03.03.06 - Pelaksanaan Kegiatan Kepramukaan',
            'bulan_pakai' => 'Agustus',
            'volume' => 15,
            'satuan' => 'Buah',
            'harga' => 15000,
            'jumlah' => 225000,
        ]);
    }

    public function test_user_can_update_item_with_multi_month_cards(): void
    {
        $item = PengajuanBarang::create([
            'tahun_anggaran' => 2026,
            'pemohon_id' => $this->user->id,
            'nama_barang' => 'Kertas F4',
            'spesifikasi' => '70 gsm',
            'kegiatan' => '02.01.01 - Penyusunan Kurikulum',
            'volume' => 5,
            'satuan' => 'Rim',
            'harga' => 50000,
            'jumlah' => 250000,
            'bulan_pakai' => 'Januari',
            'is_recurring' => false,
        ]);

        $monthsJson = json_encode([
            ['id' => $item->id, 'month' => 'Januari', 'volume' => 10, 'unit' => 'Rim'],
            ['id' => null, 'month' => 'Februari', 'volume' => 8, 'unit' => 'Rim'],
            ['id' => null, 'month' => 'Maret', 'volume' => 12, 'unit' => 'Rim'],
        ]);

        $response = $this->actingAs($this->user)->put(route('bos.rkas.update', $item->id), [
            'nama_barang' => 'Kertas F4 75gsm',
            'spesifikasi' => '75 gsm putih',
            'kegiatan' => '02.01.01 - Penyusunan Kurikulum',
            'harga' => '55.000',
            'months_json' => $monthsJson,
        ]);

        $response->assertSessionHas('success');

        $this->assertEquals(3, PengajuanBarang::where('nama_barang', 'Kertas F4 75gsm')->count());
        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Kertas F4 75gsm',
            'bulan_pakai' => 'Januari',
            'volume' => 10,
            'harga' => 55000,
            'jumlah' => 550000,
            'is_recurring' => true,
        ]);
        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Kertas F4 75gsm',
            'bulan_pakai' => 'Februari',
            'volume' => 8,
            'harga' => 55000,
            'jumlah' => 440000,
            'is_recurring' => true,
        ]);
        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Kertas F4 75gsm',
            'bulan_pakai' => 'Maret',
            'volume' => 12,
            'harga' => 55000,
            'jumlah' => 660000,
            'is_recurring' => true,
        ]);
    }

    public function test_rkas_index_view_contains_utility_profiles_and_supports_utility_profile_id(): void
    {
        $profile = UtilityProfile::firstOrCreate(
            ['code' => 'listrik_stm_test'],
            [
                'name' => 'Pembayaran Listrik atas nama STM',
                'category' => 'listrik_pasca',
                'customer_name' => 'STM',
                'idpel' => '514031084530',
                'tariff_power' => 'S1/16500 VA',
                'provider' => 'PLN',
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($this->user)->get(route('bos.rkas.index', ['year' => 2026]));
        $response->assertOk();
        $response->assertViewHas('utilityProfiles');
        $response->assertViewHas('utilityProfilesFormatted');
        $response->assertSee('Pembayaran Listrik atas nama STM');

        // Test saving with utility_profile_id
        $storeResponse = $this->actingAs($this->user)->post(route('bos.rkas.store'), [
            'tahun_anggaran' => 2026,
            'kegiatan_sekolah' => 'Listrik',
            'kegiatan' => '06.07.01 - Langganan Daya dan Jasa Listrik',
            'utility_profile_id' => $profile->id,
            'nama_barang' => $profile->name,
            'spesifikasi' => 'IDPEL: 514031084530 | Daya: S1/16500 VA | a/n STM',
            'harga' => 1500000,
            'satuan' => 'Bulan',
            'volume' => 1,
            'months_json' => json_encode([
                ['month' => 'Januari', 'volume' => 1, 'unit' => 'Bulan'],
            ]),
        ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('pengajuan_barangs', [
            'utility_profile_id' => $profile->id,
            'nama_barang' => 'Pembayaran Listrik atas nama STM',
            'satuan' => 'Bulan',
        ]);
    }
}
