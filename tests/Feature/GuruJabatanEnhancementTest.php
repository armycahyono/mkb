<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\GuruJabatan;
use App\Models\Jabatan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuruJabatanEnhancementTest extends TestCase
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
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            if (! Schema::hasColumn('users', 'guru_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreignId('guru_id')->nullable();
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

    private function getAuthenticatedUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin_test@mkb.sch.id'],
            [
                'name' => 'Admin Tester',
                'password' => bcrypt('password'),
            ]
        );
    }

    public function test_can_render_penugasan_page_with_metrics_and_alerts(): void
    {
        $user = $this->getAuthenticatedUser();

        $response = $this->actingAs($user)->get(route('jabatan.penugasan'));

        $response->assertStatus(200);
        $response->assertViewHas(['gurus', 'jabatans', 'years', 'totalActive', 'expiredList', 'expiringSoonList', 'missingSignatories', 'expiredSignatories']);
    }

    public function test_signatory_missing_and_expired_alert_detection(): void
    {
        $user = $this->getAuthenticatedUser();

        // 1. Jabatan penandatangan yang belum ada pejabatnya sama sekali
        $jabatanKosong = Jabatan::firstOrCreate(
            ['code' => 'BEND_BPOPP_KOSONG'],
            ['name' => 'Bendahara BPOPP Kosong', 'is_signatory' => true]
        );

        // 2. Jabatan penandatangan yang pejabatnya sudah habis masa jabatan (expired)
        $jabatanExpired = Jabatan::firstOrCreate(
            ['code' => 'KEPSEK_EXPIRED'],
            ['name' => 'Kepala Sekolah Expired', 'is_signatory' => true]
        );

        $guru = Guru::create([
            'name' => 'Pejabat Expired M.Pd',
            'gender' => 'L',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        GuruJabatan::create([
            'guru_id' => $guru->id,
            'jabatan_id' => $jabatanExpired->id,
            'sk_number' => 'SK/EXP/KEPSEK/01',
            'start_date' => Carbon::now()->subYears(2)->toDateString(),
            'end_date' => Carbon::now()->subDays(5)->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('jabatan.penugasan'));
        $response->assertStatus(200);

        $missingSignatories = $response->viewData('missingSignatories');
        $expiredSignatories = $response->viewData('expiredSignatories');

        $this->assertTrue($missingSignatories->contains('id', $jabatanKosong->id));
        $this->assertTrue($expiredSignatories->contains(fn ($item) => $item['jabatan']->id === $jabatanExpired->id));
    }

    public function test_can_store_penugasan_with_pdf_file_and_download_it(): void
    {
        Storage::fake('public');
        $user = $this->getAuthenticatedUser();

        $guru = Guru::create([
            'name' => 'Dr. H. Ahmad Dahlan',
            'gender' => 'L',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        $jabatan = Jabatan::firstOrCreate(
            ['code' => 'KEPSEK_TEST'],
            ['name' => 'Kepala Sekolah Uji', 'is_signatory' => true]
        );

        $fakePdf = UploadedFile::fake()->create('SK_Kepsek_2025.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($user)->post(route('guru-jabatan.store'), [
            'guru_id' => $guru->id,
            'jabatan_id' => $jabatan->id,
            'sk_number' => '800/099/SK/2025',
            'sk_date' => '2025-01-02',
            'sk_file' => $fakePdf,
            'period_year' => '2025 - 2029',
            'period_type' => 'multi_tahun',
            'start_date' => '2025-01-01',
            'end_date' => '2029-01-01',
            'is_active' => '1',
            'notes' => 'Periode 4 tahun',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message']);

        $penugasan = GuruJabatan::where('sk_number', '800/099/SK/2025')->first();
        $this->assertNotNull($penugasan);
        $this->assertNotNull($penugasan->sk_file);
        $this->assertEquals('multi_tahun', $penugasan->period_type);
        Storage::disk('public')->assertExists($penugasan->sk_file);

        // Download/serve SK file
        $downloadResponse = $this->actingAs($user)->get(route('guru-jabatan.sk-file', $penugasan->id));
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_replace_active_official_automatically_cuts_off_previous_official(): void
    {
        $user = $this->getAuthenticatedUser();

        $guruLama = Guru::create([
            'name' => 'Siti Aminah S.Pd',
            'gender' => 'P',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        $guruBaru = Guru::create([
            'name' => 'Budi Utomo M.Pd',
            'gender' => 'L',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        $jabatan = Jabatan::firstOrCreate(
            ['code' => 'BEND_BOSP_TEST'],
            ['name' => 'Bendahara BOSP Test', 'is_signatory' => true]
        );

        // 1. Pejabat lama bertugas dari 01-01-2025 s/d 31-12-2025
        $penugasanLama = GuruJabatan::create([
            'guru_id' => $guruLama->id,
            'jabatan_id' => $jabatan->id,
            'sk_number' => 'SK/BEND/01/2025',
            'period_year' => '2025',
            'period_type' => 'tahun_anggaran',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);

        // 2. Pejabat baru mulai 16-08-2025 dengan replace_active = 1
        $response = $this->actingAs($user)->post(route('guru-jabatan.store'), [
            'guru_id' => $guruBaru->id,
            'jabatan_id' => $jabatan->id,
            'sk_number' => 'SK/BEND/02/2025',
            'period_year' => '2025',
            'period_type' => 'tahun_anggaran',
            'start_date' => '2025-08-16',
            'end_date' => '2025-12-31',
            'is_active' => '1',
            'replace_active' => '1',
        ]);

        $response->assertStatus(200);

        // Verifikasi pejabat lama terpotong ke H-1 (15-08-2025) dan status non-aktif
        $penugasanLama->refresh();
        $this->assertEquals('2025-08-15', $penugasanLama->end_date->format('Y-m-d'));
        $this->assertFalse($penugasanLama->is_active);
        $this->assertStringContainsString('Digantikan oleh Budi Utomo M.Pd', $penugasanLama->notes);

        // Verifikasi pejabat baru aktif
        $penugasanBaru = GuruJabatan::where('sk_number', 'SK/BEND/02/2025')->first();
        $this->assertNotNull($penugasanBaru);
        $this->assertTrue($penugasanBaru->is_active);
        $this->assertEquals('2025-08-16', $penugasanBaru->start_date->format('Y-m-d'));
    }

    public function test_expiration_status_accessors_and_scopes(): void
    {
        $guru = Guru::create([
            'name' => 'Guru Status Test',
            'gender' => 'L',
            'employment_status' => 'GTT',
            'is_active' => true,
        ]);

        $jabatan = Jabatan::firstOrCreate(
            ['code' => 'WAKA_TEST'],
            ['name' => 'Waka Humas Test', 'is_signatory' => false]
        );

        // SK Kadaluarsa (sudah lewat 10 hari)
        $expiredItem = GuruJabatan::create([
            'guru_id' => $guru->id,
            'jabatan_id' => $jabatan->id,
            'sk_number' => 'SK/EXP/01',
            'period_year' => '2024/2025',
            'period_type' => 'tahun_pelajaran',
            'start_date' => Carbon::now()->subMonths(12)->toDateString(),
            'end_date' => Carbon::now()->subDays(10)->toDateString(),
            'is_active' => true,
        ]);

        $this->assertEquals('expired', $expiredItem->expiration_status);
        $this->assertLessThan(0, $expiredItem->days_remaining);

        // SK Mendekati kadaluarsa (sisa 15 hari)
        $expiringSoonItem = GuruJabatan::create([
            'guru_id' => $guru->id,
            'jabatan_id' => $jabatan->id,
            'sk_number' => 'SK/SOON/01',
            'period_year' => '2025',
            'period_type' => 'tahun_anggaran',
            'start_date' => Carbon::now()->subMonths(11)->toDateString(),
            'end_date' => Carbon::now()->addDays(15)->toDateString(),
            'is_active' => true,
        ]);

        $this->assertEquals('expiring_soon', $expiringSoonItem->expiration_status);
        $this->assertGreaterThan(0, $expiringSoonItem->days_remaining);

        // Cek scopes
        $this->assertTrue(GuruJabatan::expiredActive()->where('id', $expiredItem->id)->exists());
        $this->assertTrue(GuruJabatan::expiringSoon(30)->where('id', $expiringSoonItem->id)->exists());
    }

    public function test_is_active_is_automatically_calculated_from_dates_without_manual_input(): void
    {
        $user = $this->getAuthenticatedUser();

        $guru = Guru::create([
            'name' => 'Guru Otomatis Aktif',
            'gender' => 'L',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        $jabatan = Jabatan::firstOrCreate(
            ['code' => 'WAKA_KURIKULUM_TEST'],
            ['name' => 'Waka Kurikulum Test', 'is_signatory' => false]
        );

        // 1. Data periode aktif saat ini (tanpa mengirim is_active) -> harus otomatis true
        $this->actingAs($user)->post(route('guru-jabatan.store'), [
            'guru_id' => $guru->id,
            'jabatan_id' => $jabatan->id,
            'sk_number' => 'SK/AUTO/AKTIF/2026',
            'period_year' => '2026/2027',
            'period_type' => 'tahun_pelajaran',
            'start_date' => Carbon::now()->subMonths(1)->toDateString(),
            'end_date' => Carbon::now()->addMonths(11)->toDateString(),
        ])->assertStatus(200);

        $penugasanAktif = GuruJabatan::where('sk_number', 'SK/AUTO/AKTIF/2026')->first();
        $this->assertNotNull($penugasanAktif);
        $this->assertTrue($penugasanAktif->is_active);

        // 2. Data arsip masa lalu (tanpa mengirim is_active) -> harus otomatis false (demisioner)
        $this->actingAs($user)->post(route('guru-jabatan.store'), [
            'guru_id' => $guru->id,
            'jabatan_id' => $jabatan->id,
            'sk_number' => 'SK/AUTO/ARSIP/2022',
            'period_year' => '2022',
            'period_type' => 'tahun_anggaran',
            'start_date' => '2022-01-01',
            'end_date' => '2022-12-31',
        ])->assertStatus(200);

        $penugasanArsip = GuruJabatan::where('sk_number', 'SK/AUTO/ARSIP/2022')->first();
        $this->assertNotNull($penugasanArsip);
        $this->assertFalse($penugasanArsip->is_active);
    }

    public function test_datatables_returns_position_centric_data_defaulting_to_signatories(): void
    {
        $user = $this->getAuthenticatedUser();

        $jabatanSignatory = Jabatan::firstOrCreate(
            ['code' => 'KS_TEST_DT'],
            ['name' => 'Kepala Sekolah DT Test', 'is_signatory' => true]
        );

        $jabatanNonSignatory = Jabatan::firstOrCreate(
            ['code' => 'PEMBINA_DT'],
            ['name' => 'Pembina OSIS DT Test', 'is_signatory' => false]
        );

        $guru = Guru::create([
            'name' => 'Dra. Hj. Fatimah',
            'gender' => 'P',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        GuruJabatan::create([
            'guru_id' => $guru->id,
            'jabatan_id' => $jabatanSignatory->id,
            'sk_number' => 'SK/DT/01',
            'start_date' => Carbon::now()->subMonths(6)->toDateString(),
            'end_date' => Carbon::now()->addMonths(6)->toDateString(),
            'is_active' => true,
        ]);

        // Request default AJAX (scope = signatory)
        $response = $this->actingAs($user)->getJson(route('jabatan.penugasan'), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);

        $codes = collect($json['data'])->pluck('code')->all();
        $this->assertContains('KS_TEST_DT', $codes);
        $this->assertNotContains('PEMBINA_DT', $codes);

        // Check columns structure
        $firstRow = collect($json['data'])->firstWhere('code', 'KS_TEST_DT');
        $this->assertNotNull($firstRow);
        $this->assertArrayHasKey('jabatan_display', $firstRow);
        $this->assertArrayHasKey('pejabat_display', $firstRow);
        $this->assertArrayHasKey('sk_display', $firstRow);
        $this->assertArrayHasKey('masa_tugas_display', $firstRow);
        $this->assertArrayHasKey('status_display', $firstRow);
        $this->assertArrayHasKey('action', $firstRow);
        $this->assertArrayHasKey('history', $firstRow);

        // Verify history contains the appointment
        $history = is_string($firstRow['history']) ? json_decode($firstRow['history'], true) : $firstRow['history'];
        $this->assertNotEmpty($history);
        $this->assertEquals('SK/DT/01', $history[0]['sk_number']);

        // Request with scope = all -> should include non-signatory
        $responseAll = $this->actingAs($user)->getJson(route('jabatan.penugasan', ['scope' => 'all']), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $responseAll->assertStatus(200);
        $allCodes = collect($responseAll->json()['data'])->pluck('code')->all();
        $this->assertContains('KS_TEST_DT', $allCodes);
        $this->assertContains('PEMBINA_DT', $allCodes);
    }

    public function test_renew_mode_extends_official_and_deactivates_predecessor_cleanly(): void
    {
        $user = $this->getAuthenticatedUser();

        $guru = Guru::create([
            'name' => 'H. Suryadi M.Pd',
            'gender' => 'L',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        $jabatan = Jabatan::firstOrCreate(
            ['code' => 'BEND_BOSP_RENEW'],
            ['name' => 'Bendahara BOSP Renew Test', 'is_signatory' => true]
        );

        // SK Periode Pertama (sudah berakhir 31 Des 2024)
        $skLama = GuruJabatan::create([
            'guru_id' => $guru->id,
            'jabatan_id' => $jabatan->id,
            'sk_number' => 'SK/BOSP/2024',
            'period_year' => '2024',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'is_active' => true,
        ]);

        // Lakukan Perpanjangan (Orang Sama, mulai 01 Jan 2025 s/d 31 Des 2025)
        $response = $this->actingAs($user)->post(route('guru-jabatan.store'), [
            'action_mode' => 'renew',
            'guru_id' => $guru->id,
            'jabatan_id' => $jabatan->id,
            'sk_number' => 'SK/BOSP/2025',
            'period_year' => '2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => '1',
        ]);

        $response->assertStatus(200);

        // Verifikasi SK lama otomatis dinonaktifkan (demisioner)
        $skLama->refresh();
        $this->assertFalse($skLama->is_active);

        // Verifikasi SK baru aktif dan tersimpan
        $skBaru = GuruJabatan::where('sk_number', 'SK/BOSP/2025')->first();
        $this->assertNotNull($skBaru);
        $this->assertTrue($skBaru->is_active);
        $this->assertEquals($guru->id, $skBaru->guru_id);
    }

    public function test_can_assign_multiple_gurus_to_team_jabatan_simultaneously(): void
    {
        $user = $this->getAuthenticatedUser();

        $guru1 = Guru::create([
            'name' => 'Anggota Tim Satu S.Pd',
            'gender' => 'L',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        $guru2 = Guru::create([
            'name' => 'Anggota Tim Dua M.Kom',
            'gender' => 'P',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        $guru3 = Guru::create([
            'name' => 'Anggota Tim Tiga S.T',
            'gender' => 'L',
            'employment_status' => 'GTT',
            'is_active' => true,
        ]);

        $teamJabatan = Jabatan::firstOrCreate(
            ['code' => 'TIM_BOSP_MULTI_TEST'],
            ['name' => 'Tim BOSP Multi Test', 'is_signatory' => false]
        );

        $this->assertTrue($teamJabatan->is_team);

        $response = $this->actingAs($user)->post(route('guru-jabatan.store'), [
            'jabatan_id' => $teamJabatan->id,
            'guru_ids' => [$guru1->id, $guru2->id, $guru3->id],
            'sk_number' => 'SK/TIM-BOSP/001/2026',
            'sk_date' => '2026-01-02',
            'period_year' => '2026',
            'period_type' => 'tahun_anggaran',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'action_mode' => 'add_team',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'data']);
        $this->assertStringContainsString('3 anggota', $response->json('message'));

        $activeAssignments = GuruJabatan::where('jabatan_id', $teamJabatan->id)
            ->where('is_active', true)
            ->get();

        $this->assertCount(3, $activeAssignments);
        $this->assertTrue($activeAssignments->pluck('guru_id')->contains($guru1->id));
        $this->assertTrue($activeAssignments->pluck('guru_id')->contains($guru2->id));
        $this->assertTrue($activeAssignments->pluck('guru_id')->contains($guru3->id));
    }

    public function test_can_append_team_member_without_deactivating_existing_members(): void
    {
        $user = $this->getAuthenticatedUser();

        $teamJabatan = Jabatan::firstOrCreate(
            ['code' => 'TIM_BPOPP_APPEND_TEST'],
            ['name' => 'Tim BPOPP Append Test', 'is_signatory' => false]
        );

        $guruLama = Guru::create([
            'name' => 'Anggota Awal S.Pd',
            'gender' => 'L',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        $guruBaru = Guru::create([
            'name' => 'Anggota Tambahan S.Kom',
            'gender' => 'P',
            'employment_status' => 'GTT',
            'is_active' => true,
        ]);

        // Anggota pertama sudah aktif
        $assignmentLama = GuruJabatan::create([
            'guru_id' => $guruLama->id,
            'jabatan_id' => $teamJabatan->id,
            'sk_number' => 'SK/TIM/01',
            'period_year' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        // Tambah anggota kedua dengan action_mode = add_team
        $response = $this->actingAs($user)->post(route('guru-jabatan.store'), [
            'jabatan_id' => $teamJabatan->id,
            'guru_id' => $guruBaru->id,
            'sk_number' => 'SK/TIM/02',
            'period_year' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'action_mode' => 'add_team',
        ]);

        $response->assertStatus(200);

        // Anggota lama harus tetap aktif
        $assignmentLama->refresh();
        $this->assertTrue($assignmentLama->is_active);

        // Anggota baru juga aktif bersamaan
        $assignmentBaru = GuruJabatan::where('jabatan_id', $teamJabatan->id)
            ->where('guru_id', $guruBaru->id)
            ->first();
        $this->assertNotNull($assignmentBaru);
        $this->assertTrue($assignmentBaru->is_active);
    }

    public function test_datatable_pejabat_display_shows_multiple_members_for_team(): void
    {
        $user = $this->getAuthenticatedUser();

        $teamJabatan = Jabatan::firstOrCreate(
            ['code' => 'TIM_DT_TEST'],
            ['name' => 'Tim Penguji DT', 'is_signatory' => false]
        );

        $guru1 = Guru::create([
            'name' => 'Guru Satu DT',
            'gender' => 'L',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        $guru2 = Guru::create([
            'name' => 'Guru Dua DT',
            'gender' => 'P',
            'employment_status' => 'GTY',
            'is_active' => true,
        ]);

        GuruJabatan::create([
            'guru_id' => $guru1->id,
            'jabatan_id' => $teamJabatan->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        GuruJabatan::create([
            'guru_id' => $guru2->id,
            'jabatan_id' => $teamJabatan->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('jabatan.penugasan', ['scope' => 'all']));

        $response->assertStatus(200);
        $data = $response->json('data');

        $row = collect($data)->first(fn ($item) => str_contains($item['jabatan_display'], 'Tim Penguji DT'));
        $this->assertNotNull($row);
        $this->assertStringContainsString('2 Anggota Aktif', $row['pejabat_display']);
        $this->assertStringContainsString('Guru Satu DT', $row['pejabat_display']);
        $this->assertStringContainsString('Guru Dua DT', $row['pejabat_display']);
        $this->assertStringContainsString('2 Anggota Aktif', $row['status_display']);
    }
}
