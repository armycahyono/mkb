<?php

namespace Tests\Feature;

use App\Models\KasCategory;
use App\Models\KasTunai;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KasCategoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        if (! Schema::hasTable('kas_categories')) {
            Schema::create('kas_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 80)->unique();
                $table->string('name', 150);
                $table->string('type', 10)->index();
                $table->string('icon', 50)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('kas_tunais')) {
            Schema::create('kas_tunais', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP')->index();
                $table->date('transaction_date')->index();
                $table->unsignedSmallInteger('year')->nullable();
                $table->unsignedInteger('sequence')->nullable();
                $table->string('transaction_type', 10)->index();
                $table->string('category', 50)->index();
                $table->foreignId('kas_category_id')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->text('description')->nullable();
                $table->string('person_name')->nullable();
                $table->foreignId('guru_id')->nullable();
                $table->foreignId('pencairan_bank_id')->nullable();
                $table->foreignId('bos_rencana_belanja_id')->nullable();
                $table->foreignId('perjadin_item_id')->nullable();
                $table->foreignId('parent_kas_id')->nullable();
                $table->string('panjar_status', 20)->default('none');
                $table->string('evidence_number')->nullable();
                $table->string('receipt_file')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (KasCategory::count() === 0) {
            KasCategory::create([
                'code' => 'tarik_bank',
                'name' => 'Tarik Bank (Droping Kas)',
                'type' => 'masuk',
                'icon' => '🏦',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]);
            KasCategory::create([
                'code' => 'panjar_kerja',
                'name' => 'Kasbon / Panjar Belanja',
                'type' => 'keluar',
                'icon' => '💼',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 2,
            ]);
            KasCategory::create([
                'code' => 'belanja_tunai',
                'name' => 'Belanja Barang / Nota Langsung',
                'type' => 'keluar',
                'icon' => '🛍️',
                'is_system' => false,
                'is_active' => true,
                'sort_order' => 3,
            ]);
        }
    }

    public function test_can_list_kas_categories(): void
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get(route('kas-category.index'));

        $response->assertOk();
        $response->assertSee('Master Kategori Transaksi Kas');
        $response->assertSee('Kas Keluar (Pengeluaran)');
        $response->assertSee('Kas Masuk (Penerimaan)');
    }

    public function test_can_create_custom_category(): void
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->post(route('kas-category.store'), [
            'name' => 'Konsumsi Rapat Pengurus',
            'type' => 'keluar',
            'icon' => '🍵',
            'description' => 'Snack dan makan siang rapat rutin',
            'sort_order' => 15,
        ]);

        $response->assertRedirect(route('kas-category.index'));

        $category = KasCategory::where('code', 'konsumsi_rapat_pengurus')->first();
        $this->assertNotNull($category);
        $this->assertEquals('Konsumsi Rapat Pengurus', $category->name);
        $this->assertEquals('keluar', $category->type);
        $this->assertEquals('🍵', $category->icon);
        $this->assertFalse($category->is_system);
        $this->assertTrue($category->is_active);
    }

    public function test_can_update_category(): void
    {
        $user = User::first() ?? User::factory()->create();

        $category = KasCategory::create([
            'code' => 'test_cat_edit',
            'name' => 'Nama Awal',
            'type' => 'keluar',
            'icon' => '📦',
            'is_system' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('kas-category.update', $category->id), [
            'name' => 'Nama Baru Diperbarui',
            'type' => 'keluar',
            'icon' => '🏷️',
            'description' => 'Deskripsi baru',
            'sort_order' => 88,
        ]);

        $response->assertRedirect(route('kas-category.index'));

        $category->refresh();
        $this->assertEquals('Nama Baru Diperbarui', $category->name);
        $this->assertEquals('🏷️', $category->icon);
        $this->assertEquals(88, $category->sort_order);
    }

    public function test_cannot_delete_system_category(): void
    {
        $user = User::first() ?? User::factory()->create();

        $systemCat = KasCategory::where('code', 'panjar_kerja')->first()
            ?? KasCategory::create([
                'code' => 'panjar_kerja',
                'name' => 'Kasbon / Panjar Belanja',
                'type' => 'keluar',
                'is_system' => true,
                'is_active' => true,
            ]);

        $response = $this->actingAs($user)->deleteJson(route('kas-category.destroy', $systemCat->id));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('kas_categories', ['id' => $systemCat->id]);
    }

    public function test_can_delete_unused_custom_category(): void
    {
        $user = User::first() ?? User::factory()->create();

        $customCat = KasCategory::create([
            'code' => 'kategori_sementara',
            'name' => 'Kategori Sementara',
            'type' => 'masuk',
            'is_system' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('kas-category.destroy', $customCat->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertSoftDeleted('kas_categories', ['id' => $customCat->id]);
    }

    public function test_cannot_delete_category_with_transactions(): void
    {
        $user = User::first() ?? User::factory()->create();

        $customCat = KasCategory::create([
            'code' => 'kategori_berisi_trx',
            'name' => 'Kategori Berisi Trx',
            'type' => 'keluar',
            'is_system' => false,
            'is_active' => true,
        ]);

        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => $customCat->code,
            'kas_category_id' => $customCat->id,
            'amount' => 50000,
            'description' => 'Test transaksi',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('kas-category.destroy', $customCat->id));

        $response->assertStatus(422);
        $this->assertDatabaseHas('kas_categories', ['id' => $customCat->id, 'deleted_at' => null]);
    }

    public function test_can_toggle_category_status(): void
    {
        $user = User::first() ?? User::factory()->create();

        $cat = KasCategory::first();
        $initialStatus = $cat->is_active;

        $response = $this->actingAs($user)->postJson(route('kas-category.toggle-status', $cat->id));

        $response->assertOk();
        $response->assertJson(['success' => true, 'is_active' => ! $initialStatus]);

        $cat->refresh();
        $this->assertEquals(! $initialStatus, $cat->is_active);
    }

    public function test_kas_tunai_creation_auto_links_kas_category_id(): void
    {
        $tarikBankCat = KasCategory::where('code', 'tarik_bank')->first();
        $this->assertNotNull($tarikBankCat);

        $kas = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 500000,
            'description' => 'Droping dari bank',
        ]);

        $this->assertEquals($tarikBankCat->id, $kas->kas_category_id);
        $this->assertEquals($tarikBankCat->name, $kas->category_label);
    }

    public function test_kas_tunai_update_syncs_kas_category_id(): void
    {
        $tarikBankCat = KasCategory::where('code', 'tarik_bank')->first();
        $belanjaCat = KasCategory::where('code', 'belanja_tunai')->first();

        $kas = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 500000,
            'description' => 'Droping dari bank',
        ]);

        $this->assertEquals($tarikBankCat->id, $kas->kas_category_id);

        $kas->update([
            'category' => 'belanja_tunai',
        ]);

        $kas->refresh();
        $this->assertEquals($belanjaCat->id, $kas->kas_category_id);
        $this->assertEquals($belanjaCat->name, $kas->category_label);
    }
}
