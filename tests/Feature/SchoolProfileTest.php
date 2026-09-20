<?php

namespace Tests\Feature;

use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolProfileTest extends TestCase
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

        if (! Schema::hasTable('school_profiles')) {
            Schema::create('school_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('npsn', 20)->nullable();
                $table->text('address')->nullable();
                $table->string('foundation_name')->nullable();
                $table->string('logo_school')->nullable();
                $table->string('logo_foundation')->nullable();
                $table->string('letterhead_landscape')->nullable();
                $table->string('letterhead_portrait')->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->string('established_year', 4)->nullable();
                $table->string('ownership', 50)->nullable();
                $table->string('school_status', 50)->nullable();
                $table->string('sk_pendirian')->nullable();
                $table->date('sk_date')->nullable();
                $table->string('accreditation', 10)->nullable();
                $table->string('accreditation_year', 4)->nullable();
                $table->string('curriculum')->nullable();
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
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    private function getAuthenticatedUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin_school@mkb.sch.id'],
            [
                'name' => 'Admin School',
                'password' => bcrypt('password'),
            ]
        );
    }

    public function test_can_render_school_profile_page(): void
    {
        $user = $this->getAuthenticatedUser();

        $response = $this->actingAs($user)->get(route('school.index'));

        $response->assertStatus(200);
        $response->assertViewIs('master.school-profile.index');
        $response->assertSee('Profil Sekolah');
    }

    public function test_can_update_school_profile_and_redirects_cleanly(): void
    {
        $user = $this->getAuthenticatedUser();

        $response = $this->actingAs($user)->put(route('school.update'), [
            'name' => 'SMK Muhammadiyah Kertonegoro Jember',
            'npsn' => '20554123',
            'email' => 'smk.muh.kerto@example.com',
            'phone' => '0331-123456',
            'address' => 'Jl. Pendidikan No. 10',
        ]);

        $response->assertRedirect(route('school.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('school_profiles', [
            'name' => 'SMK Muhammadiyah Kertonegoro Jember',
            'npsn' => '20554123',
        ]);
    }

    public function test_alias_routes_work_for_school_profile(): void
    {
        $user = $this->getAuthenticatedUser();

        $responseIndex = $this->actingAs($user)->get(route('school-profile.index'));
        $responseIndex->assertStatus(200);

        $responseUpdate = $this->actingAs($user)->put(route('school-profile.update'), [
            'name' => 'SMK Muhammadiyah Updated via Alias',
        ]);
        $responseUpdate->assertRedirect(route('school.index'));
    }

    public function test_can_update_bank_accounts_and_see_standardized_ui_elements(): void
    {
        $user = $this->getAuthenticatedUser();

        $responseUpdate = $this->actingAs($user)->put(route('school.update'), [
            'name' => 'SMKS PGRI KRAS',
            'npsn' => '20511923',
            'bosp_bank_name' => 'BANK JATIM',
            'bosp_account_number' => '1001003336',
            'bosp_account_name' => 'SMKS PGRI KRAS (BOSP)',
            'bpopp_bank_name' => 'BANK JATIM',
            'bpopp_account_number' => '1001003337',
            'bpopp_account_name' => 'SMKS PGRI KRAS (BPOPP)',
        ]);

        $responseUpdate->assertRedirect(route('school.index'));

        $this->assertDatabaseHas('school_profiles', [
            'name' => 'SMKS PGRI KRAS',
            'bosp_bank_name' => 'BANK JATIM',
            'bosp_account_number' => '1001003336',
            'bpopp_account_number' => '1001003337',
        ]);

        $responseIndex = $this->actingAs($user)->get(route('school.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Data Pokok');
        $responseIndex->assertSee('Kas BOSP');
        $responseIndex->assertSee('Kas BPOPP');
        $responseIndex->assertSee('Kop Surat Portrait');
        $responseIndex->assertSee('Kop Surat Landscape');
    }

    public function test_can_upload_and_delete_school_logos_and_letterheads(): void
    {
        Storage::fake('public');
        $user = $this->getAuthenticatedUser();

        $logoSchool = UploadedFile::fake()->image('logo_school.png', 100, 100);
        $letterheadPortrait = UploadedFile::fake()->image('kop_portrait.jpg', 600, 200);

        $response = $this->actingAs($user)->put(route('school.update'), [
            'name' => 'SMKS PGRI KRAS',
            'logo_school' => $logoSchool,
            'letterhead_portrait' => $letterheadPortrait,
        ]);

        $response->assertRedirect(route('school.index'));
        $response->assertSessionHas('success');

        $profile = SchoolProfile::first();
        $this->assertNotNull($profile->logo_school);
        $this->assertNotNull($profile->letterhead_portrait);
        Storage::disk('public')->assertExists($profile->logo_school);
        Storage::disk('public')->assertExists($profile->letterhead_portrait);

        $savedLogo = $profile->logo_school;

        // Now simulate browser submitting the form with empty file inputs
        $responseSecond = $this->actingAs($user)->put(route('school.update'), [
            'name' => 'SMKS PGRI KRAS',
            'logo_school' => null,
        ]);

        $responseSecond->assertRedirect(route('school.index'));
        $profile->refresh();
        $this->assertEquals($savedLogo, $profile->logo_school);

        // Now test explicit removal of logo_school
        $responseRemove = $this->actingAs($user)->put(route('school.update'), [
            'name' => 'SMKS PGRI KRAS',
            'remove_logo_school' => '1',
        ]);
        $responseRemove->assertRedirect(route('school.index'));
        $profile->refresh();
        $this->assertNull($profile->logo_school);
        $this->assertNotNull($profile->letterhead_portrait);
        Storage::disk('public')->assertMissing($savedLogo);
        Storage::disk('public')->assertExists($profile->letterhead_portrait);
    }
}
