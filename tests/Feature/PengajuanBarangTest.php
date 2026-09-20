<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\PengajuanBarang;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PengajuanBarangTest extends TestCase
{
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

        if (! Schema::hasTable('pengajuan_barangs')) {
            Schema::create('pengajuan_barangs', function (Blueprint $table) {
                $table->id();
                $table->year('tahun_anggaran');
                $table->foreignId('pemohon_id')->nullable()->constrained('users');
                $table->unsignedBigInteger('guru_id')->nullable();
                $table->string('nama_barang');
                $table->text('spesifikasi');
                $table->decimal('volume', 12, 2);
                $table->string('satuan');
                $table->decimal('harga', 15, 2);
                $table->decimal('jumlah', 15, 2);
                $table->string('kegiatan');
                $table->string('bulan_pakai');
                $table->text('keterangan')->nullable();
                $table->string('realisasi')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_guest_cannot_access_pengajuan_barang_index(): void
    {
        $response = $this->get(route('pengajuan-barang.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_pengajuan_barang_index(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $pengajuan = PengajuanBarang::factory()->create([
            'pemohon_id' => $user->id,
            'nama_barang' => 'Kertas A4',
        ]);

        $response = $this->actingAs($user)->get(route('pengajuan-barang.index'));
        $response->assertOk();
        $response->assertSee('Kertas A4');
        $response->assertSee('Pengajuan Barang');
    }

    public function test_user_can_view_create_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($user)->get(route('pengajuan-barang.create'));
        $response->assertOk();
        $response->assertSee('Rencana Barang yang diajukan');
        $response->assertSee('Tahun anggaran :');
        $response->assertSee('Pemohon :');
        $response->assertSee('+ tambah barang');
    }

    public function test_user_can_create_pengajuan_barang(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $payload = [
            'tahun_anggaran' => 2026,
            'pemohon_id' => $user->id,
            'nama_barang' => 'Spidol Boardmarker',
            'spesifikasi' => 'Warna Hitam',
            'volume' => 12,
            'satuan' => 'Pcs',
            'harga' => 10000,
            'jumlah' => 120000,
            'kegiatan' => 'KBM Semester Ganjil',
            'bulan_pakai' => 'Juli',
            'keterangan' => 'Untuk ruang guru',
        ];

        $response = $this->actingAs($user)->post(route('pengajuan-barang.store'), $payload);
        $response->assertRedirect(route('pengajuan-barang.index'));
        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Spidol Boardmarker',
            'jumlah' => 120000,
        ]);
    }

    public function test_user_can_create_multiple_pengajuan_barang_at_once(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $multiPayload = [
            'tahun_anggaran' => 2026,
            'pemohon_id' => $user->id,
            'kegiatan' => 'Kegiatan PTS Genap',
            'bulan_pakai' => 'Maret',
            'items' => [
                [
                    'nama_barang' => 'Kertas Folio Bergaris',
                    'spesifikasi' => 'Sinar Dunia 100 lembar',
                    'volume' => 5,
                    'satuan' => 'Rim',
                    'harga' => 50000,
                    'jumlah' => 250000,
                    'keterangan' => 'Untuk ujian',
                ],
                [
                    'nama_barang' => 'Penghapus Papan Tulis',
                    'spesifikasi' => 'Magnetik',
                    'volume' => 10,
                    'satuan' => 'Buah',
                    'harga' => 15000,
                    'jumlah' => 150000,
                    'kegiatan' => 'Operasional Kelas',
                    'bulan_pakai' => 'April',
                    'keterangan' => null,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('pengajuan-barang.store'), $multiPayload);
        $response->assertRedirect(route('pengajuan-barang.index'));
        $response->assertSessionHas('success', 'Berhasil menyimpan 2 rencana pengajuan barang.');

        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Kertas Folio Bergaris',
            'volume' => 5,
            'jumlah' => 250000,
            'kegiatan' => 'Kegiatan PTS Genap',
            'bulan_pakai' => 'Maret',
        ]);

        $this->assertDatabaseHas('pengajuan_barangs', [
            'nama_barang' => 'Penghapus Papan Tulis',
            'volume' => 10,
            'jumlah' => 150000,
            'kegiatan' => 'Operasional Kelas',
            'bulan_pakai' => 'April',
        ]);
    }

    public function test_owner_can_update_pengajuan_barang(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $pengajuan = PengajuanBarang::factory()->create([
            'pemohon_id' => $user->id,
            'nama_barang' => 'Buku Kas',
        ]);

        $updatePayload = [
            'tahun_anggaran' => $pengajuan->tahun_anggaran,
            'pemohon_id' => $user->id,
            'nama_barang' => 'Buku Kas Folio Bergaris',
            'spesifikasi' => $pengajuan->spesifikasi,
            'volume' => $pengajuan->volume,
            'satuan' => $pengajuan->satuan,
            'harga' => $pengajuan->harga,
            'jumlah' => $pengajuan->jumlah,
            'kegiatan' => $pengajuan->kegiatan,
            'bulan_pakai' => $pengajuan->bulan_pakai,
        ];

        $response = $this->actingAs($user)->put(route('pengajuan-barang.update', $pengajuan), $updatePayload);
        $response->assertRedirect(route('pengajuan-barang.index'));
        $this->assertDatabaseHas('pengajuan_barangs', [
            'id' => $pengajuan->id,
            'nama_barang' => 'Buku Kas Folio Bergaris',
        ]);
    }

    public function test_officer_can_update_realisasi(): void
    {
        $officer = User::factory()->create([
            'role' => User::ROLE_BENDAHARA_BOS,
        ]);

        $pengajuan = PengajuanBarang::factory()->create([
            'realisasi' => null,
        ]);

        $response = $this->actingAs($officer)->post(route('pengajuan-barang.realisasi', $pengajuan), [
            'realisasi' => 'Disetujui - Beli di Marketplace SIPlah',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pengajuan_barangs', [
            'id' => $pengajuan->id,
            'realisasi' => 'Disetujui - Beli di Marketplace SIPlah',
        ]);
    }

    public function test_owner_can_delete_pengajuan_barang(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $pengajuan = PengajuanBarang::factory()->create([
            'pemohon_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('pengajuan-barang.destroy', $pengajuan));
        $response->assertRedirect(route('pengajuan-barang.index'));
        $this->assertSoftDeleted('pengajuan_barangs', [
            'id' => $pengajuan->id,
        ]);
    }

    public function test_non_owner_cannot_update_or_delete_pengajuan_barang(): void
    {
        $owner = User::factory()->create([
            'role' => 'guru',
        ]);
        $otherUser = User::factory()->create([
            'role' => 'guru',
        ]);

        $pengajuan = PengajuanBarang::factory()->create([
            'pemohon_id' => $owner->id,
            'nama_barang' => 'Laptop Asli',
        ]);

        // Attempt update by other user
        $responseUpdate = $this->actingAs($otherUser)->put(route('pengajuan-barang.update', $pengajuan), [
            'tahun_anggaran' => $pengajuan->tahun_anggaran,
            'nama_barang' => 'Laptop Palsu',
            'spesifikasi' => $pengajuan->spesifikasi,
            'volume' => $pengajuan->volume,
            'satuan' => $pengajuan->satuan,
            'harga' => $pengajuan->harga,
            'jumlah' => $pengajuan->jumlah,
            'kegiatan' => $pengajuan->kegiatan,
            'bulan_pakai' => $pengajuan->bulan_pakai,
        ]);
        $responseUpdate->assertForbidden();

        // Attempt delete by other user
        $responseDelete = $this->actingAs($otherUser)->delete(route('pengajuan-barang.destroy', $pengajuan));
        $responseDelete->assertForbidden();
    }

    public function test_non_officer_cannot_realisasi(): void
    {
        $teacher = User::factory()->create([
            'role' => 'guru',
        ]);

        $pengajuan = PengajuanBarang::factory()->create([
            'realisasi' => null,
        ]);

        $response = $this->actingAs($teacher)->post(route('pengajuan-barang.realisasi', $pengajuan), [
            'realisasi' => 'Disetujui Sendiri',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_create_pengajuan_barang_with_guru_id(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $guru = Guru::create([
            'name' => 'Budi Santoso, S.Pd',
            'is_active' => true,
        ]);

        $payload = [
            'tahun_anggaran' => 2026,
            'guru_id' => $guru->id,
            'items' => [
                [
                    'nama_barang' => 'Spidol Whiteboard',
                    'spesifikasi' => 'Snowman Boardmarker Hitam',
                    'volume' => 12,
                    'satuan' => 'Pcs',
                    'harga' => 10000,
                    'jumlah' => 120000,
                    'kegiatan' => 'KBM Semester Ganjil',
                    'bulan_pakai' => 'Agustus',
                    'keterangan' => 'Keperluan kelas X',
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('pengajuan-barang.store'), $payload);
        $response->assertRedirect(route('pengajuan-barang.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pengajuan_barangs', [
            'guru_id' => $guru->id,
            'nama_barang' => 'Spidol Whiteboard',
            'satuan' => 'Pcs',
            'kegiatan' => 'KBM Semester Ganjil',
            'bulan_pakai' => 'Agustus',
        ]);
    }

    public function test_index_defaults_to_current_year(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $currentYear = (int) date('Y');
        $pastYear = $currentYear - 1;

        $pengajuanCurrent = PengajuanBarang::factory()->create([
            'pemohon_id' => $user->id,
            'tahun_anggaran' => $currentYear,
            'nama_barang' => 'Barang Tahun Ini',
        ]);

        $pengajuanPast = PengajuanBarang::factory()->create([
            'pemohon_id' => $user->id,
            'tahun_anggaran' => $pastYear,
            'nama_barang' => 'Barang Tahun Lalu',
        ]);

        // Request without filter defaults to current year
        $response = $this->actingAs($user)->get(route('pengajuan-barang.index'));
        $response->assertOk();
        $response->assertSee('Barang Tahun Ini');
        $response->assertDontSee('Barang Tahun Lalu');

        // Request with 'all' shows both
        $responseAll = $this->actingAs($user)->get(route('pengajuan-barang.index', ['tahun_anggaran' => 'all']));
        $responseAll->assertOk();
        $responseAll->assertSee('Barang Tahun Ini');
        $responseAll->assertSee('Barang Tahun Lalu');
    }
}
