<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\GuruBankAccount;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuruModuleTest extends TestCase
{
    public function test_guru_model_and_accessors(): void
    {
        $guru = new Guru([
            'name' => 'Budi Santoso',
            'title_prefix' => 'Drs.',
            'title_suffix' => 'M.Pd',
            'gender' => 'L',
            'employment_status' => 'PNS',
            'pangkat_golongan' => 'Pembina / IV.a',
            'is_active' => true,
        ]);

        $this->assertEquals('Drs. Budi Santoso, M.Pd', $guru->full_name_with_title);
        $this->assertEquals('PNS', $guru->employment_status);
        $this->assertTrue($guru->is_active);
    }

    public function test_guru_views_render_properly(): void
    {
        $guru = new Guru([
            'name' => 'Budi Santoso',
            'title_prefix' => 'Drs.',
            'title_suffix' => 'M.Pd',
            'gender' => 'L',
            'employment_status' => 'PNS',
            'pangkat_golongan' => 'Pembina / IV.a',
            'phone' => '081234567890',
            'email' => 'budi@sekolah.sch.id',
            'is_active' => true,
        ]);
        $guru->id = 1;
        $guru->setRelation('bankAccounts', collect([
            new GuruBankAccount([
                'bank_name' => 'Bank Jatim',
                'account_number' => '123456789',
                'account_holder' => 'Budi Santoso',
                'is_primary' => true,
            ]),
        ]));
        $guru->setRelation('jabatans', collect([]));

        // 1. Index View
        $indexHtml = view('master.guru.index')->render();
        $this->assertStringContainsString('Data Guru & Pegawai', $indexHtml);
        $this->assertStringContainsString('guruTable', $indexHtml);

        // 2. Create View
        $createHtml = view('master.guru.create')->render();
        $this->assertStringContainsString('Tambah Data Guru & Pegawai', $createHtml);
        $this->assertStringContainsString('btnAddAccount', $createHtml);

        // 3. Edit View
        $editHtml = view('master.guru.edit', compact('guru'))->render();
        $this->assertStringContainsString('Edit Data Guru', $editHtml);
        $this->assertStringContainsString('Bank Jatim', $editHtml);

        // 4. Show View
        $showHtml = view('master.guru.show', compact('guru'))->render();
        $this->assertStringContainsString('Profil Guru & Pegawai', $showHtml);
        $this->assertStringContainsString('Drs. Budi Santoso, M.Pd', $showHtml);
    }

    public function test_jabatan_views_render_properly(): void
    {
        $gurus = collect([]);
        $jabatans = collect([
            new Jabatan([
                'id' => 1,
                'name' => 'Bendahara BOSP',
                'is_signatory' => true,
            ]),
        ]);
        $years = collect([date('Y')]);

        // 1. Master Jabatan Index View
        $jabatanHtml = view('master.jabatan.index')->render();
        $this->assertStringContainsString('Master Jabatan Sekolah', $jabatanHtml);
        $this->assertStringContainsString('jabatanTable', $jabatanHtml);
        $this->assertStringContainsString('btn-edit-jabatan', $jabatanHtml);
        $this->assertStringContainsString('action-url', $jabatanHtml);

        // 2. Penugasan Jabatan View
        $penugasanHtml = view('master.jabatan.penugasan', compact('gurus', 'jabatans', 'years'))->render();
        $this->assertStringContainsString('Penetapan Penugasan Jabatan Guru', $penugasanHtml);
        $this->assertStringContainsString('penugasanTable', $penugasanHtml);
        $this->assertStringContainsString('btn-edit-penugasan', $penugasanHtml);
        $this->assertStringContainsString('action-url', $penugasanHtml);
    }

    public function test_guru_store_without_bank_account(): void
    {
        $this->ensureDatabaseTables();

        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->post(route('guru.store'), [
            'name' => 'Guru Tanpa Rekening',
            'gender' => 'L',
            'employment_status' => 'GTT',
            'bank_accounts' => [
                [
                    'bank_name' => '',
                    'account_number' => '',
                    'account_holder' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('guru.index'));
        $response->assertSessionHas('success');

        $guru = Guru::where('name', 'Guru Tanpa Rekening')->first();
        $this->assertNotNull($guru);
        $this->assertCount(0, $guru->bankAccounts);
    }

    public function test_guru_store_and_update_with_bank_account_without_account_number(): void
    {
        $this->ensureDatabaseTables();

        $user = User::first() ?? User::factory()->create();

        // 1. Create guru with bank name but empty account number
        $response = $this->actingAs($user)->post(route('guru.store'), [
            'name' => 'Guru Rekening Belum Ada',
            'gender' => 'P',
            'employment_status' => 'GTY',
            'bank_accounts' => [
                [
                    'bank_name' => 'Bank Jatim',
                    'account_number' => '',
                    'account_holder' => 'Guru Rekening Belum Ada',
                    'is_primary' => '1',
                ],
            ],
        ]);

        $response->assertRedirect(route('guru.index'));
        $response->assertSessionHas('success');

        $guru = Guru::where('name', 'Guru Rekening Belum Ada')->first();
        $this->assertNotNull($guru);
        $this->assertCount(1, $guru->bankAccounts);
        $acc = $guru->bankAccounts->first();
        $this->assertEquals('Bank Jatim', $acc->bank_name);
        $this->assertNull($acc->account_number);

        // 2. Update guru
        $updateResponse = $this->actingAs($user)->put(route('guru.update', $guru->id), [
            'name' => 'Guru Rekening Belum Ada (Updated)',
            'gender' => 'P',
            'employment_status' => 'GTY',
            'bank_accounts' => [
                [
                    'bank_name' => 'BRI',
                    'account_number' => null,
                    'account_holder' => 'Guru Rekening Belum Ada',
                    'is_primary' => '1',
                ],
            ],
        ]);

        $updateResponse->assertRedirect(route('guru.index'));
        $guru->refresh();
        $this->assertEquals('Guru Rekening Belum Ada (Updated)', $guru->name);
        $this->assertCount(1, $guru->bankAccounts);
        $this->assertEquals('BRI', $guru->bankAccounts->first()->bank_name);
        $this->assertNull($guru->bankAccounts->first()->account_number);
    }

    private function ensureDatabaseTables(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
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

        if (! Schema::hasTable('guru_bank_accounts')) {
            Schema::create('guru_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
                $table->string('bank_name', 50)->nullable();
                $table->string('account_number', 50)->nullable();
                $table->string('account_holder', 150)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->string('notes', 100)->nullable();
                $table->timestamps();
            });
        }
    }
}
