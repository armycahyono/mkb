<?php

namespace Tests\Feature;

use App\Models\BendaharaTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BendaharaTaskTest extends TestCase
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

    public function test_guest_cannot_create_task(): void
    {
        $response = $this->post(route('bos.notes.tasks.store'), [
            'title' => 'Tugas bayar tagihan',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_create_bosp_task(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $payload = [
            'title' => 'Bayar Tagihan Internet Iconnet September',
            'description' => 'Selesaikan pembayaran sebelum tanggal 20 via VA Mandiri.',
            'due_date' => Carbon::tomorrow()->toDateString(),
            'due_time' => '10:00',
            'priority' => 'penting',
            'kegiatan_ref' => 'Pembayaran Langganan Daya & Jasa',
        ];

        $response = $this->actingAs($admin)->post(route('bos.notes.tasks.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bendahara_tasks', [
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Bayar Tagihan Internet Iconnet September',
            'priority' => 'penting',
            'is_completed' => 0,
            'kegiatan_ref' => 'Pembayaran Langganan Daya & Jasa',
        ]);
    }

    public function test_user_can_create_bpopp_task_via_ajax(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $payload = [
            'title' => 'Rekap SPJ Perjadin MGMP',
            'priority' => 'mendesak',
        ];

        $response = $this->actingAs($admin)->postJson(route('bpopp.notes.tasks.store'), $payload);
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'task' => [
                'tipe' => 'bpopp',
                'title' => 'Rekap SPJ Perjadin MGMP',
                'priority' => 'mendesak',
                'is_completed' => false,
            ],
        ]);
    }

    public function test_user_can_update_task(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $task = BendaharaTask::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Tugas awal',
            'priority' => 'normal',
            'is_completed' => false,
        ]);

        $response = $this->actingAs($admin)->put(route('notes.tasks.update', $task), [
            'title' => 'Tugas diperbarui',
            'priority' => 'mendesak',
            'due_date' => Carbon::today()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bendahara_tasks', [
            'id' => $task->id,
            'title' => 'Tugas diperbarui',
            'priority' => 'mendesak',
        ]);
    }

    public function test_user_can_toggle_complete_task_via_ajax(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $task = BendaharaTask::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Tugas bayar listrik',
            'due_date' => Carbon::today()->toDateString(),
            'priority' => 'penting',
            'is_completed' => false,
        ]);

        // Toggle to completed
        $response = $this->actingAs($admin)->patchJson(route('notes.tasks.toggle-complete', $task));
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_completed' => true,
        ]);

        $task->refresh();
        $this->assertTrue($task->is_completed);
        $this->assertNotNull($task->completed_at);

        // Toggle back to active
        $responseBack = $this->actingAs($admin)->patchJson(route('notes.tasks.toggle-complete', $task));
        $responseBack->assertOk();
        $responseBack->assertJson([
            'success' => true,
            'is_completed' => false,
        ]);

        $task->refresh();
        $this->assertFalse($task->is_completed);
        $this->assertNull($task->completed_at);
    }

    public function test_user_can_delete_task(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $task = BendaharaTask::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Tugas akan dihapus',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($admin)->delete(route('notes.tasks.destroy', $task));
        $response->assertRedirect();

        $this->assertSoftDeleted('bendahara_tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_task_filters_on_notes_index_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        // Task overdue
        BendaharaTask::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Tugas Lewat Batas Waktu',
            'due_date' => Carbon::yesterday()->toDateString(),
            'is_completed' => false,
        ]);

        // Task today
        BendaharaTask::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Tugas Hari Ini Banget',
            'due_date' => Carbon::today()->toDateString(),
            'is_completed' => false,
        ]);

        // Task completed
        BendaharaTask::create([
            'user_id' => $admin->id,
            'tipe' => 'bosp',
            'title' => 'Tugas Sudah Rampung',
            'is_completed' => true,
        ]);

        // Filter: today
        $resToday = $this->actingAs($admin)->get(route('bos.notes.index', ['task_filter' => 'today']));
        $resToday->assertOk();
        $resToday->assertSee('Tugas Hari Ini Banget');
        $resToday->assertDontSee('Tugas Lewat Batas Waktu');

        // Filter: overdue
        $resOverdue = $this->actingAs($admin)->get(route('bos.notes.index', ['task_filter' => 'overdue']));
        $resOverdue->assertOk();
        $resOverdue->assertSee('Tugas Lewat Batas Waktu');
        $resOverdue->assertDontSee('Tugas Hari Ini Banget');

        // Filter: completed
        $resCompleted = $this->actingAs($admin)->get(route('bos.notes.index', ['task_filter' => 'completed']));
        $resCompleted->assertOk();
        $resCompleted->assertSee('Tugas Sudah Rampung');
        $resCompleted->assertDontSee('Tugas Hari Ini Banget');
    }
}
