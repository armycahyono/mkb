<?php

namespace Tests\Feature;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\BosRealisasi;
use App\Models\Guru;
use App\Models\GuruBankAccount;
use App\Models\GuruJabatan;
use App\Models\Jabatan;
use App\Models\PencairanBank;
use App\Models\PengajuanBarang;
use App\Models\SchoolProfile;
use App\Models\User;
use App\Models\UtilityProfile;
use App\Models\UtilityReceipt;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UtilityLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Guru $bendahara;

    protected Guru $guruReimburse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTablesExist();

        $this->user = User::first() ?? User::create([
            'name' => 'Admin Tester',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($this->user);

        if (! SchoolProfile::first()) {
            SchoolProfile::create([
                'name' => 'SMKS PGRI KRAS',
                'bosp_bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR',
                'bosp_bank_branch' => 'CABANG KEDIRI',
                'bosp_bank_address' => 'Jl. PK. Bangsa No. 122 Kediri',
                'bosp_account_number' => '1001003336',
                'bosp_account_name' => '20511923 SMKS PGRI KRAS KEDIRI (BOSP)',
            ]);
        }

        $this->bendahara = Guru::firstOrCreate(
            ['name' => 'ARMY WIDHI CAHYONO, S.Pt'],
            [
                'is_active' => true,
                'email' => 'bendahara@smkpgrikras.sch.id',
            ]
        );

        $this->guruReimburse = Guru::firstOrCreate(
            ['name' => 'GURU PEMBAYAR LISTRIK'],
            [
                'is_active' => true,
                'email' => 'pembayar@smkpgrikras.sch.id',
            ]
        );

        GuruBankAccount::updateOrCreate(
            ['guru_id' => $this->guruReimburse->id, 'account_number' => '0233445566'],
            [
                'bank_name' => 'Bank Jatim',
                'account_holder' => 'GURU PEMBAYAR LISTRIK',
                'is_primary' => true,
            ]
        );

        $jabatanBendahara = Jabatan::firstOrCreate(['code' => 'BEND_BOSP'], ['name' => 'Bendahara BOSP']);
        GuruJabatan::firstOrCreate(
            ['guru_id' => $this->bendahara->id, 'jabatan_id' => $jabatanBendahara->id],
            ['start_date' => '2020-01-01', 'is_active' => true]
        );
    }

    private function ensureTablesExist(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_id')->nullable();
                $table->string('name');
                $table->string('username', 50)->nullable()->unique();
                $table->string('email')->unique();
                $table->string('role', 30)->default('bendahara');
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('school_profiles')) {
            Schema::create('school_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('npsn', 20)->nullable();
                $table->text('address')->nullable();
                $table->string('bosp_bank_name')->nullable();
                $table->string('bosp_bank_branch')->nullable();
                $table->string('bosp_bank_address')->nullable();
                $table->string('bosp_account_number')->nullable();
                $table->string('bosp_account_name')->nullable();
                $table->string('bpopp_bank_name')->nullable();
                $table->string('bpopp_bank_branch')->nullable();
                $table->string('bpopp_bank_address')->nullable();
                $table->string('bpopp_account_number')->nullable();
                $table->string('bpopp_account_name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('gurus')) {
            Schema::create('gurus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('guru_bank_accounts')) {
            Schema::create('guru_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('guru_id');
                $table->string('bank_name', 100);
                $table->string('account_number', 50);
                $table->string('account_holder', 150)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
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
                $table->foreignId('guru_id');
                $table->foreignId('jabatan_id')->nullable();
                $table->string('jabatan_type', 50)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('utility_profiles')) {
            Schema::create('utility_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable()->unique();
                $table->string('name');
                $table->string('category', 50);
                $table->string('customer_name')->nullable();
                $table->string('customer_number')->nullable();
                $table->string('meter_number')->nullable();
                $table->string('idpel')->nullable();
                $table->string('tariff_power')->nullable();
                $table->string('provider')->nullable();
                $table->string('payment_method')->nullable();
                $table->unsignedBigInteger('default_reimburse_guru_id')->nullable();
                $table->boolean('is_budgeted')->default(true);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(1);
                $table->text('description')->nullable();
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
                $table->string('kode_rekening', 50)->nullable();
                $table->string('bulan_pakai', 50)->nullable();
                $table->text('keterangan')->nullable();
                $table->boolean('realisasi')->default(false);
                $table->boolean('is_recurring')->default(false);
                $table->string('recurring_group_id', 50)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            if (! Schema::hasColumn('pengajuan_barangs', 'kode_rekening')) {
                Schema::table('pengajuan_barangs', function (Blueprint $table) {
                    $table->string('kode_rekening', 50)->nullable();
                });
            }
        }

        if (! Schema::hasTable('bos_kertas_kerja_files')) {
            Schema::create('bos_kertas_kerja_files', function (Blueprint $table) {
                $table->id();
                $table->string('filename')->nullable();
                $table->string('original_filename')->nullable();
                $table->string('file_path')->nullable();
                $table->integer('year')->default(2026);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_items')) {
            Schema::create('bos_kertas_kerja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_file_id')->nullable();
                $table->unsignedBigInteger('utility_profile_id')->nullable();
                $table->integer('year')->default(2026);
                $table->string('month', 50)->nullable();
                $table->string('account_code', 50)->nullable();
                $table->string('program_code', 50)->nullable();
                $table->string('kegiatan')->nullable();
                $table->string('description')->nullable();
                $table->decimal('volume', 10, 2)->default(0);
                $table->string('unit', 50)->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
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

        if (! Schema::hasTable('perjadins')) {
            Schema::create('perjadins', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->string('bku_number', 100)->nullable();
                $table->string('account_code', 50)->nullable();
                $table->text('activity_purpose')->nullable();
                $table->string('destination_name', 255)->nullable();
                $table->string('status', 20)->default('draft');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('perjadin_items')) {
            Schema::create('perjadin_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('perjadin_id')->nullable();
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('net_amount', 15, 2)->default(0);
                $table->boolean('is_paid')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanjas')) {
            Schema::create('bos_rencana_belanjas', function (Blueprint $table) {
                $table->id();
                $table->string('no_nota', 50)->nullable();
                $table->integer('year')->nullable();
                $table->string('month', 50)->nullable();
                $table->string('title')->nullable();
                $table->string('status', 50)->default('draft');
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanja_items')) {
            Schema::create('bos_rencana_belanja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_rencana_belanja_id');
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->decimal('volume_plan', 10, 2)->default(0);
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('pencairan_banks')) {
            Schema::create('pencairan_banks', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->string('letter_number', 100);
                $table->date('letter_date');
                $table->string('bank_name', 100)->nullable();
                $table->string('bank_branch', 100)->nullable();
                $table->string('bank_address', 255)->nullable();
                $table->string('source_account_number', 50)->nullable();
                $table->string('source_account_name', 150)->nullable();
                $table->unsignedBigInteger('kepala_sekolah_id')->nullable();
                $table->unsignedBigInteger('bendahara_id')->nullable();
                $table->decimal('total_nominal', 15, 2)->default(0);
                $table->integer('total_accounts')->default(0);
                $table->string('status', 20)->default('draft');
                $table->date('cair_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('pencairan_bank_items')) {
            Schema::create('pencairan_bank_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pencairan_bank_id');
                $table->string('type', 30)->default('perjadin');
                $table->string('recipient_name', 150)->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 50)->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('description', 255)->nullable();
                $table->unsignedBigInteger('utility_receipt_id')->nullable();
                $table->unsignedBigInteger('bos_rencana_belanja_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('utility_receipts')) {
            Schema::create('utility_receipts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('utility_profile_id')->nullable();
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->integer('fiscal_year')->default(2026);
                $table->string('billing_month', 50)->nullable();
                $table->dateTime('transaction_date')->nullable();
                $table->string('receipt_number', 100)->nullable();
                $table->string('reference_number', 100)->nullable();
                $table->string('customer_name', 150)->nullable();
                $table->string('customer_number', 100)->nullable();
                $table->string('meter_number', 100)->nullable();
                $table->string('tariff_power', 100)->nullable();
                $table->decimal('kwh_amount', 12, 2)->nullable();
                $table->decimal('base_amount', 15, 2)->default(0);
                $table->decimal('tax_pbjt', 15, 2)->default(0);
                $table->decimal('admin_fee', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->boolean('is_paid')->default(false);
                $table->date('payment_date')->nullable();
                $table->string('bku_number', 100)->nullable();
                $table->string('source_type', 50)->default('upload_web');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_realisasies')) {
            Schema::create('bos_realisasies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->string('no_transaksi', 100)->nullable();
                $table->string('no_nota', 100)->nullable();
                $table->unsignedBigInteger('bos_rencana_belanja_id')->nullable();
                $table->unsignedBigInteger('perjadin_id')->nullable();
                $table->unsignedBigInteger('utility_receipt_id')->nullable();
                $table->integer('year')->nullable();
                $table->string('month', 50)->nullable();
                $table->date('transaction_date')->nullable();
                $table->date('receipt_date')->nullable();
                $table->decimal('volume', 10, 2)->default(0);
                $table->string('unit', 50)->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_pagu_anggarans')) {
            Schema::create('bos_pagu_anggarans', function (Blueprint $table) {
                $table->id();
                $table->integer('year');
                $table->decimal('amount', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function test_full_utility_integrated_lifecycle(): void
    {
        // 1. Setup Master Utility Profile: 1 Budgeted (SMK), 1 Non-Budgeted (SMP PGRI)
        $profileSmk = UtilityProfile::create([
            'category' => 'listrik_pra',
            'name' => 'Listrik Laboratorium Komputer',
            'customer_name' => 'SMK PGRI KRAS LAB',
            'meter_number' => '513012345678',
            'customer_number' => '513012345678',
            'tariff_power' => 'R1M / 1300 VA',
            'payment_method' => 'Indomaret / Transfer',
            'default_reimburse_guru_id' => $this->guruReimburse->id,
            'is_budgeted' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $profileSmp = UtilityProfile::create([
            'category' => 'listrik_pasca',
            'name' => 'Listrik Gedung SMP PGRI',
            'customer_name' => 'SMP PGRI KRAS',
            'customer_number' => '513099999999',
            'tariff_power' => 'B1 / 2200 VA',
            'payment_method' => 'Transfer Bank',
            'default_reimburse_guru_id' => $this->guruReimburse->id,
            'is_budgeted' => false, // Special Rule: Non-Anggaran BOS
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->assertTrue((bool) $profileSmk->is_budgeted);
        $this->assertFalse((bool) $profileSmp->is_budgeted);

        // 2. RKAS Planning: Pembuatan pengajuan utilitas 12 bulan rutin
        $currentYear = (int) date('Y');
        $rkasResponse = $this->post(route('bos.rkas.store'), [
            'tipe_pengajuan' => 'utilitas',
            'utility_profile_id' => $profileSmk->id,
            'nama_barang' => $profileSmk->name,
            'spesifikasi' => 'R1M / 1300 VA (ID: 513012345678)',
            'kegiatan' => 'Pembayaran Daya dan Jasa Listrik',
            'karakter' => 'berulang',
            'harga' => 250000,
            'satuan' => 'Bulan',
            'volume' => 1,
            'months' => [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
            ],
            'tahun_anggaran' => $currentYear,
        ]);

        $rkasResponse->assertSessionHasNoErrors();

        $createdRkasItems = PengajuanBarang::where('utility_profile_id', $profileSmk->id)
            ->where('tahun_anggaran', $currentYear)
            ->get();
        $this->assertCount(12, $createdRkasItems);

        // 3. Pemaduan Kertas Kerja: Hubungkan RKAS dengan Kertas Kerja
        $kkFile = BosKertasKerjaFile::firstOrCreate(
            ['year' => $currentYear, 'is_active' => true],
            ['filename' => 'kertas_kerja_test.xlsx', 'original_filename' => 'kertas_kerja_test.xlsx', 'file_path' => 'dummy']
        );

        $kkItemJanuari = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => $currentYear,
            'month' => 'Januari',
            'account_code' => '5.1.02.01.01.0024',
            'kegiatan' => 'Pembayaran Daya dan Jasa Listrik',
            'description' => 'Langganan Daya dan Jasa Listrik Bulan Januari',
            'volume' => 1,
            'unit' => 'Bulan',
            'price' => 250000,
            'total_amount' => 250000,
        ]);

        $rkasJanuari = $createdRkasItems->firstWhere('bulan_pakai', 'Januari');

        $pairResponse = $this->postJson(route('bos.pemaduan.pair'), [
            'pengajuan_barang_id' => $rkasJanuari->id,
            'bos_kertas_kerja_item_id' => $kkItemJanuari->id,
        ]);
        $pairResponse->assertStatus(200);

        $kkItemJanuari->refresh();
        $this->assertEquals($profileSmk->id, $kkItemJanuari->utility_profile_id);

        // 4. Input Struk Pembayaran Utilitas
        // a. Struk Utilitas SMK (Budgeted -> Terhubung ke Kertas Kerja)
        $receiptSmk = UtilityReceipt::create([
            'utility_profile_id' => $profileSmk->id,
            'bos_kertas_kerja_item_id' => $kkItemJanuari->id,
            'fiscal_year' => $currentYear,
            'billing_month' => 'Januari',
            'transaction_date' => $currentYear.'-01-15 10:30:00',
            'total_amount' => 250320, // Riil struk dengan admin fee/pecahan
            'base_amount' => 247320,
            'admin_fee' => 3000,
            'meter_number' => '513012345678',
            'customer_number' => '513012345678',
            'customer_name' => 'SMK PGRI KRAS LAB',
            'receipt_number' => 'TRX-PLN-001',
            'is_paid' => false,
        ]);

        // b. Struk Utilitas SMP PGRI (Non-Budgeted -> Tidak boleh masuk kertas kerja)
        $receiptSmp = UtilityReceipt::create([
            'utility_profile_id' => $profileSmp->id,
            'bos_kertas_kerja_item_id' => null, // Forced null
            'fiscal_year' => $currentYear,
            'billing_month' => 'Januari',
            'transaction_date' => $currentYear.'-01-16 11:00:00',
            'total_amount' => 150000,
            'base_amount' => 147500,
            'admin_fee' => 2500,
            'customer_number' => '513099999999',
            'customer_name' => 'SMP PGRI KRAS',
            'receipt_number' => 'TRX-PLN-SMP-001',
            'is_paid' => false,
        ]);

        $this->assertNotNull($receiptSmk->bos_kertas_kerja_item_id);
        $this->assertNull($receiptSmp->bos_kertas_kerja_item_id);

        // 5. API Unpaid Utility Receipts
        $apiResponse = $this->get(route('bos.api.unpaid-utility-receipts', ['year' => $currentYear]));
        $apiResponse->assertStatus(200);
        $unpaidJson = $apiResponse->json();

        $this->assertNotEmpty($unpaidJson);
        $foundSmk = collect($unpaidJson)->firstWhere('id', $receiptSmk->id);
        $foundSmp = collect($unpaidJson)->firstWhere('id', $receiptSmp->id);

        $this->assertNotNull($foundSmk);
        $this->assertTrue((bool) $foundSmk['is_budgeted']);
        $this->assertTrue((bool) $foundSmk['has_kertas_kerja']);
        $this->assertEquals('GURU PEMBAYAR LISTRIK', $foundSmk['recipient_name']);

        $this->assertNotNull($foundSmp);
        $this->assertFalse((bool) $foundSmp['is_budgeted']);

        // 6. Buat Surat TNT (Pencairan Bank) yang memuat reimburse kedua struk tersebut
        // Pengguna membulatkan nominal SMK dari 250.320 menjadi 250.000
        $tntStoreResponse = $this->post(route('bos.pencairan-bank.store'), [
            'letter_number' => '400.3.8.7/TEST-TNT/'.date('Y'),
            'letter_date' => date('Y-m-d'),
            'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR',
            'bank_branch' => 'CABANG KEDIRI',
            'bank_address' => 'Jl. PK. Bangsa No. 122 Kediri',
            'source_account_number' => '1001003336',
            'source_account_name' => 'SMKS PGRI KRAS',
            'kepala_sekolah_id' => $this->bendahara->id,
            'bendahara_id' => $this->bendahara->id,
            'items' => [
                [
                    'type' => 'utilitas',
                    'utility_receipt_id' => $receiptSmk->id,
                    'recipient_name' => 'GURU PEMBAYAR LISTRIK',
                    'bank_name' => 'Bank Jatim',
                    'account_number' => '0233445566',
                    'amount' => 250000, // Dibulatkan
                    'description' => 'Reimburse Listrik Lab SMK Januari',
                ],
                [
                    'type' => 'utilitas',
                    'utility_receipt_id' => $receiptSmp->id,
                    'recipient_name' => 'GURU PEMBAYAR LISTRIK',
                    'bank_name' => 'Bank Jatim',
                    'account_number' => '0233445566',
                    'amount' => 150000,
                    'description' => 'Reimburse Listrik SMP PGRI Januari',
                ],
            ],
        ]);

        $tntStoreResponse->assertSessionHasNoErrors();
        $pencairanBank = PencairanBank::where('letter_number', 'like', '%TEST-TNT%')->first();
        $this->assertNotNull($pencairanBank);

        $receiptSmk->refresh();
        $receiptSmp->refresh();
        $this->assertNotNull($receiptSmk->pencairan_bank_item_id);
        $this->assertNotNull($receiptSmp->pencairan_bank_item_id);

        // 7. Cairkan Surat TNT (confirmCair)
        $cairDate = $currentYear.'-01-20';
        $bkuNo = 'BKU-001/2026';
        $cairResponse = $this->post(route('bos.pencairan-bank.cairkan', $pencairanBank->id), [
            'cair_date' => $cairDate,
            'bku_number' => $bkuNo,
        ]);
        $cairResponse->assertStatus(200);

        $pencairanBank->refresh();
        $this->assertEquals('dicairkan', $pencairanBank->status);

        $receiptSmk->refresh();
        $receiptSmp->refresh();

        // Keduanya ditandai sudah dibayar/reimburse di TNT
        $this->assertTrue((bool) $receiptSmk->is_paid);
        $this->assertTrue((bool) $receiptSmp->is_paid);
        $this->assertEquals($cairDate, $receiptSmk->payment_date->format('Y-m-d'));

        // 8. Verifikasi Realisasi BKU (BosRealisasi)
        // a. Tagihan SMK (budgeted & terhubung KK) HARUS otomatis membuat realisasi di BKU
        $realisasiSmk = BosRealisasi::where('utility_receipt_id', $receiptSmk->id)->first();
        $this->assertNotNull($realisasiSmk, 'BosRealisasi untuk struk utilitas SMK yang terhubung kertas kerja harus terbuat.');
        $this->assertEquals($kkItemJanuari->id, $realisasiSmk->bos_kertas_kerja_item_id);
        $this->assertEquals(250000, $realisasiSmk->total_amount, 'Nominal realisasi harus sesuai nominal TNT yang disesuaikan');
        $this->assertEquals($bkuNo, $realisasiSmk->no_transaksi);
        $this->assertStringContainsString('Realisasi otomatis Tagihan Utilitas', $realisasiSmk->notes);

        // b. Tagihan SMP PGRI (non-budgeted) TIDAK BOLEH dibuatkan realisasi BKU
        $realisasiSmp = BosRealisasi::where('utility_receipt_id', $receiptSmp->id)->first();
        $this->assertNull($realisasiSmp, 'Tagihan SMP PGRI (Non-Anggaran) tidak boleh menghasilkan rekaman BosRealisasi');

        // 9. Hapus Surat TNT (destroy)
        $deleteResponse = $this->delete(route('bos.pencairan-bank.destroy', $pencairanBank->id));
        $deleteResponse->assertStatus(200);

        // Verifikasi receipt status direset dan BosRealisasi dihapus
        $receiptSmk->refresh();
        $this->assertNull($receiptSmk->pencairan_bank_item_id);
        $this->assertFalse((bool) $receiptSmk->is_paid);
        $this->assertNull(BosRealisasi::where('utility_receipt_id', $receiptSmk->id)->first());
    }

    public function test_kertas_kerja_items_filtered_to_allowed_utility_activities_only(): void
    {
        $year = (int) date('Y');
        $kkFile = BosKertasKerjaFile::firstOrCreate(
            ['year' => $year, 'is_active' => true],
            ['filename' => 'kertas_kerja_filter_test.xlsx', 'file_path' => 'dummy']
        );

        // 1. Kegiatan yang DIIZINKAN (3 kegiatan utilitas BOS)
        $itemListrik = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => $year,
            'month' => 'Januari',
            'program_code' => '07.06.01.',
            'kegiatan' => 'Pembayaran daya listrik',
            'description' => 'Listrik R-1/TR 1300 VA',
            'price' => 250000,
        ]);

        $itemTelepon = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => $year,
            'month' => 'Januari',
            'program_code' => '07.06.04.',
            'kegiatan' => 'Pembayaran biaya telepon',
            'description' => 'Abonemen Telepon',
            'price' => 150000,
        ]);

        $itemInternet = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => $year,
            'month' => 'Januari',
            'program_code' => '07.06.05.',
            'kegiatan' => 'Pembayaran jasa internet',
            'description' => 'Internet Dedicated FO',
            'price' => 500000,
        ]);

        // 2. Kegiatan yang TIDAK DIIZINKAN (non-utilitas)
        $itemPrinter = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => $year,
            'month' => 'Januari',
            'program_code' => '09.05.04.',
            'kegiatan' => 'Pengadaan Printer',
            'description' => 'Printer Epson L3210',
            'price' => 2500000,
        ]);

        $itemHonor = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => $year,
            'month' => 'Januari',
            'program_code' => '12.07.01.',
            'kegiatan' => 'Pembayaran honor Guru/Pendidik',
            'description' => 'Honor Guru Bulan Januari',
            'price' => 3000000,
        ]);

        // 3. Akses halaman index utilitas
        $response = $this->get(route('bos.utilitas.index'));
        $response->assertStatus(200);

        $viewKertasKerja = $response->viewData('kertasKerjaItems');
        $this->assertNotNull($viewKertasKerja);

        $kkiIds = $viewKertasKerja->pluck('id')->all();

        // Verifikasi yang diizinkan masuk
        $this->assertContains($itemListrik->id, $kkiIds);
        $this->assertContains($itemTelepon->id, $kkiIds);
        $this->assertContains($itemInternet->id, $kkiIds);

        // Verifikasi yang tidak diizinkan TIDAK MASUK
        $this->assertNotContains($itemPrinter->id, $kkiIds);
        $this->assertNotContains($itemHonor->id, $kkiIds);
    }
}
