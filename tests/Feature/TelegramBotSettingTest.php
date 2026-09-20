<?php

namespace Tests\Feature;

use App\Models\TelegramCommand;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class TelegramBotSettingTest extends TestCase
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
            if (! Schema::hasColumn('users', 'role')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('role', 30)->default('bendahara');
                });
            }
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

    public function test_guest_cannot_access_telegram_bot_settings(): void
    {
        $response = $this->get(route('telegram-bot.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_telegram_bot_settings(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'bot_admin'],
            [
                'name' => 'Bot Admin',
                'email' => 'bot_admin@test.local',
                'password' => Hash::make('password'),
            ]
        );

        $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getMe')->andReturn([
                'ok' => true,
                'result' => [
                    'id' => 8662735727,
                    'first_name' => 'bot mkb grik',
                    'username' => 'mkbgrik_bot',
                    'can_join_groups' => true,
                ],
            ]);
            $mock->shouldReceive('getWebhookInfo')->andReturn([
                'ok' => true,
                'result' => [
                    'url' => 'https://mkb.sudahonline.my.id/telegram/webhook',
                    'pending_update_count' => 0,
                    'ip_address' => '1.2.3.4',
                ],
            ]);
            $mock->shouldReceive('getMyCommands')->andReturn([
                'ok' => true,
                'result' => [],
            ]);
        });

        $response = $this->actingAs($user)->get(route('telegram-bot.index'));

        $response->assertOk();
        $response->assertSee('Pengaturan & Pengembangan Bot Telegram');
        $response->assertSee('mkbgrik_bot');
    }

    public function test_can_create_custom_telegram_command(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'cmd_admin'],
            [
                'name' => 'Cmd Admin',
                'email' => 'cmd_admin@test.local',
                'password' => Hash::make('password'),
            ]
        );

        $response = $this->actingAs($user)->postJson(route('telegram-bot.commands.store'), [
            'command' => 'kontak_sekolah',
            'description' => 'Info nomor kontak sekolah',
            'response_text' => 'Telepon: 031-123456, WA: 08123456789',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'message' => "Perintah '/kontak_sekolah' berhasil dibuat.",
        ]);

        $this->assertDatabaseHas('telegram_commands', [
            'command' => 'kontak_sekolah',
            'response_type' => 'text',
        ]);
    }

    public function test_can_update_telegram_command(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'updater_admin2'],
            [
                'name' => 'Updater Admin',
                'email' => 'updater2@test.local',
                'password' => Hash::make('password'),
            ]
        );

        $cmd = TelegramCommand::create([
            'command' => 'rekening_old',
            'description' => 'Rekening lama',
            'response_type' => 'text',
            'response_text' => 'Bank ABC 123',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->putJson(route('telegram-bot.commands.update', $cmd->id), [
            'command' => 'rekening_bosp',
            'description' => 'Rekening Bank Jatim BOSP',
            'response_text' => 'Bank Jatim No: 0123456789 a.n SMKN 1 Grik',
        ]);

        $response->assertOk();
        $cmd->refresh();
        $this->assertEquals('rekening_bosp', $cmd->command);
        $this->assertEquals('Rekening Bank Jatim BOSP', $cmd->description);
    }

    public function test_cannot_delete_system_command(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'deleter_admin'],
            [
                'name' => 'Deleter Admin',
                'email' => 'deleter@test.local',
                'password' => Hash::make('password'),
            ]
        );

        $sysCmd = TelegramCommand::firstOrCreate(
            ['command' => 'transaksi'],
            [
                'description' => 'Catat transaksi kas',
                'response_type' => 'system',
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($user)->deleteJson(route('telegram-bot.commands.destroy', $sysCmd->id));

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Perintah sistem bawaan tidak dapat dihapus.',
        ]);
    }

    public function test_can_delete_custom_command(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'deleter_admin2'],
            [
                'name' => 'Deleter Admin 2',
                'email' => 'deleter2@test.local',
                'password' => Hash::make('password'),
            ]
        );

        $custom = TelegramCommand::create([
            'command' => 'faq_tes',
            'description' => 'FAQ test',
            'response_type' => 'text',
            'response_text' => 'Jawaban FAQ',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('telegram-bot.commands.destroy', $custom->id));

        $response->assertOk();
        $this->assertDatabaseMissing('telegram_commands', ['id' => $custom->id]);
    }

    public function test_can_toggle_command_status(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'toggler_admin'],
            [
                'name' => 'Toggler Admin',
                'email' => 'toggler@test.local',
                'password' => Hash::make('password'),
            ]
        );

        $cmd = TelegramCommand::create([
            'command' => 'toggle_test',
            'description' => 'Testing toggle',
            'response_type' => 'text',
            'response_text' => 'Halo',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('telegram-bot.commands.toggle', $cmd->id));
        $response->assertOk();
        $this->assertFalse($cmd->fresh()->is_active);

        $response = $this->actingAs($user)->postJson(route('telegram-bot.commands.toggle', $cmd->id));
        $response->assertOk();
        $this->assertTrue($cmd->fresh()->is_active);
    }

    public function test_sync_commands_calls_telegram_api(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'syncer_admin'],
            [
                'name' => 'Syncer Admin',
                'email' => 'syncer@test.local',
                'password' => Hash::make('password'),
            ]
        );

        $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setMyCommands')
                ->once()
                ->andReturn([
                    'ok' => true,
                    'result' => true,
                ]);
        });

        $response = $this->actingAs($user)->postJson(route('telegram-bot.sync-commands'));

        $response->assertOk();
        $response->assertJsonStructure(['message', 'count']);
    }

    public function test_webhook_handles_custom_auto_reply_command(): void
    {
        TelegramCommand::create([
            'command' => 'panduan_spj',
            'description' => 'Panduan pengisian SPJ',
            'response_type' => 'text',
            'response_text' => 'Silakan download format SPJ di portal sekolah.',
            'is_active' => true,
        ]);

        $user = User::firstOrCreate(
            ['username' => 'paired_user_test'],
            [
                'name' => 'User Paired',
                'email' => 'paired@test.local',
                'password' => Hash::make('password'),
                'telegram_chat_id' => '11223344',
            ]
        );

        $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('11223344', 'Silakan download format SPJ di portal sekolah.')
                ->andReturn(['ok' => true]);
        });

        $payload = [
            'update_id' => 999123,
            'message' => [
                'message_id' => 1234,
                'from' => [
                    'id' => 11223344,
                    'username' => 'testuser',
                ],
                'chat' => [
                    'id' => 11223344,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/panduan_spj',
            ],
        ];

        $response = $this->postJson(route('telegram.webhook'), $payload);

        $response->assertOk();
    }
}
