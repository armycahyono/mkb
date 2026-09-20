<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserManagementTest extends TestCase
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
                    $table->string('username', 50)->nullable()->unique()->after('name');
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

        if (! Schema::hasTable('gurus')) {
            Schema::create('gurus', function (Blueprint $table) {
                $table->id();
                $table->string('nip', 30)->nullable();
                $table->string('nik', 20)->nullable();
                $table->string('nuptk', 30)->nullable();
                $table->string('npwp', 30)->nullable();
                $table->string('name', 150);
                $table->string('title_prefix', 50)->nullable();
                $table->string('title_suffix', 50)->nullable();
                $table->enum('gender', ['L', 'P'])->default('L');
                $table->string('birth_place', 100)->nullable();
                $table->date('birth_date')->nullable();
                $table->string('religion', 30)->nullable();
                $table->string('employment_status', 30)->default('GTT');
                $table->string('pangkat_golongan', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email', 100)->nullable();
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
    }

    public function test_guest_cannot_access_user_management(): void
    {
        $response = $this->get(route('user.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_user_management(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'admin_test'],
            [
                'name' => 'Admin Test',
                'email' => 'admin_test@test.local',
                'password' => Hash::make('secret123'),
            ]
        );

        $response = $this->actingAs($user)->get(route('user.index'));

        $response->assertOk();
        $response->assertSee('Manajemen User');
        $response->assertSee('Daftar Akun Pengguna Sistem');
    }

    public function test_user_index_returns_datatables_json(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'dt_user'],
            [
                'name' => 'DataTables User',
                'email' => 'dt_user@test.local',
                'password' => Hash::make('secret123'),
                'telegram_chat_id' => '12345678',
                'telegram_username' => 'dt_telegram',
            ]
        );

        $response = $this->actingAs($user)->getJson(route('user.index'), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['data', 'draw', 'recordsTotal', 'recordsFiltered']);
        $content = $response->getContent();
        $this->assertStringContainsString('DataTables User', $content);
        $this->assertStringContainsString('dt_telegram', $content);
    }

    public function test_can_create_new_user(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'creator_admin'],
            [
                'name' => 'Creator Admin',
                'email' => 'creator@test.local',
                'password' => Hash::make('secret123'),
            ]
        );

        $response = $this->actingAs($admin)->postJson(route('user.store'), [
            'name' => 'Bendahara Baru',
            'username' => 'bendahara_baru',
            'email' => 'bendahara_baru@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'message' => "User 'Bendahara Baru' berhasil ditambahkan.",
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'bendahara_baru',
            'email' => 'bendahara_baru@test.local',
        ]);
    }

    public function test_create_user_validation(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'val_admin'],
            [
                'name' => 'Validation Admin',
                'email' => 'val_admin@test.local',
                'password' => Hash::make('secret123'),
            ]
        );

        // Test duplicate username and email
        $response = $this->actingAs($admin)->postJson(route('user.store'), [
            'name' => 'Duplicate User',
            'username' => 'val_admin',
            'email' => 'val_admin@test.local',
            'password' => '123',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username', 'email', 'password']);
    }

    public function test_can_update_user(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'updater_admin'],
            [
                'name' => 'Updater Admin',
                'email' => 'updater@test.local',
                'password' => Hash::make('secret123'),
            ]
        );

        $targetUser = User::create([
            'name' => 'Old Name',
            'username' => 'old_username',
            'email' => 'old_email@test.local',
            'password' => Hash::make('oldpassword'),
            'telegram_chat_id' => '99988877',
            'telegram_username' => 'old_bot_user',
        ]);

        $response = $this->actingAs($admin)->putJson(route('user.update', $targetUser->id), [
            'name' => 'Updated Name',
            'username' => 'new_username',
            'email' => 'new_email@test.local',
            'unlink_telegram' => '1',
        ]);

        $response->assertOk();
        $targetUser->refresh();

        $this->assertEquals('Updated Name', $targetUser->name);
        $this->assertEquals('new_username', $targetUser->username);
        $this->assertEquals('new_email@test.local', $targetUser->email);
        $this->assertNull($targetUser->telegram_chat_id);
        $this->assertNull($targetUser->telegram_username);
    }

    public function test_cannot_delete_own_account(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'self_deleter'],
            [
                'name' => 'Self Deleter',
                'email' => 'self@test.local',
                'password' => Hash::make('secret123'),
            ]
        );

        $response = $this->actingAs($admin)->deleteJson(route('user.destroy', $admin->id));

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.',
        ]);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_can_delete_other_user(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'remover_admin'],
            [
                'name' => 'Remover Admin',
                'email' => 'remover@test.local',
                'password' => Hash::make('secret123'),
            ]
        );

        $other = User::create([
            'name' => 'User To Delete',
            'username' => 'to_delete',
            'email' => 'delete_me@test.local',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('user.destroy', $other->id));

        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }
}
