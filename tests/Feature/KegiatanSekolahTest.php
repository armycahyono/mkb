<?php

namespace Tests\Feature;

use App\Models\ArkasKegiatan;
use App\Models\KegiatanSekolah;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KegiatanSekolahTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('role', 30)->default('bendahara');
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
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

        if (! Schema::hasTable('pengajuan_barangs')) {
            Schema::create('pengajuan_barangs', function (Blueprint $table) {
                $table->id();
                $table->integer('tahun_anggaran')->default(2026);
                $table->string('nama_barang');
                $table->string('kegiatan_sekolah')->nullable();
                $table->unsignedBigInteger('kegiatan_sekolah_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $this->user = User::firstOrCreate(
            ['email' => 'admin_test@test.com'],
            [
                'name' => 'Admin Test',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]
        );
    }

    public function test_user_can_view_kegiatan_sekolah_index(): void
    {
        $ks = KegiatanSekolah::create([
            'nama' => 'Pramuka',
            'kode' => 'PRA',
            'color' => '#16a34a',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('kegiatan-sekolah.index'));
        $response->assertOk();
        $response->assertSee('Master Program');
        $response->assertSee('Pramuka');
        $response->assertSee('PRA');
    }

    public function test_user_can_create_kegiatan_sekolah(): void
    {
        $arkas = ArkasKegiatan::create([
            'kode_kegiatan' => '03.03.07',
            'kegiatan' => 'Kegiatan Ekstrakurikuler Kesenian dan Olahraga',
        ]);

        $response = $this->actingAs($this->user)->post(route('kegiatan-sekolah.store'), [
            'nama' => 'Robotik & Coding',
            'kode' => 'ROBO',
            'arkas_kegiatan_kode' => $arkas->kode_kegiatan,
            'color' => '#0891b2',
            'urutan' => 15,
            'keterangan' => 'Ekstrakurikuler teknologi dan sains',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kegiatan_sekolahs', [
            'nama' => 'Robotik & Coding',
            'kode' => 'ROBO',
            'arkas_kegiatan_kode' => '03.03.07',
            'color' => '#0891b2',
            'urutan' => 15,
        ]);
    }

    public function test_user_can_create_kegiatan_sekolah_by_writing_full_arkas_label(): void
    {
        ArkasKegiatan::create([
            'kode_kegiatan' => '03.03.06',
            'kegiatan' => 'Pengelolaan Perpustakaan',
        ]);

        $response = $this->actingAs($this->user)->post(route('kegiatan-sekolah.store'), [
            'nama' => 'Pustaka Digital',
            'kode' => 'PUS',
            'arkas_kegiatan_kode' => '03.03.06 - Pengelolaan Perpustakaan (Pengembangan Perpustakaan)',
            'color' => '#16a34a',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kegiatan_sekolahs', [
            'nama' => 'Pustaka Digital',
            'arkas_kegiatan_kode' => '03.03.06',
        ]);
    }

    public function test_user_can_create_kegiatan_sekolah_with_manual_arkas_code(): void
    {
        $response = $this->actingAs($this->user)->post(route('kegiatan-sekolah.store'), [
            'nama' => 'Komunitas Literasi Bebas',
            'kode' => 'KLB',
            'arkas_kegiatan_kode' => 'MANUAL-LIT-01',
            'color' => '#d97706',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kegiatan_sekolahs', [
            'nama' => 'Komunitas Literasi Bebas',
            'arkas_kegiatan_kode' => 'MANUAL-LIT-01',
        ]);
    }

    public function test_user_can_update_kegiatan_sekolah(): void
    {
        $ks = KegiatanSekolah::create([
            'nama' => 'Paskibraka',
            'kode' => 'PAS',
            'color' => '#dc2626',
        ]);

        $response = $this->actingAs($this->user)->put(route('kegiatan-sekolah.update', $ks->id), [
            'nama' => 'Paskibra Sekolah',
            'kode' => 'PASKIB',
            'color' => '#b91c1c',
            'urutan' => 2,
            'keterangan' => 'Pasukan Pengibar Bendera',
        ]);

        $response->assertRedirect();
        $ks->refresh();

        $this->assertEquals('Paskibra Sekolah', $ks->nama);
        $this->assertEquals('PASKIB', $ks->kode);
        $this->assertEquals('#b91c1c', $ks->color);
    }

    public function test_user_can_toggle_kegiatan_sekolah_status(): void
    {
        $ks = KegiatanSekolah::create([
            'nama' => 'Futsal',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('kegiatan-sekolah.toggle-status', $ks->id));
        $response->assertRedirect();

        $ks->refresh();
        $this->assertFalse((bool) $ks->is_active);

        $response2 = $this->actingAs($this->user)->post(route('kegiatan-sekolah.toggle-status', $ks->id));
        $response2->assertRedirect();

        $ks->refresh();
        $this->assertTrue((bool) $ks->is_active);
    }

    public function test_user_can_delete_kegiatan_sekolah(): void
    {
        $ks = KegiatanSekolah::create([
            'nama' => 'Drum Band',
            'kode' => 'DB',
        ]);

        $response = $this->actingAs($this->user)->delete(route('kegiatan-sekolah.destroy', $ks->id));
        $response->assertRedirect();

        $this->assertSoftDeleted('kegiatan_sekolahs', ['id' => $ks->id]);
    }
}
