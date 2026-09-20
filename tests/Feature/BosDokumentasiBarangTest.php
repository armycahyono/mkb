<?php

namespace Tests\Feature;

use App\Models\BarangDokumentasi;
use App\Models\BarangDokumentasiFoto;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BosDokumentasiBarangTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTablesExist();
        Storage::fake('public');
    }

    private function ensureTablesExist(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_id')->nullable();
                $table->string('name');
                $table->string('username')->nullable();
                $table->string('email')->unique();
                $table->string('role')->nullable();
                $table->string('telegram_chat_id')->nullable();
                $table->string('telegram_username')->nullable();
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
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('gurus')) {
            Schema::create('gurus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('title_prefix')->nullable();
                $table->string('title_suffix')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('jabatans')) {
            Schema::create('jabatans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('guru_jabatans')) {
            Schema::create('guru_jabatans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_id')->nullable();
                $table->foreignId('jabatan_id')->nullable();
                $table->string('period_year', 20)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('kas_tunais')) {
            Schema::create('kas_tunais', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOS');
                $table->date('transaction_date')->nullable();
                $table->unsignedSmallInteger('year')->nullable();
                $table->unsignedInteger('sequence')->nullable();
                $table->string('transaction_type', 10)->default('keluar');
                $table->string('category', 50)->default('belanja_tunai');
                $table->decimal('amount', 15, 2)->default(0);
                $table->text('description')->nullable();
                $table->string('person_name')->nullable();
                $table->string('evidence_number')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('barang_dokumentasis')) {
            Schema::create('barang_dokumentasis', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOS');
                $table->integer('year');
                $table->string('no_nota', 100);
                $table->date('tanggal_terima');
                $table->string('nama_toko')->nullable();
                $table->text('uraian')->nullable();
                $table->decimal('nominal', 15, 2)->nullable();
                $table->foreignId('kas_tunai_id')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('barang_dokumentasi_fotos')) {
            Schema::create('barang_dokumentasi_fotos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('barang_dokumentasi_id')->constrained('barang_dokumentasis')->cascadeOnDelete();
                $table->integer('urutan')->default(1);
                $table->string('file_path');
                $table->string('keterangan')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function getTestUser(): User
    {
        return User::first() ?? User::create([
            'name' => 'Test Bendahara',
            'email' => 'bendahara@mkb.test',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_index_page_can_be_accessed_by_authenticated_user(): void
    {
        $user = $this->getTestUser();

        $response = $this->actingAs($user)->get(route('bos.dokumentasi-barang.index'));

        $response->assertStatus(200);
        $response->assertSee('Foto BOSP');
    }

    public function test_can_store_barang_dokumentasi_with_photos(): void
    {
        $user = $this->getTestUser();

        $file1 = UploadedFile::fake()->image('foto1.jpg');
        $file2 = UploadedFile::fake()->image('foto2.jpg');
        $file3 = UploadedFile::fake()->image('foto3.jpg');

        $response = $this->actingAs($user)->post(route('bos.dokumentasi-barang.store'), [
            'no_nota' => '024/BOS/2026',
            'tanggal_terima' => '2026-02-12',
            'nama_toko' => 'Toko Barokah ATK',
            'uraian' => 'Papan Tulis dan Spidol',
            'nominal' => 850000,
            'photo_files' => [$file1, $file2, $file3],
            'captions' => [
                'Papan tulis saat diterima',
                'Fisik spidol dan whiteboard',
                'Papan tulis disandingkan nota toko',
            ],
        ]);

        $response->assertRedirect(route('bos.dokumentasi-barang.index'));

        $this->assertDatabaseHas('barang_dokumentasis', [
            'no_nota' => '024/BOS/2026',
            'nama_toko' => 'Toko Barokah ATK',
            'uraian' => 'Papan Tulis dan Spidol',
        ]);

        $doc = BarangDokumentasi::where('no_nota', '024/BOS/2026')->first();
        $this->assertNotNull($doc);
        $this->assertCount(3, $doc->fotos);
        $this->assertTrue($doc->is_complete);
    }

    public function test_photo_download_has_correct_custom_filename_format(): void
    {
        $user = $this->getTestUser();

        $doc = BarangDokumentasi::create([
            'funding_source' => 'BOS',
            'year' => 2026,
            'no_nota' => '024/BOS/2026',
            'tanggal_terima' => '2026-02-12',
            'nama_toko' => 'Toko Barokah ATK',
            'uraian' => 'Papan Tulis dan Spidol',
        ]);

        $fakePath = 'dokumentasi_barang/2026/024-BOS-2026/foto_1_abc.jpg';
        Storage::disk('public')->put($fakePath, 'dummy image content');

        $foto = BarangDokumentasiFoto::create([
            'barang_dokumentasi_id' => $doc->id,
            'urutan' => 1,
            'file_path' => $fakePath,
            'keterangan' => 'Papan tulis dan spidol',
        ]);

        // Verifikasi nama file terformat: {No_Nota}_Foto_{urutan}_{keterangan}.jpg
        $expectedFilename = '024-BOS-2026_Foto_1_Papan_Tulis_Dan_Spidol.jpg';
        $this->assertEquals($expectedFilename, $foto->download_filename);

        $response = $this->actingAs($user)->get(route('bos.dokumentasi-barang.download-foto', $foto->id));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename='.$expectedFilename);
    }

    public function test_zip_download_contains_all_photos_with_custom_names(): void
    {
        $user = $this->getTestUser();

        $doc = BarangDokumentasi::create([
            'funding_source' => 'BOS',
            'year' => 2026,
            'no_nota' => '025/BOS/2026',
            'tanggal_terima' => '2026-02-14',
            'nama_toko' => 'CV Maju Jaya',
            'uraian' => 'Kabel HDMI dan Router Wifi',
        ]);

        $path1 = 'dokumentasi_barang/2026/025-BOS-2026/foto_1.jpg';
        $path2 = 'dokumentasi_barang/2026/025-BOS-2026/foto_2.jpg';
        Storage::disk('public')->put($path1, 'content 1');
        Storage::disk('public')->put($path2, 'content 2');

        BarangDokumentasiFoto::create([
            'barang_dokumentasi_id' => $doc->id,
            'urutan' => 1,
            'file_path' => $path1,
            'keterangan' => 'Router wifi saat unboxing',
        ]);

        BarangDokumentasiFoto::create([
            'barang_dokumentasi_id' => $doc->id,
            'urutan' => 2,
            'file_path' => $path2,
            'keterangan' => 'Fisik kabel HDMI 10 meter',
        ]);

        $response = $this->actingAs($user)->get(route('bos.dokumentasi-barang.download-zip', $doc->id));
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition') ?? '', 'Dokumentasi_025-BOS-2026.zip'));
    }

    public function test_print_page_renders_successfully(): void
    {
        $user = $this->getTestUser();

        $doc = BarangDokumentasi::create([
            'funding_source' => 'BOS',
            'year' => 2026,
            'no_nota' => '026/BOS/2026',
            'tanggal_terima' => '2026-02-15',
            'nama_toko' => 'Toko Mitra',
            'uraian' => 'Buku Siswa Kelas X',
        ]);

        $response = $this->actingAs($user)->get(route('bos.dokumentasi-barang.print', $doc->id));
        $response->assertStatus(200);
        $response->assertSee('LEMBAR BUKTI DOKUMENTASI FISIK PENGADAAN BARANG');
        $response->assertSee('026/BOS/2026');
        $response->assertSee('FOTO 1');
        $response->assertSee('FOTO 2');
        $response->assertSee('FOTO 3');
    }

    public function test_can_store_with_caption_derived_from_uraian(): void
    {
        $user = $this->getTestUser();

        $file1 = UploadedFile::fake()->image('foto1.jpg');
        $file2 = UploadedFile::fake()->image('foto2.jpg');
        $file3 = UploadedFile::fake()->image('foto3.jpg');

        $response = $this->actingAs($user)->post(route('bos.dokumentasi-barang.store'), [
            'no_nota' => '027/BOS/2026',
            'tanggal_terima' => '2026-02-18',
            'nama_toko' => 'Toko Sarana Bersama',
            'uraian' => 'Pengadaan Meja Kursi Siswa',
            'photo_files' => [$file1, $file2, $file3],
        ]);

        $response->assertRedirect(route('bos.dokumentasi-barang.index'));

        $doc = BarangDokumentasi::where('no_nota', '027/BOS/2026')->first();
        $this->assertNotNull($doc);
        $this->assertCount(3, $doc->fotos);

        // Pastikan ketiga foto mendapatkan keterangan otomatis dari uraian belanja
        foreach ($doc->fotos as $foto) {
            $this->assertEquals('Pengadaan Meja Kursi Siswa', $foto->keterangan);
            $this->assertStringContainsString('Pengadaan_Meja_Kursi_Siswa', $foto->download_filename);
        }
    }

    public function test_available_notas_endpoint_returns_json(): void
    {
        $user = $this->getTestUser();

        $response = $this->actingAs($user)->get(route('bos.dokumentasi-barang.available-notas', ['year' => 2026]));

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_index_displays_tambah_foto_and_sorts_by_newest_date_first(): void
    {
        $user = $this->getTestUser();

        // Buat data dengan tanggal lebih lama
        $docOld = BarangDokumentasi::create([
            'funding_source' => 'BOS',
            'year' => 2026,
            'no_nota' => 'OLD/001',
            'tanggal_terima' => '2026-02-05',
            'uraian' => 'Barang Lama',
        ]);

        // Buat data dengan tanggal lebih baru beserta foto
        $docNew = BarangDokumentasi::create([
            'funding_source' => 'BOS',
            'year' => 2026,
            'no_nota' => 'NEW/002',
            'tanggal_terima' => '2026-02-25',
            'uraian' => 'Barang Baru',
        ]);

        $filePath = 'dokumentasi_barang/2026/NEW_002/foto1.jpg';
        Storage::disk('public')->put($filePath, 'fake image content');
        BarangDokumentasiFoto::create([
            'barang_dokumentasi_id' => $docNew->id,
            'urutan' => 1,
            'file_path' => $filePath,
            'keterangan' => 'Barang Baru',
        ]);

        $response = $this->actingAs($user)->get(route('bos.dokumentasi-barang.index', ['year' => 2026]));

        $response->assertStatus(200);
        $response->assertSee('Tambah Foto');
        $response->assertSee('Foto 1');

        // Pastikan Barang Baru (tanggal terbaru) muncul sebelum Barang Lama
        $content = $response->getContent();
        $posNew = strpos($content, 'NEW/002');
        $posOld = strpos($content, 'OLD/001');

        $this->assertNotFalse($posNew);
        $this->assertNotFalse($posOld);
        $this->assertLessThan($posOld, $posNew, 'Dokumentasi dengan tanggal lebih baru harus muncul lebih dulu di atas.');
    }

    public function test_store_validation_requires_uraian(): void
    {
        $user = $this->getTestUser();

        $response = $this->actingAs($user)->post(route('bos.dokumentasi-barang.store'), [
            'no_nota' => '028/BOS/2026',
            'tanggal_terima' => '2026-02-18',
            'uraian' => '', // kosong
        ]);

        $response->assertSessionHasErrors('uraian');
    }

    public function test_sender_account_information_is_displayed_and_searchable(): void
    {
        $user = $this->getTestUser();
        $user->update([
            'name' => 'Budi Santoso',
            'telegram_username' => 'budibos',
            'role' => 'team_bosp',
        ]);

        $doc = BarangDokumentasi::create([
            'funding_source' => 'BOS',
            'year' => 2026,
            'no_nota' => 'NOTA/SENDER/01',
            'tanggal_terima' => '2026-03-01',
            'nama_toko' => 'Toko Alat',
            'uraian' => 'Pengadaan perkakas kantor',
            'created_by' => $user->id,
        ]);

        $this->assertEquals('Budi Santoso', $doc->sender_name);
        $this->assertStringContainsString('@budibos', $doc->sender_detail);
        $this->assertEquals('Tim BOS', $doc->creator_role_label);

        // Test index page shows sender name and telegram username
        $response = $this->actingAs($user)->get(route('bos.dokumentasi-barang.index', ['year' => 2026]));
        $response->assertOk();
        $response->assertSee('Budi Santoso');
        $response->assertSee('@budibos');

        // Test searching by sender's telegram username
        $searchResponse = $this->actingAs($user)->get(route('bos.dokumentasi-barang.index', ['year' => 2026, 'search' => 'budibos']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('NOTA/SENDER/01');

        // Test show endpoint returns sender info
        $showResponse = $this->actingAs($user)->getJson(route('bos.dokumentasi-barang.show', $doc->id));
        $showResponse->assertOk();
        $showResponse->assertJsonFragment([
            'sender_name' => 'Budi Santoso',
        ]);
    }
}
