<?php

namespace Tests\Feature;

use App\Exports\InstansiTemplateExport;
use App\Imports\InstansiImport;
use App\Models\Instansi;
use App\Models\SchoolProfile;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class InstansiTest extends TestCase
{
    public function test_detect_category_accurately_classifies_names(): void
    {
        // 1. Sekolah
        $this->assertEquals('sekolah', Instansi::detectCategory('SMPN 1 RINGINREJO'));
        $this->assertEquals('sekolah', Instansi::detectCategory('SMK PGRI KRAS KEDIRI'));
        $this->assertEquals('sekolah', Instansi::detectCategory('SMAN 2 KEDIRI'));
        $this->assertEquals('sekolah', Instansi::detectCategory('MTsN 1 Kota Kediri'));
        $this->assertEquals('sekolah', Instansi::detectCategory('Universitas Brawijaya'));
        $this->assertEquals('sekolah', Instansi::detectCategory('Politeknik Negeri Malang'));

        // 2. Dinas / Pemerintahan
        $this->assertEquals('dinas', Instansi::detectCategory('Dinas Pendidikan Kabupaten Kediri'));
        $this->assertEquals('dinas', Instansi::detectCategory('Cabdin Pendidikan Wilayah Kediri'));
        $this->assertEquals('dinas', Instansi::detectCategory('Kecamatan Kras'));
        $this->assertEquals('dinas', Instansi::detectCategory('Puskesmas Kras'));
        $this->assertEquals('dinas', Instansi::detectCategory('Polsek Kras'));

        // 3. DUDI / Industri / Perusahaan
        $this->assertEquals('dudi', Instansi::detectCategory('PT. Telkom Indonesia Tbk'));
        $this->assertEquals('dudi', Instansi::detectCategory('CV. Surya Mandiri'));
        $this->assertEquals('dudi', Instansi::detectCategory('Bengkel Mobil Sentosa'));
        $this->assertEquals('dudi', Instansi::detectCategory('Hotel Grand Surya'));
        $this->assertEquals('dudi', Instansi::detectCategory('Bank Jatim Cabang Kediri'));

        // 4. Lainnya
        $this->assertEquals('lainnya', Instansi::detectCategory('Alun-Alun Kota Kediri'));
        $this->assertEquals('lainnya', Instansi::detectCategory('Masjid Agung'));
    }

    public function test_instansi_model_accessors(): void
    {
        $instansi = new Instansi([
            'name' => 'SMPN 1 RINGINREJO',
            'category' => 'sekolah',
            'address' => 'Jl. Marabunta, Selodono',
            'city' => 'Kediri',
        ]);

        $this->assertEquals('Sekolah / Pendidikan', $instansi->category_label);
        $this->assertStringContainsString('badge-primary', $instansi->category_badge);
        $this->assertStringContainsString('Sekolah', $instansi->category_badge);

        $dudi = new Instansi(['category' => 'dudi']);
        $this->assertEquals('DUDI / Perusahaan', $dudi->category_label);
        $this->assertStringContainsString('badge-warning', $dudi->category_badge);

        $dinas = new Instansi(['category' => 'dinas']);
        $this->assertEquals('Dinas / Pemerintahan', $dinas->category_label);
        $this->assertStringContainsString('badge-info', $dinas->category_badge);

        $lainnya = new Instansi(['category' => 'lainnya']);
        $this->assertEquals('Lainnya', $lainnya->category_label);
        $this->assertStringContainsString('badge-secondary', $lainnya->category_badge);
    }

    public function test_master_instansi_views_render_properly(): void
    {
        View::share('errors', new ViewErrorBag);

        $counts = [
            'total' => 10,
            'sekolah' => 5,
            'dudi' => 3,
            'dinas' => 2,
            'lainnya' => 0,
        ];

        $html = view('master.instansi.index', compact('counts'))->render();

        $this->assertStringContainsString('Daftar Instansi & Mitra', $html);
        $this->assertStringContainsString('instansiTable', $html);
        $this->assertStringContainsString('btnCreateInstansi', $html);
        $this->assertStringContainsString('btnOpenImport', $html);
        $this->assertStringContainsString('importModal', $html);
        $this->assertStringContainsString('importForm', $html);
        $this->assertStringContainsString(route('instansi.template'), $html);
        $this->assertStringContainsString(route('instansi.import'), $html);
        $this->assertStringContainsString('instansiModal', $html);
        $this->assertStringContainsString('badge-filter', $html);
    }

    public function test_perjadin_create_and_edit_render_instansi_datalist(): void
    {
        View::share('errors', new ViewErrorBag);

        $fundingSource = 'BOSP';
        $routePrefix = 'bos';
        $gurus = collect([]);
        $school = new SchoolProfile(['name' => 'SMK PGRI KRAS']);
        $defaultKepsekId = null;
        $defaultBendaharaId = null;
        $defaultAccountCode = '5.1.02.04.01.0003';

        $instansis = collect([
            new Instansi([
                'name' => 'SMPN 1 RINGINREJO',
                'category' => 'sekolah',
                'address' => 'Jl. Marabunta, Selodono',
                'city' => 'Kediri',
            ]),
            new Instansi([
                'name' => 'PT. TELKOM KEDIRI',
                'category' => 'dudi',
                'address' => 'Jl. Hayam Wuruk No. 10',
                'city' => 'Kediri',
            ]),
        ]);

        $createHtml = view('perjadin.create', compact(
            'fundingSource',
            'routePrefix',
            'school',
            'gurus',
            'instansis',
            'defaultKepsekId',
            'defaultBendaharaId',
            'defaultAccountCode'
        ))->render();

        $this->assertStringContainsString('id="instansiSuggestBox"', $createHtml);
        $this->assertStringContainsString('id="destinationName"', $createHtml);
        $this->assertStringContainsString('SMPN 1 RINGINREJO', $createHtml);
        $this->assertStringContainsString('PT. TELKOM KEDIRI', $createHtml);
        $this->assertStringContainsString('destinationAddress', $createHtml);
    }

    public function test_instansi_auto_creation_and_backfill_logic(): void
    {
        // 1. Detection matches school, dudi, dinas
        $destName = 'SMKN 1 KEDIRI';
        $destAddress = 'Jl. Veteran No. 9, Mojoroto, Kediri';
        $detectedCategory = Instansi::detectCategory($destName);

        $instansi = new Instansi([
            'name' => $destName,
            'category' => $detectedCategory,
            'address' => $destAddress,
            'city' => 'Kediri',
        ]);

        $this->assertEquals('SMKN 1 KEDIRI', $instansi->name);
        $this->assertEquals('sekolah', $instansi->category);
        $this->assertEquals($destAddress, $instansi->address);

        // 2. Existing destination without address backfill logic
        $instansiNoAddr = new Instansi([
            'name' => 'PT. GUDANG GARAM TBK',
            'category' => 'dudi',
            'address' => null,
            'city' => 'Kediri',
        ]);

        $newAddress = 'Jl. Semampir II No. 1, Kediri';
        if (empty($instansiNoAddr->address) && ! empty($newAddress)) {
            $instansiNoAddr->address = $newAddress;
        }

        $this->assertEquals($newAddress, $instansiNoAddr->address);
    }

    public function test_instansi_template_export(): void
    {
        $export = new InstansiTemplateExport;
        $headings = $export->headings();
        $this->assertContains('Nama Instansi', $headings);
        $this->assertContains('Kategori', $headings);
        $this->assertContains('Alamat', $headings);
        $this->assertContains('Kota', $headings);

        $rows = $export->array();
        $this->assertNotEmpty($rows);
        $this->assertEquals('SMPN 1 RINGINREJO', $rows[0][0]);
        $this->assertEquals('sekolah', $rows[0][1]);
        $this->assertEquals('Template Instansi', $export->title());
    }

    public function test_instansi_import_resolve_category(): void
    {
        $import = new InstansiImport;

        // Explicit category in excel
        $this->assertEquals('sekolah', $import->resolveCategory('Sekolah', 'SMPN 1'));
        $this->assertEquals('dudi', $import->resolveCategory('DUDI / Industri', 'PT. ABC'));
        $this->assertEquals('dinas', $import->resolveCategory('Pemerintahan / Kantor', 'Kantor Balai'));
        $this->assertEquals('lainnya', $import->resolveCategory('Lainnya', 'Alun-alun'));

        // Empty category fallback to detectCategory
        $this->assertEquals('sekolah', $import->resolveCategory('', 'SMPN 1 KANDAT'));
        $this->assertEquals('dudi', $import->resolveCategory('', 'PT. INDOFOOD'));
        $this->assertEquals('dinas', $import->resolveCategory('', 'DINAS KESEHATAN KEDIRI'));
    }
}
