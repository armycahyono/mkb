<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\GuruJabatan;
use App\Models\Jabatan;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class UserRoleAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTablesExist();
    }

    private function ensureTablesExist(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_id')->nullable();
                $table->string('name');
                $table->string('username', 50)->unique();
                $table->string('email')->unique();
                $table->string('role', 30)->default('bendahara');
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('telegram_chat_id', 50)->nullable()->unique();
                $table->string('telegram_username', 100)->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            if (! Schema::hasColumn('users', 'guru_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreignId('guru_id')->nullable();
                });
            }
            if (! Schema::hasColumn('users', 'username')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('username', 50)->nullable()->unique();
                });
            }
            if (! Schema::hasColumn('users', 'role')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('role', 30)->default('bendahara');
                });
            }
            if (! Schema::hasColumn('users', 'telegram_chat_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('telegram_chat_id', 50)->nullable()->unique();
                    $table->string('telegram_username', 100)->nullable();
                });
            }
        }

        if (! Schema::hasTable('school_profiles')) {
            Schema::create('school_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('gurus')) {
            Schema::create('gurus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('title_prefix')->nullable();
                $table->string('title_suffix')->nullable();
                $table->string('title_ahead')->nullable();
                $table->string('title_behind')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            if (! Schema::hasColumn('gurus', 'email')) {
                Schema::table('gurus', function (Blueprint $table) {
                    $table->string('email')->nullable();
                });
            }
        }

        if (! Schema::hasTable('jabatans')) {
            Schema::create('jabatans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_signatory')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('guru_jabatans')) {
            Schema::create('guru_jabatans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
                $table->foreignId('jabatan_id')->constrained('jabatans')->cascadeOnDelete();
                $table->string('sk_number', 100)->nullable();
                $table->date('sk_date')->nullable();
                $table->string('sk_file', 255)->nullable();
                $table->string('period_year', 20)->default(date('Y'))->index();
                $table->string('period_type', 30)->nullable()->default('tahun_anggaran');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('perjadins')) {
            Schema::create('perjadins', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->string('bku_number')->nullable();
                $table->string('activity_purpose')->nullable();
                $table->date('departure_date')->nullable();
                $table->date('payment_date')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('perjadin_items')) {
            Schema::create('perjadin_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('perjadin_id')->nullable();
                $table->foreignId('guru_id')->nullable();
                $table->string('receipt_number')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('net_amount', 15, 2)->default(0);
                $table->boolean('is_paid')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('pencairan_banks')) {
            Schema::create('pencairan_banks', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->string('letter_number')->nullable();
                $table->date('letter_date')->nullable();
                $table->decimal('total_nominal', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_files')) {
            Schema::create('bos_kertas_kerja_files', function (Blueprint $table) {
                $table->id();
                $table->year('fiscal_year')->default(2026);
                $table->string('version_name', 100);
                $table->string('file_path');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('kas_tunais')) {
            Schema::create('kas_tunais', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->date('transaction_date')->nullable();
                $table->unsignedSmallInteger('year')->nullable();
                $table->unsignedInteger('sequence')->nullable();
                $table->string('transaction_type', 10)->default('keluar');
                $table->string('category', 50)->default('belanja_tunai');
                $table->decimal('amount', 15, 2)->default(0);
                $table->text('description')->nullable();
                $table->string('person_name')->nullable();
                $table->foreignId('guru_id')->nullable();
                $table->string('panjar_status', 20)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('barang_dokumentasis')) {
            Schema::create('barang_dokumentasis', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->year('fiscal_year')->default(2026);
                $table->string('kode_kegiatan', 50)->nullable();
                $table->string('no_nota', 100)->nullable();
                $table->text('uraian')->nullable();
                $table->string('nama_toko')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanjas')) {
            Schema::create('bos_rencana_belanjas', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->foreignId('pencairan_bank_item_id')->nullable();
                $table->string('status', 30)->nullable();
                $table->date('planned_date')->nullable();
                $table->integer('year')->nullable();
                $table->string('activity_name')->nullable();
                $table->string('item_name')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('total_price', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('telegram_commands')) {
            Schema::create('telegram_commands', function (Blueprint $table) {
                $table->id();
                $table->string('command', 50)->unique();
                $table->string('description', 255);
                $table->string('response_type', 20)->default('text');
                $table->text('response_text')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('telegram_conversations')) {
            Schema::create('telegram_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('chat_id', 50)->index();
                $table->foreignId('user_id')->nullable();
                $table->string('command', 50)->nullable();
                $table->string('step', 50)->nullable();
                $table->json('draft_data')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_kepala_sekolah_has_access_to_all_modules(): void
    {
        $kepsek = User::create([
            'name' => 'Dr. Kepala Sekolah, M.Pd.',
            'username' => 'kepsek1',
            'email' => 'kepsek@sekolah.sch.id',
            'role' => User::ROLE_KEPALA_SEKOLAH,
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($kepsek)->get(route('dashboard'))->assertOk();
        $this->actingAs($kepsek)->get(route('guru.index'))->assertOk();
        $this->actingAs($kepsek)->get(route('bos.files.index'))->assertOk();
        $this->actingAs($kepsek)->get(route('bos.kas-tunai.index'))->assertOk();
        $this->actingAs($kepsek)->get(route('bpopp.kas-tunai.index'))->assertOk();
        $this->actingAs($kepsek)->get(route('user.index'))->assertOk();
        $this->actingAs($kepsek)->get(route('telegram-bot.index'))->assertOk();
    }

    public function test_super_admin_and_admin_have_full_access_to_all_modules(): void
    {
        foreach ([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN] as $role) {
            $user = User::create([
                'name' => "User {$role}",
                'username' => "user_{$role}",
                'email' => "{$role}@sekolah.sch.id",
                'role' => $role,
                'password' => Hash::make('password123'),
            ]);

            $this->actingAs($user)->get(route('dashboard'))->assertOk();
            $this->actingAs($user)->get(route('guru.index'))->assertOk();
            $this->actingAs($user)->get(route('bos.files.index'))->assertOk();
            $this->actingAs($user)->get(route('bos.kas-tunai.index'))->assertOk();
            $this->actingAs($user)->get(route('bpopp.perjadin.index'))->assertOk();
            $this->actingAs($user)->get(route('bpopp.kas-tunai.index'))->assertOk();
            $this->actingAs($user)->get(route('user.index'))->assertOk();
            $this->actingAs($user)->get(route('telegram-bot.index'))->assertOk();

            $this->assertTrue($user->canAccessDataPokok());
            $this->assertTrue($user->canAccessBosp());
            $this->assertTrue($user->canAccessBospKas());
            $this->assertTrue($user->canAccessBpopp());
            $this->assertTrue($user->canAccessBpoppKas());
            $this->assertTrue($user->canManageSettings());
            $this->assertTrue($user->canManageFinance());
            $this->assertTrue($user->canBotViewSaldo());
            $this->assertFalse($user->canBotFotoOnly());
        }
    }

    public function test_bendahara_bos_can_access_bosp_but_forbidden_bpopp(): void
    {
        $bendaharaBos = User::create([
            'name' => 'Bendahara BOS',
            'username' => 'benbos1',
            'email' => 'benbos@sekolah.sch.id',
            'role' => User::ROLE_BENDAHARA_BOS,
            'password' => Hash::make('password123'),
        ]);

        // Data Pokok & BOSP: Allowed
        $this->actingAs($bendaharaBos)->get(route('guru.index'))->assertOk();
        $this->actingAs($bendaharaBos)->get(route('bos.files.index'))->assertOk();
        $this->actingAs($bendaharaBos)->get(route('bos.kas-tunai.index'))->assertOk();

        // BPOPP: Forbidden
        $this->actingAs($bendaharaBos)->get(route('bpopp.perjadin.index'))->assertForbidden();
        $this->actingAs($bendaharaBos)->get(route('bpopp.kas-tunai.index'))->assertForbidden();
    }

    public function test_bendahara_bpopp_can_access_bpopp_but_forbidden_bosp(): void
    {
        $bendaharaBpopp = User::create([
            'name' => 'Bendahara BPOPP',
            'username' => 'benbpopp1',
            'email' => 'benbpopp@sekolah.sch.id',
            'role' => User::ROLE_BENDAHARA_BPOPP,
            'password' => Hash::make('password123'),
        ]);

        // Data Pokok & BPOPP: Allowed
        $this->actingAs($bendaharaBpopp)->get(route('guru.index'))->assertOk();
        $this->actingAs($bendaharaBpopp)->get(route('bpopp.perjadin.index'))->assertOk();
        $this->actingAs($bendaharaBpopp)->get(route('bpopp.kas-tunai.index'))->assertOk();

        // BOSP: Forbidden
        $this->actingAs($bendaharaBpopp)->get(route('bos.files.index'))->assertForbidden();
        $this->actingAs($bendaharaBpopp)->get(route('bos.kas-tunai.index'))->assertForbidden();
    }

    public function test_waka_sarpras_can_access_bosp_and_bpopp_except_kas(): void
    {
        $wakaSarpras = User::create([
            'name' => 'Waka Sarpras',
            'username' => 'sarpras1',
            'email' => 'sarpras@sekolah.sch.id',
            'role' => User::ROLE_WAKA_SARPRAS,
            'password' => Hash::make('password123'),
        ]);

        // Data Pokok: Allowed
        $this->actingAs($wakaSarpras)->get(route('guru.index'))->assertOk();

        // BOSP non-kas: Allowed
        $this->actingAs($wakaSarpras)->get(route('bos.files.index'))->assertOk();
        $this->actingAs($wakaSarpras)->get(route('bos.dokumentasi-barang.index'))->assertOk();

        // BOSP Kas: Strictly Forbidden
        $this->actingAs($wakaSarpras)->get(route('bos.kas-tunai.index'))->assertForbidden();
        $this->actingAs($wakaSarpras)->get(route('bos.kas-tunai.kasbon'))->assertForbidden();

        // BPOPP non-kas: Allowed
        $this->actingAs($wakaSarpras)->get(route('bpopp.perjadin.index'))->assertOk();

        // BPOPP Kas: Strictly Forbidden
        $this->actingAs($wakaSarpras)->get(route('bpopp.kas-tunai.index'))->assertForbidden();

        // System Settings: Forbidden
        $this->actingAs($wakaSarpras)->get(route('user.index'))->assertForbidden();
    }

    public function test_team_bosp_can_access_bosp_except_kas_and_forbidden_bpopp(): void
    {
        $teamBosp = User::create([
            'name' => 'Tim BOSP',
            'username' => 'teambosp1',
            'email' => 'teambosp@sekolah.sch.id',
            'role' => User::ROLE_TEAM_BOSP,
            'password' => Hash::make('password123'),
        ]);

        // Data Pokok: Allowed
        $this->actingAs($teamBosp)->get(route('guru.index'))->assertOk();

        // BOSP non-kas: Allowed
        $this->actingAs($teamBosp)->get(route('bos.files.index'))->assertOk();
        $this->actingAs($teamBosp)->get(route('bos.dokumentasi-barang.index'))->assertOk();

        // BOSP Kas: Strictly Forbidden
        $this->actingAs($teamBosp)->get(route('bos.kas-tunai.index'))->assertForbidden();

        // BPOPP: Strictly Forbidden
        $this->actingAs($teamBosp)->get(route('bpopp.perjadin.index'))->assertForbidden();
        $this->actingAs($teamBosp)->get(route('bpopp.kas-tunai.index'))->assertForbidden();
    }

    public function test_team_bpopp_can_access_bpopp_except_kas_and_forbidden_bosp(): void
    {
        $teamBpopp = User::create([
            'name' => 'Tim BPOPP',
            'username' => 'teambpopp1',
            'email' => 'teambpopp@sekolah.sch.id',
            'role' => User::ROLE_TEAM_BPOPP,
            'password' => Hash::make('password123'),
        ]);

        // Data Pokok: Allowed
        $this->actingAs($teamBpopp)->get(route('guru.index'))->assertOk();

        // BPOPP non-kas: Allowed
        $this->actingAs($teamBpopp)->get(route('bpopp.perjadin.index'))->assertOk();

        // BPOPP Kas: Strictly Forbidden
        $this->actingAs($teamBpopp)->get(route('bpopp.kas-tunai.index'))->assertForbidden();

        // BOSP: Strictly Forbidden
        $this->actingAs($teamBpopp)->get(route('bos.files.index'))->assertForbidden();
        $this->actingAs($teamBpopp)->get(route('bos.kas-tunai.index'))->assertForbidden();
    }

    public function test_can_create_and_update_users_with_all_roles(): void
    {
        $admin = User::create([
            'name' => 'Admin Utama',
            'username' => 'adminutama',
            'email' => 'admin@sekolah.sch.id',
            'role' => User::ROLE_BENDAHARA_BOS,
            'password' => Hash::make('password123'),
        ]);

        $roles = [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_KEPALA_SEKOLAH,
            User::ROLE_BENDAHARA_BOS,
            User::ROLE_BENDAHARA_BPOPP,
            User::ROLE_WAKA_SARPRAS,
            User::ROLE_TEAM_BOSP,
            User::ROLE_TEAM_BPOPP,
        ];

        foreach ($roles as $idx => $role) {
            $response = $this->actingAs($admin)->postJson(route('user.store'), [
                'name' => "User Test {$role}",
                'username' => "testuser{$idx}",
                'email' => "test{$idx}@sekolah.sch.id",
                'role' => $role,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertOk();
            $this->assertDatabaseHas('users', [
                'username' => "testuser{$idx}",
                'role' => $role,
            ]);
        }

        // Test update role
        $targetUser = User::where('username', 'testuser0')->first();
        $updateRes = $this->actingAs($admin)->putJson(route('user.update', $targetUser->id), [
            'name' => 'User Updated',
            'username' => 'testuser0',
            'email' => $targetUser->email,
            'role' => User::ROLE_WAKA_SARPRAS,
        ]);
        $updateRes->assertOk();
        $this->assertEquals(User::ROLE_WAKA_SARPRAS, $targetUser->fresh()->role);

        // Test invalid role
        $invalidRes = $this->actingAs($admin)->postJson(route('user.store'), [
            'name' => 'Invalid Role User',
            'username' => 'invaliduser',
            'email' => 'invalid@sekolah.sch.id',
            'role' => 'super_admin_fake',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $invalidRes->assertStatus(422);
    }

    public function test_telegram_bot_role_restrictions(): void
    {
        $sarpras = User::create([
            'name' => 'Waka Sarpras Bot',
            'username' => 'sarprasbot',
            'email' => 'sarprasbot@sekolah.sch.id',
            'role' => User::ROLE_WAKA_SARPRAS,
            'telegram_chat_id' => '11223344',
            'password' => Hash::make('password123'),
        ]);

        $kepsek = User::create([
            'name' => 'Kepsek Bot',
            'username' => 'kepsekbot',
            'email' => 'kepsekbot@sekolah.sch.id',
            'role' => User::ROLE_KEPALA_SEKOLAH,
            'telegram_chat_id' => '55667788',
            'password' => Hash::make('password123'),
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            // Waka Sarpras /transaksi -> Ditolak
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($chatId, $text) {
                    return $chatId === '11223344' && str_contains($text, 'Akses Dibatasi');
                })
                ->andReturn(['ok' => true]);

            // Waka Sarpras /saldo -> Ditolak
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($chatId, $text) {
                    return $chatId === '11223344' && str_contains($text, 'Akses Dibatasi');
                })
                ->andReturn(['ok' => true]);

            // Kepsek /transaksi -> Diarahkan khusus bendahara
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($chatId, $text) {
                    return $chatId === '55667788' && str_contains($text, 'Akses Khusus Bendahara');
                })
                ->andReturn(['ok' => true]);

            // Kepsek /saldo -> Diizinkan (info saldo)
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($chatId, $text) {
                    return $chatId === '55667788' && str_contains($text, 'INFORMASI SALDO KAS TUNAI BOSP');
                })
                ->andReturn(['ok' => true]);
        });

        // 1. Waka Sarpras coba /transaksi
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => '11223344'],
                'text' => '/transaksi',
            ],
        ])->assertOk();

        // 2. Waka Sarpras coba /saldo
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => '11223344'],
                'text' => '/saldo',
            ],
        ])->assertOk();

        // 3. Kepsek coba /transaksi
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => '55667788'],
                'text' => '/transaksi',
            ],
        ])->assertOk();

        // 4. Kepsek coba /saldo
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => '55667788'],
                'text' => '/saldo',
            ],
        ])->assertOk();
    }

    public function test_user_can_be_linked_to_guru_and_sync_role_from_active_jabatan(): void
    {
        $admin = User::create([
            'name' => 'Admin Boss',
            'username' => 'adminboss',
            'email' => 'adminboss@test.sch.id',
            'role' => User::ROLE_BENDAHARA_BOS,
            'password' => Hash::make('password'),
        ]);

        $guru = Guru::create([
            'name' => 'Mochamad Wahyudi',
            'title_prefix' => 'Drs.',
            'title_suffix' => 'M.Pd.',
            'email' => 'wahyudi@sekolah.sch.id',
            'is_active' => true,
        ]);

        $jabatanKs = Jabatan::create([
            'name' => 'Kepala Sekolah',
            'code' => 'KS',
            'is_signatory' => true,
        ]);

        $jabatanBendahara = Jabatan::create([
            'name' => 'Bendahara BOSP',
            'code' => 'BEND_BOSP',
            'is_signatory' => true,
        ]);

        // Berikan SK Kepala Sekolah aktif kepada guru
        $penugasanKs = GuruJabatan::create([
            'guru_id' => $guru->id,
            'jabatan_id' => $jabatanKs->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => true,
        ]);

        // Verifikasi accessor suggested_role pada model Guru
        $this->assertEquals(User::ROLE_KEPALA_SEKOLAH, $guru->suggested_role);
        $this->assertEquals('Kepala Sekolah', $guru->suggested_role_label);

        // 1. Buat User baru yang ditautkan ke profil Guru tersebut
        $createResponse = $this->actingAs($admin)->postJson(route('user.store'), [
            'guru_id' => $guru->id,
            'name' => 'Drs. Mochamad Wahyudi, M.Pd.',
            'username' => 'wahyudi',
            'email' => 'wahyudi@sekolah.sch.id',
            'role' => $guru->suggested_role,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $createResponse->assertOk();
        $user = User::where('username', 'wahyudi')->first();
        $this->assertNotNull($user);
        $this->assertEquals($guru->id, $user->guru_id);
        $this->assertEquals(User::ROLE_KEPALA_SEKOLAH, $user->role);
        $this->assertTrue($user->isKepalaSekolah());

        // 2. Cek tampilan DataTables User (name_display memuat badge/nama guru & action membawa data-guru-id)
        $dtResponse = $this->actingAs($admin)->get(route('user.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $dtResponse->assertOk();
        $dtData = $dtResponse->json('data');
        $userRow = collect($dtData)->firstWhere('username', 'wahyudi');
        $this->assertNotNull($userRow);
        $this->assertStringContainsString('Guru:', $userRow['name_display']);
        $this->assertStringContainsString('Mochamad Wahyudi', $userRow['name_display']);
        $this->assertStringContainsString('Kepala Sekolah', $userRow['name_display']);
        $this->assertStringContainsString('data-guru-id="'.$guru->id.'"', $userRow['action']);

        // 3. Saat SK KS demisioner dan Guru mendapatkan penetapan penugasan baru (Bendahara BOSP)
        // melalui GuruJabatanController, role user tertaut otomatis tersinkronisasi!
        $penugasanKs->update(['is_active' => false]);
        $this->actingAs($admin)->postJson(route('guru-jabatan.store'), [
            'guru_ids' => [$guru->id],
            'jabatan_id' => $jabatanBendahara->id,
            'start_date' => '2026-07-01',
            'is_active' => true,
        ])->assertOk();

        $user->refresh();
        $this->assertEquals(User::ROLE_BENDAHARA_BOS, $user->role);
        $this->assertTrue($user->isBendaharaBos());

        // 4. Update user: lepaskan tautan guru (unlinking)
        $updateResponse = $this->actingAs($admin)->putJson(route('user.update', $user->id), [
            'guru_id' => null,
            'name' => 'Wahyudi Biasa',
            'username' => 'wahyudi',
            'email' => 'wahyudi@sekolah.sch.id',
            'role' => User::ROLE_BENDAHARA_BOS,
        ]);
        $updateResponse->assertOk();
        $user->refresh();
        $this->assertNull($user->guru_id);
    }
}
