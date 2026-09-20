<?php

namespace Tests\Feature;

use App\Models\BendaharaNote;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BendaharaNoteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        if (! Schema::hasTable('bendahara_notes')) {
            Schema::create('bendahara_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('tipe', 20)->default('bosp');
                $table->string('title')->nullable();
                $table->text('content');
                $table->string('color', 20)->default('yellow');
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_completed')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bendahara_tasks')) {
            Schema::create('bendahara_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('tipe', 20)->default('bosp');
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('due_date')->nullable();
                $table->time('due_time')->nullable();
                $table->string('priority', 20)->default('normal');
                $table->string('kegiatan_ref')->nullable();
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_guest_cannot_access_bosp_or_bpopp_notes(): void
    {
        $responseBosp = $this->get(route('bos.notes.index'));
        $responseBosp->assertRedirect(route('login'));

        $responseBpopp = $this->get(route('bpopp.notes.index'));
        $responseBpopp->assertRedirect(route('login'));
    }

    public function test_admin_can_access_bosp_and_bpopp_notes(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $responseBosp = $this->actingAs($admin)->get(route('bos.notes.index'));
        $responseBosp->assertOk();
        $responseBosp->assertSee('Catatan BOSP');

        $responseBpopp = $this->actingAs($admin)->get(route('bpopp.notes.index'));
        $responseBpopp->assertOk();
        $responseBpopp->assertSee('Catatan BPOPP');
    }

    public function test_user_can_create_bosp_note(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $payload = [
            'title' => 'Pengingat Belanja ATK',
            'content' => 'Beli kertas A4 10 rim di Toko Barokah besok pagi.',
            'color' => 'yellow',
            'is_pinned' => 1,
        ];

        $response = $this->actingAs($admin)->post(route('bos.notes.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bendahara_notes', [
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Pengingat Belanja ATK',
            'content' => 'Beli kertas A4 10 rim di Toko Barokah besok pagi.',
            'color' => 'yellow',
            'is_pinned' => 1,
            'is_completed' => 0,
        ]);
    }

    public function test_user_can_create_bpopp_note(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $payload = [
            'title' => 'Tenggat SPJ BPOPP',
            'content' => 'Laporan SPJ Bulan September harus dikumpulkan maksimal tanggal 25.',
            'color' => 'red',
            'is_pinned' => 0,
        ];

        $response = $this->actingAs($admin)->post(route('bpopp.notes.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bendahara_notes', [
            'user_id' => $admin->id,
            'tipe' => 'bpopp',
            'title' => 'Tenggat SPJ BPOPP',
            'color' => 'red',
            'is_pinned' => 0,
        ]);
    }

    public function test_user_can_update_note(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $note = BendaharaNote::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Judul Lama',
            'content' => 'Isi catatan lama',
            'color' => 'yellow',
        ]);

        $updatePayload = [
            'title' => 'Judul Baru',
            'content' => 'Isi catatan yang sudah diperbarui',
            'color' => 'green',
            'is_pinned' => 1,
            'is_completed' => 1,
        ];

        $response = $this->actingAs($admin)->put(route('notes.update', $note), $updatePayload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bendahara_notes', [
            'id' => $note->id,
            'title' => 'Judul Baru',
            'content' => 'Isi catatan yang sudah diperbarui',
            'color' => 'green',
            'is_pinned' => 1,
            'is_completed' => 1,
        ]);
    }

    public function test_user_can_toggle_pin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $note = BendaharaNote::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Note to Pin',
            'content' => 'Test content',
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($admin)->patch(route('notes.toggle-pin', $note));
        $response->assertRedirect();

        $this->assertDatabaseHas('bendahara_notes', [
            'id' => $note->id,
            'is_pinned' => 1,
        ]);

        // Toggle again to unpin
        $response2 = $this->actingAs($admin)->patch(route('notes.toggle-pin', $note));
        $response2->assertRedirect();

        $this->assertDatabaseHas('bendahara_notes', [
            'id' => $note->id,
            'is_pinned' => 0,
        ]);
    }

    public function test_user_can_toggle_complete(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $note = BendaharaNote::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Note to Complete',
            'content' => 'Test content',
            'is_completed' => false,
        ]);

        $response = $this->actingAs($admin)->patch(route('notes.toggle-complete', $note));
        $response->assertRedirect();

        $this->assertDatabaseHas('bendahara_notes', [
            'id' => $note->id,
            'is_completed' => 1,
        ]);
    }

    public function test_user_can_delete_note(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $note = BendaharaNote::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Note to Delete',
            'content' => 'Test content',
        ]);

        $response = $this->actingAs($admin)->delete(route('notes.destroy', $note));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('bendahara_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_user_can_filter_and_search_notes(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        BendaharaNote::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Tagihan Listrik PLN',
            'content' => 'ID Pelanggan 54321, bayar sebelum tgl 20',
            'is_pinned' => true,
            'is_completed' => false,
        ]);

        BendaharaNote::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Konsumsi Rapat',
            'content' => 'Snack dan kue kotak',
            'is_pinned' => false,
            'is_completed' => true,
        ]);

        // Search test
        $responseSearch = $this->actingAs($admin)->get(route('bos.notes.index', ['search' => 'Listrik']));
        $responseSearch->assertOk();
        $responseSearch->assertSee('Tagihan Listrik PLN');
        $responseSearch->assertDontSee('Konsumsi Rapat');

        // Filter pinned test
        $responsePinned = $this->actingAs($admin)->get(route('bos.notes.index', ['filter' => 'pinned']));
        $responsePinned->assertOk();
        $responsePinned->assertSee('Tagihan Listrik PLN');
        $responsePinned->assertDontSee('Konsumsi Rapat');

        // Filter completed test
        $responseCompleted = $this->actingAs($admin)->get(route('bos.notes.index', ['filter' => 'completed']));
        $responseCompleted->assertOk();
        $responseCompleted->assertSee('Konsumsi Rapat');
        $responseCompleted->assertDontSee('Tagihan Listrik PLN');
    }
}
