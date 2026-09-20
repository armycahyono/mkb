<?php

namespace Tests\Feature;

use App\Models\BarangDokumentasi;
use App\Models\BarangDokumentasiFoto;
use App\Models\Guru;
use App\Models\KasTunai;
use App\Models\TelegramCommand;
use App\Models\TelegramConversation;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class TelegramBotTest extends TestCase
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
        } else {
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
                $table->foreignId('parent_kas_id')->nullable();
                $table->string('panjar_status', 20)->default('none');
                $table->string('evidence_number')->nullable();
                $table->string('receipt_file')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->foreignId('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            if (! Schema::hasColumn('kas_tunais', 'updated_by')) {
                Schema::table('kas_tunais', function (Blueprint $table) {
                    $table->foreignId('updated_by')->nullable();
                });
            }
            if (! Schema::hasColumn('kas_tunais', 'receipt_file')) {
                Schema::table('kas_tunais', function (Blueprint $table) {
                    $table->string('receipt_file')->nullable();
                });
            }
        }

        if (! Schema::hasTable('gurus')) {
            Schema::create('gurus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('title_prefix')->nullable();
                $table->string('title_suffix')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            if (! Schema::hasColumn('gurus', 'phone')) {
                Schema::table('gurus', function (Blueprint $table) {
                    $table->string('phone')->nullable();
                });
            }
        }

        if (! Schema::hasTable('barang_dokumentasis')) {
            Schema::create('barang_dokumentasis', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOS');
                $table->integer('year');
                $table->string('no_nota', 100)->nullable();
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
                $table->foreignId('barang_dokumentasi_id');
                $table->integer('urutan')->default(1);
                $table->string('file_path');
                $table->string('keterangan')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('telegram_commands')) {
            Schema::create('telegram_commands', function (Blueprint $table) {
                $table->id();
                $table->string('command', 50)->unique();
                $table->string('description', 255);
                $table->string('response_type', 20)->default('text');
                $table->text('response_text')->nullable();
                $table->json('flow_config')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function test_webhook_endpoint_returns_ok(): void
    {
        $response = $this->postJson(route('telegram.webhook'), []);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_unpaired_user_login_flow(): void
    {
        $user = User::create([
            'name' => 'Bendahara Sekolah',
            'email' => 'bendahara_test@mkb.test',
            'password' => Hash::make('secret123'),
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->times(3)
                ->andReturn(['ok' => true]);
        });

        // 1. Kirim /start
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 12345678],
                'from' => ['id' => 12345678, 'username' => 'bendahara_tele'],
                'text' => '/start',
            ],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '12345678')->first();
        $this->assertNotNull($conv);
        $this->assertEquals('login', $conv->command);
        $this->assertEquals('waiting_email', $conv->step);

        // 2. Kirim Email
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => 'bendahara_test@mkb.test',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_password', $conv->step);

        // 3. Kirim Password
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => 'secret123',
            ],
        ])->assertStatus(200);

        $user->refresh();
        $this->assertEquals('12345678', $user->telegram_chat_id);
        $this->assertNull(TelegramConversation::where('chat_id', '12345678')->first());
    }

    public function test_unpaired_user_can_pair_via_username_only_and_password(): void
    {
        $user = User::create([
            'name' => 'Tim BOS Lapangan',
            'username' => 'timbos_kras',
            'email' => 'timbos@mkb.test',
            'role' => User::ROLE_TEAM_BOS,
            'password' => Hash::make('secret123'),
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->times(3)
                ->andReturn(['ok' => true]);
        });

        // 1. Kirim /start
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 88877766],
                'text' => '/start',
            ],
        ])->assertStatus(200);

        // 2. Kirim Username saja (tanpa email)
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 88877766],
                'text' => 'timbos_kras',
            ],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '88877766')->first();
        $this->assertNotNull($conv);
        $this->assertEquals('waiting_password', $conv->step);

        // 3. Kirim Password
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 88877766],
                'text' => 'secret123',
            ],
        ])->assertStatus(200);

        $user->refresh();
        $this->assertEquals('88877766', $user->telegram_chat_id);
        $this->assertNull(TelegramConversation::where('chat_id', '88877766')->first());
    }

    public function test_paired_user_can_view_help_and_saldo(): void
    {
        $user = User::create([
            'name' => 'Bendahara Paired',
            'email' => 'paired@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '99887766',
        ]);

        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Tarik bank BOSP',
            'created_by' => $user->id,
        ]);

        $messagesSent = [];
        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) use (&$messagesSent) {
            $mock->shouldReceive('sendMessage')
                ->twice()
                ->andReturnUsing(function ($chatId, $text) use (&$messagesSent) {
                    $messagesSent[] = $text;

                    return ['ok' => true];
                });
        });

        // Test /help
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 99887766],
                'text' => '/help',
            ],
        ])->assertStatus(200);

        // Test /saldo
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 99887766],
                'text' => '/saldo',
            ],
        ])->assertStatus(200);

        $this->assertCount(2, $messagesSent);
        $saldoText = $messagesSent[1];
        $this->assertStringContainsString('Kas Tunai BOSP', $saldoText);
        $this->assertStringNotContainsString('Kas Komite', $saldoText);
    }

    public function test_transaction_wizard_full_flow(): void
    {
        $user = User::create([
            'name' => 'Bendahara Trx',
            'email' => 'trx@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '55443322',
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['ok' => true]);
            $mock->shouldReceive('editMessageText')->andReturn(['ok' => true]);
            $mock->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        });

        // 1. Mulai /pengeluaran langsung (tanpa /transaksi)
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 55443322],
                'text' => '/pengeluaran',
            ],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '55443322')->first();
        $this->assertNotNull($conv);
        $this->assertEquals('keluar', $conv->draft_data['type']);
        $this->assertEquals('BOSP', $conv->draft_data['funding_source']);

        // 2b. Klik callback trx_cat:belanja_tunai
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_124',
                'from' => ['id' => 55443322],
                'message' => ['chat' => ['id' => 55443322], 'message_id' => 101],
                'data' => 'trx_cat:belanja_tunai',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_amount', $conv->step);
        $this->assertEquals('belanja_tunai', $conv->draft_data['category']);

        // 3. Masukkan Nominal (750.000)
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 55443322],
                'text' => '750.000',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_description', $conv->step);
        $this->assertEquals(750000, $conv->draft_data['amount']);

        // 4. Masukkan Uraian
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 55443322],
                'text' => 'Pembelian Kertas HVS dan Tinta',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_person', $conv->step);

        // 5. Masukkan Nama Orang yang Bertransaksi
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 55443322],
                'text' => 'Pak Budi',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_store_nota', $conv->step);
        $this->assertEquals('Pak Budi', $conv->draft_data['person']);

        // 6. Masukkan Toko dan Nomor Nota Sekaligus
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 55443322],
                'text' => 'Toko Barokah ATK, N26-0050',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_photos', $conv->step);
        $this->assertEquals('Toko Barokah ATK', $conv->draft_data['store_name']);
        $this->assertEquals('N26-0050', $conv->draft_data['evidence_number']);

        // 7. Skip foto
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 55443322],
                'text' => '/skip',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_confirmation', $conv->step);

        // 8. Klik confirm:yes
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_confirm',
                'from' => ['id' => 55443322],
                'message' => ['chat' => ['id' => 55443322], 'message_id' => 102],
                'data' => 'confirm:yes',
            ],
        ])->assertStatus(200);

        // Pastikan transaksi tersimpan di kas_tunais
        $kas = KasTunai::where('evidence_number', 'N26-0050')->first();
        $this->assertNotNull($kas);
        $this->assertEquals('BOSP', $kas->funding_source);
        $this->assertEquals('keluar', $kas->transaction_type);
        $this->assertEquals('belanja_tunai', $kas->category);
        $this->assertEquals(750000, (float) $kas->amount);
        $this->assertEquals('Pembelian Kertas HVS dan Tinta', $kas->description);
        $this->assertEquals('Toko Barokah ATK', $kas->person_name);

        // Sesi percakapan harus sudah terhapus
        $this->assertNull(TelegramConversation::where('chat_id', '55443322')->first());
    }

    public function test_cancel_resets_conversation(): void
    {
        $user = User::create([
            'name' => 'Bendahara Cancel',
            'email' => 'cancel@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '11223344',
        ]);

        TelegramConversation::create([
            'chat_id' => '11223344',
            'user_id' => $user->id,
            'command' => 'transaksi',
            'step' => 'waiting_amount',
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->once()->andReturn(['ok' => true]);
        });

        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 11223344],
                'text' => '/batal',
            ],
        ])->assertStatus(200);

        $this->assertNull(TelegramConversation::where('chat_id', '11223344')->first());
    }

    public function test_get_and_update_command_flow_config(): void
    {
        $user = User::create([
            'name' => 'Admin Bot',
            'email' => 'adminbot@mkb.test',
            'password' => Hash::make('secret123'),
        ]);

        $cmd = TelegramCommand::create([
            'command' => 'transaksi',
            'description' => 'Catat transaksi kas',
            'response_type' => 'system',
            'is_active' => true,
        ]);

        // 1. GET flow config bawaan
        $res = $this->actingAs($user)->getJson(route('telegram-bot.commands.flow.get', $cmd->id));
        $res->assertStatus(200);
        $res->assertJsonStructure([
            'command',
            'description',
            'flow' => [
                'category',
                'amount',
                'description',
                'person',
                'store_nota',
                'photos',
            ],
            'is_customized',
        ]);

        // 2. UPDATE flow config (nonaktifkan photos dan ubah prompt uraian)
        $updateRes = $this->actingAs($user)->putJson(route('telegram-bot.commands.flow.update', $cmd->id), [
            'steps' => [
                'photos' => [
                    'is_enabled' => 0,
                    'is_required' => 0,
                    'prompt' => 'Lampiran foto dinonaktifkan',
                ],
                'description' => [
                    'is_enabled' => 1,
                    'is_required' => 1,
                    'prompt' => 'Masukkan keterangan transaksi kas:',
                ],
            ],
        ]);
        $updateRes->assertStatus(200);
        $this->assertNotNull($updateRes->json('flow'));

        $cmd->refresh();
        $this->assertFalse((bool) $cmd->flow_config['photos']['is_enabled']);
        $this->assertEquals('Masukkan keterangan transaksi kas:', $cmd->flow_config['description']['prompt']);

        // 3. RESET flow config ke standar
        $resetRes = $this->actingAs($user)->postJson(route('telegram-bot.commands.flow.reset', $cmd->id));
        $resetRes->assertStatus(200);
        $cmd->refresh();
        $this->assertNull($cmd->flow_config);
    }

    public function test_bot_respects_disabled_steps_and_skips_them(): void
    {
        $user = User::create([
            'name' => 'Bendahara Skip Steps',
            'email' => 'skipsteps@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '77665544',
        ]);

        // Nonaktifkan person dan store_nota pada flow config transaksi
        $cmd = TelegramCommand::create([
            'command' => 'transaksi',
            'description' => 'Catat transaksi kas',
            'response_type' => 'system',
            'is_active' => true,
            'flow_config' => [
                'person' => [
                    'key' => 'person',
                    'title' => 'Nama Orang',
                    'description' => 'Orang',
                    'is_enabled' => false,
                    'is_required' => false,
                    'prompt' => 'Orang:',
                    'help_text' => '',
                ],
                'store_nota' => [
                    'key' => 'store_nota',
                    'title' => 'Nama Toko & No Nota',
                    'description' => 'Toko & Nota',
                    'is_enabled' => false,
                    'is_required' => false,
                    'prompt' => 'Toko & Nota:',
                    'help_text' => '',
                ],
            ],
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['ok' => true]);
            $mock->shouldReceive('editMessageText')->andReturn(['ok' => true]);
            $mock->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        });

        // 1. Mulai /transaksi
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 77665544], 'text' => '/transaksi'],
        ])->assertStatus(200);

        // 2. Pilih jenis
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_1',
                'from' => ['id' => 77665544],
                'message' => ['chat' => ['id' => 77665544], 'message_id' => 10],
                'data' => 'trx_type:keluar_bosp',
            ],
        ])->assertStatus(200);

        // 3. Pilih kategori
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_2',
                'from' => ['id' => 77665544],
                'message' => ['chat' => ['id' => 77665544], 'message_id' => 10],
                'data' => 'trx_cat:belanja_tunai',
            ],
        ])->assertStatus(200);

        // 4. Masukkan nominal
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 77665544], 'text' => '250.000'],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '77665544')->first();
        $this->assertEquals('waiting_description', $conv->step);

        // 5. Masukkan uraian -> Karena store_nota nonaktif, harus langsung melompat ke waiting_photos!
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 77665544], 'text' => 'Beli Baterai Jam Dinding'],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_photos', $conv->step);
    }

    public function test_bot_respects_required_step_validation(): void
    {
        $user = User::create([
            'name' => 'Bendahara Required Step',
            'email' => 'required@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '33221100',
        ]);

        // Atur store_nota wajib diisi (is_required: true)
        TelegramCommand::create([
            'command' => 'transaksi',
            'description' => 'Catat transaksi kas',
            'response_type' => 'system',
            'is_active' => true,
            'flow_config' => [
                'store_nota' => [
                    'key' => 'store_nota',
                    'title' => 'Nama Toko & No Nota',
                    'description' => 'Toko & Nota',
                    'is_enabled' => true,
                    'is_required' => true,
                    'prompt' => 'Toko & Nota wajib:',
                    'help_text' => '',
                ],
            ],
        ]);

        $conv = TelegramConversation::create([
            'chat_id' => '33221100',
            'user_id' => $user->id,
            'command' => 'transaksi',
            'step' => 'waiting_store_nota',
            'draft_data' => [
                'amount' => 100000,
                'description' => 'Belanja ATK',
            ],
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->once()->andReturn(['ok' => true]);
        });

        // Coba kirim /skip pada store_nota yang wajib
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 33221100], 'text' => '/skip'],
        ])->assertStatus(200);

        $conv->refresh();
        // Step tidak boleh berpindah karena wajib diisi
        $this->assertEquals('waiting_store_nota', $conv->step);
    }

    public function test_income_transaction_asks_person_and_skips_store_nota(): void
    {
        $user = User::create([
            'name' => 'Bendahara Pemasukan',
            'email' => 'pemasukan@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '99887766',
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['ok' => true]);
            $mock->shouldReceive('editMessageText')->andReturn(['ok' => true]);
            $mock->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        });

        // 1. Mulai /transaksi
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99887766], 'text' => '/transaksi'],
        ])->assertStatus(200);

        // 2. Pilih jenis: Pemasukan BOSP
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_pemasukan',
                'from' => ['id' => 99887766],
                'message' => ['chat' => ['id' => 99887766], 'message_id' => 20],
                'data' => 'trx_type:masuk_bosp',
            ],
        ])->assertStatus(200);

        // 3. Pilih kategori: Tarik Bank (Droping Kas)
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_cat_bank',
                'from' => ['id' => 99887766],
                'message' => ['chat' => ['id' => 99887766], 'message_id' => 20],
                'data' => 'trx_cat:tarik_bank',
            ],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '99887766')->first();
        $this->assertEquals('waiting_amount', $conv->step);

        // 4. Masukkan nominal
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99887766], 'text' => '2.500.000'],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_description', $conv->step);

        // 5. Masukkan uraian
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99887766], 'text' => 'Tarik tunai dari rekening BOSP'],
        ])->assertStatus(200);

        // HARUS masuk ke waiting_person (baik penerimaan maupun pengeluaran)
        $conv->refresh();
        $this->assertEquals('waiting_person', $conv->step);

        // 6. Masukkan nama orang / sumber penyetor
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99887766], 'text' => 'Bank Jatim, BPU-019'],
        ])->assertStatus(200);

        // Pada penerimaan kas, store_nota HARUS dilewati dan langsung ke waiting_photos!
        $conv->refresh();
        $this->assertEquals('waiting_photos', $conv->step);
        $this->assertEquals('Bank Jatim', $conv->draft_data['person']);
        $this->assertEquals('BPU-019', $conv->draft_data['evidence_number']);

        // 7. Skip foto
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99887766], 'text' => '/skip'],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_confirmation', $conv->step);

        // 8. Konfirmasi simpan
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_confirm_masuk',
                'from' => ['id' => 99887766],
                'message' => ['chat' => ['id' => 99887766], 'message_id' => 21],
                'data' => 'confirm:yes',
            ],
        ])->assertStatus(200);

        // Pastikan tersimpan di kas_tunais sebagai penerimaan
        $kas = KasTunai::where('evidence_number', 'BPU-019')->first();
        $this->assertNotNull($kas);
        $this->assertEquals('BOSP', $kas->funding_source);
        $this->assertEquals('masuk', $kas->transaction_type);
        $this->assertEquals('tarik_bank', $kas->category);
        $this->assertEquals(2500000, (float) $kas->amount);
        $this->assertEquals('Bank Jatim', $kas->person_name);
    }

    public function test_kasbon_outflow_records_as_pending_panjar(): void
    {
        $user = User::create([
            'name' => 'Bendahara Kasbon',
            'email' => 'kasbon@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '12344321',
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['ok' => true]);
            $mock->shouldReceive('editMessageText')->andReturn(['ok' => true]);
            $mock->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        });

        // 1. Mulai /transaksi
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 12344321], 'text' => '/transaksi'],
        ])->assertStatus(200);

        // 2. Pilih jenis: Pengeluaran BOSP
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_keluar',
                'from' => ['id' => 12344321],
                'message' => ['chat' => ['id' => 12344321], 'message_id' => 30],
                'data' => 'trx_type:keluar_bosp',
            ],
        ])->assertStatus(200);

        // 3. Pilih kategori: Kasbon / Panjar Belanja
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_cat_kasbon',
                'from' => ['id' => 12344321],
                'message' => ['chat' => ['id' => 12344321], 'message_id' => 30],
                'data' => 'trx_cat:panjar_kerja',
            ],
        ])->assertStatus(200);

        // 4. Masukkan nominal kasbon
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 12344321], 'text' => '1.200.000'],
        ])->assertStatus(200);

        // 5. Masukkan uraian
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 12344321], 'text' => 'Kasbon beli sembako rapat'],
        ])->assertStatus(200);

        // 6. Masukkan nama penerima uang kasbon
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 12344321], 'text' => 'Pak Army'],
        ])->assertStatus(200);

        // 7. Skip toko & nota (karena kasbon uang muka belum ada nota)
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 12344321], 'text' => '/skip'],
        ])->assertStatus(200);

        // 8. Skip foto
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 12344321], 'text' => '/skip'],
        ])->assertStatus(200);

        // 9. Konfirmasi simpan
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_confirm_kasbon',
                'from' => ['id' => 12344321],
                'message' => ['chat' => ['id' => 12344321], 'message_id' => 31],
                'data' => 'confirm:yes',
            ],
        ])->assertStatus(200);

        // Pastikan tersimpan dengan panjar_status = 'pending'
        $kas = KasTunai::where('description', 'Kasbon beli sembako rapat')->first();
        $this->assertNotNull($kas);
        $this->assertEquals('panjar_kerja', $kas->category);
        $this->assertEquals('pending', $kas->panjar_status);
        $this->assertEquals('Pak Army', $kas->person_name);
        $this->assertEquals(1200000, (float) $kas->amount);
    }

    public function test_bot_automatically_matches_guru_name_and_stores_full_name(): void
    {
        $guru = Guru::create([
            'name' => 'ARMY WIDHI CAHYONO',
            'title_suffix' => 'S.Pt.',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Bendahara Guru Match',
            'email' => 'matchguru@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '88990011',
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['ok' => true]);
            $mock->shouldReceive('editMessageText')->andReturn(['ok' => true]);
            $mock->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        });

        // 1. Mulai /transaksi
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 88990011], 'text' => '/transaksi'],
        ])->assertStatus(200);

        // 2. Pilih Pengeluaran BOSP
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_match_type',
                'from' => ['id' => 88990011],
                'message' => ['chat' => ['id' => 88990011], 'message_id' => 40],
                'data' => 'trx_type:keluar_bosp',
            ],
        ])->assertStatus(200);

        // 2.5 Pilih Kategori
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_cat_match',
                'from' => ['id' => 88990011],
                'message' => ['chat' => ['id' => 88990011], 'message_id' => 40],
                'data' => 'trx_cat:belanja_tunai',
            ],
        ])->assertStatus(200);

        // 3. Masukkan Nominal
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 88990011], 'text' => '500.000'],
        ])->assertStatus(200);

        // 4. Masukkan Uraian
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 88990011], 'text' => 'Belanja pakan ternak ayam'],
        ])->assertStatus(200);

        // 5. Masukkan nama pendek guru: "army"
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 88990011], 'text' => 'army'],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '88990011')->first();
        $this->assertEquals('waiting_store_nota', $conv->step);
        // Harus otomatis mencocokkan guru_id dan nama lengkap bergelar
        $this->assertEquals($guru->id, $conv->draft_data['guru_id']);
        $this->assertEquals('ARMY WIDHI CAHYONO, S.Pt.', $conv->draft_data['person']);

        // 6. Masukkan Toko & Nota
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 88990011], 'text' => 'Toko Ternak Makmur, N26-999'],
        ])->assertStatus(200);

        // 7. Skip foto
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 88990011], 'text' => '/skip'],
        ])->assertStatus(200);

        // 8. Konfirmasi simpan
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_confirm_match',
                'from' => ['id' => 88990011],
                'message' => ['chat' => ['id' => 88990011], 'message_id' => 41],
                'data' => 'confirm:yes',
            ],
        ])->assertStatus(200);

        // Pastikan tersimpan dengan guru_id terhubung dan person_name lengkap
        $kas = KasTunai::where('evidence_number', 'N26-999')->first();
        $this->assertNotNull($kas);
        $this->assertEquals($guru->id, $kas->guru_id);
        $this->assertEquals('Toko Ternak Makmur', $kas->person_name);
        $this->assertEquals(500000, (float) $kas->amount);
    }

    public function test_bot_sisa_panjar_parent_selection_and_auto_settle(): void
    {
        $user = User::create([
            'name' => 'Bendahara Panjar',
            'email' => 'panjar@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '99112233',
        ]);

        $guru = Guru::create([
            'name' => 'DEWI SARTIKA',
            'title_suffix' => 'S.Pd.',
            'is_active' => true,
        ]);

        $parentPanjar = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-03',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 400000,
            'description' => 'Kasbon seminar pendidikan',
            'guru_id' => $guru->id,
            'person_name' => 'DEWI SARTIKA, S.Pd.',
            'panjar_status' => 'pending',
            'created_by' => $user->id,
        ]);

        $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['ok' => true]);
            $mock->shouldReceive('editMessageText')->andReturn(['ok' => true]);
            $mock->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        });

        // 1. Start transaksi
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99112233], 'text' => '/transaksi'],
        ])->assertStatus(200);

        // 2. Pilih masuk_bosp
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_p1',
                'from' => ['id' => 99112233],
                'message' => ['chat' => ['id' => 99112233], 'message_id' => 50],
                'data' => 'trx_type:masuk_bosp',
            ],
        ])->assertStatus(200);

        // 3. Pilih kategori sisa_panjar
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_p2',
                'from' => ['id' => 99112233],
                'message' => ['chat' => ['id' => 99112233], 'message_id' => 50],
                'data' => 'trx_cat:sisa_panjar',
            ],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '99112233')->first();
        $this->assertEquals('waiting_parent_panjar', $conv->step);

        // 4. Pilih parent panjar lewat callback
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_p3',
                'from' => ['id' => 99112233],
                'message' => ['chat' => ['id' => 99112233], 'message_id' => 50],
                'data' => "trx_parent:{$parentPanjar->id}",
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_amount', $conv->step);
        $this->assertEquals($parentPanjar->id, $conv->draft_data['parent_kas_id']);
        $this->assertEquals($guru->id, $conv->draft_data['guru_id']);

        // 5. Masukkan nominal sisa kasbon
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99112233], 'text' => '35.000'],
        ])->assertStatus(200);

        $conv->refresh();
        // Karena parent_kas_id terisi, langsung lompat ke photos/confirmation
        $this->assertEquals('waiting_photos', $conv->step);
        $this->assertEquals(35000, $conv->draft_data['amount']);

        // 6. Skip foto
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99112233], 'text' => '/skip'],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_confirmation', $conv->step);

        // 7. Konfirmasi Simpan
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_p4',
                'from' => ['id' => 99112233],
                'message' => ['chat' => ['id' => 99112233], 'message_id' => 51],
                'data' => 'confirm:yes',
            ],
        ])->assertStatus(200);

        // Verifikasi data kas masuk sisa_panjar tersimpan dengan benar
        $sisaKas = KasTunai::where('parent_kas_id', $parentPanjar->id)->first();
        $this->assertNotNull($sisaKas);
        $this->assertEquals('masuk', $sisaKas->transaction_type);
        $this->assertEquals('sisa_panjar', $sisaKas->category);
        $this->assertEquals(35000, (float) $sisaKas->amount);
        $this->assertEquals($guru->id, $sisaKas->guru_id);

        // Verifikasi kasbon induk otomatis ditandai partial karena masih tersisa Rp 365.000
        $this->assertEquals('partial', $parentPanjar->fresh()->panjar_status);
        $this->assertEquals(365000, $parentPanjar->fresh()->remaining_panjar_amount);
    }

    public function test_bot_rejects_skipping_person_in_charge(): void
    {
        $user = User::create([
            'name' => 'Bendahara Mandat',
            'email' => 'mandat@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '77665544',
        ]);

        $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['ok' => true]);
            $mock->shouldReceive('editMessageText')->andReturn(['ok' => true]);
            $mock->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        });

        // 1. Mulai transaksi dan set ke step waiting_person
        TelegramConversation::create([
            'chat_id' => '77665544',
            'user_id' => $user->id,
            'command' => 'transaksi',
            'step' => 'waiting_person',
            'draft_data' => [
                'funding_source' => 'BOSP',
                'type' => 'keluar',
                'category' => 'belanja_tunai',
                'amount' => 150000,
                'description' => 'Beli perlengkapan',
            ],
        ]);

        // 2. Coba kirim /skip
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 77665544], 'text' => '/skip'],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '77665544')->first();
        // Harus tetap di waiting_person karena wajib diisi!
        $this->assertEquals('waiting_person', $conv->step);

        // 3. Coba kirim tanda strip '-'
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 77665544], 'text' => '-'],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_person', $conv->step);

        // 4. Masukkan nama yang valid
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 77665544], 'text' => 'Ibu Siti Aminah'],
        ])->assertStatus(200);

        $conv->refresh();
        // Berhasil lanjut ke step berikutnya (waiting_store_nota)
        $this->assertEquals('waiting_store_nota', $conv->step);
        $this->assertEquals('Ibu Siti Aminah', $conv->draft_data['person']);
    }

    public function test_team_bos_role_can_only_access_foto_and_hides_finance_menus(): void
    {
        $mock = $this->mock(TelegramBotService::class);

        $teamBosUser = User::create([
            'name' => 'Pak Rudi (Tim BOS)',
            'email' => 'rudi_teambos@test.local',
            'role' => User::ROLE_TEAM_BOS,
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '99887766',
        ]);

        // 1. /help -> Menu bersih, hanya /foto, /batal, /help, /logout (keuangan disembunyikan)
        $mock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === '99887766'
                    && str_contains($text, 'Tim BOS')
                    && str_contains($text, '/foto')
                    && ! str_contains($text, '/transaksi')
                    && ! str_contains($text, '/saldo');
            });

        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99887766], 'text' => '/help'],
        ])->assertStatus(200);

        // 2. /saldo -> Akses dibatasi
        $mock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === '99887766'
                    && str_contains($text, 'Akses Dibatasi')
                    && str_contains($text, 'Tim BOS');
            });

        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99887766], 'text' => '/saldo'],
        ])->assertStatus(200);

        // 3. /transaksi -> Akses dibatasi
        $mock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === '99887766'
                    && str_contains($text, 'Akses Dibatasi');
            });

        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99887766], 'text' => '/transaksi'],
        ])->assertStatus(200);

        // 4. Callback query transaksi keuangan -> Akses dibatasi
        $mock->shouldReceive('answerCallbackQuery')->once();
        $mock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === 99887766 && str_contains($text, 'Akses Dibatasi');
            });

        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_tb_1',
                'from' => ['id' => 99887766],
                'message' => ['chat' => ['id' => 99887766], 'message_id' => 123],
                'data' => 'trx_type:keluar_bosp',
            ],
        ])->assertStatus(200);

        // 5. /foto -> Diizinkan dan memulai sesi foto
        $mock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === '99887766' && str_contains($text, 'DOKUMENTASI FOTO BARANG BOSP');
            });

        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99887766], 'text' => '/foto'],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '99887766')->first();
        $this->assertNotNull($conv);
        $this->assertEquals('foto', $conv->command);
        $this->assertEquals('waiting_foto_year', $conv->step);

        // 6. /batal -> Menghapus sesi dan memberi instruksi khusus Tim BOS
        $mock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === '99887766' && str_contains($text, '/foto');
            });

        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 99887766], 'text' => '/batal'],
        ])->assertStatus(200);

        $this->assertDatabaseMissing('telegram_conversations', ['chat_id' => '99887766']);
    }

    public function test_partial_and_full_kasbon_repayment_via_telegram_bot(): void
    {
        $mock = $this->mock(TelegramBotService::class);

        $bendahara = User::create([
            'name' => 'Bendahara Uji',
            'email' => 'bendahara_kasbon@test.local',
            'role' => User::ROLE_BENDAHARA_BOS,
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '55443322',
        ]);

        // Buat kasbon awal Rp 500.000 (status pending)
        $parentKasbon = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 500000,
            'description' => 'Panjar Beli ATK',
            'person_name' => 'Bpk. Ahmad',
            'panjar_status' => 'pending',
            'created_by' => $bendahara->id,
        ]);

        // 1. Simulasikan sesi bot mengembalikan sebagian: Rp 200.000
        TelegramConversation::create([
            'chat_id' => '55443322',
            'user_id' => $bendahara->id,
            'command' => 'transaksi',
            'step' => 'waiting_confirmation',
            'draft_data' => [
                'funding_source' => 'BOSP',
                'type' => 'masuk',
                'category' => 'sisa_panjar',
                'category_label' => 'Kembalian Sisa Kasbon / Panjar',
                'parent_kas_id' => $parentKasbon->id,
                'amount' => 200000,
                'description' => 'Cicilan kasbon #'.$parentKasbon->id,
                'person' => 'Bpk. Ahmad',
            ],
        ]);

        $mock->shouldReceive('answerCallbackQuery')->once();
        $mock->shouldReceive('editMessageText')
            ->once()
            ->withArgs(function ($chatId, $msgId, $text) {
                return (string) $chatId === '55443322'
                    && str_contains($text, 'TRANSAKSI BERHASIL DISIMPAN')
                    && str_contains($text, 'Sebagian (Partial)')
                    && str_contains($text, '300.000');
            });

        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_confirm_partial',
                'from' => ['id' => 55443322],
                'message' => ['chat' => ['id' => 55443322], 'message_id' => 123],
                'data' => 'confirm:yes',
            ],
        ])->assertStatus(200);

        // Periksa kasbon induk berubah status menjadi partial
        $parentKasbon->refresh();
        $this->assertEquals('partial', $parentKasbon->panjar_status);
        $this->assertEquals(300000, $parentKasbon->remaining_panjar_amount);

        // 2. Simulasikan pengembalian sisa Rp 300.000 (Pelunasan Penuh)
        TelegramConversation::create([
            'chat_id' => '55443322',
            'user_id' => $bendahara->id,
            'command' => 'transaksi',
            'step' => 'waiting_confirmation',
            'draft_data' => [
                'funding_source' => 'BOSP',
                'type' => 'masuk',
                'category' => 'sisa_panjar',
                'category_label' => 'Kembalian Sisa Kasbon / Panjar',
                'parent_kas_id' => $parentKasbon->id,
                'amount' => 300000,
                'description' => 'Pelunasan sisa kasbon #'.$parentKasbon->id,
                'person' => 'Bpk. Ahmad',
            ],
        ]);

        $mock->shouldReceive('answerCallbackQuery')->once();
        $mock->shouldReceive('editMessageText')
            ->once()
            ->withArgs(function ($chatId, $msgId, $text) {
                return (string) $chatId === '55443322'
                    && str_contains($text, 'TRANSAKSI BERHASIL DISIMPAN')
                    && str_contains($text, 'Telah Lunas (Settled)');
            });

        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_confirm_full',
                'from' => ['id' => 55443322],
                'message' => ['chat' => ['id' => 55443322], 'message_id' => 124],
                'data' => 'confirm:yes',
            ],
        ])->assertStatus(200);

        // Periksa kasbon induk berubah status menjadi settled
        $parentKasbon->refresh();
        $this->assertEquals('settled', $parentKasbon->panjar_status);
        $this->assertEquals(0, $parentKasbon->remaining_panjar_amount);
    }

    public function test_direct_pengeluaran_flow_without_year_selection_and_combined_photo_store_nota(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Bendahara Pengeluaran Langsung',
            'email' => 'pengeluaran_direct@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '88990011',
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['ok' => true]);
            $mock->shouldReceive('editMessageText')->andReturn(['ok' => true]);
            $mock->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
            $mock->shouldReceive('downloadFile')->andReturnUsing(function ($fileId, $path) {
                Storage::disk('public')->put($path, 'dummy-photo-content');

                return $path;
            });
        });

        // 1. Ketik /pengeluaran langsung (langsung ke pilihan kategori, tanpa pilih tahun)
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 88990011],
                'text' => '/pengeluaran',
            ],
        ])->assertStatus(200);

        $currentYear = (int) date('Y');
        $conv = TelegramConversation::where('chat_id', '88990011')->first();
        $this->assertNotNull($conv);
        $this->assertEquals('waiting_category', $conv->step);
        $this->assertEquals('keluar', $conv->draft_data['type']);
        $this->assertEquals($currentYear, $conv->draft_data['fiscal_year']);

        // 2. Pilih Kategori Belanja Tunai
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_cat',
                'from' => ['id' => 88990011],
                'message' => ['chat' => ['id' => 88990011], 'message_id' => 201],
                'data' => 'trx_cat:belanja_tunai',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_amount', $conv->step);

        // 3. Masukkan Nominal
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 88990011],
                'text' => '450.000',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_description', $conv->step);
        $this->assertEquals(450000, $conv->draft_data['amount']);

        // 4. Masukkan Uraian Belanja
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 88990011],
                'text' => 'Pembelian Kertas HVS F4 dan Tinta Epson',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_person', $conv->step);

        // 5. Masukkan Nama Penanggung Jawab
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 88990011],
                'text' => 'Ibu Siti Aminah',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_store_nota', $conv->step);
        $this->assertEquals('Ibu Siti Aminah', $conv->draft_data['person']);

        // 6. Kirim Foto dengan Caption (Foto + Toko + No Nota digabung satu langkah)
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 88990011],
                'photo' => [
                    ['file_id' => 'thumb_123', 'width' => 100, 'height' => 100],
                    ['file_id' => 'full_photo_999', 'width' => 800, 'height' => 600],
                ],
                'caption' => 'Toko Subur ATK, NOTA-2025-001',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        // Langkah 6 selesai -> Lanjut ke Langkah 7: Foto Dokumentasi Barang (Maks. 3 foto)
        $this->assertEquals('waiting_photos', $conv->step);
        $this->assertEquals('Toko Subur ATK', $conv->draft_data['store_name']);
        $this->assertEquals('NOTA-2025-001', $conv->draft_data['evidence_number']);
        $this->assertContains('full_photo_999', $conv->draft_data['photos']);

        // 7. Langkah 7: Upload Foto Dokumentasi Barang Fisik
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 88990011],
                'photo' => [
                    ['file_id' => 'doc_thumb_123', 'width' => 100, 'height' => 100],
                    ['file_id' => 'doc_photo_888', 'width' => 800, 'height' => 600],
                ],
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertContains('doc_photo_888', $conv->draft_data['photos']);

        // Selesai upload foto dokumentasi
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 88990011],
                'text' => '/selesai',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_confirmation', $conv->step);

        // 8. Konfirmasi Simpan Transaksi
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_confirm_save',
                'from' => ['id' => 88990011],
                'message' => ['chat' => ['id' => 88990011], 'message_id' => 202],
                'data' => 'confirm:yes',
            ],
        ])->assertStatus(200);

        // Verifikasi Sesi Percakapan terhapus
        $this->assertNull(TelegramConversation::where('chat_id', '88990011')->first());

        // Verifikasi KasTunai tersimpan dengan tahun anggaran berjalan
        $kas = KasTunai::where('amount', 450000)->where('transaction_type', 'keluar')->first();
        $this->assertNotNull($kas);
        $this->assertStringStartsWith("{$currentYear}-", $kas->transaction_date->format('Y-m-d'));
        $this->assertEquals('Toko Subur ATK', $kas->person_name);
        $this->assertEquals('NOTA-2025-001', $kas->evidence_number);

        // Verifikasi BarangDokumentasi tersimpan di tahun anggaran berjalan
        $doc = BarangDokumentasi::where('kas_tunai_id', $kas->id)->first();
        $this->assertNotNull($doc);
        $this->assertEquals($currentYear, $doc->year);
        $this->assertEquals('Toko Subur ATK', $doc->nama_toko);
        $this->assertEquals('NOTA-2025-001', $doc->no_nota);

        // Verifikasi foto tersimpan
        $foto = BarangDokumentasiFoto::where('barang_dokumentasi_id', $doc->id)->first();
        $this->assertNotNull($foto);
        $this->assertStringContainsString("dokumentasi_barang/{$currentYear}/", $foto->file_path);
    }

    public function test_direct_pemasukan_flow_without_year_selection(): void
    {
        $user = User::create([
            'name' => 'Bendahara Penerimaan Langsung',
            'email' => 'pemasukan_direct@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '77889900',
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['ok' => true]);
            $mock->shouldReceive('editMessageText')->andReturn(['ok' => true]);
            $mock->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        });

        // 1. Ketik /pemasukan langsung (langsung ke pilihan kategori, tanpa pilih tahun)
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 77889900],
                'text' => '/pemasukan',
            ],
        ])->assertStatus(200);

        $currentYear = (int) date('Y');
        $conv = TelegramConversation::where('chat_id', '77889900')->first();
        $this->assertNotNull($conv);
        $this->assertEquals('waiting_category', $conv->step);
        $this->assertEquals('masuk', $conv->draft_data['type']);
        $this->assertEquals($currentYear, $conv->draft_data['fiscal_year']);

        // 2. Pilih Kategori Droping BOSP
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_cat_in',
                'from' => ['id' => 77889900],
                'message' => ['chat' => ['id' => 77889900], 'message_id' => 301],
                'data' => 'trx_cat:droping_bosp',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_amount', $conv->step);

        // 3. Masukkan Nominal
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 77889900],
                'text' => '5.000.000',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_description', $conv->step);

        // 4. Masukkan Uraian
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 77889900],
                'text' => 'Tarik Tunai BOSP Tahap 1 Rekening Bank Jatim',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_person', $conv->step);

        // 5. Masukkan Penyetor / Sumber
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 77889900],
                'text' => 'Bank Jatim Cabang',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        // Penerimaan langsung ke waiting_photos (bukan store_nota)
        $this->assertEquals('waiting_photos', $conv->step);

        // 6. Skip foto
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 77889900],
                'text' => '/skip',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_confirmation', $conv->step);

        // 7. Simpan Transaksi
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_confirm_in',
                'from' => ['id' => 77889900],
                'message' => ['chat' => ['id' => 77889900], 'message_id' => 302],
                'data' => 'confirm:yes',
            ],
        ])->assertStatus(200);

        $kas = KasTunai::where('amount', 5000000)->where('transaction_type', 'masuk')->first();
        $this->assertNotNull($kas);
        $this->assertEquals('droping_bosp', $kas->category);
        $this->assertEquals('Bank Jatim Cabang', $kas->person_name);
        $this->assertStringStartsWith("{$currentYear}-", $kas->transaction_date->format('Y-m-d'));
    }

    public function test_foto_flow_with_previous_year_selection(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Guru Pengunggah Foto',
            'email' => 'guru_foto@mkb.test',
            'role' => User::ROLE_TEAM_BOS,
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '12123434',
        ]);

        $mock = $this->mock(TelegramBotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['ok' => true]);
            $mock->shouldReceive('editMessageText')->andReturn(['ok' => true]);
            $mock->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
            $mock->shouldReceive('downloadFile')->andReturnUsing(function ($fileId, $path) {
                Storage::disk('public')->put($path, 'dummy-photo-content');

                return $path;
            });
        });

        // 1. Ketik /foto -> mulai alur pilih tahun anggaran foto
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 12123434],
                'text' => '/foto',
            ],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '12123434')->first();
        $this->assertNotNull($conv);
        $this->assertEquals('foto', $conv->command);
        $this->assertEquals('waiting_foto_year', $conv->step);

        // 2. Pilih Tahun Anggaran Lalu (misal 2025)
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_foto_yr',
                'from' => ['id' => 12123434],
                'message' => ['chat' => ['id' => 12123434], 'message_id' => 501],
                'data' => 'foto_year:2025',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_photo_direct', $conv->step);
        $this->assertEquals(2025, $conv->draft_data['fiscal_year']);

        // 3. Kirim Foto
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 12123434],
                'photo' => [
                    ['file_id' => 'thumb_f', 'width' => 100, 'height' => 100],
                    ['file_id' => 'foto_bosp_2025', 'width' => 800, 'height' => 600],
                ],
            ],
        ])->assertStatus(200);

        // 4. Ketik /selesai untuk menyelesaikan kirim foto
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 12123434],
                'text' => '/selesai',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_foto_uraian', $conv->step);

        // 5. Masukkan uraian
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 12123434],
                'text' => 'Pengadaan Komputer Lab Tahun Lalu',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        // Step nama toko
        $this->assertEquals('waiting_foto_toko', $conv->step);

        // 6. Masukkan nama toko
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 12123434],
                'text' => 'CV Komputer Sejahtera',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        // Step nota
        $this->assertEquals('waiting_foto_nota', $conv->step);

        // 7. Masukkan no nota
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 12123434],
                'text' => 'NOTA-2025-LAB-01',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_confirmation_foto', $conv->step);

        // 8. Konfirmasi simpan foto
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_confirm_foto',
                'from' => ['id' => 12123434],
                'message' => ['chat' => ['id' => 12123434], 'message_id' => 502],
                'data' => 'confirm_foto:yes',
            ],
        ])->assertStatus(200);

        // Verifikasi dokumentasi barang tersimpan dengan tahun 2025
        $doc = BarangDokumentasi::where('no_nota', 'NOTA-2025-LAB-01')->first();
        $this->assertNotNull($doc);
        $this->assertEquals(2025, $doc->year);
        $this->assertEquals('Pengadaan Komputer Lab Tahun Lalu', $doc->uraian);

        // Verifikasi foto tersimpan pada folder 2025
        $foto = BarangDokumentasiFoto::where('barang_dokumentasi_id', $doc->id)->first();
        $this->assertNotNull($foto);
        $this->assertStringContainsString('dokumentasi_barang/2025/', $foto->file_path);
    }

    public function test_combined_nominal_and_guru_name_in_amount_step_matches_guru_and_skips_person_step(): void
    {
        $user = User::create([
            'name' => 'Bendahara Gabung',
            'email' => 'bendahara.gabung@test.com',
            'role' => 'bendahara',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '99887766',
        ]);

        $guruArmy = Guru::create([
            'name' => 'ARMY WIDHI CAHYONO',
            'title_suffix' => 'S.Pd.',
            'is_active' => true,
        ]);

        $mockBot = \Mockery::mock(TelegramBotService::class);
        $mockBot->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        $mockBot->shouldReceive('sendMessage')->andReturn(['ok' => true]);
        $mockBot->shouldReceive('editMessageText')->andReturn(['ok' => true]);
        $this->app->instance(TelegramBotService::class, $mockBot);

        // 1. Mulai perintah /pengeluaran
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 99887766],
                'text' => '/pengeluaran',
            ],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '99887766')->first();
        $this->assertNotNull($conv);

        // 2. Pilih kategori belanja_tunai
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_cat_tunai',
                'from' => ['id' => 99887766],
                'message' => ['chat' => ['id' => 99887766], 'message_id' => 201],
                'data' => 'trx_cat:belanja_tunai',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_amount', $conv->step);

        // 3. Masukkan Nominal digabung dengan nama guru: "100.000, army"
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 99887766],
                'text' => '100.000, army',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        // Langsung lompat ke waiting_description
        $this->assertEquals('waiting_description', $conv->step);
        $this->assertEquals(100000, $conv->draft_data['amount']);
        $this->assertEquals($guruArmy->id, $conv->draft_data['guru_id']);
        $this->assertEquals('ARMY WIDHI CAHYONO, S.Pd.', $conv->draft_data['person']);

        // 4. Masukkan Uraian Belanja
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 99887766],
                'text' => 'Pembelian Kertas HVS F4',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        // Langkah waiting_person harus OTOMATIS DILEWATI (skip) langsung ke waiting_store_nota
        $this->assertEquals('waiting_store_nota', $conv->step);

        // 5. Masukkan Toko dan Nota
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 99887766],
                'text' => 'Toko Barokah, NOTA-ARMY-01',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_photos', $conv->step);

        // 6. Skip Foto
        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => 99887766],
                'text' => '/skip',
            ],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_confirmation', $conv->step);

        // 7. Konfirmasi Simpan
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_confirm_save',
                'from' => ['id' => 99887766],
                'message' => ['chat' => ['id' => 99887766], 'message_id' => 205],
                'data' => 'confirm:yes',
            ],
        ])->assertStatus(200);

        // Verifikasi kas_tunais tersimpan dengan benar
        $kas = KasTunai::where('evidence_number', 'NOTA-ARMY-01')->first();
        $this->assertNotNull($kas);
        $this->assertEquals(100000, (float) $kas->amount);
        $this->assertEquals($guruArmy->id, $kas->guru_id);
        $this->assertEquals('Toko Barokah', $kas->person_name);
        $this->assertEquals('Pembelian Kertas HVS F4', $kas->description);
    }

    public function test_combined_nominal_and_custom_person_with_hyphen_delimiter(): void
    {
        $user = User::create([
            'name' => 'Bendahara Strip',
            'email' => 'bendahara.strip@test.com',
            'role' => 'bendahara',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '88776655',
        ]);

        $mockBot = \Mockery::mock(TelegramBotService::class);
        $mockBot->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        $mockBot->shouldReceive('sendMessage')->andReturn(['ok' => true]);
        $mockBot->shouldReceive('editMessageText')->andReturn(['ok' => true]);
        $this->app->instance(TelegramBotService::class, $mockBot);

        // Mulai /pengeluaran
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 88776655], 'text' => '/pengeluaran'],
        ])->assertStatus(200);

        // Pilih kategori belanja_tunai
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_cat_strip',
                'from' => ['id' => 88776655],
                'message' => ['chat' => ['id' => 88776655], 'message_id' => 301],
                'data' => 'trx_cat:belanja_tunai',
            ],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '88776655')->first();
        $this->assertEquals('waiting_amount', $conv->step);

        // Input gabungan dengan strip: "50000 - Mas Danang"
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 88776655], 'text' => '50000 - Mas Danang'],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_description', $conv->step);
        $this->assertEquals(50000, $conv->draft_data['amount']);
        $this->assertEquals('Mas Danang', $conv->draft_data['person']);
        $this->assertNull($conv->draft_data['guru_id']);

        // Input Uraian Belanja
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 88776655], 'text' => 'Service Printer Ruang Guru'],
        ])->assertStatus(200);

        $conv->refresh();
        // Tetap auto-skip waiting_person langsung ke waiting_store_nota
        $this->assertEquals('waiting_store_nota', $conv->step);
    }

    public function test_combined_nominal_and_person_in_pemasukan_flow(): void
    {
        $user = User::create([
            'name' => 'Bendahara Masuk',
            'email' => 'bendahara.masuk@test.com',
            'role' => 'bendahara',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '77665544',
        ]);

        $mockBot = \Mockery::mock(TelegramBotService::class);
        $mockBot->shouldReceive('answerCallbackQuery')->andReturn(['ok' => true]);
        $mockBot->shouldReceive('sendMessage')->andReturn(['ok' => true]);
        $mockBot->shouldReceive('editMessageText')->andReturn(['ok' => true]);
        $this->app->instance(TelegramBotService::class, $mockBot);

        // 1. Mulai /pemasukan
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 77665544], 'text' => '/pemasukan'],
        ])->assertStatus(200);

        // 2. Pilih kategori penerimaan (misal: bank_bosp atau lainnya_masuk)
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_cat_masuk',
                'from' => ['id' => 77665544],
                'message' => ['chat' => ['id' => 77665544], 'message_id' => 401],
                'data' => 'trx_cat:lainnya_masuk',
            ],
        ])->assertStatus(200);

        $conv = TelegramConversation::where('chat_id', '77665544')->first();
        $this->assertEquals('waiting_amount', $conv->step);

        // 3. Masukkan nominal digabung penyetor: "250.000, Bank Jatim"
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 77665544], 'text' => '250.000, Bank Jatim'],
        ])->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('waiting_description', $conv->step);
        $this->assertEquals(250000, $conv->draft_data['amount']);
        $this->assertEquals('Bank Jatim', $conv->draft_data['person']);

        // 4. Masukkan uraian penerimaan
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 77665544], 'text' => 'Bunga Rekening Bank'],
        ])->assertStatus(200);

        $conv->refresh();
        // Untuk pemasukan: setelah description, karena person sudah terisi, langsung lompat ke waiting_photos
        $this->assertEquals('waiting_photos', $conv->step);
    }

    public function test_kasbon_menu_and_detail_flow(): void
    {
        $user = User::create([
            'name' => 'Bendahara Kasbon',
            'email' => 'bendahara.kasbon@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '33445566',
            'role' => 'bendahara',
        ]);

        $guru = Guru::create([
            'name' => 'ANANG SURYANTO',
            'title_suffix' => 'S.Pd.',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $messagesSent = [];
        $messagesEdited = [];

        $this->mock(TelegramBotService::class, function (MockInterface $mock) use (&$messagesSent, &$messagesEdited) {
            $mock->shouldReceive('sendMessage')
                ->andReturnUsing(function ($chatId, $text, $keyboard = null) use (&$messagesSent) {
                    $messagesSent[] = ['text' => $text, 'keyboard' => $keyboard];

                    return ['ok' => true];
                });
            $mock->shouldReceive('editMessageText')
                ->andReturnUsing(function ($chatId, $messageId, $text, $keyboard = null) use (&$messagesEdited) {
                    $messagesEdited[] = ['text' => $text, 'keyboard' => $keyboard];

                    return ['ok' => true];
                });
            $mock->shouldReceive('answerCallbackQuery')
                ->andReturn(['ok' => true]);
        });

        // 1. Cek saat belum ada kasbon
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 33445566], 'text' => '/kasbon'],
        ])->assertStatus(200);

        $this->assertNotEmpty($messagesSent);
        $this->assertStringContainsString('tidak ada kasbon yang aktif', $messagesSent[0]['text']);

        // 2. Buat kasbon
        $kasbon = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => now()->subDays(2),
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 500000,
            'description' => 'Panjar Konsumsi Rapat',
            'guru_id' => $guru->id,
            'panjar_status' => 'pending',
            'created_by' => $user->id,
        ]);

        // Buat child SPJ (cicilan / sisa panjar)
        $child = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => now()->subDay(),
            'transaction_type' => 'masuk',
            'category' => 'sisa_panjar',
            'amount' => 100000,
            'description' => 'Kembalian sisa belanja rapat',
            'parent_kas_id' => $kasbon->id,
            'created_by' => $user->id,
        ]);
        $kasbon->update(['panjar_status' => 'partial']);

        // 3. Panggil /kasbon lagi
        $messagesSent = [];
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 33445566], 'text' => '/kasbon'],
        ])->assertStatus(200);

        $this->assertNotEmpty($messagesSent);
        $kasbonListMsg = $messagesSent[0]['text'];
        $this->assertStringContainsString('RINCIAN SELURUH KASBON', $kasbonListMsg);
        $this->assertStringContainsString('ANANG SURYANTO', $kasbonListMsg);
        $this->assertStringContainsString('Panjar Konsumsi Rapat', $kasbonListMsg);
        $this->assertStringContainsString('400.000', $kasbonListMsg); // Sisa tanggungan: 500k - 100k = 400k

        // Tombol keyboard harus memiliki detail kasbon
        $keyboard = $messagesSent[0]['keyboard'];
        $this->assertNotNull($keyboard);

        // 4. Klik callback kasbon_detail
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_kasbon_det',
                'from' => ['id' => 33445566],
                'message' => ['chat' => ['id' => 33445566], 'message_id' => 999],
                'data' => "kasbon_detail:{$kasbon->id}",
            ],
        ])->assertStatus(200);

        $this->assertNotEmpty($messagesEdited);
        $detailMsg = $messagesEdited[0]['text'];
        $this->assertStringContainsString('DETAIL KASBON', $detailMsg);
        $this->assertStringContainsString('081234567890', $detailMsg);
        $this->assertStringContainsString('RIWAYAT PENGEMBALIAN', $detailMsg);
        $this->assertStringContainsString('Kembalian sisa belanja rapat', $detailMsg);

        // 5. Direct /kasbon {id} command
        $messagesSent = [];
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 33445566], 'text' => "/kasbon {$kasbon->id}"],
        ])->assertStatus(200);

        $this->assertNotEmpty($messagesSent);
        $this->assertStringContainsString('DETAIL KASBON', $messagesSent[0]['text']);
        $this->assertStringContainsString('ANANG SURYANTO', $messagesSent[0]['text']);

        // 6. Callback kasbon_page:1
        $messagesEdited = [];
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_kasbon_pg',
                'from' => ['id' => 33445566],
                'message' => ['chat' => ['id' => 33445566], 'message_id' => 999],
                'data' => 'kasbon_page:1',
            ],
        ])->assertStatus(200);

        $this->assertNotEmpty($messagesEdited);
        $this->assertStringContainsString('RINCIAN SELURUH KASBON', $messagesEdited[0]['text']);

        // 7. Callback kasbon_saldo
        $messagesEdited = [];
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb_kasbon_sld',
                'from' => ['id' => 33445566],
                'message' => ['chat' => ['id' => 33445566], 'message_id' => 999],
                'data' => 'kasbon_saldo',
            ],
        ])->assertStatus(200);

        $this->assertNotEmpty($messagesEdited);
        $this->assertStringContainsString('INFORMASI SALDO KAS TUNAI BOSP', $messagesEdited[0]['text']);
        $this->assertStringContainsString('Kasbon / Panjar Belum Lunas', $messagesEdited[0]['text']);

        // 8. /saldo command shows Cek Rincian Kasbon button
        $messagesSent = [];
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 33445566], 'text' => '/saldo'],
        ])->assertStatus(200);

        $this->assertNotEmpty($messagesSent);
        $this->assertStringContainsString('Kasbon / Panjar Belum Lunas', $messagesSent[0]['text']);
        $this->assertNotNull($messagesSent[0]['keyboard']);
        $this->assertEquals('kasbon_page:1', $messagesSent[0]['keyboard'][0][0]['callback_data']);

        // 9. Test akses ditolak untuk user foto only
        $userFoto = User::create([
            'name' => 'Waka Sarpras Foto',
            'email' => 'sarpras@mkb.test',
            'password' => Hash::make('secret123'),
            'telegram_chat_id' => '11223344',
            'role' => 'waka_sarpras',
        ]);

        $messagesSent = [];
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 11223344], 'text' => '/kasbon'],
        ])->assertStatus(200);

        $this->assertStringContainsString('Akses Dibatasi', $messagesSent[0]['text']);
    }
}
