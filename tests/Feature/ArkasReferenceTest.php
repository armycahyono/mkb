<?php

namespace Tests\Feature;

use App\Models\ArkasBarang;
use App\Models\ArkasKegiatan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArkasReferenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('username', 50)->unique();
                $table->string('email')->unique();
                $table->string('role', 30)->default('bendahara');
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('arkas_kegiatans')) {
            Schema::create('arkas_kegiatans', function (Blueprint $table) {
                $table->id();
                $table->string('snp', 100)->index();
                $table->string('komponen', 150)->index();
                $table->string('kode_kegiatan', 50)->index();
                $table->text('kegiatan');
                $table->text('definisi_operasional')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('arkas_barangs')) {
            Schema::create('arkas_barangs', function (Blueprint $table) {
                $table->id();
                $table->string('jenis_barang', 100)->index();
                $table->string('kode_rekening', 50)->index();
                $table->string('nama_barang', 255)->index();
                $table->string('satuan', 50);
                $table->decimal('harga_min', 15, 2);
                $table->decimal('harga_max', 15, 2);
                $table->unsignedInteger('varian_count')->default(1);
                $table->string('kode_belanja', 50)->nullable();
                $table->timestamps();
            });
        }
    }

    protected function makeUser(string $role = 'super_admin'): User
    {
        return User::firstOrCreate(
            ['username' => 'test_'.$role],
            [
                'name' => 'Test User '.$role,
                'email' => 'test_'.$role.'@example.com',
                'password' => bcrypt('password'),
                'role' => $role,
            ]
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('bos.referensi-arkas.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_access_referensi_arkas_index(): void
    {
        $user = $this->makeUser('super_admin');

        $response = $this->actingAs($user)->get(route('bos.referensi-arkas.index'));

        $response->assertStatus(200);
        $response->assertSee('Referensi ARKAS');
        $response->assertSee('Kegiatan ARKAS');
        $response->assertSee('Referensi Barang');
        $response->assertSee('tableKegiatan');
        $response->assertSee('tableBarang');
        $response->assertSee('data-bs-target="#tab-barang"', false);
        $response->assertSee('href="#tab-barang"', false);
    }

    public function test_data_kegiatan_returns_valid_datatables_json(): void
    {
        $user = $this->makeUser('super_admin');

        ArkasKegiatan::firstOrCreate(
            ['kode_kegiatan' => '99.99.99'],
            [
                'snp' => 'Standar Uji',
                'komponen' => 'Komponen Uji Coba',
                'kegiatan' => 'Kegiatan Uji Coba Spesifik',
                'definisi_operasional' => 'Definisi lengkap kegiatan uji coba untuk memastikan DataTables bekerja.',
            ]
        );

        $response = $this->actingAs($user)->getJson(route('bos.referensi-arkas.data-kegiatan', [
            'snp' => 'Standar Uji',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);

        $json = $response->json();
        $this->assertNotEmpty($json['data']);
        $this->assertStringContainsString('Standar Uji', $json['data'][0]['snp_badge']);
        $this->assertStringContainsString('99.99.99', $json['data'][0]['kode_kegiatan_display']);
    }

    public function test_data_barang_returns_valid_datatables_json_with_price_range(): void
    {
        $user = $this->makeUser('super_admin');

        ArkasBarang::firstOrCreate(
            ['kode_rekening' => '9.9.99.99.99.9999', 'nama_barang' => 'Barang Uji Rentang Harga'],
            [
                'jenis_barang' => 'BARANG JASA',
                'satuan' => 'Pcs',
                'harga_min' => 100000,
                'harga_max' => 150000,
                'varian_count' => 5,
                'kode_belanja' => 'BHP',
            ]
        );

        $response = $this->actingAs($user)->getJson(route('bos.referensi-arkas.data-barang', [
            'kode_rekening' => '9.9.99.99.99.9999',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);

        $json = $response->json();
        $this->assertNotEmpty($json['data']);
        $this->assertStringContainsString('Rp 100.000 - Rp 150.000', $json['data'][0]['harga_range_display']);
        $this->assertStringContainsString('BARANG JASA', $json['data'][0]['jenis_badge']);
        $this->assertStringContainsString('5 varian harga', $json['data'][0]['keterangan_varian']);
        $this->assertStringContainsString('BHP', $json['data'][0]['kode_belanja_display']);
        $this->assertStringContainsString('font-weight: 400', $json['data'][0]['nama_barang_display']);
    }

    public function test_arkas_barang_harga_range_display_accessor(): void
    {
        $itemRange = new ArkasBarang([
            'harga_min' => 150000,
            'harga_max' => 200000,
        ]);
        $this->assertEquals('Rp 150.000 - Rp 200.000', $itemRange->harga_range_display);

        $itemSingle = new ArkasBarang([
            'harga_min' => 150000,
            'harga_max' => 150000,
        ]);
        $this->assertEquals('Rp 150.000', $itemSingle->harga_range_display);
    }
}
