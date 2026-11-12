<?php

namespace Tests\Feature;

use App\Exports\PencairanBankBatchUploadSheet;
use App\Exports\PencairanBankExport;
use App\Exports\PencairanBankFormalSheet;
use App\Exports\PerjadinTemplateExport;
use App\Helpers\TerbilangHelper;
use App\Http\Controllers\PencairanBankController;
use App\Imports\PerjadinImport;
use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\BosRealisasi;
use App\Models\BosRencanaBelanja;
use App\Models\BosRencanaBelanjaItem;
use App\Models\Guru;
use App\Models\GuruBankAccount;
use App\Models\Instansi;
use App\Models\PencairanBank;
use App\Models\PencairanBankItem;
use App\Models\Perjadin;
use App\Models\PerjadinItem;
use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PerjadinAndPencairanBankTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureDatabaseTables();
    }

    public function test_terbilang_helper(): void
    {
        $this->assertEquals('Enam Puluh Lima Ribu Rupiah', TerbilangHelper::rupiah(65000));
        $this->assertEquals('Dua Puluh Delapan Juta Seratus Tujuh Puluh Empat Ribu Rupiah', TerbilangHelper::rupiah(28174000));
        $this->assertEquals('Lima', TerbilangHelper::make(5));
        $this->assertEquals('Satu Juta Lima Ratus Ribu Rupiah', TerbilangHelper::rupiah(1500000));
    }

    public function test_perjadin_bku_number_generation_and_range(): void
    {
        $perjadin = new Perjadin([
            'bku_number' => 'BPU070 - BPU074 / 2026',
            'departure_date' => '2026-08-25',
        ]);

        $this->assertEquals('BPU070 / 2026', $perjadin->getBkuNumberForItem(0));
        $this->assertEquals('BPU071 / 2026', $perjadin->getBkuNumberForItem(1));
        $this->assertEquals('BPU072 / 2026', $perjadin->getBkuNumberForItem(2));
        $this->assertEquals('BPU073 / 2026', $perjadin->getBkuNumberForItem(3));
        $this->assertEquals('BPU074 / 2026', $perjadin->getBkuNumberForItem(4));

        // Single BKU code with year
        $singlePerjadin = new Perjadin([
            'bku_number' => 'BPU075 / 2026',
            'departure_date' => '2026-08-25',
        ]);
        $this->assertEquals('BPU075 / 2026', $singlePerjadin->getBkuNumberForItem(0));

        // BKU code without year, automatically appends year from departure_date
        $autoYearPerjadin = new Perjadin([
            'bku_number' => 'BPU070 - BPU072',
            'departure_date' => '2026-09-08',
        ]);
        $this->assertEquals('BPU070 / 2026', $autoYearPerjadin->getBkuNumberForItem(0));
        $this->assertEquals('BPU071 / 2026', $autoYearPerjadin->getBkuNumberForItem(1));
        $this->assertEquals('BPU072 / 2026', $autoYearPerjadin->getBkuNumberForItem(2));
    }

    public function test_perjadin_views_render_properly(): void
    {
        View::share('errors', new ViewErrorBag);

        $fundingSource = 'BOSP';
        $routePrefix = 'bos';
        $gurus = collect([]);
        $school = new SchoolProfile([
            'name' => 'SMK PGRI KRAS',
        ]);
        $defaultKepsekId = null;
        $defaultBendaharaId = null;
        $defaultAccountCode = '5.1.02.04.01.0003';

        // 1. Index Perjadin
        $indexHtml = view('perjadin.index', compact('fundingSource', 'routePrefix'))->render();
        $this->assertStringContainsString('Perjalanan Dinas (Perjadin)', $indexHtml);
        $this->assertStringContainsString('perjadinTable', $indexHtml);

        // 2. Create Perjadin
        $createHtml = view('perjadin.create', compact(
            'fundingSource',
            'routePrefix',
            'school',
            'gurus',
            'defaultKepsekId',
            'defaultBendaharaId',
            'defaultAccountCode'
        ))->render();
        $this->assertStringContainsString('Tambah Perjalanan Dinas', $createHtml);
        $this->assertStringContainsString('tablePeserta', $createHtml);
        $this->assertStringContainsString('quickNominal', $createHtml);

        // 3. Index Pencairan Bank (TNT)
        $bankIndexHtml = view('pencairan_bank.index', compact('fundingSource', 'routePrefix'))->render();
        $this->assertStringContainsString('TNT BOSP', $bankIndexHtml);
        $this->assertStringContainsString('bankTable', $bankIndexHtml);

        // 3b. Index Pencairan Bank dengan Notifikasi Perjadin Belum Cair
        $unpaidBankIndexHtml = view('pencairan_bank.index', [
            'fundingSource' => $fundingSource,
            'routePrefix' => $routePrefix,
            'unpaidItemsCount' => 5,
            'unpaidTotalAmount' => 325000,
            'unpaidPerjadinsCount' => 2,
            'unpaidItems' => collect([]),
            'unpaidRencanaCount' => 0,
            'unpaidRencanaTotalAmount' => 0,
            'unpaidRencanaNotas' => collect([]),
        ])->render();
        $this->assertStringContainsString('Ada Perjalanan Dinas yang Belum Masuk Daftar TNT', $unpaidBankIndexHtml);
        $this->assertStringContainsString('325.000', $unpaidBankIndexHtml);
        $this->assertStringContainsString('Buat Surat TNT Sekarang', $unpaidBankIndexHtml);

        // 3c. Index Pencairan Bank dengan Notifikasi Rencana Belanja Belum Masuk TNT
        $unpaidRencanaBankIndexHtml = view('pencairan_bank.index', [
            'fundingSource' => $fundingSource,
            'routePrefix' => $routePrefix,
            'unpaidItemsCount' => 0,
            'unpaidTotalAmount' => 0,
            'unpaidPerjadinsCount' => 0,
            'unpaidItems' => collect([]),
            'unpaidRencanaCount' => 2,
            'unpaidRencanaTotalAmount' => 750000,
            'unpaidRencanaNotas' => collect([]),
        ])->render();
        $this->assertStringContainsString('Ada Rencana Belanja yang Belum Masuk Daftar TNT', $unpaidRencanaBankIndexHtml);
        $this->assertStringContainsString('750.000', $unpaidRencanaBankIndexHtml);

        // 3d. Index Pencairan Bank dengan Notifikasi Keduanya (Perjadin & Rencana Belanja)
        $combinedBankIndexHtml = view('pencairan_bank.index', [
            'fundingSource' => $fundingSource,
            'routePrefix' => $routePrefix,
            'unpaidItemsCount' => 3,
            'unpaidTotalAmount' => 300000,
            'unpaidPerjadinsCount' => 1,
            'unpaidItems' => collect([]),
            'unpaidRencanaCount' => 1,
            'unpaidRencanaTotalAmount' => 500000,
            'unpaidRencanaNotas' => collect([]),
        ])->render();
        $this->assertStringContainsString('Ada Perjalanan Dinas dan Rencana Belanja yang Belum Masuk Daftar TNT', $combinedBankIndexHtml);
        $this->assertStringContainsString('800.000', $combinedBankIndexHtml);

        // 4. Create Pencairan Bank (TNT)
        $bankName = 'PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR';
        $bankBranch = 'CABANG KEDIRI';
        $bankAddress = 'Jl. PK. Bangsa No. 122 Kediri';
        $sourceAccountNum = '1001003336';
        $sourceAccountName = '20511923 SMKS PGRI KRAS KEDIRI (BOSP)';

        $bankCreateHtml = view('pencairan_bank.create', compact(
            'fundingSource',
            'routePrefix',
            'school',
            'gurus',
            'defaultKepsekId',
            'defaultBendaharaId',
            'bankName',
            'bankBranch',
            'bankAddress',
            'sourceAccountNum',
            'sourceAccountName'
        ))->render();
        $this->assertStringContainsString('Buat Surat TNT BOSP', $bankCreateHtml);
        $this->assertStringContainsString('btnPullPerjadin', $bankCreateHtml);
        $this->assertStringContainsString('modalPullPerjadin', $bankCreateHtml);
        $this->assertStringContainsString('btnPullRencanaNota', $bankCreateHtml);
        $this->assertStringContainsString('modalPullRencanaNota', $bankCreateHtml);
    }

    public function test_print_kwitansi_perjadin_renders_matching_spec(): void
    {
        $fundingSource = 'BOSP';
        $school = new SchoolProfile(['name' => 'SMK PGRI KRAS KEDIRI']);

        $perjadin = new Perjadin([
            'funding_source' => 'BOSP',
            'bku_number' => 'BPU149 / 2025',
            'account_code' => '5.1.02.04.01.0003',
            'activity_purpose' => 'Sosialisasi Penerimaan Peserta Didik Baru',
            'destination_name' => 'SMPN 1 RINGINREJO',
            'destination_address' => 'Jl. Marabunta, Ds. Selodono, Kec. Ringinrejo, Kab. Kediri, Prov. Jawa Timur',
            'departure_date' => '2025-05-20',
            'payment_date' => '2025-05-22',
            'city' => 'Kediri',
        ]);
        $perjadin->id = 1;

        $guru = new Guru([
            'name' => 'LEO HERMAWAN SANTOSO',
            'gender' => 'L',
        ]);
        $guru->id = 1;

        $item = new PerjadinItem([
            'amount' => 65000,
            'ppn' => 0,
            'pph23' => 0,
            'net_amount' => 65000,
        ]);
        $item->id = 1;
        $item->setRelation('guru', $guru);
        $item->setRelation('bankAccount', new GuruBankAccount([
            'bank_name' => 'Bank Jatim',
            'account_number' => '1001003336',
            'account_holder' => 'LEO HERMAWAN SANTOSO',
        ]));

        $items = collect([$item]);
        $perjadin->setRelation('items', $items);
        $perjadin->setRelation('kepalaSekolah', new Guru(['name' => 'ENDANG PATMOWATI, S.Pd']));
        $perjadin->setRelation('bendahara', new Guru(['name' => 'ARMY WIDHI CAHYONO, S.Pt']));

        $html = view('perjadin.print_kwitansi', compact('perjadin', 'items', 'school', 'fundingSource'))->render();

        $this->assertStringContainsString('KWITANSI', $html);
        $this->assertStringContainsString('PEMBAYARAN PERJALANAN DINAS', $html);
        $this->assertStringContainsString('BPU149 / 2025', $html);
        $this->assertStringContainsString('5.1.02.04.01.0003', $html);
        $this->assertStringContainsString('LEO HERMAWAN SANTOSO', $html);
        $this->assertStringContainsString('Rp. 65.000', $html);
        $this->assertStringContainsString('Enam Puluh Lima Ribu Rupiah', $html);
        $this->assertStringContainsString('SMPN 1 RINGINREJO', $html);
        $this->assertStringContainsString('ENDANG PATMOWATI', $html);
        $this->assertStringContainsString('ARMY WIDHI CAHYONO', $html);
        $this->assertStringNotContainsString('page-footer-num', $html);
    }

    public function test_print_surat_kuasa_bank_renders_three_pages(): void
    {
        $fundingSource = 'BOSP';
        $school = new SchoolProfile([
            'name' => 'SMK PGRI KRAS',
            'address' => 'JL. RAYA DESA KRAS KEC. KRAS KAB. KEDIRI',
        ]);

        $pencairanBank = new PencairanBank([
            'funding_source' => 'BOSP',
            'letter_number' => '400.3.8.7/083.1/SMK PGRI Kras/2026',
            'letter_date' => '2026-05-07',
            'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR',
            'bank_branch' => 'CABANG KEDIRI',
            'bank_address' => 'Jl. PK. Bangsa No. 122 Kediri',
            'source_account_number' => '1001003336',
            'source_account_name' => '20511923 SMKS PGRI KRAS KEDIRI (BOSP)',
            'total_nominal' => 28174000,
            'total_accounts' => 5,
            'status' => 'draft',
        ]);
        $pencairanBank->id = 1;

        $pencairanBank->setRelation('kepalaSekolah', new Guru([
            'name' => 'ENDANG PATMOWATI, S.Pd',
            'address' => 'Dsn. Nglarangan, Rt 01/03, Ds. Selosari, Kec. Kandat, Kab. Kediri',
        ]));
        $pencairanBank->setRelation('bendahara', new Guru([
            'name' => 'ARMY WIDHI CAHYONO, S.Pt',
            'address' => 'Rt. 03/04, Ds. Banjaranyar, Kec. Kras, Kab. Kediri',
        ]));

        $items = collect([
            new PencairanBankItem([
                'recipient_name' => 'PT. LADANG KARYA HUSADA',
                'account_number' => 'VA. 1029700010171697',
                'amount' => 6000000,
                'description' => 'Map File',
            ]),
            new PencairanBankItem([
                'recipient_name' => 'ALIYA NUSAIBA',
                'account_number' => 'BRI. 627701018988538',
                'amount' => 5674000,
                'description' => 'Listrik',
            ]),
        ]);
        $pencairanBank->setRelation('items', $items);

        $html = view('pencairan_bank.print_surat', compact('pencairanBank', 'school', 'fundingSource'))->render();

        // Halaman 1: Pengantar
        $this->assertStringContainsString('Kuasa Pemindahbukuan', $html);
        $this->assertStringContainsString('Nomor: 400.3.8.7/083.1/SMK PGRI Kras/2026', $html);
        $this->assertStringContainsString('1001003336', $html);

        // Halaman 2: Surat Kuasa (nomor memiliki tambahan .1 jika belum ada desimal)
        $this->assertStringContainsString('SURAT KUASA', $html);
        $this->assertStringContainsString('K H U S U S', $html);
        $this->assertStringContainsString('No. 400.3.8.7/083.1/SMK PGRI Kras/2026', $html);
        $this->assertStringContainsString('MATERAI', $html);
        $this->assertStringContainsString('Dsn. Nglarangan, Rt 01/03, Ds. Selosari', $html);
        $this->assertStringContainsString('Rt. 03/04, Ds. Banjaranyar', $html);

        // Halaman 3: Lampiran Rincian (nomor sama dengan halaman 2)
        $this->assertStringContainsString('DAFTAR SURAT PERINTAH PENCAIRAN DANA', $html);
        $this->assertStringContainsString('YANG TIDAK TERPISAHKAN DARI SURAT KUASA', $html);
        $this->assertStringContainsString('<div>400.3.8.7/083.1/SMK PGRI Kras/2026</div>', $html);
        $this->assertStringContainsString('PT. LADANG KARYA HUSADA', $html);
        $this->assertStringContainsString('VA. 1029700010171697', $html);
        $this->assertStringContainsString('6.000.000', $html);
        $this->assertStringContainsString('ALIYA NUSAIBA', $html);
    }

    public function test_pencairan_bank_letter_numbering_pages_increment_convention(): void
    {
        // Kasus 1: Nomor tanpa desimal (misal 80 -> hal 1: 80, hal 2 & 3: 80.1)
        $p1 = new PencairanBank(['letter_number' => '400.3.8.7/80/smk pgri kras/2026']);
        $this->assertEquals('400.3.8.7/80/smk pgri kras/2026', $p1->letter_number);
        $this->assertEquals('400.3.8.7/80.1/smk pgri kras/2026', $p1->kuasa_letter_number);

        // Kasus 2: Nomor tunggal angka 80
        $p2 = new PencairanBank(['letter_number' => '80']);
        $this->assertEquals('80', $p2->letter_number);
        $this->assertEquals('80.1', $p2->kuasa_letter_number);

        // Kasus 3: Nomor sudah berdesimal .1 (tetap mempertahankan formatnya)
        $p3 = new PencairanBank(['letter_number' => '400.3.8.7/80.1/smk pgri kras/2026']);
        $this->assertEquals('400.3.8.7/80.1/smk pgri kras/2026', $p3->letter_number);
        $this->assertEquals('400.3.8.7/80.1/smk pgri kras/2026', $p3->kuasa_letter_number);

        // View rendering assertion
        $school = new SchoolProfile(['name' => 'SMK PGRI KRAS']);
        $p1->id = 27;
        $p1->total_accounts = 1;
        $p1->total_nominal = 50000;
        $p1->setRelation('items', collect([]));
        $html = view('pencairan_bank.print_surat', [
            'pencairanBank' => $p1,
            'school' => $school,
            'fundingSource' => 'BOSP',
        ])->render();

        // Halaman 1 memiliki nomor surat pengantar (80)
        $this->assertStringContainsString('Nomor: 400.3.8.7/80/smk pgri kras/2026', $html);
        // Halaman 2 memiliki nomor Surat Kuasa (+0.1 menjadi 80.1)
        $this->assertStringContainsString('No. 400.3.8.7/80.1/smk pgri kras/2026', $html);
        // Halaman 3 memiliki nomor Lampiran yang sama dengan Halaman 2 (80.1)
        $this->assertStringContainsString('<div>400.3.8.7/80.1/smk pgri kras/2026</div>', $html);
    }

    public function test_pencairan_bank_excel_export_has_two_sheets(): void
    {
        $pencairanBank = new PencairanBank([
            'funding_source' => 'BOSP',
            'letter_number' => '400.3.8.7/083.1/SMK PGRI Kras/2026',
            'letter_date' => '2026-05-07',
            'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR',
            'bank_branch' => 'CABANG KEDIRI',
            'source_account_number' => '1001003336',
            'source_account_name' => 'SMKS PGRI KRAS',
            'total_nominal' => 100000,
            'total_accounts' => 1,
            'status' => 'draft',
        ]);
        $pencairanBank->id = 1;

        $items = collect([
            new PencairanBankItem([
                'recipient_name' => 'PT. LADANG KARYA HUSADA',
                'account_number' => '1029700010171697',
                'amount' => 100000,
                'description' => 'Belanja ATK',
            ]),
        ]);
        $pencairanBank->setRelation('items', $items);

        $export = new PencairanBankExport($pencairanBank);
        $sheets = $export->sheets();

        $this->assertCount(2, $sheets);
        $this->assertEquals('Format Upload Bank (CMS)', $sheets[0]->title());
        $this->assertEquals('Lampiran Surat Perintah', $sheets[1]->title());
    }

    public function test_perjadin_template_export_sheets(): void
    {
        $export = new PerjadinTemplateExport;
        $sheets = $export->sheets();

        $this->assertCount(3, $sheets);
        $this->assertEquals('Form Input Perjadin', $sheets[0]->title());
        $this->assertEquals('Referensi Guru Terdaftar', $sheets[1]->title());
        $this->assertEquals('Referensi Instansi Terdaftar', $sheets[2]->title());

        $headings = $sheets[0]->headings();
        $this->assertEquals([
            'No',
            'Tanggal',
            'No Surat',
            'Tujuan Dinas',
            'Alamat Tujuan (Opsional)',
            'Maksud Perjalanan',
            'Nama / NIP Guru',
            'Uang Diterima (Rp)',
            'No BKU Awal (Opsional)',
            'PPN (Opsional)',
            'PPh 21/23 (Opsional)',
            'Catatan (Opsional)',
        ], $headings);

        $instansiHeadings = $sheets[2]->headings();
        $this->assertContains('Nama Instansi / Sekolah / DUDI', $instansiHeadings);
        $this->assertContains('Kategori', $instansiHeadings);
    }

    public function test_perjadin_import_bku_and_date_helpers(): void
    {
        // 1. BKU range calculation
        $this->assertEquals('BPU070 - BPU074 / 2026', PerjadinImport::calculateBkuRange('BPU070', 5, '2026'));
        $this->assertEquals('BPU075 - BPU077 / 2026', PerjadinImport::calculateBkuRange('BPU075', 3, '2026'));
        $this->assertEquals('BPU078 / 2026', PerjadinImport::calculateBkuRange('BPU078', 1, '2026'));
        $this->assertEquals('BPU070 - BPU074 / 2026', PerjadinImport::calculateBkuRange('BPU070 - BPU074 / 2026', 5, '2026'));

        // 2. Date parsing (ISO, Slash, Dash, Dot, Indonesian text month, Excel serial)
        $this->assertEquals('2026-05-03', PerjadinImport::parseExcelDate('2026-05-03'));
        $this->assertEquals('2026-05-03', PerjadinImport::parseExcelDate('03/05/2026'));
        $this->assertEquals('2026-05-03', PerjadinImport::parseExcelDate('03-05-2026'));
        $this->assertEquals('2026-05-03', PerjadinImport::parseExcelDate('03.05.2026'));
        $this->assertEquals('2026-05-03', PerjadinImport::parseExcelDate('3 Mei 2026'));
        $this->assertEquals('2026-08-17', PerjadinImport::parseExcelDate('17 Agustus 2026'));

        // 3. Clean numeric
        $this->assertEquals(65000.0, PerjadinImport::cleanNumeric('65.000'));
        $this->assertEquals(75000.0, PerjadinImport::cleanNumeric('Rp 75,000'));
        $this->assertEquals(65000.0, PerjadinImport::cleanNumeric('Rp 65.000,-'));
        $this->assertEquals(65000.0, PerjadinImport::cleanNumeric(65000));
        $this->assertEquals(100000.0, PerjadinImport::cleanNumeric('=IFERROR(XLOOKUP(...), 100000)'));
        $this->assertEquals(100000.0, PerjadinImport::cleanNumeric('="100.000"'));

        // 4. Clean uncalculated Excel/Google Sheets formula strings
        $formulaDestination = '=IFERROR(__xludf.DUMMYFUNCTION("XLOOKUP( A:A, IMPORTRANGE(""https://docs.google.com/spreadsheets/d/1kB2iVIXpUXdAg3epSf6J1bZ4XRzPiQ32Y2H1rzw_lMM/edit?gid=1146774691#gid=1146774691"",""\'3 Monitoring 2\'!A:A""), IMPORTRANGE(""https://docs.google.com/spreadsheets/d/1kB2iVIXpUXdAg3epSf6"&"J1bZ4XRzPiQ32Y2H1rzw_lMM/edit?gid=1146774691#gid=1146774691"",""\'3 Monitoring 2\'!B:B"") ) "),"Bengkel Mandala Auto")';
        $this->assertEquals('Bengkel Mandala Auto', PerjadinImport::cleanFormulaOrText($formulaDestination));
        $this->assertEquals('Bengkel Mandala Auto', PerjadinImport::cleanFormulaOrText("=IFERROR(XLOOKUP(A1, B:B, C:C), 'Bengkel Mandala Auto')"));
        $this->assertEquals('SMK PGRI Kras', PerjadinImport::cleanFormulaOrText('="SMK PGRI Kras"'));
        $this->assertEquals('100000', PerjadinImport::cleanFormulaOrText('=100000'));
        $this->assertEquals('2026-10-14', PerjadinImport::parseExcelDate('=IFERROR(TODAY(), "2026-10-14")'));
    }

    public function test_perjadin_import_find_guru_and_grouping_logic(): void
    {
        $g1 = new Guru([
            'name' => 'Budi Santoso, S.Pd.',
            'nip' => '198001012005011005',
        ]);
        $g1->id = 10;

        $g2 = new Guru([
            'name' => 'Siti Aminah, M.Pd.',
            'nip' => '198502022008012003',
        ]);
        $g2->id = 20;

        $g3 = new Guru([
            'name' => 'Hendra Wijaya, S.Kom.',
            'nip' => null,
        ]);
        $g3->id = 30;

        $gurus = collect([$g1, $g2, $g3]);

        // 1. Pencarian berdasarkan NIP
        $found1 = PerjadinImport::findGuru('198001012005011005', $gurus);
        $this->assertNotNull($found1);
        $this->assertEquals(10, $found1->id);

        // 2. Pencarian berdasarkan nama lengkap dengan gelar
        $found2 = PerjadinImport::findGuru('Siti Aminah, M.Pd.', $gurus);
        $this->assertNotNull($found2);
        $this->assertEquals(20, $found2->id);

        // 3. Pencarian tanpa gelar akademis
        $found3 = PerjadinImport::findGuru('Budi Santoso', $gurus);
        $this->assertNotNull($found3);
        $this->assertEquals(10, $found3->id);

        // 4. Pencarian gabungan Nama dan NIP dalam kurung
        $found4 = PerjadinImport::findGuru('Budi Santoso (198001012005011005)', $gurus);
        $this->assertNotNull($found4);
        $this->assertEquals(10, $found4->id);

        // 5. Fuzzy Matching (Toleransi Typo)
        // Typo: "Budy Santoso" vs "Budi Santoso, S.Pd."
        $fuzzy1 = PerjadinImport::findGuru('Budy Santoso', $gurus);
        $this->assertNotNull($fuzzy1);
        $this->assertEquals(10, $fuzzy1->id);

        // Typo: "Siti Aminh" vs "Siti Aminah, M.Pd."
        $fuzzy2 = PerjadinImport::findGuru('Siti Aminh', $gurus);
        $this->assertNotNull($fuzzy2);
        $this->assertEquals(20, $fuzzy2->id);

        // Typo: "Hendro Wijaya" vs "Hendra Wijaya, S.Kom."
        $fuzzy3 = PerjadinImport::findGuru('Hendro Wijaya', $gurus);
        $this->assertNotNull($fuzzy3);
        $this->assertEquals(30, $fuzzy3->id);

        // 6. Guru tidak ditemukan (nama fiktif / perbedaan terlalu jauh)
        $notFound = PerjadinImport::findGuru('Guru Fiktif 123', $gurus);
        $this->assertNull($notFound);

        // 7. Pengelompokan baris (Grouping Logic)
        $sampleRows = [
            ['tanggal' => '2026-05-03', 'tujuan' => 'SMPN 1 Ringinrejo', 'maksud' => 'Sosialisasi Asesmen'],
            ['tanggal' => '2026-05-03', 'tujuan' => 'SMPN 1 Ringinrejo', 'maksud' => 'Sosialisasi Asesmen'],
            ['tanggal' => '2026-05-03', 'tujuan' => 'SMPN 2 Kras', 'maksud' => 'Monitoring Mutu'],
            ['tanggal' => '2026-05-04', 'tujuan' => 'SMPN 3 Kediri', 'maksud' => 'Workshop Kurikulum'],
            ['tanggal' => '2026-05-04', 'tujuan' => 'SMPN 3 Kediri', 'maksud' => 'Workshop Kurikulum'],
        ];

        $groups = [];
        foreach ($sampleRows as $row) {
            $key = $row['tanggal'].'|'.mb_strtolower($row['tujuan']).'|'.mb_strtolower($row['maksud']);
            if (! isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $row;
        }

        // Harus terbagi tepat menjadi 3 kelompok
        $this->assertCount(3, $groups);

        $keys = array_keys($groups);
        $this->assertCount(2, $groups[$keys[0]]); // SMP 1: 2 orang
        $this->assertCount(1, $groups[$keys[1]]); // SMP 2: 1 orang
        $this->assertCount(2, $groups[$keys[2]]); // SMP 3: 2 orang
    }

    public function test_perjadin_import_merged_cells_and_sheets(): void
    {
        $import = new PerjadinImport('BOSP');
        $sheets = $import->sheets();

        // Must strictly process Sheet 0
        $this->assertArrayHasKey(0, $sheets);
        $this->assertCount(1, $sheets);

        // Test forward-fill logic for merged rows:
        // Baris 1 memiliki tanggal dan tujuan, baris 2 kosong tanggal/tujuan tetapi ada guru & uang
        $rows = collect([
            [
                'tanggal_yyyy_mm_dd' => '2026-05-03',
                'tujuan_dinas' => 'SMPN 1 Ringinrejo',
                'alamat_tujuan_opsional' => 'Jl. Marabunta',
                'maksud_perjalanan' => 'Sosialisasi AN',
                'nama_nip_guru' => 'Budi Santoso',
                'uang_diterima_rp' => 65000,
            ],
            [
                'tanggal_yyyy_mm_dd' => null, // Merged cell / baris kosong
                'tujuan_dinas' => '', // Merged cell / baris kosong
                'alamat_tujuan_opsional' => '',
                'maksud_perjalanan' => '',
                'nama_nip_guru' => 'Siti Aminah',
                'uang_diterima_rp' => 65000,
            ],
        ]);

        $g1 = new Guru(['name' => 'Budi Santoso']);
        $g1->id = 1;
        $g2 = new Guru(['name' => 'Siti Aminah']);
        $g2->id = 2;
        $gurus = collect([$g1, $g2]);

        $lastDate = null;
        $lastDest = null;
        $lastAddress = null;
        $lastPurpose = null;
        $processed = [];

        foreach ($rows as $r) {
            $rawDate = $r['tanggal_yyyy_mm_dd'];
            $parsedDate = PerjadinImport::parseExcelDate($rawDate);
            if ($parsedDate) {
                $lastDate = $parsedDate;
                $lastDest = trim($r['tujuan_dinas']);
                $lastAddress = trim($r['alamat_tujuan_opsional']);
                $lastPurpose = trim($r['maksud_perjalanan']);
            }

            $date = $parsedDate ?: $lastDate;
            $dest = trim($r['tujuan_dinas']) ?: $lastDest;
            $purpose = trim($r['maksud_perjalanan']) ?: $lastPurpose;

            $processed[] = [
                'date' => $date,
                'dest' => $dest,
                'purpose' => $purpose,
                'guru' => PerjadinImport::findGuru($r['nama_nip_guru'], $gurus)?->name,
            ];
        }

        $this->assertEquals('2026-05-03', $processed[0]['date']);
        $this->assertEquals('SMPN 1 Ringinrejo', $processed[0]['dest']);
        $this->assertEquals('Budi Santoso', $processed[0]['guru']);

        // Baris ke-2 sukses mewarisi tanggal & tujuan dari baris ke-1
        $this->assertEquals('2026-05-03', $processed[1]['date']);
        $this->assertEquals('SMPN 1 Ringinrejo', $processed[1]['dest']);
        $this->assertEquals('Sosialisasi AN', $processed[1]['purpose']);
        $this->assertEquals('Siti Aminah', $processed[1]['guru']);
    }

    public function test_perjadin_import_auto_fills_destination_address_and_finds_instansi(): void
    {
        $i1 = new Instansi([
            'name' => 'SMP Negeri 1 Kras',
            'category' => 'sekolah',
            'address' => 'Jl. Raya Purwodadi No. 04, Desa Purwodadi, Kec. Kras, Kab. Kediri',
            'city' => 'Kab. Kediri',
        ]);
        $i1->id = 101;

        $i2 = new Instansi([
            'name' => 'Kantor Kecamatan Ringinrejo',
            'category' => 'dinas',
            'address' => 'Dilem, Ringinrejo, Kec. Ringinrejo',
            'city' => 'Kab. Kediri',
        ]);
        $i2->id = 102;

        $instansis = collect([$i1, $i2]);

        // 1. Exact match
        $found1 = PerjadinImport::findInstansi('SMP Negeri 1 Kras', $instansis);
        $this->assertNotNull($found1);
        $this->assertEquals(101, $found1->id);

        // 2. Abbreviation match: "SMPN 1 Kras" -> "SMP Negeri 1 Kras"
        $found2 = PerjadinImport::findInstansi('SMPN 1 Kras', $instansis);
        $this->assertNotNull($found2);
        $this->assertEquals(101, $found2->id);
        $this->assertEquals('Jl. Raya Purwodadi No. 04, Desa Purwodadi, Kec. Kras, Kab. Kediri', $found2->address);

        // 3. Substring match: "Kecamatan Ringinrejo" -> "Kantor Kecamatan Ringinrejo"
        $found3 = PerjadinImport::findInstansi('Kecamatan Ringinrejo', $instansis);
        $this->assertNotNull($found3);
        $this->assertEquals(102, $found3->id);

        // 4. Verifikasi autofill alamat jika di Excel kosong
        $excelDestination = 'SMPN 1 Kras';
        $excelAddress = ''; // Dikosongkan oleh pengguna

        $matchedInstansi = PerjadinImport::findInstansi($excelDestination, $instansis);
        $finalAddress = $excelAddress ?: ($matchedInstansi ? $matchedInstansi->address : null);
        $finalCity = $matchedInstansi && ! empty($matchedInstansi->city) ? $matchedInstansi->city : 'Kediri';

        $this->assertEquals('Jl. Raya Purwodadi No. 04, Desa Purwodadi, Kec. Kras, Kab. Kediri', $finalAddress);
        $this->assertEquals('Kab. Kediri', $finalCity);
    }

    public function test_perjadin_template_download_route(): void
    {
        $user = new User(['id' => 1, 'name' => 'Admin']);

        $response = $this->actingAs($user)->get(route('bos.perjadin.template'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_rencana_nota_integration_with_tnt_and_auto_realisasi(): void
    {
        $user = new User(['id' => 1, 'name' => 'Admin']);

        if (! Schema::hasTable('bos_rencana_belanjas')) {
            Schema::create('bos_rencana_belanjas', function (Blueprint $table) {
                $table->id();
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
        if (! Schema::hasTable('perjadin_items')) {
            Schema::create('perjadin_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('perjadin_id')->nullable();
                $table->unsignedBigInteger('guru_id')->nullable();
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->boolean('is_paid')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (! Schema::hasTable('pencairan_banks')) {
            Schema::create('pencairan_banks', function (Blueprint $table) {
                $table->id();
                $table->string('status', 20)->default('draft');
                $table->date('cair_date')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('bos_realisasies')) {
            Schema::create('bos_realisasies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->string('no_transaksi', 50)->nullable();
                $table->string('no_nota', 50)->nullable();
                $table->unsignedBigInteger('bos_rencana_belanja_id')->nullable();
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

        // Buat sample rencana nota draft
        $nota = BosRencanaBelanja::create([
            'no_nota' => 'N26-0001',
            'year' => 2026,
            'month' => 'Mei',
            'title' => 'Belanja ATK Toko Gramedia',
            'supplier_name' => 'Toko Gramedia Kediri',
            'planned_date' => '2026-05-10',
            'status' => 'draft',
        ]);
        BosRencanaBelanjaItem::create([
            'bos_rencana_belanja_id' => $nota->id,
            'volume_plan' => 5,
            'unit' => 'Rim',
            'price' => 50000,
            'total_amount' => 250000,
        ]);

        // 1. Test endpoint api.unpaid-rencana-notas
        $response = $this->actingAs($user)->get(route('bos.api.unpaid-rencana-notas'));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('N26-0001', $data[0]['no_nota']);
        $this->assertEquals(250000, $data[0]['total_amount']);

        // 2. Test edit view includes buttons & modal
        $fundingSource = 'BOSP';
        $routePrefix = 'bos';
        $pencairanBank = new PencairanBank([
            'funding_source' => 'BOSP',
            'letter_number' => '421.5/001/TNT/2026',
            'letter_date' => '2026-08-25',
            'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR',
            'bank_branch' => 'CABANG KEDIRI',
            'bank_address' => 'Jl. PK. Bangsa No. 122 Kediri',
            'source_account_number' => '1001003336',
            'source_account_name' => 'SMKS PGRI KRAS',
            'kepala_sekolah_id' => 1,
            'bendahara_id' => 2,
            'total_nominal' => 250000,
            'total_accounts' => 1,
            'status' => 'draft',
        ]);
        $pencairanBank->id = 999;
        $bankItem = new PencairanBankItem([
            'type' => 'rencana_nota',
            'recipient_name' => 'Toko Gramedia Kediri',
            'account_number' => '1002003004',
            'amount' => 250000,
            'description' => 'Belanja ATK',
        ]);
        $bankItem->id = 888;
        $pencairanBank->setRelation('items', collect([$bankItem]));

        $gurus = collect([
            new Guru(['name' => 'Kepsek', 'is_active' => true]),
            new Guru(['name' => 'Bendahara', 'is_active' => true]),
        ]);

        $editHtml = view('pencairan_bank.edit', compact('pencairanBank', 'fundingSource', 'gurus', 'routePrefix'))->render();
        $this->assertStringContainsString('btnPullRencanaNota', $editHtml);
        $this->assertStringContainsString('modalPullRencanaNota', $editHtml);
        $this->assertStringContainsString('btnPullPerjadin', $editHtml);
        $this->assertStringContainsString('Toko Gramedia Kediri', $editHtml);

        // 3. Test Auto Realisasi when TNT is confirmed cair
        // Hubungkan nota dengan bankItem
        $nota->update(['pencairan_bank_item_id' => $bankItem->id]);
        $bankItem->setRelation('rencanaBelanja', $nota);

        $controller = new PencairanBankController;
        $request = new Request([
            'cair_date' => '2026-05-15',
            'bku_number' => 'BPU080 / 2026',
        ]);
        $res = $controller->confirmCair($request, $pencairanBank);
        $this->assertEquals(200, $res->getStatusCode());

        // Verifikasi BosRealisasi terbuat otomatis
        $this->assertDatabaseHas('bos_realisasies', [
            'bos_rencana_belanja_id' => $nota->id,
            'no_transaksi' => 'BPU080 / 2026',
            'no_nota' => 'N26-0001',
            'total_amount' => 250000,
        ]);

        // Verifikasi status nota berubah menjadi 'realized'
        $nota->refresh();
        $this->assertEquals('realized', $nota->status);
    }

    public function test_pencairan_bank_amount_separator_and_sanitization(): void
    {
        View::share('errors', new ViewErrorBag);

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
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('pencairan_banks')) {
            Schema::drop('pencairan_banks');
        }
        Schema::create('pencairan_banks', function (Blueprint $table) {
            $table->id();
            $table->string('funding_source', 20)->default('BOSP');
            $table->string('letter_number', 100);
            $table->date('letter_date');
            $table->string('bank_name', 100);
            $table->string('bank_branch', 100);
            $table->string('bank_address', 255);
            $table->string('source_account_number', 50);
            $table->string('source_account_name', 150);
            $table->unsignedBigInteger('kepala_sekolah_id');
            $table->unsignedBigInteger('bendahara_id');
            $table->decimal('total_nominal', 15, 2)->default(0);
            $table->integer('total_accounts')->default(0);
            $table->string('status', 20)->default('draft');
            $table->date('cair_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        if (Schema::hasTable('pencairan_bank_items')) {
            Schema::drop('pencairan_bank_items');
        }
        Schema::create('pencairan_bank_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pencairan_bank_id');
            $table->string('type', 30)->default('belanja');
            $table->string('recipient_name', 150)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        if (! Schema::hasTable('perjadin_items')) {
            Schema::create('perjadin_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('perjadin_id')->nullable();
                $table->unsignedBigInteger('guru_id')->nullable();
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->boolean('is_paid')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanjas')) {
            Schema::create('bos_rencana_belanjas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('no_nota', 50)->nullable();
                $table->integer('year')->nullable();
                $table->string('month', 50)->nullable();
                $table->string('title');
                $table->string('supplier_name')->nullable();
                $table->date('planned_date')->nullable();
                $table->string('status', 50)->default('draft');
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 50)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $user = User::factory()->create();
        $kepsek = Guru::create(['name' => 'Kepala Sekolah', 'is_active' => true]);
        $bendahara = Guru::create(['name' => 'Bendahara Sekolah', 'is_active' => true]);

        // 1. Verifikasi view create memiliki input separator dan hidden input raw
        $createHtml = view('pencairan_bank.create', [
            'fundingSource' => 'BOSP',
            'routePrefix' => 'bos',
            'school' => new SchoolProfile(['name' => 'SMK PGRI KRAS']),
            'gurus' => collect([$kepsek, $bendahara]),
            'defaultKepsekId' => $kepsek->id,
            'defaultBendaharaId' => $bendahara->id,
            'bankName' => 'Bank Jatim',
            'bankBranch' => 'Kediri',
            'bankAddress' => 'Jl. Pemuda',
            'sourceAccountNum' => '123456789',
            'sourceAccountName' => 'SMK PGRI KRAS',
        ])->render();

        $this->assertStringContainsString('input-item-amount', $createHtml);
        $this->assertStringContainsString('input-item-amount-raw', $createHtml);

        // 2. Test Store dengan amount bertitik (separator ribuan: 1.750.000)
        $storeData = [
            'letter_number' => '421.5/TEST-SEP/2026',
            'letter_date' => '2026-09-08',
            'bank_name' => 'Bank Jatim',
            'bank_branch' => 'Kediri',
            'bank_address' => 'Kediri',
            'source_account_number' => '123456789',
            'source_account_name' => 'SMK PGRI KRAS',
            'kepala_sekolah_id' => $kepsek->id,
            'bendahara_id' => $bendahara->id,
            'items' => [
                [
                    'type' => 'belanja',
                    'recipient_name' => 'Toko ATK Berkah',
                    'account_number' => '100200300',
                    'amount' => '1.750.000', // Dikirim dalam format string ribuan
                    'description' => 'Pembelian Kertas',
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('bos.pencairan-bank.store'), $storeData);
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pencairan_banks', [
            'letter_number' => '421.5/TEST-SEP/2026',
            'total_nominal' => 1750000,
        ]);

        $this->assertDatabaseHas('pencairan_bank_items', [
            'recipient_name' => 'Toko ATK Berkah',
            'amount' => 1750000,
        ]);

        $pencairanBank = PencairanBank::where('letter_number', '421.5/TEST-SEP/2026')->first();

        // 3. Test Update dengan amount bertitik baru (2.500.000)
        $updateData = array_merge($storeData, [
            'items' => [
                [
                    'type' => 'belanja',
                    'recipient_name' => 'Toko ATK Berkah',
                    'account_number' => '100200300',
                    'amount' => '2.500.000',
                    'description' => 'Pembelian Kertas dan Tinta',
                ],
            ],
        ]);

        $updateResponse = $this->actingAs($user)->put(route('bos.pencairan-bank.update', $pencairanBank->id), $updateData);
        $updateResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pencairan_banks', [
            'id' => $pencairanBank->id,
            'total_nominal' => 2500000,
        ]);

        $this->assertDatabaseHas('pencairan_bank_items', [
            'pencairan_bank_id' => $pencairanBank->id,
            'amount' => 2500000,
        ]);
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

        if (! Schema::hasTable('school_profiles')) {
            Schema::create('school_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('address')->nullable();
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
                $table->string('account_holder', 150);
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('jabatans')) {
            Schema::create('jabatans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('code', 50)->nullable();
                $table->boolean('is_signatory')->default(false);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('guru_jabatans')) {
            Schema::create('guru_jabatans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('guru_id');
                $table->unsignedBigInteger('jabatan_id');
                $table->string('sk_number', 100)->nullable();
                $table->date('sk_date')->nullable();
                $table->string('period_year', 20)->default(date('Y'));
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bos_kertas_kerja_files')) {
            Schema::create('bos_kertas_kerja_files', function (Blueprint $table) {
                $table->id();
                $table->string('filename')->nullable();
                $table->string('original_name')->nullable();
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
                $table->unsignedBigInteger('bos_kertas_kerja_file_id');
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

        if (! Schema::hasTable('perjadins')) {
            Schema::create('perjadins', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->string('bku_number', 100)->nullable();
                $table->string('letter_number', 100)->nullable();
                $table->string('account_code', 50)->nullable();
                $table->text('activity_purpose');
                $table->string('destination_name', 255);
                $table->text('destination_address')->nullable();
                $table->unsignedBigInteger('instansi_id')->nullable();
                $table->date('departure_date');
                $table->date('return_date')->nullable();
                $table->date('payment_date')->nullable();
                $table->string('city', 100)->default('Kediri');
                $table->unsignedBigInteger('kepala_sekolah_id')->nullable();
                $table->unsignedBigInteger('bendahara_id')->nullable();
                $table->string('status', 20)->default('draft');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('perjadin_items')) {
            Schema::create('perjadin_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('perjadin_id')->nullable();
                $table->unsignedBigInteger('guru_id')->nullable();
                $table->unsignedBigInteger('guru_bank_account_id')->nullable();
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->string('receipt_number', 50)->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('ppn', 15, 2)->default(0);
                $table->decimal('pph23', 15, 2)->default(0);
                $table->decimal('net_amount', 15, 2)->default(0);
                $table->boolean('is_paid')->default(false);
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

        if (! Schema::hasTable('utility_profiles')) {
            Schema::create('utility_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable();
                $table->string('name')->nullable();
                $table->string('category', 50)->nullable();
                $table->string('customer_name')->nullable();
                $table->boolean('is_budgeted')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('utility_receipts')) {
            Schema::create('utility_receipts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('utility_profile_id')->nullable();
                $table->unsignedBigInteger('bos_kertas_kerja_item_id')->nullable();
                $table->unsignedBigInteger('bos_rencana_belanja_id')->nullable();
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
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanjas')) {
            Schema::create('bos_rencana_belanjas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->unsignedBigInteger('guru_id')->nullable();
                $table->string('no_nota', 50)->nullable();
                $table->integer('year')->nullable();
                $table->string('month', 50)->nullable();
                $table->string('title')->nullable();
                $table->string('type', 30)->default('belanja');
                $table->string('supplier_name')->nullable();
                $table->date('planned_date')->nullable();
                $table->string('status', 50)->default('draft');
                $table->unsignedBigInteger('pencairan_bank_item_id')->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 50)->nullable();
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

        if (! Schema::hasTable('instansis')) {
            Schema::create('instansis', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('category', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('city', 100)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_perjadin_linked_to_kertas_kerja_automatically_creates_realisasi_on_pencairan(): void
    {
        $this->ensureDatabaseTables();

        $user = User::factory()->create();
        $kepsek = Guru::create(['name' => 'Kepala Sekolah', 'is_active' => true]);
        $bendahara = Guru::create(['name' => 'Bendahara', 'is_active' => true]);
        $guru1 = Guru::create(['name' => 'Guru 1', 'is_active' => true]);
        $guru2 = Guru::create(['name' => 'Guru 2', 'is_active' => true]);

        $kkFile = BosKertasKerjaFile::create([
            'filename' => 'test.xlsx',
            'file_path' => 'dummy',
            'year' => 2026,
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.04.01.0003',
            'program_code' => '01',
            'kegiatan' => 'Kegiatan Dinas Uji Coba',
            'description' => 'Uang transport dinas dalam kota',
            'volume' => 10,
            'unit' => 'oh',
            'price' => 65000,
            'total_amount' => 650000,
        ]);

        // 1. Create Perjadin linked to Kertas Kerja
        $perjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'activity_purpose' => 'Kegiatan Dinas Uji Coba',
            'destination_name' => 'Dinas Pendidikan',
            'departure_date' => '2026-09-08',
            'city' => 'Kediri',
            'kepala_sekolah_id' => $kepsek->id,
            'bendahara_id' => $bendahara->id,
            'status' => 'draft',
        ]);

        $item1 = PerjadinItem::create([
            'perjadin_id' => $perjadin->id,
            'guru_id' => $guru1->id,
            'amount' => 65000,
            'net_amount' => 65000,
            'is_paid' => false,
        ]);

        $item2 = PerjadinItem::create([
            'perjadin_id' => $perjadin->id,
            'guru_id' => $guru2->id,
            'amount' => 65000,
            'net_amount' => 65000,
            'is_paid' => false,
        ]);

        // 2. Create Pencairan Bank (TNT) containing these items
        $pencairanBank = PencairanBank::create([
            'funding_source' => 'BOSP',
            'letter_number' => '421.5/TNT-TEST/2026',
            'letter_date' => '2026-09-08',
            'bank_name' => 'Bank Jatim',
            'source_account_number' => '1001003336',
            'total_nominal' => 130000,
            'total_accounts' => 2,
            'status' => 'draft',
        ]);

        $pbItem1 = PencairanBankItem::create([
            'pencairan_bank_id' => $pencairanBank->id,
            'type' => 'perjadin',
            'recipient_name' => $guru1->name,
            'account_number' => '11111',
            'amount' => 65000,
            'description' => 'Perjadin',
        ]);
        $item1->update(['pencairan_bank_item_id' => $pbItem1->id]);

        $pbItem2 = PencairanBankItem::create([
            'pencairan_bank_id' => $pencairanBank->id,
            'type' => 'perjadin',
            'recipient_name' => $guru2->name,
            'account_number' => '22222',
            'amount' => 65000,
            'description' => 'Perjadin',
        ]);
        $item2->update(['pencairan_bank_item_id' => $pbItem2->id]);

        // 3. Confirm Cairkan TNT
        $response = $this->actingAs($user)->post(route('bos.pencairan-bank.cairkan', $pencairanBank->id), [
            'cair_date' => '2026-09-08',
            'bku_number' => 'BPU099 / 2026',
        ]);

        $response->assertJsonStructure(['message']);

        // Assert Perjadin is now lunas
        $perjadin->refresh();
        $this->assertEquals('lunas', $perjadin->status);
        $this->assertEquals('2026-09-08', $perjadin->payment_date->format('Y-m-d'));

        // Assert BosRealisasi is automatically created
        $this->assertDatabaseHas('bos_realisasies', [
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'perjadin_id' => $perjadin->id,
            'volume' => 2,
            'total_amount' => 130000,
        ]);

        // Assert Kertas Kerja item realized volume is updated
        $this->assertEquals(2.0, (float) $kkItem->fresh()->realized_volume);
        $this->assertEquals(8.0, (float) $kkItem->fresh()->remaining_volume);

        // 4. Delete TNT and assert rollback
        $delResponse = $this->actingAs($user)->delete(route('bos.pencairan-bank.destroy', $pencairanBank->id));
        $delResponse->assertJsonStructure(['message']);

        $this->assertSoftDeleted('bos_realisasies', [
            'perjadin_id' => $perjadin->id,
        ]);
        $this->assertEquals(0.0, (float) $kkItem->fresh()->realized_volume);
    }

    public function test_perjadin_create_and_edit_view_contains_budget_year_filter(): void
    {
        View::share('errors', new ViewErrorBag);

        $kkItem = new BosKertasKerjaItem([
            'id' => 999,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.04.01.0003',
            'kegiatan' => 'Kegiatan Dinas 2026',
            'description' => 'Transport 2026',
            'volume' => 10,
            'unit' => 'OH',
            'price' => 65000,
            'total_amount' => 650000,
        ]);
        $kkItem->setRelation('realisasis', collect([]));

        $fundingSource = 'BOSP';
        $routePrefix = 'bos';
        $school = new SchoolProfile(['name' => 'SMK PGRI KRAS']);
        $gurus = collect([]);
        $instansis = collect([]);
        $defaultKepsekId = null;
        $defaultBendaharaId = null;
        $defaultAccountCode = '5.1.02.04.01.0003';
        $kertasKerjaItems = collect([$kkItem]);
        $availableYears = collect([2026, 2025]);
        $selectedYear = 2026;

        $createHtml = view('perjadin.create', compact(
            'fundingSource',
            'routePrefix',
            'school',
            'gurus',
            'instansis',
            'defaultKepsekId',
            'defaultBendaharaId',
            'defaultAccountCode',
            'kertasKerjaItems',
            'availableYears',
            'selectedYear'
        ))->render();

        $this->assertStringContainsString('Tahun Anggaran:', $createHtml);
        $this->assertStringContainsString('id="filterBudgetYear"', $createHtml);
        $this->assertStringContainsString('data-year="2026"', $createHtml);
        $this->assertStringContainsString('filterKkByYear', $createHtml);

        $perjadin = new Perjadin([
            'funding_source' => 'BOSP',
            'bku_number' => 'BPU100 / 2026',
            'departure_date' => '2026-09-08',
            'activity_purpose' => 'Test Dinas',
            'destination_name' => 'Kantor Dinas',
            'bos_kertas_kerja_item_id' => 999,
            'status' => 'draft',
        ]);
        $perjadin->id = 888;
        $perjadin->setRelation('items', collect([]));
        $perjadin->setRelation('kertasKerjaItem', $kkItem);

        $editHtml = view('perjadin.edit', compact(
            'perjadin',
            'fundingSource',
            'routePrefix',
            'gurus',
            'instansis',
            'kertasKerjaItems',
            'availableYears',
            'selectedYear'
        ))->render();

        $this->assertStringContainsString('Tahun Anggaran:', $editHtml);
        $this->assertStringContainsString('id="filterBudgetYear"', $editHtml);
        $this->assertStringContainsString('data-year="2026"', $editHtml);
        $this->assertStringContainsString('filterKkByYear', $editHtml);
    }

    public function test_perjadin_create_and_edit_view_contains_smart_purpose_references(): void
    {
        View::share('errors', new ViewErrorBag);

        $fundingSource = 'BOSP';
        $routePrefix = 'bos';
        $school = new SchoolProfile(['name' => 'SMK PGRI KRAS']);
        $gurus = collect([]);
        $instansis = collect([]);
        $defaultKepsekId = null;
        $defaultBendaharaId = null;
        $defaultAccountCode = '5.1.02.04.01.0003';
        $kertasKerjaItems = collect([]);
        $availableYears = collect([2026]);
        $selectedYear = 2026;
        $existingPurposes = [
            'Konsultasi dan Koordinasi Laporan BOSP ke Cabang Dinas Pendidikan',
            'Menghadiri Rapat Koordinasi MKKS SMK se-Kabupaten Kediri',
        ];

        $createHtml = view('perjadin.create', compact(
            'fundingSource',
            'routePrefix',
            'school',
            'gurus',
            'instansis',
            'defaultKepsekId',
            'defaultBendaharaId',
            'defaultAccountCode',
            'kertasKerjaItems',
            'availableYears',
            'selectedYear',
            'existingPurposes'
        ))->render();

        $this->assertStringContainsString('id="activityPurpose"', $createHtml);
        $this->assertStringContainsString('id="purposeSuggestBox"', $createHtml);
        $this->assertStringContainsString('Referensi Pintar Aktif', $createHtml);
        $this->assertStringContainsString('purposesData', $createHtml);
        $this->assertStringContainsString('Konsultasi dan Koordinasi Laporan BOSP', $createHtml);

        $perjadin = new Perjadin([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Konsultasi BOSP',
            'destination_name' => 'Cabang Dinas',
        ]);
        $perjadin->id = 123;
        $perjadin->setRelation('items', collect([]));

        $editHtml = view('perjadin.edit', compact(
            'perjadin',
            'fundingSource',
            'routePrefix',
            'gurus',
            'instansis',
            'kertasKerjaItems',
            'availableYears',
            'selectedYear',
            'existingPurposes'
        ))->render();

        $this->assertStringContainsString('id="activityPurpose"', $editHtml);
        $this->assertStringContainsString('id="purposeSuggestBox"', $editHtml);
        $this->assertStringContainsString('purposesData', $editHtml);
    }

    public function test_perjadin_create_hides_bku_number_input_while_edit_keeps_it(): void
    {
        View::share('errors', new ViewErrorBag);

        $fundingSource = 'BOSP';
        $routePrefix = 'bos';
        $gurus = collect([]);
        $instansis = collect([]);
        $kertasKerjaItems = collect([]);
        $availableYears = [2026];
        $selectedYear = 2026;
        $defaultKepsekId = null;
        $defaultBendaharaId = null;
        $defaultAccountCode = '5.1.02.04.01.0003';

        $createHtml = view('perjadin.create', compact(
            'fundingSource',
            'routePrefix',
            'gurus',
            'instansis',
            'kertasKerjaItems',
            'availableYears',
            'selectedYear',
            'defaultKepsekId',
            'defaultBendaharaId',
            'defaultAccountCode'
        ))->render();

        $this->assertStringNotContainsString('name="bku_number"', $createHtml);
        $this->assertStringNotContainsString('id="btnAutoBku"', $createHtml);

        $perjadin = new Perjadin([
            'funding_source' => 'BOSP',
            'bku_number' => 'BPU100 / 2026',
            'departure_date' => '2026-09-08',
            'activity_purpose' => 'Test Dinas',
            'destination_name' => 'Kantor Dinas',
            'status' => 'draft',
        ]);
        $perjadin->id = 888;
        $perjadin->setRelation('items', collect([]));

        $editHtml = view('perjadin.edit', compact(
            'perjadin',
            'fundingSource',
            'routePrefix',
            'gurus',
            'instansis',
            'kertasKerjaItems',
            'availableYears',
            'selectedYear'
        ))->render();

        $this->assertStringContainsString('name="bku_number"', $editHtml);
    }

    public function test_perjadin_update_bku_and_sync_with_bos_realisasi(): void
    {
        $this->ensureDatabaseTables();

        $user = User::first() ?? User::factory()->create();

        $perjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'SMKN 1 Kras',
            'activity_purpose' => 'Koordinasi Pelatihan',
            'departure_date' => '2026-09-10',
            'city' => 'Kediri',
            'bos_kertas_kerja_item_id' => 99999,
            'status' => 'lunas',
        ]);

        $realisasi = BosRealisasi::create([
            'bos_kertas_kerja_item_id' => 99999,
            'perjadin_id' => $perjadin->id,
            'no_transaksi' => 'OLD_BKU',
            'no_nota' => 'NOTA-001',
            'year' => 2026,
            'month' => 'September',
            'transaction_date' => '2026-09-10',
            'receipt_date' => '2026-09-10',
            'volume' => 1,
            'unit' => 'oh',
            'price' => 65000,
            'total_amount' => 65000,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->patchJson(route('bos.perjadin.update-bku', $perjadin->id), [
            'bku_number' => 'BPU123 / 2026',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'bku_number' => 'BPU123 / 2026',
        ]);

        $this->assertEquals('BPU123 / 2026', $perjadin->fresh()->bku_number);
        $this->assertEquals('BPU123 / 2026', $realisasi->fresh()->no_transaksi);
    }

    public function test_perjadin_batch_update_bku_sequence_and_same_mode(): void
    {
        $this->ensureDatabaseTables();

        $user = User::first() ?? User::factory()->create();

        $perjadin1 = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Tujuan 1',
            'activity_purpose' => 'Dinas 1',
            'departure_date' => '2026-09-01',
            'city' => 'Kediri',
            'status' => 'draft',
        ]);
        PerjadinItem::create([
            'perjadin_id' => $perjadin1->id,
            'guru_id' => 1,
            'amount' => 65000,
            'net_amount' => 65000,
        ]);
        PerjadinItem::create([
            'perjadin_id' => $perjadin1->id,
            'guru_id' => 2,
            'amount' => 65000,
            'net_amount' => 65000,
        ]);

        $perjadin2 = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Tujuan 2',
            'activity_purpose' => 'Dinas 2',
            'departure_date' => '2026-09-02',
            'city' => 'Kediri',
            'status' => 'draft',
        ]);
        PerjadinItem::create([
            'perjadin_id' => $perjadin2->id,
            'guru_id' => 1,
            'amount' => 65000,
            'net_amount' => 65000,
        ]);

        // 1. Test Mode Sequence
        $responseSeq = $this->actingAs($user)->postJson(route('bos.perjadin.batch-update-bku'), [
            'ids' => [$perjadin1->id, $perjadin2->id],
            'mode' => 'sequence',
            'bku_start' => 'BPU050',
            'year' => 2026,
        ]);

        $responseSeq->assertOk();
        $responseSeq->assertJson(['success' => true, 'updated_count' => 2]);

        // Perjadin 1 has 2 teachers -> BPU050 - BPU051 / 2026
        $this->assertEquals('BPU050 - BPU051 / 2026', $perjadin1->fresh()->bku_number);
        // Perjadin 2 has 1 teacher -> BPU052 / 2026
        $this->assertEquals('BPU052 / 2026', $perjadin2->fresh()->bku_number);

        // 2. Test Mode Same
        $responseSame = $this->actingAs($user)->postJson(route('bos.perjadin.batch-update-bku'), [
            'ids' => [$perjadin1->id, $perjadin2->id],
            'mode' => 'same',
            'bku_number' => 'BPU099 / 2026',
        ]);

        $responseSame->assertOk();
        $this->assertEquals('BPU099 / 2026', $perjadin1->fresh()->bku_number);
        $this->assertEquals('BPU099 / 2026', $perjadin2->fresh()->bku_number);
    }

    public function test_perjadin_index_bku_status_filter(): void
    {
        $this->ensureDatabaseTables();

        $user = User::first() ?? User::factory()->create();

        // 1 unassigned
        $unassigned = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Tujuan Belum BKU',
            'activity_purpose' => 'Dinas Belum BKU',
            'departure_date' => '2026-09-01',
            'city' => 'Kediri',
            'bku_number' => null,
            'status' => 'draft',
        ]);

        // 1 assigned
        $assigned = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Tujuan Sudah BKU',
            'activity_purpose' => 'Dinas Sudah BKU',
            'departure_date' => '2026-09-02',
            'city' => 'Kediri',
            'bku_number' => 'BPU001 / 2026',
            'status' => 'lunas',
        ]);

        // Filter unassigned
        $respUnassigned = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['bku_status' => 'unassigned']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $respUnassigned->assertOk();
        $dataUnassigned = $respUnassigned->json('data');
        $this->assertCount(1, $dataUnassigned);
        $this->assertStringContainsString('Tujuan Belum BKU', $dataUnassigned[0]['tujuan_display']);

        // Filter assigned
        $respAssigned = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['bku_status' => 'assigned']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $respAssigned->assertOk();
        $dataAssigned = $respAssigned->json('data');
        $this->assertCount(1, $dataAssigned);
        $this->assertStringContainsString('Tujuan Sudah BKU', $dataAssigned[0]['tujuan_display']);
    }

    public function test_perjadin_index_year_filter(): void
    {
        $this->ensureDatabaseTables();

        $user = User::first() ?? User::factory()->create();

        // 1 perjadin in 2025
        $p2025 = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Dinas Tahun 2025',
            'activity_purpose' => 'Kegiatan 2025',
            'departure_date' => '2025-11-10',
            'city' => 'Surabaya',
            'bku_number' => 'BPU001 / 2025',
            'status' => 'lunas',
        ]);

        // 1 perjadin in 2026
        $p2026 = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Dinas Tahun 2026',
            'activity_purpose' => 'Kegiatan 2026',
            'departure_date' => '2026-08-15',
            'city' => 'Malang',
            'bku_number' => null,
            'status' => 'draft',
        ]);

        // 1. Filter 2025
        $resp2025 = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['year' => '2025']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $resp2025->assertOk();
        $data2025 = $resp2025->json('data');
        $this->assertCount(1, $data2025);
        $this->assertStringContainsString('Dinas Tahun 2025', $data2025[0]['tujuan_display']);
        $this->assertEquals(1, $resp2025->json('counters.total'));
        $this->assertEquals(1, $resp2025->json('counters.assigned'));
        $this->assertEquals(0, $resp2025->json('counters.unassigned'));

        // 2. Filter 2026
        $resp2026 = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['year' => '2026']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $resp2026->assertOk();
        $data2026 = $resp2026->json('data');
        $this->assertCount(1, $data2026);
        $this->assertStringContainsString('Dinas Tahun 2026', $data2026[0]['tujuan_display']);
        $this->assertEquals(1, $resp2026->json('counters.total'));
        $this->assertEquals(0, $resp2026->json('counters.assigned'));
        $this->assertEquals(1, $resp2026->json('counters.unassigned'));

        // 3. Filter all
        $respAll = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['year' => 'all']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $respAll->assertOk();
        $this->assertCount(2, $respAll->json('data'));
        $this->assertEquals(2, $respAll->json('counters.total'));

        // 4. Action counters endpoint
        $respCounter2025 = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['action' => 'counters', 'year' => '2025']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $respCounter2025->assertOk();
        $this->assertEquals(1, $respCounter2025->json('total'));
        $this->assertEquals(1, $respCounter2025->json('assigned'));
        $this->assertEquals(0, $respCounter2025->json('unassigned'));

        // 5. HTML view includes availableYears & selectedYear
        $respHtml = $this->actingAs($user)->get(route('bos.perjadin.index'));
        $respHtml->assertOk();
        $respHtml->assertViewHas('availableYears');
        $respHtml->assertViewHas('selectedYear');
    }

    public function test_pencairan_bank_index_year_filter(): void
    {
        $this->ensureDatabaseTables();

        $user = User::first() ?? User::factory()->create();

        // 1 TNT in 2025
        $tnt2025 = PencairanBank::create([
            'funding_source' => 'BOSP',
            'letter_number' => '421.5/TNT-2025/SMK-PGRI/2025',
            'letter_date' => '2025-10-15',
            'bank_name' => 'BANK JATIM',
            'bank_branch' => 'KEDIRI',
            'bank_address' => 'Jl. PK Bangsa',
            'source_account_number' => '1001003336',
            'source_account_name' => 'SMK PGRI KRAS',
            'total_nominal' => 5000000,
            'total_accounts' => 5,
            'status' => 'dicairkan',
        ]);

        // 1 TNT in 2026
        $tnt2026 = PencairanBank::create([
            'funding_source' => 'BOSP',
            'letter_number' => '421.5/TNT-2026/SMK-PGRI/2026',
            'letter_date' => '2026-03-20',
            'bank_name' => 'BANK JATIM',
            'bank_branch' => 'KEDIRI',
            'bank_address' => 'Jl. PK Bangsa',
            'source_account_number' => '1001003336',
            'source_account_name' => 'SMK PGRI KRAS',
            'total_nominal' => 3000000,
            'total_accounts' => 3,
            'status' => 'draft',
        ]);

        // 1. Filter 2025
        $resp2025 = $this->actingAs($user)->getJson(route('bos.pencairan-bank.index', ['year' => '2025']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $resp2025->assertOk();
        $data2025 = $resp2025->json('data');
        $this->assertCount(1, $data2025);
        $this->assertStringContainsString('TNT-2025', $data2025[0]['surat_display']);

        // 2. Filter 2026
        $resp2026 = $this->actingAs($user)->getJson(route('bos.pencairan-bank.index', ['year' => '2026']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $resp2026->assertOk();
        $data2026 = $resp2026->json('data');
        $this->assertCount(1, $data2026);
        $this->assertStringContainsString('TNT-2026', $data2026[0]['surat_display']);

        // 3. Filter all
        $respAll = $this->actingAs($user)->getJson(route('bos.pencairan-bank.index', ['year' => 'all']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $respAll->assertOk();
        $this->assertCount(2, $respAll->json('data'));

        // 4. HTML view includes availableYears & selectedYear
        $respHtml = $this->actingAs($user)->get(route('bos.pencairan-bank.index'));
        $respHtml->assertOk();
        $respHtml->assertViewHas('availableYears');
        $respHtml->assertViewHas('selectedYear');
    }

    public function test_perjadin_index_and_show_displays_tnt_letter_number(): void
    {
        $this->ensureDatabaseTables();

        $user = User::first() ?? User::factory()->create();
        $kepsek = Guru::create(['name' => 'Kepala Sekolah Test', 'is_active' => true]);
        $bendahara = Guru::create(['name' => 'Bendahara Test', 'is_active' => true]);
        $guru = Guru::create(['name' => 'Guru Test TNT', 'is_active' => true]);

        $perjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Koordinasi BOSP ke Cabdin',
            'destination_name' => 'Cabang Dinas Kediri',
            'departure_date' => '2026-09-08',
            'city' => 'Kediri',
            'kepala_sekolah_id' => $kepsek->id,
            'bendahara_id' => $bendahara->id,
            'status' => 'diajukan_ke_bank',
        ]);

        $item = PerjadinItem::create([
            'perjadin_id' => $perjadin->id,
            'guru_id' => $guru->id,
            'amount' => 100000,
            'net_amount' => 100000,
            'is_paid' => false,
        ]);

        $pencairanBank = PencairanBank::create([
            'funding_source' => 'BOSP',
            'letter_number' => '400.3.8.7/999/SMK/2026',
            'letter_date' => '2026-09-08',
            'bank_name' => 'Bank Jatim',
            'source_account_number' => '1001003336',
            'total_nominal' => 100000,
            'total_accounts' => 1,
            'status' => 'draft',
        ]);

        $pbItem = PencairanBankItem::create([
            'pencairan_bank_id' => $pencairanBank->id,
            'type' => 'perjadin',
            'recipient_name' => $guru->name,
            'account_number' => '99999999',
            'amount' => 100000,
            'description' => 'Perjadin',
        ]);
        $item->update(['pencairan_bank_item_id' => $pbItem->id]);

        // 1. Assert accessor
        $this->assertContains('400.3.8.7/999/SMK/2026', $perjadin->fresh()->tnt_letters);

        // 2. Assert index datatables contains TNT letter and Diajukan ke TNT badge
        $response = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['year' => 'all']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $response->assertOk();
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $row = collect($data)->firstWhere('id', $perjadin->id);
        $this->assertNotNull($row);
        $this->assertStringContainsString('400.3.8.7/999/SMK/2026', $row['tanggal_display']);
        $this->assertStringContainsString('TNT', $row['status_display']);

        // 3. Assert search by TNT number works
        $searchResponse = $this->actingAs($user)->getJson(route('bos.perjadin.index', [
            'year' => 'all',
            'search' => ['value' => '400.3.8.7/999'],
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $searchResponse->assertOk();
        $searchData = $searchResponse->json('data');
        $this->assertCount(1, $searchData);
        $this->assertEquals($perjadin->id, $searchData[0]['id']);

        // 4. Assert detail show view displays TNT letter
        $showResponse = $this->actingAs($user)->get(route('bos.perjadin.show', $perjadin->id));
        $showResponse->assertOk();
        $showResponse->assertSee('400.3.8.7/999/SMK/2026');
        $showResponse->assertSee('Diajukan ke TNT');
    }

    public function test_pencairan_bank_export_parse_account_info_handles_va_and_bank_name(): void
    {
        // 1. VA with bank name and account number -> VA, BANK JATIM
        [$bank1, $acc1] = PencairanBankExport::parseAccountInfo('VA. Bank Jatim 25558856875055');
        $this->assertEquals('VA, BANK JATIM', $bank1);
        $this->assertEquals('25558856875055', $acc1);

        // 2. VA with only account number (fallback bank name)
        [$bank2, $acc2] = PencairanBankExport::parseAccountInfo('VA. 25558856875055', 'Bank Mandiri');
        $this->assertEquals('VA, BANK MANDIRI', $bank2);
        $this->assertEquals('25558856875055', $acc2);

        // 3. VA with different bank name
        [$bank3, $acc3] = PencairanBankExport::parseAccountInfo('VA. Bank BCA 987654321');
        $this->assertEquals('VA, BANK BCA', $bank3);
        $this->assertEquals('987654321', $acc3);

        // 4. VA without digits yet
        [$bank4, $acc4] = PencairanBankExport::parseAccountInfo('VA. Bank Jatim');
        $this->assertEquals('VA, BANK JATIM', $bank4);
        $this->assertEquals('', $acc4);

        // 5. Standard non-VA dot format
        [$bank5, $acc5] = PencairanBankExport::parseAccountInfo('Bank Jatim. 1006640440');
        $this->assertEquals('BANK JATIM', $bank5);
        $this->assertEquals('1006640440', $acc5);

        // 6. Plain account number without bank
        [$bank6, $acc6] = PencairanBankExport::parseAccountInfo('1006640440', 'Bank Jatim');
        $this->assertEquals('BANK JATIM', $bank6);
        $this->assertEquals('1006640440', $acc6);

        // 7. Online item with raw numbers
        [$bank7, $acc7] = PencairanBankExport::parseAccountInfo('25558856875055', 'Bank Jatim', true);
        $this->assertEquals('VA, BANK JATIM', $bank7);
        $this->assertEquals('25558856875055', $acc7);

        // 8. Sheet 1 = Batch Upload Sheet, Sheet 2 = Formal Sheet
        $pb = new PencairanBank;
        $export = new PencairanBankExport($pb);
        $sheets = $export->sheets();
        $this->assertCount(2, $sheets);
        $this->assertInstanceOf(PencairanBankBatchUploadSheet::class, $sheets[0]);
        $this->assertInstanceOf(PencairanBankFormalSheet::class, $sheets[1]);
    }

    public function test_pencairan_bank_export_excel_filename_is_formatted_correctly(): void
    {
        // Simulate the filename sanitization logic from PencairanBankController::exportExcel
        $inputs = [
            ['400.3.8.7/083.1/SMK PGRI Kras/2026', '400-3-8-7_083-1_SMK_PGRI_Kras_2026'],
            ['400.3.8.7/001/SMK/2026',              '400-3-8-7_001_SMK_2026'],
            ['400.3.8.7/002.A/SMK PGRI/2026',       '400-3-8-7_002-A_SMK_PGRI_2026'],
        ];

        foreach ($inputs as [$letterNumber, $expected]) {
            $clean = $letterNumber;
            $clean = str_replace('/', '_', $clean);
            $clean = str_replace(['.', ','], '-', $clean);
            $clean = str_replace(' ', '_', $clean);
            $clean = preg_replace('/[^A-Za-z0-9_\-]/', '', $clean);
            $clean = preg_replace('/_+/', '_', $clean);
            $clean = trim($clean, '_-');

            $fileName = "Daftar_Transfer_Bank_Jatim_TNT_{$clean}.xlsx";

            $this->assertEquals(
                "Daftar_Transfer_Bank_Jatim_TNT_{$expected}.xlsx",
                $fileName,
                "Gagal untuk letter_number: {$letterNumber}"
            );
        }
    }

    public function test_perjadin_creation_fails_when_kertas_kerja_item_has_zero_remaining_volume(): void
    {
        $this->ensureDatabaseTables();

        $user = User::factory()->create();
        $kepsek = Guru::create(['name' => 'Kepala Sekolah', 'is_active' => true]);
        $bendahara = Guru::create(['name' => 'Bendahara', 'is_active' => true]);
        $guru1 = Guru::create(['name' => 'Guru 1', 'is_active' => true]);

        $kkFile = BosKertasKerjaFile::create([
            'filename' => 'test.xlsx',
            'file_path' => 'dummy',
            'year' => 2026,
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.04.01.0003',
            'program_code' => '01',
            'kegiatan' => 'Kegiatan Dinas Uji Coba',
            'description' => 'Uang transport dinas dalam kota',
            'volume' => 2,
            'unit' => 'oh',
            'price' => 65000,
            'total_amount' => 130000,
        ]);

        // Simulasikan volume sudah terealisasi penuh (2 dari 2)
        BosRealisasi::create([
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'year' => 2026,
            'month' => 'Januari',
            'volume' => 2,
            'unit' => 'oh',
            'price' => 65000,
            'total_amount' => 130000,
            'transaction_date' => '2026-01-15',
            'receipt_date' => '2026-01-15',
            'created_by' => $user->id,
        ]);

        $this->assertEquals(0.0, $kkItem->fresh()->getAvailableVolumeForPerjadin());

        // Coba buat Perjadin dengan KK item yang volumenya sudah 0
        $response = $this->actingAs($user)->post(route('bos.perjadin.store'), [
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'activity_purpose' => 'Kegiatan Dinas Uji Coba',
            'destination_name' => 'Dinas Pendidikan',
            'departure_date' => '2026-09-08',
            'city' => 'Kediri',
            'kepala_sekolah_id' => $kepsek->id,
            'bendahara_id' => $bendahara->id,
            'items' => [
                [
                    'guru_id' => $guru1->id,
                    'amount' => 65000,
                    'net_amount' => 65000,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('bos_kertas_kerja_item_id');
        $this->assertDatabaseMissing('perjadins', [
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'destination_name' => 'Dinas Pendidikan',
        ]);
    }

    public function test_perjadin_creation_fails_when_participants_exceed_remaining_volume(): void
    {
        $this->ensureDatabaseTables();

        $user = User::factory()->create();
        $kepsek = Guru::create(['name' => 'Kepala Sekolah', 'is_active' => true]);
        $bendahara = Guru::create(['name' => 'Bendahara', 'is_active' => true]);
        $guru1 = Guru::create(['name' => 'Guru 1', 'is_active' => true]);
        $guru2 = Guru::create(['name' => 'Guru 2', 'is_active' => true]);

        $kkFile = BosKertasKerjaFile::create([
            'filename' => 'test.xlsx',
            'file_path' => 'dummy',
            'year' => 2026,
            'is_active' => true,
        ]);

        // Volume anggaran hanya 1 oh
        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.04.01.0003',
            'program_code' => '01',
            'kegiatan' => 'Kegiatan Dinas Uji Coba',
            'description' => 'Uang transport dinas dalam kota',
            'volume' => 1,
            'unit' => 'oh',
            'price' => 65000,
            'total_amount' => 65000,
        ]);

        // Coba ajukan 2 orang peserta untuk KK dengan sisa alokasi 1
        $response = $this->actingAs($user)->post(route('bos.perjadin.store'), [
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'activity_purpose' => 'Kegiatan Dinas Uji Coba',
            'destination_name' => 'Dinas Pendidikan',
            'departure_date' => '2026-09-08',
            'city' => 'Kediri',
            'kepala_sekolah_id' => $kepsek->id,
            'bendahara_id' => $bendahara->id,
            'items' => [
                [
                    'guru_id' => $guru1->id,
                    'amount' => 65000,
                    'net_amount' => 65000,
                ],
                [
                    'guru_id' => $guru2->id,
                    'amount' => 65000,
                    'net_amount' => 65000,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('bos_kertas_kerja_item_id');
        $this->assertDatabaseMissing('perjadins', [
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'destination_name' => 'Dinas Pendidikan',
        ]);
    }

    public function test_perjadin_creation_succeeds_when_participants_within_remaining_volume(): void
    {
        $this->ensureDatabaseTables();

        $user = User::factory()->create();
        $kepsek = Guru::create(['name' => 'Kepala Sekolah', 'is_active' => true]);
        $bendahara = Guru::create(['name' => 'Bendahara', 'is_active' => true]);
        $guru1 = Guru::create(['name' => 'Guru 1', 'is_active' => true]);

        $kkFile = BosKertasKerjaFile::create([
            'filename' => 'test.xlsx',
            'file_path' => 'dummy',
            'year' => 2026,
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.04.01.0003',
            'program_code' => '01',
            'kegiatan' => 'Kegiatan Dinas Uji Coba',
            'description' => 'Uang transport dinas dalam kota',
            'volume' => 5,
            'unit' => 'oh',
            'price' => 65000,
            'total_amount' => 325000,
        ]);

        $response = $this->actingAs($user)->post(route('bos.perjadin.store'), [
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'activity_purpose' => 'Kegiatan Dinas Uji Coba',
            'destination_name' => 'Dinas Pendidikan Sukses',
            'departure_date' => '2026-09-08',
            'city' => 'Kediri',
            'kepala_sekolah_id' => $kepsek->id,
            'bendahara_id' => $bendahara->id,
            'items' => [
                [
                    'guru_id' => $guru1->id,
                    'amount' => 65000,
                    'net_amount' => 65000,
                ],
            ],
        ]);

        $this->assertEquals(302, $response->getStatusCode(), (string) $response->getContent());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('perjadins', [
            'bos_kertas_kerja_item_id' => $kkItem->id,
            'destination_name' => 'Dinas Pendidikan Sukses',
        ]);
    }

    public function test_perjadin_create_and_edit_view_disables_exhausted_kertas_kerja_item(): void
    {
        View::share('errors', new ViewErrorBag);

        $kkExhausted = new BosKertasKerjaItem([
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.04.01.0003',
            'kegiatan' => 'Kegiatan Dinas Habis',
            'description' => 'Transport Habis',
            'volume' => 2,
            'unit' => 'oh',
            'price' => 65000,
            'total_amount' => 130000,
        ]);
        $kkExhausted->id = 101;
        $kkExhausted->available_volume = 0;
        $kkExhausted->setRelation('realisasis', collect([]));

        $kkAvailable = new BosKertasKerjaItem([
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.04.01.0003',
            'kegiatan' => 'Kegiatan Dinas Tersedia',
            'description' => 'Transport Tersedia',
            'volume' => 5,
            'unit' => 'oh',
            'price' => 65000,
            'total_amount' => 325000,
        ]);
        $kkAvailable->id = 102;
        $kkAvailable->available_volume = 5;
        $kkAvailable->setRelation('realisasis', collect([]));

        $fundingSource = 'BOSP';
        $routePrefix = 'bos';
        $school = new SchoolProfile(['name' => 'SMK PGRI KRAS']);
        $gurus = collect([]);
        $instansis = collect([]);
        $defaultKepsekId = null;
        $defaultBendaharaId = null;
        $defaultAccountCode = '5.1.02.04.01.0003';
        $kertasKerjaItems = collect([$kkExhausted, $kkAvailable]);
        $availableYears = collect([2026]);
        $selectedYear = 2026;
        $existingPurposes = [];

        // 1. Create View
        $createHtml = view('perjadin.create', compact(
            'fundingSource',
            'routePrefix',
            'school',
            'gurus',
            'instansis',
            'defaultKepsekId',
            'defaultBendaharaId',
            'defaultAccountCode',
            'kertasKerjaItems',
            'availableYears',
            'selectedYear',
            'existingPurposes'
        ))->render();

        $this->assertStringContainsString('value="101"', $createHtml);
        $this->assertStringContainsString('disabled', $createHtml);
        $this->assertStringContainsString('HABIS - Sisa: 0 oh', $createHtml);
        $this->assertStringContainsString('value="102"', $createHtml);
        $this->assertStringContainsString('Sisa: 5 oh', $createHtml);

        // 2. Edit View
        $perjadin = new Perjadin([
            'funding_source' => 'BOSP',
            'bos_kertas_kerja_item_id' => 101, // Ini item yang sedang dipilih oleh perjadin ini
            'activity_purpose' => 'Kegiatan Edit',
            'destination_name' => 'Kediri',
            'departure_date' => '2026-09-08',
            'city' => 'Kediri',
        ]);
        $perjadin->id = 888;
        $perjadin->setRelation('items', collect([]));
        $perjadin->setRelation('kertasKerjaItem', $kkExhausted);

        $editHtml = view('perjadin.edit', compact(
            'perjadin',
            'fundingSource',
            'routePrefix',
            'gurus',
            'instansis',
            'kertasKerjaItems',
            'availableYears',
            'selectedYear',
            'existingPurposes'
        ))->render();

        // Pada item 101 karena merupakan item yang sedang aktif diedit, dia tetap terpilih
        $this->assertStringContainsString('value="101"', $editHtml);
        $this->assertStringContainsString('selected', $editHtml);
    }

    public function test_print_batch_kwitansi_renders_multiple_receipts(): void
    {
        $school = new SchoolProfile([
            'name' => 'SMK PGRI KRAS',
            'city' => 'Kediri',
        ]);

        $guru1 = new Guru(['name' => 'Budi Santoso', 'nip' => '19800101']);
        $guru1->id = 1;
        $guru2 = new Guru(['name' => 'Siti Aminah', 'nip' => '19850202']);
        $guru2->id = 2;

        $item1 = new PerjadinItem([
            'perjadin_id' => 1,
            'guru_id' => 1,
            'amount' => 65000,
            'ppn' => 0,
            'pph23' => 0,
            'net_amount' => 65000,
        ]);
        $item1->setRelation('guru', $guru1);
        $item1->setRelation('bankAccount', null);

        $perjadin1 = new Perjadin([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Rapat Koordinasi BOSP',
            'destination_name' => 'Cabang Dinas Pendidikan',
            'departure_date' => '2026-05-10',
            'return_date' => '2026-05-10',
            'bku_number' => 'BPU001 / 2026',
            'letter_number' => '094/082/101.6.21/2026',
            'city' => 'Kediri',
        ]);
        $perjadin1->id = 1;
        $perjadin1->setRelation('items', collect([$item1]));
        $perjadin1->setRelation('kepalaSekolah', null);
        $perjadin1->setRelation('bendahara', null);

        $item2 = new PerjadinItem([
            'perjadin_id' => 2,
            'guru_id' => 2,
            'amount' => 100000,
            'ppn' => 0,
            'pph23' => 0,
            'net_amount' => 100000,
        ]);
        $item2->setRelation('guru', $guru2);
        $item2->setRelation('bankAccount', null);

        $perjadin2 = new Perjadin([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Workshop Kurikulum Merdeka',
            'destination_name' => 'Hotel Lotus Kediri',
            'departure_date' => '2026-05-12',
            'return_date' => '2026-05-12',
            'bku_number' => 'BPU002 / 2026',
            'letter_number' => '094/083/101.6.21/2026',
            'city' => 'Kediri',
        ]);
        $perjadin2->id = 2;
        $perjadin2->setRelation('items', collect([$item2]));
        $perjadin2->setRelation('kepalaSekolah', null);
        $perjadin2->setRelation('bendahara', null);

        $perjadins = collect([$perjadin1, $perjadin2]);
        $fundingSource = 'BOSP';
        $routePrefix = 'bos';

        $batchHtml = view('perjadin.print_kwitansi_batch', compact(
            'perjadins',
            'school',
            'fundingSource',
            'routePrefix'
        ))->render();

        $this->assertStringContainsString('Cetak Kwitansi Massal', $batchHtml);
        $this->assertStringContainsString('Rapat Koordinasi BOSP', $batchHtml);
        $this->assertStringContainsString('Workshop Kurikulum Merdeka', $batchHtml);
        $this->assertStringContainsString('BUDI SANTOSO', $batchHtml);
        $this->assertStringContainsString('SITI AMINAH', $batchHtml);
        $this->assertStringContainsString('BPU001 / 2026', $batchHtml);
        $this->assertStringContainsString('BPU002 / 2026', $batchHtml);
        $this->assertStringContainsString('Nomor Surat', $batchHtml);
        $this->assertStringContainsString('094/082/101.6.21/2026', $batchHtml);
        $this->assertStringContainsString('094/083/101.6.21/2026', $batchHtml);
    }

    public function test_print_single_kwitansi_renders_letter_number(): void
    {
        $school = new SchoolProfile([
            'name' => 'SMK PGRI KRAS',
            'city' => 'Kediri',
        ]);

        $guru = new Guru(['name' => 'Budi Santoso', 'nip' => '19800101']);
        $guru->id = 1;

        $item = new PerjadinItem([
            'perjadin_id' => 65,
            'guru_id' => 1,
            'amount' => 65000,
            'ppn' => 0,
            'pph23' => 0,
            'net_amount' => 65000,
        ]);
        $item->setRelation('guru', $guru);
        $item->setRelation('bankAccount', null);

        $perjadin = new Perjadin([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Konsultasi Laporan BOSP',
            'destination_name' => 'Cabang Dinas Pendidikan',
            'departure_date' => '2026-05-10',
            'return_date' => '2026-05-10',
            'bku_number' => 'BPU001 / 2026',
            'letter_number' => '094/065/101.6.21/2026',
            'city' => 'Kediri',
        ]);
        $perjadin->id = 65;
        $perjadin->setRelation('items', collect([$item]));
        $perjadin->setRelation('kepalaSekolah', null);
        $perjadin->setRelation('bendahara', null);

        $items = collect([$item]);
        $fundingSource = 'BOSP';
        $routePrefix = 'bos';

        $singleHtml = view('perjadin.print_kwitansi', compact(
            'perjadin',
            'items',
            'school',
            'fundingSource',
            'routePrefix'
        ))->render();

        $this->assertStringContainsString('KWITANSI', $singleHtml);
        $this->assertStringContainsString('Nomor Surat', $singleHtml);
        $this->assertStringContainsString('094/065/101.6.21/2026', $singleHtml);
        $this->assertStringContainsString('Konsultasi Laporan BOSP', $singleHtml);
    }

    public function test_perjadin_import_reads_no_surat_column(): void
    {
        $rows = collect([
            [
                'tanggal_yyyy_mm_dd' => '2026-05-03',
                'no_surat_opsional' => '094/082/101.6.21/2026',
                'tujuan_dinas' => 'SMPN 1 Ringinrejo',
                'alamat_tujuan_opsional' => 'Jl. Marabunta',
                'maksud_perjalanan' => 'Sosialisasi AN',
                'nama_nip_guru' => 'Budi Santoso Import',
                'uang_diterima_rp' => 65000,
                'no_bku_awal_opsional' => 'BPU070',
                'ppn_opsional' => 0,
                'pph_2123_opsional' => 0,
                'catatan_opsional' => '',
            ],
            [
                'tanggal_yyyy_mm_dd' => '2026-05-03',
                'no_surat_opsional' => '',
                'tujuan_dinas' => 'SMPN 1 Ringinrejo',
                'alamat_tujuan_opsional' => '',
                'maksud_perjalanan' => 'Sosialisasi AN',
                'nama_nip_guru' => 'Siti Aminah Import',
                'uang_diterima_rp' => 65000,
                'no_bku_awal_opsional' => '',
                'ppn_opsional' => 0,
                'pph_2123_opsional' => 0,
                'catatan_opsional' => '',
            ],
        ]);

        Guru::create(['name' => 'Budi Santoso Import', 'is_active' => true]);
        Guru::create(['name' => 'Siti Aminah Import', 'is_active' => true]);

        $import = new PerjadinImport('BOSP');
        $import->collection($rows);

        $perjadin = Perjadin::where('destination_name', 'SMPN 1 Ringinrejo')->first();
        $this->assertNotNull($perjadin);
        $this->assertEquals('094/082/101.6.21/2026', $perjadin->letter_number);
        $this->assertEquals(2, $perjadin->items()->count());
    }

    public function test_perjadin_index_payment_date_filter(): void
    {
        $p1 = Perjadin::create([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Dinas 1',
            'destination_name' => 'Tujuan 1',
            'departure_date' => '2026-05-10',
            'payment_date' => '2026-05-15',
            'status' => 'lunas',
            'city' => 'Kediri',
        ]);

        $p2 = Perjadin::create([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Dinas 2',
            'destination_name' => 'Tujuan 2',
            'departure_date' => '2026-06-10',
            'payment_date' => '2026-06-20',
            'status' => 'lunas',
            'city' => 'Kediri',
        ]);

        $p3 = Perjadin::create([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Dinas 3',
            'destination_name' => 'Tujuan 3',
            'departure_date' => '2026-07-10',
            'payment_date' => null,
            'status' => 'draft',
            'city' => 'Kediri',
        ]);

        $p4 = Perjadin::create([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Dinas 4',
            'destination_name' => 'Tujuan 4',
            'departure_date' => '2026-08-10',
            'payment_date' => null,
            'status' => 'diajukan_ke_bank',
            'city' => 'Kediri',
        ]);

        $user = User::first() ?? User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password'),
        ]);

        // 1. Filter by specific payment_date: 2026-05-15
        $res1 = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['payment_date' => '2026-05-15', 'year' => 'all']));
        $res1->assertOk();
        $data1 = $res1->json('data');
        $this->assertCount(1, $data1);
        $this->assertEquals('Tujuan 1', $data1[0]['destination_name']);

        // 2. Filter by unpaid (all without payment date: draft & tnt)
        $res2 = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['payment_date' => 'unpaid', 'year' => 'all']));
        $res2->assertOk();
        $this->assertTrue(collect($res2->json('data'))->contains('destination_name', 'Tujuan 3'));
        $this->assertTrue(collect($res2->json('data'))->contains('destination_name', 'Tujuan 4'));
        $this->assertFalse(collect($res2->json('data'))->contains('destination_name', 'Tujuan 1'));

        // 3. Filter by paid
        $res3 = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['payment_date' => 'paid', 'year' => 'all']));
        $res3->assertOk();
        $this->assertTrue(collect($res3->json('data'))->contains('destination_name', 'Tujuan 1'));
        $this->assertTrue(collect($res3->json('data'))->contains('destination_name', 'Tujuan 2'));
        $this->assertFalse(collect($res3->json('data'))->contains('destination_name', 'Tujuan 3'));
        $this->assertFalse(collect($res3->json('data'))->contains('destination_name', 'Tujuan 4'));

        // 4. Filter by draft
        $resDraft = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['payment_date' => 'draft', 'year' => 'all']));
        $resDraft->assertOk();
        $this->assertTrue(collect($resDraft->json('data'))->contains('destination_name', 'Tujuan 3'));
        $this->assertFalse(collect($resDraft->json('data'))->contains('destination_name', 'Tujuan 4'));
        $this->assertFalse(collect($resDraft->json('data'))->contains('destination_name', 'Tujuan 1'));

        // 5. Filter by tnt
        $resTnt = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['payment_date' => 'tnt', 'year' => 'all']));
        $resTnt->assertOk();
        $this->assertTrue(collect($resTnt->json('data'))->contains('destination_name', 'Tujuan 4'));
        $this->assertFalse(collect($resTnt->json('data'))->contains('destination_name', 'Tujuan 3'));
        $this->assertFalse(collect($resTnt->json('data'))->contains('destination_name', 'Tujuan 1'));

        // 6. Filter by status parameter (draft, tnt, cair)
        $resStatusDraft = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['status' => 'draft', 'year' => 'all']));
        $resStatusDraft->assertOk();
        $this->assertTrue(collect($resStatusDraft->json('data'))->contains('destination_name', 'Tujuan 3'));
        $this->assertFalse(collect($resStatusDraft->json('data'))->contains('destination_name', 'Tujuan 4'));

        $resStatusTnt = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['status' => 'tnt', 'year' => 'all']));
        $resStatusTnt->assertOk();
        $this->assertTrue(collect($resStatusTnt->json('data'))->contains('destination_name', 'Tujuan 4'));
        $this->assertFalse(collect($resStatusTnt->json('data'))->contains('destination_name', 'Tujuan 3'));

        $resStatusCair = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['status' => 'cair', 'year' => 'all']));
        $resStatusCair->assertOk();
        $this->assertTrue(collect($resStatusCair->json('data'))->contains('destination_name', 'Tujuan 1'));
        $this->assertTrue(collect($resStatusCair->json('data'))->contains('destination_name', 'Tujuan 2'));
        $this->assertFalse(collect($resStatusCair->json('data'))->contains('destination_name', 'Tujuan 3'));
        $this->assertFalse(collect($resStatusCair->json('data'))->contains('destination_name', 'Tujuan 4'));

        // 7. Check counters
        $resCounters = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['action' => 'counters', 'year' => 'all']));
        $resCounters->assertOk();
        $this->assertEquals(1, $resCounters->json('draft'));
        $this->assertEquals(1, $resCounters->json('tnt'));
        $this->assertEquals(2, $resCounters->json('cair'));
    }

    public function test_perjadin_item_amount_casts_and_edit_view(): void
    {
        $guru = Guru::create(['name' => 'Guru Test Amount', 'is_active' => true]);
        $perjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Test Edit Amount',
            'destination_name' => 'SMKN 1 Kediri',
            'departure_date' => '2026-05-10',
            'city' => 'Kediri',
            'kepala_sekolah_id' => $guru->id,
            'bendahara_id' => $guru->id,
        ]);

        $item = PerjadinItem::create([
            'perjadin_id' => $perjadin->id,
            'guru_id' => $guru->id,
            'amount' => 100000,
            'ppn' => 0,
            'pph23' => 0,
            'net_amount' => 100000,
        ]);

        // Assert model cast returns float, not string '100000.00'
        $this->assertIsFloat($item->fresh()->amount);
        $this->assertEquals(100000.0, $item->fresh()->amount);

        $user = User::first() ?? User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password'),
        ]);

        $res = $this->actingAs($user)->get(route('bos.perjadin.edit', $perjadin->id));
        $res->assertOk();
        // In json serialization of items, amount should be 100000 (numeric)
        $res->assertSee('"amount":100000', false);
    }

    public function test_perjadin_import_links_to_kertas_kerja_item(): void
    {
        $kkFile = BosKertasKerjaFile::create([
            'fiscal_year' => 2026,
            'filename' => 'kertas_kerja_test_import.xlsx',
            'original_filename' => 'kertas_kerja_test_import.xlsx',
            'file_path' => 'kertas_kerja/test.xlsx',
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Mei',
            'account_code' => '5.1.02.04.01.0003',
            'program_code' => '01',
            'kegiatan' => 'Kegiatan Dinas Import',
            'description' => 'Transport dinas import',
            'volume' => 10,
            'unit' => 'oh',
            'price' => 75000,
            'total_amount' => 750000,
        ]);

        Guru::create(['name' => 'Guru Import KK', 'is_active' => true]);

        $rows = collect([
            [
                'tanggal_yyyy_mm_dd' => '2026-05-15',
                'no_surat_opsional' => '094/KK/2026',
                'tujuan_dinas' => 'Dinas Pendidikan Provinsi',
                'alamat_tujuan_opsional' => 'Surabaya',
                'maksud_perjalanan' => 'Koordinasi BOS',
                'nama_nip_guru' => 'Guru Import KK',
                'uang_diterima_rp' => '', // Kosong agar otomatis mengambil tarif KK
                'no_bku_awal_opsional' => '',
                'ppn_opsional' => 0,
                'pph_2123_opsional' => 0,
                'catatan_opsional' => '',
            ],
        ]);

        $import = new PerjadinImport('BOSP', $kkItem->id);
        $import->collection($rows);

        $perjadin = Perjadin::where('destination_name', 'Dinas Pendidikan Provinsi')->first();
        $this->assertNotNull($perjadin);
        $this->assertEquals($kkItem->id, $perjadin->bos_kertas_kerja_item_id);
        $this->assertEquals('5.1.02.04.01.0003', $perjadin->account_code);
        $this->assertEquals(1, $perjadin->items()->count());
        $this->assertEquals(75000.0, $perjadin->items()->first()->amount);
    }

    public function test_edit_perjadin_preserves_tnt_status_and_links(): void
    {
        $user = new User(['id' => 1, 'name' => 'Admin']);
        $ks = Guru::create(['name' => 'Kepala Sekolah TNT', 'is_active' => true]);
        $bendahara = Guru::create(['name' => 'Bendahara TNT', 'is_active' => true]);
        $guru = Guru::create(['name' => 'Guru Peserta TNT', 'is_active' => true]);
        $bankAcc = GuruBankAccount::create([
            'guru_id' => $guru->id,
            'bank_name' => 'Bank Jatim',
            'account_number' => '1234567890',
            'account_holder' => 'Guru Peserta TNT',
            'is_primary' => true,
        ]);

        $perjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Dinas Koordinasi',
            'destination_name' => 'Kantor Cabdin',
            'departure_date' => '2026-06-10',
            'city' => 'Kediri',
            'kepala_sekolah_id' => $ks->id,
            'bendahara_id' => $bendahara->id,
            'status' => 'draft',
        ]);

        $item = PerjadinItem::create([
            'perjadin_id' => $perjadin->id,
            'guru_id' => $guru->id,
            'guru_bank_account_id' => $bankAcc->id,
            'amount' => 100000,
            'net_amount' => 100000,
            'is_paid' => false,
        ]);

        $pencairanBank = PencairanBank::create([
            'funding_source' => 'BOSP',
            'letter_number' => 'TNT/2026/001',
            'letter_date' => '2026-06-11',
            'bank_name' => 'Bank Jatim',
            'bank_branch' => 'Kediri',
            'bank_address' => 'Jl. Brawijaya',
            'source_account_number' => '00112233',
            'source_account_name' => 'BOS SMK',
            'kepala_sekolah_id' => $ks->id,
            'bendahara_id' => $bendahara->id,
            'status' => 'diajukan',
        ]);

        $bankItem = PencairanBankItem::create([
            'pencairan_bank_id' => $pencairanBank->id,
            'type' => 'perjadin',
            'recipient_name' => 'GURU PESERTA TNT',
            'bank_name' => 'Bank Jatim',
            'account_number' => '1234567890',
            'amount' => 100000,
            'description' => 'Perjadin',
        ]);

        $item->update(['pencairan_bank_item_id' => $bankItem->id]);
        $perjadin->update(['status' => 'diajukan_ke_bank']);

        $this->assertEquals('diajukan_ke_bank', $perjadin->status);
        $this->assertEquals(['TNT/2026/001'], $perjadin->tnt_letters->toArray());

        // Simulasi submit form edit
        $response = $this->actingAs($user)->put(route('bos.perjadin.update', $perjadin->id), [
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Dinas Koordinasi Diperbarui',
            'destination_name' => 'Kantor Cabdin Kediri',
            'departure_date' => '2026-06-10',
            'city' => 'Kediri',
            'kepala_sekolah_id' => $ks->id,
            'bendahara_id' => $bendahara->id,
            'payment_date' => '2026-06-12', // Input tanggal bayar tidak boleh memaksa status lunas
            'items' => [
                [
                    'id' => $item->id,
                    'guru_id' => $guru->id,
                    'guru_bank_account_id' => $bankAcc->id,
                    'amount' => 120000,
                    'net_amount' => 120000,
                    'ppn' => 0,
                    'pph23' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('bos.perjadin.show', $perjadin->id));

        $perjadin->refresh();
        $this->assertEquals('diajukan_ke_bank', $perjadin->status, 'Status harus tetap diajukan_ke_bank saat masih dalam proses TNT');
        $this->assertNull($perjadin->payment_date, 'payment_date harus tetap null sebelum TNT dicairkan bank');
        $this->assertEquals(['TNT/2026/001'], $perjadin->tnt_letters->toArray(), 'Nomor TNT tidak boleh hilang');

        $updatedItem = $perjadin->items()->first();
        $this->assertEquals($bankItem->id, $updatedItem->pencairan_bank_item_id, 'Relasi ke TNT item harus tetap terjaga');
        $this->assertEquals(120000.0, (float) $bankItem->fresh()->amount, 'Nominal di TNT item harus tersinkron');
    }

    public function test_import_perjadin_links_to_selected_kertas_kerja_item(): void
    {
        $kkFile = BosKertasKerjaFile::create([
            'filename' => '2026_kk.pdf',
            'year' => 2026,
            'is_active' => true,
        ]);

        $kkItem = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Januari',
            'account_code' => '5.1.02.04.01.0003',
            'program_code' => '01',
            'kegiatan' => 'Kegiatan Januari',
            'description' => 'Uang Transport Perjalanan dinas Januari',
            'volume' => 50,
            'unit' => 'oh',
            'price' => 65000,
            'total_amount' => 3250000,
        ]);

        $g = Guru::create(['name' => 'Guru Test KK Selection', 'is_active' => true]);

        $rows = collect([
            [
                'tanggal_yyyy_mm_dd' => '2026-01-20',
                'no_surat_opsional' => '094/01/2026',
                'tujuan_dinas' => 'Dinas Pendidikan Kediri',
                'maksud_perjalanan' => 'Koordinasi PMB',
                'nama_nip_guru' => 'Guru Test KK Selection',
                'uang_diterima_rp' => '', // kosong, harus otomatis ambil tarif KK (65.000)
            ],
        ]);

        $import = new PerjadinImport('BOSP', $kkItem->id);
        $import->collection($rows);

        $perjadin = Perjadin::where('destination_name', 'Dinas Pendidikan Kediri')->first();
        $this->assertNotNull($perjadin);
        $this->assertEquals($kkItem->id, $perjadin->bos_kertas_kerja_item_id);
        $this->assertEquals('5.1.02.04.01.0003', $perjadin->account_code);
        $this->assertEquals(65000.0, (float) $perjadin->items()->first()->amount);
    }

    public function test_pencairan_bank_create_page_has_default_letter_number_matching_active_year(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin_test@mkb.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['fiscal_year' => 2026])
            ->get(route('bos.pencairan-bank.create'));

        $response->assertOk();
        $response->assertSee('400.3.8.7/_____/SMK PGRI Kras/2026');
    }

    public function test_perjadin_datatable_does_not_show_badge_if_only_one_participant(): void
    {
        $user = User::create([
            'name' => 'Admin Test 2',
            'email' => 'admin_test2@mkb.test',
            'password' => bcrypt('password'),
        ]);

        $guru1 = Guru::create(['name' => 'Peserta Tunggal', 'is_active' => true]);
        $guru2 = Guru::create(['name' => 'Peserta Kedua', 'is_active' => true]);

        // Perjadin dengan 1 peserta
        $singlePerjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Tujuan Dinas Tunggal',
            'activity_purpose' => 'Kegiatan Tunggal',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-01',
            'status' => 'draft',
        ]);
        PerjadinItem::create([
            'perjadin_id' => $singlePerjadin->id,
            'guru_id' => $guru1->id,
            'amount' => 100000,
            'net_amount' => 100000,
        ]);

        // Perjadin dengan 2 peserta
        $multiPerjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Tujuan Dinas Bersama',
            'activity_purpose' => 'Kegiatan Bersama',
            'departure_date' => '2026-03-02',
            'return_date' => '2026-03-02',
            'status' => 'draft',
        ]);
        PerjadinItem::create([
            'perjadin_id' => $multiPerjadin->id,
            'guru_id' => $guru1->id,
            'amount' => 100000,
            'net_amount' => 100000,
        ]);
        PerjadinItem::create([
            'perjadin_id' => $multiPerjadin->id,
            'guru_id' => $guru2->id,
            'amount' => 100000,
            'net_amount' => 100000,
        ]);

        $response = $this->actingAs($user)->getJson(route('bos.perjadin.index', ['year' => 'all']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $response->assertOk();

        $data = collect($response->json('data'));

        $singleRow = $data->firstWhere('id', $singlePerjadin->id);
        $this->assertNotNull($singleRow);
        $this->assertStringNotContainsString('1 Orang', $singleRow['peserta_display']);
        $this->assertStringContainsString('Peserta Tunggal', $singleRow['peserta_display']);

        $multiRow = $data->firstWhere('id', $multiPerjadin->id);
        $this->assertNotNull($multiRow);
        $this->assertStringContainsString('2 Orang', $multiRow['peserta_display']);
        $this->assertStringContainsString('Peserta Tunggal', $multiRow['peserta_display']);
        $this->assertStringContainsString('Peserta Kedua', $multiRow['peserta_display']);
    }

    public function test_search_perjadin_by_sppd_and_bku_number(): void
    {
        $this->ensureDatabaseTables();

        $user = User::first() ?? User::factory()->create();

        $perjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Dinas Pendidikan Wilayah Kediri',
            'activity_purpose' => 'Koordinasi Kurikulum',
            'departure_date' => '2026-05-10',
            'return_date' => '2026-05-10',
            'bku_number' => 'BPU070 - BPU074 / 2026',
            'status' => 'draft',
        ]);

        $sppdCode = 'SPPD-'.str_pad($perjadin->id, 4, '0', STR_PAD_LEFT);

        // 1. Cari berdasarkan kode SPPD lengkap (misal SPPD-0001)
        $respSppd = $this->actingAs($user)->getJson(route('bos.perjadin.index', [
            'year' => 'all',
            'search' => ['value' => $sppdCode],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respSppd->assertOk();
        $this->assertTrue(collect($respSppd->json('data'))->contains('id', $perjadin->id));

        // 2. Cari berdasarkan keyword 'SPPD'
        $respWord = $this->actingAs($user)->getJson(route('bos.perjadin.index', [
            'year' => 'all',
            'search' => ['value' => 'sppd'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respWord->assertOk();
        $this->assertTrue(collect($respWord->json('data'))->contains('id', $perjadin->id));

        // 3. Cari nomor di tengah rentang BKU (BPU072)
        $respBkuMid = $this->actingAs($user)->getJson(route('bos.perjadin.index', [
            'year' => 'all',
            'search' => ['value' => 'BPU072'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respBkuMid->assertOk();
        $this->assertTrue(collect($respBkuMid->json('data'))->contains('id', $perjadin->id));

        // 4. Cari nomor BKU dengan spasi: 'BPU 070'
        $respBkuSpace = $this->actingAs($user)->getJson(route('bos.perjadin.index', [
            'year' => 'all',
            'search' => ['value' => 'BPU 070'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respBkuSpace->assertOk();
        $this->assertTrue(collect($respBkuSpace->json('data'))->contains('id', $perjadin->id));
    }

    public function test_perjadin_datatable_sorting(): void
    {
        $this->ensureDatabaseTables();

        $user = User::first() ?? User::factory()->create();

        $perjadin1 = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Lokasi A (Lama)',
            'activity_purpose' => 'Kegiatan Dinas Lama',
            'departure_date' => '2026-01-10',
            'return_date' => '2026-01-10',
            'status' => 'draft',
        ]);
        PerjadinItem::create([
            'perjadin_id' => $perjadin1->id,
            'amount' => 500000,
            'net_amount' => 500000,
        ]);

        $perjadin2 = Perjadin::create([
            'funding_source' => 'BOSP',
            'destination_name' => 'Lokasi B (Baru)',
            'activity_purpose' => 'Kegiatan Dinas Baru',
            'departure_date' => '2026-09-20',
            'return_date' => '2026-09-20',
            'status' => 'lunas',
        ]);
        PerjadinItem::create([
            'perjadin_id' => $perjadin2->id,
            'amount' => 100000,
            'net_amount' => 100000,
        ]);
        PerjadinItem::create([
            'perjadin_id' => $perjadin2->id,
            'amount' => 150000,
            'net_amount' => 150000,
        ]);

        // 1. Default sorting (departure_date desc): perjadin2 harus di atas perjadin1
        $respDefault = $this->actingAs($user)->getJson(route('bos.perjadin.index', [
            'year' => 'all',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respDefault->assertOk();
        $dataDefault = collect($respDefault->json('data'));
        $idx1 = $dataDefault->search(fn ($item) => $item['id'] === $perjadin1->id);
        $idx2 = $dataDefault->search(fn ($item) => $item['id'] === $perjadin2->id);
        $this->assertNotFalse($idx1);
        $this->assertNotFalse($idx2);
        $this->assertLessThan($idx1, $idx2, 'Perjadin dengan tanggal terbaru (perjadin2) harus berada di atas perjadin lama (perjadin1)');

        // 2. Sorting total_net_amount desc: perjadin1 (500k) harus di atas perjadin2 (250k)
        $respNominal = $this->actingAs($user)->getJson(route('bos.perjadin.index', [
            'year' => 'all',
            'columns' => [
                ['data' => 'nominal_display', 'name' => 'total_net_amount'],
            ],
            'order' => [
                ['column' => 0, 'dir' => 'desc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respNominal->assertOk();
        $dataNominal = collect($respNominal->json('data'));
        $nIdx1 = $dataNominal->search(fn ($item) => $item['id'] === $perjadin1->id);
        $nIdx2 = $dataNominal->search(fn ($item) => $item['id'] === $perjadin2->id);
        $this->assertLessThan($nIdx2, $nIdx1, 'Sorting total_net_amount desc harus menempatkan nominal lebih besar di atas');

        // 3. Sorting peserta desc: perjadin2 (2 orang) harus di atas perjadin1 (1 orang)
        $respPeserta = $this->actingAs($user)->getJson(route('bos.perjadin.index', [
            'year' => 'all',
            'columns' => [
                ['data' => 'peserta_display', 'name' => 'peserta'],
            ],
            'order' => [
                ['column' => 0, 'dir' => 'desc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respPeserta->assertOk();
        $dataPeserta = collect($respPeserta->json('data'));
        $pIdx1 = $dataPeserta->search(fn ($item) => $item['id'] === $perjadin1->id);
        $pIdx2 = $dataPeserta->search(fn ($item) => $item['id'] === $perjadin2->id);
        $this->assertLessThan($pIdx1, $pIdx2, 'Sorting peserta desc harus menempatkan jumlah peserta lebih banyak di atas');

        // 4. Sorting SPPD / BKU (id desc)
        $respBku = $this->actingAs($user)->getJson(route('bos.perjadin.index', [
            'year' => 'all',
            'columns' => [
                ['data' => 'bku_display', 'name' => 'bku_number'],
            ],
            'order' => [
                ['column' => 0, 'dir' => 'desc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respBku->assertOk();

        // 5. Sorting Status
        $respStatus = $this->actingAs($user)->getJson(route('bos.perjadin.index', [
            'year' => 'all',
            'columns' => [
                ['data' => 'status_display', 'name' => 'status'],
            ],
            'order' => [
                ['column' => 0, 'dir' => 'desc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respStatus->assertOk();
    }

    public function test_perjadin_import_handles_external_lookup_formulas_in_destination_column(): void
    {
        $guru = Guru::create([
            'name' => 'Guru Testing Formula',
            'is_active' => true,
        ]);

        $formulaDestination = '=IFERROR(__xludf.DUMMYFUNCTION("XLOOKUP( A:A, IMPORTRANGE(""https://docs.google.com/spreadsheets/d/1kB2iVIXpUXdAg3epSf6J1bZ4XRzPiQ32Y2H1rzw_lMM/edit?gid=1146774691#gid=1146774691"",""\'3 Monitoring 2\'!A:A""), IMPORTRANGE(""https://docs.google.com/spreadsheets/d/1kB2iVIXpUXdAg3epSf6"&"J1bZ4XRzPiQ32Y2H1rzw_lMM/edit?gid=1146774691#gid=1146774691"",""\'3 Monitoring 2\'!B:B"") ) "),"Bengkel Mandala Auto")';

        $rows = collect([
            [
                'tanggal_yyyy_mm_dd' => '2026-10-14',
                'no_surat_opsional' => '800.1.11.1/200/SMK PGRI Kras/2026',
                'tujuan_dinas' => $formulaDestination,
                'alamat_tujuan_opsional' => 'Ds. Kanigoro, Kec. Kras, Kab. Kediri',
                'maksud_perjalanan' => 'Monitoring PKL',
                'nama_nip_guru' => 'Guru Testing Formula',
                'uang_diterima_rp' => '100.000',
                'no_bku_awal_opsional' => 'BPU001',
                'catatan_opsional' => 'Diimpor dari file Excel',
            ],
        ]);

        $import = new PerjadinImport('BOSP');
        $import->collection($rows);

        $this->assertEmpty($import->getResults()['warnings']);
        $perjadin = Perjadin::where('destination_name', 'Bengkel Mandala Auto')->first();
        $this->assertNotNull($perjadin);
        $this->assertEquals('2026-10-14', $perjadin->departure_date->format('Y-m-d'));
        $this->assertEquals('Monitoring PKL', $perjadin->activity_purpose);
        $this->assertEquals('800.1.11.1/200/SMK PGRI Kras/2026', $perjadin->letter_number);
    }
}
