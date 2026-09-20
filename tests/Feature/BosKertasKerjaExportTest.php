<?php

namespace Tests\Feature;

use App\Exports\BosKertasKerjaExport;
use App\Imports\BosKertasKerjaImport;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class BosKertasKerjaExportTest extends TestCase
{
    public function test_export_class_can_be_instantiated_and_queried(): void
    {
        $export = new BosKertasKerjaExport('2025', 'Januari', 'all');
        $this->assertNotEmpty($export->headings());
        $this->assertEquals('Kertas Kerja BOS 2025 Januari', $export->title());
        $this->assertNotNull($export->query());
    }

    public function test_import_class_can_be_instantiated_with_counts(): void
    {
        $import = new BosKertasKerjaImport;
        $counts = $import->getCounts();

        $this->assertArrayHasKey('created', $counts);
        $this->assertArrayHasKey('updated', $counts);
        $this->assertArrayHasKey('skipped', $counts);
    }

    public function test_export_endpoint_returns_download_response(): void
    {
        Excel::fake();

        $response = $this->withoutMiddleware()->get(route('bos.data.export', [
            'year' => '2025',
            'month' => 'Januari',
            'version_filter' => 'all',
        ]));

        Excel::matchByRegex();
        Excel::assertDownloaded('/^Kertas_Kerja_BOS_2025_Januari_.*\.xlsx$/', function (BosKertasKerjaExport $export) {
            return $export->year === '2025' && $export->month === 'Januari' && $export->versionFilter === 'all';
        });
    }
}
