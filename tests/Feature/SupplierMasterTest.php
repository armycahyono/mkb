<?php

namespace Tests\Feature;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierMasterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type', 20)->default('offline');
                $table->string('marketplace_name', 100)->nullable();
                $table->string('pic_name', 150)->nullable();
                $table->string('category', 50)->default('lainnya');
                $table->string('npwp', 30)->nullable();
                $table->string('nik', 20)->nullable();
                $table->string('status_pkp', 20)->default('non_pkp');
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 50)->nullable();
                $table->string('account_holder', 150)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 100)->nullable();
                $table->text('address')->nullable();
                $table->string('city', 100)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('supplier_bank_accounts')) {
            Schema::create('supplier_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_id');
                $table->string('bank_name', 100);
                $table->string('account_number', 50);
                $table->string('account_holder', 150)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanjas')) {
            Schema::create('bos_rencana_belanjas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('no_nota', 50)->nullable();
                $table->integer('year')->nullable();
                $table->string('month', 50)->nullable();
                $table->string('title', 255)->nullable();
                $table->string('supplier_name', 255)->nullable();
                $table->date('planned_date')->nullable();
                $table->string('status', 50)->default('draft');
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 50)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanja_items')) {
            Schema::create('bos_rencana_belanja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_rencana_belanja_id');
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->float('volume_plan')->default(0);
                $table->string('unit', 50)->nullable();
                $table->float('price')->default(0);
                $table->float('total_amount')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_files')) {
            Schema::create('bos_kertas_kerja_files', function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('file_path');
                $table->integer('version')->default(1);
                $table->boolean('is_active')->default(false);
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_items')) {
            Schema::create('bos_kertas_kerja_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_file_id');
                $table->integer('year');
                $table->string('month');
                $table->string('account_code');
                $table->string('kegiatan')->nullable();
                $table->string('description');
                $table->string('unit')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('volume', 15, 2)->default(0);
                $table->decimal('total_volume', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_realisasies')) {
            Schema::create('bos_realisasies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->unsignedBigInteger('bos_rencana_belanja_id')->nullable();
                $table->unsignedBigInteger('perjadin_id')->nullable();
                $table->string('no_transaksi', 50)->nullable();
                $table->string('no_nota', 50)->nullable();
                $table->integer('year')->nullable();
                $table->string('month', 50)->nullable();
                $table->date('transaction_date')->nullable();
                $table->date('receipt_date')->nullable();
                $table->float('volume')->default(0);
                $table->string('unit', 50)->nullable();
                $table->float('price')->default(0);
                $table->float('total_amount')->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    private function authenticateUser(): User
    {
        $user = new User([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->id = 1;
        $this->actingAs($user);

        return $user;
    }

    public function test_can_render_supplier_index_page_for_authenticated_user(): void
    {
        $this->authenticateUser();

        $response = $this->get(route('supplier.index'));
        $response->assertStatus(200);
        $response->assertSee('Daftar Supplier / Rekanan');
        $response->assertSee('Tambah Supplier Baru');
    }

    public function test_can_fetch_supplier_datatable_ajax(): void
    {
        $this->authenticateUser();

        Supplier::create([
            'name' => 'Toko Gramedia Test',
            'type' => 'offline',
            'category' => 'atk_fotokopi',
            'status_pkp' => 'pkp',
            'npwp' => '01.234.567.8-123.000',
            'bank_name' => 'Bank Jatim',
            'account_number' => '9988776655',
            'is_active' => true,
        ]);

        Supplier::create([
            'name' => 'CV. DINAR KARYA SOLUSI',
            'type' => 'online',
            'marketplace_name' => 'Siplah Toko Ladang',
            'pic_name' => 'HENDY WIDIARTO',
            'category' => 'lainnya',
            'npwp' => '0285633558655000',
            'status_pkp' => 'non_pkp',
            'bank_name' => 'Bank Jatim',
            'account_number' => null,
            'account_holder' => 'PT. LADANG KARYA HUSADA',
            'phone' => '082338097994',
            'address' => "Jl. Sumbawa Gang 01 RT/RW 004/002 Baba'an, Tugerejo, Ngasem Kab. Kediri",
            'city' => 'Kediri',
            'is_active' => true,
        ]);

        $response = $this->getJson(route('supplier.index'), ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_can_store_supplier_with_bank_and_tax_info(): void
    {
        $this->authenticateUser();

        $data = [
            'name' => 'CV. Graha Perkasa Mandiri',
            'pic_name' => 'Bambang Sudibyo',
            'category' => 'sarpras_bangunan',
            'status_pkp' => 'pkp',
            'npwp' => '31.444.555.6-777.000',
            'nik' => '3506123456780001',
            'bank_name' => 'Bank Jatim',
            'account_number' => '1002233445',
            'account_holder' => 'CV. Graha Perkasa Mandiri',
            'phone' => '08123456789',
            'email' => 'graha@example.com',
            'address' => 'Jl. Pahlawan No. 45',
            'city' => 'Kediri',
            'notes' => 'Mitra bangunan langganan',
            'is_active' => 1,
        ];

        $response = $this->postJson(route('supplier.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('suppliers', [
            'name' => 'CV. Graha Perkasa Mandiri',
            'account_number' => '1002233445',
            'bank_name' => 'Bank Jatim',
        ]);
    }

    public function test_can_update_and_toggle_status_supplier(): void
    {
        $this->authenticateUser();

        $supplier = Supplier::create([
            'name' => 'Toko Mitra Lama',
            'category' => 'lainnya',
            'status_pkp' => 'non_pkp',
            'is_active' => true,
        ]);

        // Toggle status to false
        $toggleRes = $this->postJson(route('supplier.toggle-status', $supplier->id));
        $toggleRes->assertStatus(200);
        $toggleRes->assertJson(['success' => true, 'is_active' => false]);
        $this->assertFalse($supplier->fresh()->is_active);

        // Update name
        $updateRes = $this->putJson(route('supplier.update', $supplier->id), [
            'name' => 'Toko Mitra Baru Updated',
            'category' => 'atk_fotokopi',
            'status_pkp' => 'pkp',
            'is_active' => 1,
        ]);
        $updateRes->assertStatus(200);
        $this->assertEquals('Toko Mitra Baru Updated', $supplier->fresh()->name);
    }

    public function test_can_search_supplier_via_api(): void
    {
        $this->authenticateUser();

        Supplier::create([
            'name' => 'Toko Alat Tulis Pelita Harapan',
            'category' => 'atk_fotokopi',
            'status_pkp' => 'non_pkp',
            'bank_name' => 'BRI',
            'account_number' => '01234567890123',
            'is_active' => true,
        ]);

        $response = $this->getJson(route('api.suppliers.search', ['q' => 'Pelita']));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals('Toko Alat Tulis Pelita Harapan', $data[0]['name']);
        $this->assertEquals('01234567890123', $data[0]['account_number']);
    }

    public function test_panduan_belanja_can_create_nota_linked_to_supplier(): void
    {
        $user = $this->authenticateUser();

        $supplier = Supplier::create([
            'name' => 'UD. Sentosa Berkah',
            'category' => 'sarpras_bangunan',
            'bank_name' => 'Bank Jatim',
            'account_number' => '1009988776',
            'status_pkp' => 'non_pkp',
            'is_active' => true,
        ]);

        $file = BosKertasKerjaFile::create([
            'filename' => 'test_kertas_kerja.pdf',
            'file_path' => 'bos/test_kertas_kerja.pdf',
            'version' => 1,
            'is_active' => true,
            'uploaded_by' => $user->id,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'September',
            'account_code' => '5.1.02.01.01.0024',
            'kegiatan' => 'Pemeliharaan Sarana Sekolah',
            'description' => 'Semen Gresik 40kg',
            'unit' => 'Zak',
            'price' => 65000,
            'volume' => 10,
            'total_volume' => 10,
            'total_amount' => 650000,
        ]);

        $payload = [
            'year' => 2026,
            'month' => 'September',
            'title' => 'Belanja Semen Perbaikan Kelas',
            'supplier_id' => $supplier->id,
            'planned_date' => '2026-09-08',
            'items' => [
                [
                    'bos_kertas_kerja_item_id' => $kkItem->id,
                    'volume_plan' => 5,
                    'price' => 65000,
                ],
            ],
        ];

        $response = $this->post(route('bos.panduan.store-group'), $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('bos_rencana_belanjas', [
            'supplier_id' => $supplier->id,
            'supplier_name' => 'UD. Sentosa Berkah',
            'bank_name' => 'Bank Jatim',
            'account_number' => '1009988776',
            'title' => 'Belanja Semen Perbaikan Kelas',
        ]);
    }

    public function test_can_store_online_supplier_with_marketplace_toko_ladang(): void
    {
        $this->authenticateUser();

        $data = [
            'name' => 'Toko Mitra Edukasi Online',
            'type' => 'online',
            'marketplace_name' => 'Siplah Toko Ladang',
            'category' => 'buku_media',
            'status_pkp' => 'non_pkp',
            'bank_name' => 'Bank Jatim',
            'is_active' => 1,
        ];

        $response = $this->postJson(route('supplier.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Toko Mitra Edukasi Online',
            'type' => 'online',
            'marketplace_name' => 'Siplah Toko Ladang',
            'account_number' => null,
            'account_holder' => 'PT. LADANG KARYA HUSADA',
        ]);
    }

    public function test_unpaid_rencana_notas_formats_recipient_to_marketplace_and_uses_virtual_account(): void
    {
        $user = $this->authenticateUser();

        $onlineSupplier = Supplier::create([
            'name' => 'Toko CV Sumber Belajar',
            'type' => 'online',
            'marketplace_name' => 'Toko Ladang',
            'category' => 'buku_media',
            'bank_name' => 'Bank Jatim',
            'account_number' => '1009998887',
            'is_active' => true,
        ]);

        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_online.pdf',
            'file_path' => 'bos/kertas_kerja_online.pdf',
            'version' => 1,
            'is_active' => true,
            'uploaded_by' => $user->id,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'September',
            'account_code' => '5.1.02.01.01.0025',
            'kegiatan' => 'Pengadaan Buku Pelajaran',
            'description' => 'Buku Siswa Informatika Kelas X',
            'unit' => 'Eksemplar',
            'price' => 85000,
            'volume' => 20,
            'total_volume' => 20,
            'total_amount' => 1700000,
        ]);

        // Simpan rencana nota belanja dengan input Virtual Account khusus pesanan
        $payload = [
            'year' => 2026,
            'month' => 'September',
            'title' => 'Pengadaan Buku Toko Ladang',
            'supplier_id' => $onlineSupplier->id,
            'account_number' => '1006640440', // Nomor VA Invoice Toko Ladang
            'planned_date' => '2026-09-08',
            'items' => [
                [
                    'bos_kertas_kerja_item_id' => $kkItem->id,
                    'volume_plan' => 10,
                    'price' => 85000,
                ],
            ],
        ];

        $storeRes = $this->post(route('bos.panduan.store-group'), $payload);
        $storeRes->assertStatus(200);

        $this->assertDatabaseHas('bos_rencana_belanjas', [
            'supplier_id' => $onlineSupplier->id,
            'account_number' => '1006640440',
        ]);

        // Request unpaid rencana notas endpoint untuk Surat TNT
        $tntRes = $this->getJson(route('bos.api.unpaid-rencana-notas'));
        $tntRes->assertStatus(200);

        $data = $tntRes->json();
        $matched = collect($data)->firstWhere('account_number', '1006640440');

        $this->assertNotNull($matched);
        $this->assertTrue($matched['is_online']);
        $this->assertEquals('Toko Ladang', $matched['marketplace_name']);
        $this->assertEquals('PT. LADANG KARYA HUSADA', $matched['recipient_name']);
        $this->assertEquals('1006640440', $matched['account_number']);
    }

    public function test_can_store_offline_supplier_with_multiple_bank_accounts(): void
    {
        $this->authenticateUser();

        $data = [
            'name' => 'Toko Bangunan Sumber Makmur',
            'type' => 'offline',
            'category' => 'sarpras_bangunan',
            'status_pkp' => 'non_pkp',
            'bank_accounts' => [
                [
                    'bank_name' => 'Bank Jatim',
                    'account_number' => '1001122334',
                    'account_holder' => 'Toko Sumber Makmur',
                    'is_primary' => '1',
                ],
                [
                    'bank_name' => 'BRI',
                    'account_number' => '0012345678901',
                    'account_holder' => 'Toko Sumber Makmur',
                    'is_primary' => '0',
                ],
            ],
            'is_active' => 1,
        ];

        $response = $this->postJson(route('supplier.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $supplier = Supplier::where('name', 'Toko Bangunan Sumber Makmur')->first();
        $this->assertNotNull($supplier);
        $this->assertCount(2, $supplier->bankAccounts);
        $this->assertEquals('Bank Jatim', $supplier->bank_name);
        $this->assertEquals('1001122334', $supplier->account_number);
        $this->assertEquals('Toko Sumber Makmur', $supplier->account_holder);
        $this->assertEquals('Toko Sumber Makmur', $supplier->bankAccounts->first()->account_holder);

        // Test Update rekening offline dengan nama pemilik baru
        $updateData = [
            'name' => 'Toko Bangunan Sumber Makmur',
            'type' => 'offline',
            'category' => 'sarpras_bangunan',
            'status_pkp' => 'non_pkp',
            'bank_accounts' => [
                [
                    'bank_name' => 'Bank Jatim',
                    'account_number' => '1001122334',
                    'account_holder' => 'H. Ahmad Subandi',
                    'is_primary' => '1',
                ],
                [
                    'bank_name' => 'BRI',
                    'account_number' => '0012345678901',
                    'account_holder' => 'H. Ahmad Subandi',
                    'is_primary' => '0',
                ],
            ],
            'is_active' => 1,
        ];
        $updateRes = $this->putJson(route('supplier.update', $supplier->id), $updateData);
        $updateRes->assertStatus(200);
        $this->assertEquals('H. Ahmad Subandi', $supplier->fresh()->account_holder);
        $this->assertEquals('H. Ahmad Subandi', $supplier->fresh()->bankAccounts->first()->account_holder);

        // Test API search returns bank accounts
        $searchRes = $this->getJson(route('api.suppliers.search', ['q' => 'Sumber Makmur']));
        $searchRes->assertStatus(200);
        $searchData = $searchRes->json();
        $this->assertNotEmpty($searchData);
        $this->assertCount(2, $searchData[0]['bank_accounts']);
    }

    public function test_unpaid_rencana_notas_formats_recipient_with_account_holder_for_offline_supplier(): void
    {
        $user = $this->authenticateUser();

        $offlineSupplier = Supplier::create([
            'name' => 'CV Abadi Offline',
            'type' => 'offline',
            'account_holder' => 'H. Abadi Santoso',
            'bank_name' => 'Bank Jatim',
            'account_number' => '1005544332',
            'is_active' => true,
        ]);

        $file = BosKertasKerjaFile::create([
            'filename' => 'kertas_kerja_offline.pdf',
            'file_path' => 'bos/kertas_kerja_offline.pdf',
            'version' => 1,
            'is_active' => true,
            'uploaded_by' => $user->id,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $file->id,
            'year' => 2026,
            'month' => 'September',
            'account_code' => '5.1.02.01.01.0026',
            'kegiatan' => 'Belanja ATK Kantor',
            'description' => 'Kertas HVS A4 80gr',
            'unit' => 'Rim',
            'price' => 55000,
            'volume' => 10,
            'total_volume' => 10,
            'total_amount' => 550000,
        ]);

        $payload = [
            'year' => 2026,
            'month' => 'September',
            'title' => 'Pengadaan Kertas HVS',
            'supplier_id' => $offlineSupplier->id,
            'account_number' => '1005544332',
            'planned_date' => '2026-09-08',
            'items' => [
                [
                    'bos_kertas_kerja_item_id' => $kkItem->id,
                    'volume_plan' => 5,
                    'price' => 55000,
                ],
            ],
        ];

        $storeRes = $this->post(route('bos.panduan.store-group'), $payload);
        $storeRes->assertStatus(200);

        $tntRes = $this->getJson(route('bos.api.unpaid-rencana-notas'));
        $tntRes->assertStatus(200);

        $data = $tntRes->json();
        $matched = collect($data)->firstWhere('account_number', '1005544332');

        $this->assertNotNull($matched);
        $this->assertFalse($matched['is_online']);
        // Untuk toko offline nama penerima di TNT otomatis terisi nama pemilik rekening
        $this->assertEquals('H. Abadi Santoso', $matched['recipient_name']);
    }
}
