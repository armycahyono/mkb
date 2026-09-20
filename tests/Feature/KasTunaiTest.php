<?php

namespace Tests\Feature;

use App\Models\BosRencanaBelanja;
use App\Models\Guru;
use App\Models\KasTunai;
use App\Models\PencairanBank;
use App\Models\Perjadin;
use App\Models\PerjadinItem;
use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KasTunaiTest extends TestCase
{
    protected Guru $defaultGuru;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTablesExist();
        $this->defaultGuru = Guru::first() ?? Guru::create([
            'name' => 'Bendahara Sekolah',
            'is_active' => true,
        ]);
    }

    public function test_kas_tunai_categories_are_defined(): void
    {
        $categories = KasTunai::categories();

        $this->assertArrayHasKey('tarik_bank', $categories);
        $this->assertArrayHasKey('panjar_kerja', $categories);
        $this->assertArrayHasKey('sisa_panjar', $categories);
        $this->assertArrayHasKey('belanja_tunai', $categories);
        $this->assertEquals('masuk', $categories['tarik_bank']['type']);
        $this->assertEquals('keluar', $categories['panjar_kerja']['type']);
    }

    public function test_kas_tunai_store_masuk_and_keluar(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Catat kas masuk tarik bank
        $response = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Penarikan tunai dari Bank Jatim untuk operasional',
            'guru_id' => $this->defaultGuru->id,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $kas = KasTunai::where('category', 'tarik_bank')->where('amount', 5000000)->first();
        $this->assertNotNull($kas);
        $this->assertEquals('BOSP', $kas->funding_source);
        $this->assertEquals('none', $kas->panjar_status);
        $this->assertEquals('Tarik Bank (Droping Kas)', $kas->category_label);
        $this->assertEquals($this->defaultGuru->id, $kas->guru_id);

        // 2. Catat kas keluar belanja tunai
        $response2 = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 250000,
            'description' => 'Beli materai dan map arsip',
            'person_name' => 'Toko Alat Tulis',
            'guru_id' => $this->defaultGuru->id,
        ]);

        $response2->assertOk();
        $belanja = KasTunai::where('category', 'belanja_tunai')->where('amount', 250000)->first();
        $this->assertNotNull($belanja);
        $this->assertEquals('keluar', $belanja->transaction_type);
    }

    public function test_kas_tunai_panjar_kerja_lifecycle_with_sisa_kembalian(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 0. Droping kas awal dari bank Rp 5.000.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas Bank Jatim',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'none',
        ]);

        // 1. Guru mengambil panjar / kasbon Rp 1.000.000
        $response = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 1000000,
            'description' => 'Panjar belanja bahan perbaikan pintu kelas',
            'person_name' => 'Pak Joko',
            'guru_id' => $this->defaultGuru->id,
        ]);

        $response->assertOk();
        $panjar = KasTunai::where('category', 'panjar_kerja')->where('amount', 1000000)->first();
        $this->assertNotNull($panjar);
        $this->assertEquals('pending', $panjar->panjar_status);

        // 2. Selesaikan panjar: belanja riil Rp 850.000 (ada sisa kembalian Rp 150.000)
        $settleResponse = $this->actingAs($user)->post(route('bos.kas-tunai.settle-panjar', $panjar->id), [
            'settle_date' => '2026-09-10',
            'real_amount' => 850000,
            'evidence_number' => 'NOTA-BSI-01',
            'notes' => 'Beli engsel, cat, dan paku',
        ]);

        $settleResponse->assertOk();
        $settleResponse->assertJson(['success' => true]);

        // Verifikasi status panjar asal menjadi settled
        $panjar->refresh();
        $this->assertEquals('settled', $panjar->panjar_status);

        // Verifikasi transaksi belanja riil tercatat
        $belanjaRiil = KasTunai::where('parent_kas_id', $panjar->id)
            ->where('category', 'belanja_tunai')
            ->first();
        $this->assertNotNull($belanjaRiil);
        $this->assertEquals(850000, (float) $belanjaRiil->amount);
        $this->assertEquals('keluar', $belanjaRiil->transaction_type);

        // Verifikasi transaksi pengembalian sisa uang tercatat masuk ke kas
        $sisaKembalian = KasTunai::where('parent_kas_id', $panjar->id)
            ->where('category', 'sisa_panjar')
            ->first();
        $this->assertNotNull($sisaKembalian);
        $this->assertEquals(150000, (float) $sisaKembalian->amount);
        $this->assertEquals('masuk', $sisaKembalian->transaction_type);
    }

    public function test_kas_tunai_panjar_kerja_lifecycle_with_shortage_nombok(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 0. Droping kas awal dari bank Rp 5.000.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas Bank Jatim',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'none',
        ]);

        // 1. Panjar Rp 500.000
        $panjar = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 500000,
            'description' => 'Panjar belanja paku dan semen',
            'panjar_status' => 'pending',
        ]);

        // 2. Belanja nota Rp 550.000 (nombok Rp 50.000)
        $response = $this->actingAs($user)->post(route('bos.kas-tunai.settle-panjar', $panjar->id), [
            'settle_date' => '2026-09-10',
            'real_amount' => 550000,
            'notes' => 'Tambahan beli semen 1 sak',
        ]);

        $response->assertOk();
        $panjar->refresh();
        $this->assertEquals('settled', $panjar->panjar_status);

        // Ada reimburse keluar Rp 50.000
        $reimburse = KasTunai::where('parent_kas_id', $panjar->id)
            ->where('category', 'reimburse')
            ->first();
        $this->assertNotNull($reimburse);
        $this->assertEquals(50000, (float) $reimburse->amount);
        $this->assertEquals('keluar', $reimburse->transaction_type);
    }

    public function test_kas_tunai_funding_source_separation(): void
    {
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 3000000,
            'description' => 'Droping BOSP',
        ]);

        KasTunai::create([
            'funding_source' => 'BPOPP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 1500000,
            'description' => 'Droping BPOPP',
        ]);

        $this->assertCount(1, KasTunai::funding('BOSP')->where('amount', 3000000)->get());
        $this->assertCount(1, KasTunai::funding('BPOPP')->where('amount', 1500000)->get());
        $this->assertCount(0, KasTunai::funding('BOSP')->where('amount', 1500000)->get());
    }

    public function test_kas_tunai_print_and_opname_views(): void
    {
        $school = new SchoolProfile(['name' => 'SMK PGRI KRAS']);
        $transactions = collect([
            new KasTunai([
                'transaction_date' => now(),
                'transaction_type' => 'masuk',
                'category' => 'tarik_bank',
                'amount' => 1000000,
                'description' => 'Tarik Tunai',
            ]),
        ]);

        // 1. Print Kas
        $printHtml = view('kas_tunai.print', [
            'fundingSource' => 'BOSP',
            'school' => $school,
            'transactions' => $transactions,
            'month' => '9',
            'year' => '2026',
            'kepsekName' => 'ENDANG PATMOWATI, S.Pd',
            'bendaharaName' => 'ALIYA NUSAIBA, S.Pd.',
            'bendaharaTitle' => 'Bendahara BOSP',
        ])->render();

        $this->assertStringContainsString('BUKU PEMBANTU KAS TUNAI', $printHtml);
        $this->assertStringContainsString('Tarik Tunai', $printHtml);
        $this->assertStringContainsString('ENDANG PATMOWATI, S.Pd', $printHtml);

        // 2. Print Opname
        $opnameHtml = view('kas_tunai.print_opname', [
            'fundingSource' => 'BOSP',
            'school' => $school,
            'opnameDate' => '2026-09-09',
            'denominations' => ['100000' => 10, '50000' => 0, '20000' => 0, '10000' => 0, '5000' => 0, '2000' => 0, '1000' => 0, 'koin' => 0],
            'totalFisik' => 1000000,
            'saldoBuku' => 1000000,
            'selisih' => 0,
            'notes' => 'Kas cocok',
            'kepsekName' => 'ENDANG PATMOWATI, S.Pd',
            'bendaharaName' => 'ALIYA NUSAIBA, S.Pd.',
            'bendaharaTitle' => 'Bendahara BOSP',
        ])->render();

        $this->assertStringContainsString('BERITA ACARA PEMERIKSAAN KAS', $opnameHtml);
        $this->assertStringContainsString('NIHIL / COCOK', $opnameHtml);
    }

    public function test_kas_tunai_index_page_returns_ok_for_bosp_and_bpopp(): void
    {
        $user = User::first() ?? User::factory()->create();

        $resBosp = $this->actingAs($user)->get(route('bos.kas-tunai.index'));
        $resBosp->assertOk();
        $resBosp->assertSee('Buku Kas Tunai BOSP');

        $resBpopp = $this->actingAs($user)->get(route('bpopp.kas-tunai.index'));
        $resBpopp->assertOk();
        $resBpopp->assertSee('Buku Kas Tunai BPOPP');
    }

    public function test_panjar_pending_datatable_ajax(): void
    {
        $user = User::first() ?? User::factory()->create();

        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 500000,
            'description' => 'Panjar belanja',
            'person_name' => 'Budi',
            'panjar_status' => 'pending',
        ]);

        // Request with Ajax header and panjar_status = pending
        $response = $this->actingAs($user)->get(route('bos.kas-tunai.index', [
            'panjar_status' => 'pending',
            'year' => '2026',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'columns' => [
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'transaction_date', 'name' => 'transaction_date', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'person_display', 'name' => 'person_display', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'description', 'name' => 'description', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'amount', 'name' => 'amount', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'action', 'name' => 'action', 'searchable' => 'false', 'orderable' => 'false'],
            ],
            'order' => [['column' => 1, 'dir' => 'asc']],
            'search' => ['value' => 'Budi', 'regex' => 'false'],
        ]), ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
        $json = $response->json();
        $this->assertEquals(1, $json['recordsFiltered']);
        $this->assertStringContainsString('Budi', $json['data'][0]['person_display']);
    }

    public function test_kas_tunai_handles_thousand_separators_in_input(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Mengirim amount dengan format pemisah ribuan '2.500.000'
        $response = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => '2.500.000',
            'description' => 'Penarikan dengan separator',
            'guru_id' => $this->defaultGuru->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('kas_tunais', [
            'category' => 'tarik_bank',
            'amount' => 2500000,
        ]);
    }

    public function test_kas_tunai_can_link_to_tnt_bank_rencana_belanja_and_perjadin(): void
    {
        $user = User::first() ?? User::factory()->create();

        $tnt = PencairanBank::create([
            'funding_source' => 'BOSP',
            'letter_number' => 'TNT/2026/001',
            'letter_date' => '2026-09-09',
            'total_nominal' => 10000000,
        ]);

        $rb = BosRencanaBelanja::create([
            'title' => 'Pengadaan ATK Kantor',
            'no_nota' => 'RB-001',
            'total_amount' => 1500000,
            'supplier_name' => 'Toko Subur',
        ]);

        $perjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Rakor Kurikulum Merdeka',
        ]);

        $perjadinItem = PerjadinItem::create([
            'perjadin_id' => $perjadin->id,
            'receipt_number' => 'KWT-PJ-001',
            'amount' => 500000,
            'net_amount' => 500000,
        ]);

        // 1. Kas masuk dari TNT dan terhubung ke Rencana Belanja
        $resMasuk = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => '1.500.000',
            'description' => 'Droping kas untuk nota RB-001',
            'pencairan_bank_id' => $tnt->id,
            'bos_rencana_belanja_id' => $rb->id,
            'guru_id' => $this->defaultGuru->id,
        ]);
        $resMasuk->assertOk();

        // 2. Kas keluar terhubung ke Perjadin
        $resKeluar = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => 'perjadin_tunai',
            'amount' => '500.000',
            'description' => 'Pembayaran perjadin rakor',
            'perjadin_item_id' => $perjadinItem->id,
            'pencairan_bank_id' => $tnt->id,
            'guru_id' => $this->defaultGuru->id,
        ]);
        $resKeluar->assertOk();

        $this->assertDatabaseHas('kas_tunais', [
            'pencairan_bank_id' => $tnt->id,
            'bos_rencana_belanja_id' => $rb->id,
            'amount' => 1500000,
        ]);

        $this->assertDatabaseHas('kas_tunais', [
            'pencairan_bank_id' => $tnt->id,
            'perjadin_item_id' => $perjadinItem->id,
            'amount' => 500000,
        ]);
    }

    public function test_kas_tunai_get_available_documents_endpoint_filters_properly(): void
    {
        $user = User::first() ?? User::factory()->create();

        $tnt = PencairanBank::create([
            'funding_source' => 'BOSP',
            'letter_number' => 'TNT/2026/DOC-01',
            'letter_date' => '2026-09-09',
            'total_nominal' => 5000000,
        ]);

        $rb = BosRencanaBelanja::create([
            'title' => 'Pembelian Kertas HVS',
            'no_nota' => 'RB-DOC-01',
            'year' => '2026',
            'total_amount' => 800000,
            'supplier_name' => 'Toko Alat Tulis',
        ]);

        $perjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'departure_date' => '2026-09-09',
            'activity_purpose' => 'Workshop IT',
        ]);

        $perjadinItem = PerjadinItem::create([
            'perjadin_id' => $perjadin->id,
            'receipt_number' => 'KWT-DOC-01',
            'amount' => 450000,
            'net_amount' => 450000,
            'is_paid' => false,
        ]);

        // 1. Panggil endpoint available-documents tahun 2026
        $res = $this->actingAs($user)->get(route('bos.kas-tunai.available-documents', [
            'year' => 2026,
            'include_settled' => 0,
        ]));

        $res->assertOk();
        $data = $res->json();

        $this->assertTrue(collect($data['pencairan_banks'])->contains('id', $tnt->id));
        $this->assertTrue(collect($data['rencana_belanjas'])->contains('id', $rb->id));
        $this->assertTrue(collect($data['perjadin_items'])->contains('id', $perjadinItem->id));

        // 2. Tandai perjadin sebagai lunas lewat KasTunai keluar
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => 'perjadin_tunai',
            'amount' => 450000,
            'description' => 'Bayar Perjadin',
            'perjadin_item_id' => $perjadinItem->id,
        ]);

        // Saat filter include_settled = 0, perjadinItem tidak boleh muncul lagi
        $resAfterPaid = $this->actingAs($user)->get(route('bos.kas-tunai.available-documents', [
            'year' => 2026,
            'include_settled' => 0,
        ]));
        $this->assertFalse(collect($resAfterPaid->json('perjadin_items'))->contains('id', $perjadinItem->id));

        // Saat filter include_settled = 1, perjadinItem harus muncul kembali
        $resIncludeSettled = $this->actingAs($user)->get(route('bos.kas-tunai.available-documents', [
            'year' => 2026,
            'include_settled' => 1,
        ]));
        $this->assertTrue(collect($resIncludeSettled->json('perjadin_items'))->contains('id', $perjadinItem->id));
    }

    public function test_kas_tunai_settle_panjar_links_documents_and_marks_perjadin_paid(): void
    {
        $user = User::first() ?? User::factory()->create();

        $perjadin = Perjadin::create([
            'funding_source' => 'BOSP',
            'activity_purpose' => 'Kunjungan Industri',
        ]);

        $perjadinItem = PerjadinItem::create([
            'perjadin_id' => $perjadin->id,
            'receipt_number' => 'KWT-KI-001',
            'amount' => 300000,
            'net_amount' => 300000,
            'is_paid' => false,
        ]);

        $panjar = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 300000,
            'panjar_status' => 'pending',
            'description' => 'Kasbon kunjungan',
        ]);

        $res = $this->actingAs($user)->post(route('bos.kas-tunai.settle-panjar', $panjar->id), [
            'settle_date' => '2026-09-10',
            'real_amount' => '300.000',
            'perjadin_item_id' => $perjadinItem->id,
            'notes' => 'Selesai SPJ kunjungan industri',
        ]);

        $res->assertOk();
        $this->assertEquals(1, $perjadinItem->fresh()->is_paid);
    }

    public function test_kas_tunai_accumulates_past_year_balance_and_unsettled_panjar(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Tahun 2025: Penerimaan Rp 5.000.000, Belanja Rp 2.000.000, Panjar pending Rp 500.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2025-05-10',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping 2025',
        ]);

        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2025-06-15',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 2000000,
            'description' => 'Belanja ATK 2025',
        ]);

        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2025-11-20',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 500000,
            'description' => 'Kasbon Perbaikan Pompa 2025',
            'panjar_status' => 'pending',
            'person_name' => 'Pak Joko',
        ]);

        // Sisa kas akhir 2025 = 5.000.000 - 2.000.000 - 500.000 = 2.500.000

        // 2. Tahun 2026: Penerimaan kas baru Rp 1.000.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-02-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 1000000,
            'description' => 'Droping 2026',
        ]);

        // Akses index kas tunai dengan filter tahun 2026
        $res = $this->actingAs($user)->get(route('bos.kas-tunai.index', ['year' => 2026]));
        $res->assertOk();

        // Saldo kas 2026 harus mengakumulasikan saldo awal 2025 (2.500.000 + 1.000.000 = 3.500.000)
        $res->assertViewHas('saldoAwal', 2500000.0);
        $res->assertViewHas('totalMasuk', 1000000.0);
        $res->assertViewHas('totalKeluar', 0.0);
        $res->assertViewHas('saldoKas', 3500000.0);

        // Kasbon pending dari 2025 tetap dihitung di tahun 2026
        $res->assertViewHas('totalPanjarPending', 500000.0);
        $res->assertViewHas('countPanjarPending', 1);
        $res->assertViewHas('kasSiapPakai', 3000000.0);

        // 3. Test DataTables tab Kasbon Pending di tahun 2026 harus menampilkan panjar dari 2025
        $ajaxRes = $this->actingAs($user)->get(route('bos.kas-tunai.index', [
            'panjar_status' => 'pending',
            'year' => 2026,
        ]), ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $ajaxRes->assertOk();
        $json = $ajaxRes->json();
        $this->assertEquals(1, $json['recordsTotal']);
        $this->assertStringContainsString('Kasbon Perbaikan Pompa 2025', $json['data'][0]['description']);
    }

    public function test_kas_tunai_store_with_receipt_items(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Droping kas awal Rp 5.000.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas Bank Jatim',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'none',
        ]);

        $items = [
            [
                'item_name' => 'Kabel NYA 1.5mm',
                'quantity' => 2,
                'unit' => 'roll',
                'price' => 150000,
                'total_price' => 300000,
                'notes' => 'Warna biru',
            ],
            [
                'item_name' => 'Saklar Tunggal Broco',
                'quantity' => 5,
                'unit' => 'pcs',
                'price' => 15000,
                'total_price' => 75000,
                'notes' => '',
            ],
        ];

        $response = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 375000,
            'description' => 'Belanja alat listrik praktikum',
            'person_name' => 'Toko Terang Jaya',
            'evidence_number' => 'NOTA-TJ-101',
            'guru_id' => $this->defaultGuru->id,
            'items' => $items,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $kas = KasTunai::where('evidence_number', 'NOTA-TJ-101')->first();
        $this->assertNotNull($kas);
        $this->assertEquals(375000, (float) $kas->amount);
        $this->assertCount(2, $kas->items);

        $firstItem = $kas->items()->where('item_name', 'Kabel NYA 1.5mm')->first();
        $this->assertNotNull($firstItem);
        $this->assertEquals(2, (float) $firstItem->quantity);
        $this->assertEquals('roll', $firstItem->unit);
        $this->assertEquals(150000, (float) $firstItem->price);
        $this->assertEquals(300000, (float) $firstItem->total_price);
    }

    public function test_kas_tunai_store_with_formatted_currency_items(): void
    {
        $user = User::first() ?? User::factory()->create();

        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 10000000,
            'description' => 'Droping Kas Bank',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'none',
        ]);

        $items = [
            [
                'item_name' => 'Semen Tiga Roda',
                'quantity' => '1',
                'unit' => 'sak',
                'price' => '50.000',
                'total_price' => '50.000',
                'notes' => 'Sak 50kg',
            ],
            [
                'item_name' => 'Mesin Potong Rumput',
                'quantity' => '1',
                'unit' => 'unit',
                'price' => '2.000.000',
                'total_price' => '2.000.000',
                'notes' => 'Tipe 2 Tak',
            ],
            [
                'item_name' => 'Batako Semen',
                'quantity' => '50',
                'unit' => 'buah',
                'price' => '6.000',
                'total_price' => '300.000',
                'notes' => '',
            ],
        ];

        $response = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => '2.350.000',
            'description' => 'Belanja material dan mesin',
            'person_name' => 'Toko Bangunan Sejahtera',
            'evidence_number' => 'NOTA-SEJAHTERA-999',
            'guru_id' => $this->defaultGuru->id,
            'items' => $items,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $kas = KasTunai::where('evidence_number', 'NOTA-SEJAHTERA-999')->first();
        $this->assertNotNull($kas);
        $this->assertEquals(2350000, (float) $kas->amount);
        $this->assertCount(3, $kas->items);

        $semen = $kas->items()->where('item_name', 'Semen Tiga Roda')->first();
        $this->assertEquals(50000, (float) $semen->price);
        $this->assertEquals(50000, (float) $semen->total_price);

        $mesin = $kas->items()->where('item_name', 'Mesin Potong Rumput')->first();
        $this->assertEquals(2000000, (float) $mesin->price);
        $this->assertEquals(2000000, (float) $mesin->total_price);

        $batako = $kas->items()->where('item_name', 'Batako Semen')->first();
        $this->assertEquals(6000, (float) $batako->price);
        $this->assertEquals(300000, (float) $batako->total_price);
    }

    public function test_kas_tunai_store_various_categories_with_contextual_payload(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Droping Sekolah (tanpa person_name, tanpa items, tanpa rencana belanja)
        $respDroping = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'masuk',
            'category' => 'droping_sekolah',
            'amount' => 5000000,
            'description' => 'Droping kas awal dari sekolah',
            'guru_id' => $this->defaultGuru->id,
            'evidence_number' => 'DROP-001',
        ]);
        $respDroping->assertOk();
        $respDroping->assertJson(['success' => true]);

        $droping = KasTunai::where('evidence_number', 'DROP-001')->first();
        $this->assertNotNull($droping);
        $this->assertNull($droping->person_name);
        $this->assertCount(0, $droping->items);

        // 2. Kasbon / Panjar Kerja (tanpa items, tanpa person_name)
        $respPanjar = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-11',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 500000,
            'description' => 'Kasbon belanja bahan praktik siswa',
            'guru_id' => $this->defaultGuru->id,
            'evidence_number' => 'BON-001',
        ]);
        $respPanjar->assertOk();
        $respPanjar->assertJson(['success' => true]);

        $panjar = KasTunai::where('evidence_number', 'BON-001')->first();
        $this->assertNotNull($panjar);
        $this->assertEquals('pending', $panjar->panjar_status);
        $this->assertCount(0, $panjar->items);

        // 3. Honor Tim (tanpa person_name, tanpa items)
        $respHonor = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-12',
            'transaction_type' => 'keluar',
            'category' => 'honor_tim',
            'amount' => 300000,
            'description' => 'Honor tim pengelola BOS',
            'guru_id' => $this->defaultGuru->id,
            'evidence_number' => 'HONOR-001',
        ]);
        $respHonor->assertOk();
        $respHonor->assertJson(['success' => true]);

        $honor = KasTunai::where('evidence_number', 'HONOR-001')->first();
        $this->assertNotNull($honor);
        $this->assertNull($honor->person_name);
        $this->assertCount(0, $honor->items);
    }

    public function test_kas_tunai_settle_panjar_with_receipt_items(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Kasbon awal Rp 500.000
        $panjar = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 500000,
            'description' => 'Kasbon belanja ATK',
            'panjar_status' => 'pending',
        ]);

        // 2. Settle dengan rincian nota belanja Rp 400.000 (sisa Rp 100.000)
        $items = [
            [
                'item_name' => 'Kertas HVS A4 80gr',
                'quantity' => 5,
                'unit' => 'rim',
                'price' => '55.000',
                'total_price' => '275.000',
                'notes' => 'PaperOne',
            ],
            [
                'item_name' => 'Tinta Spidol Snowman',
                'quantity' => 5,
                'unit' => 'botol',
                'price' => '25.000',
                'total_price' => '125.000',
                'notes' => 'Hitam',
            ],
        ];

        $response = $this->actingAs($user)->post(route('bos.kas-tunai.settle-panjar', $panjar->id), [
            'settle_date' => '2026-09-10',
            'real_amount' => '400.000',
            'evidence_number' => 'NOTA-ATK-55',
            'notes' => 'Belanja ATK kantor',
            'items' => $items,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Verifikasi belanja riil memiliki item nota yang tersimpan
        $belanjaRiil = KasTunai::where('parent_kas_id', $panjar->id)
            ->where('category', 'belanja_tunai')
            ->first();

        $this->assertNotNull($belanjaRiil);
        $this->assertEquals(400000, (float) $belanjaRiil->amount);
        $this->assertCount(2, $belanjaRiil->items);

        $first = $belanjaRiil->items()->where('item_name', 'Kertas HVS A4 80gr')->first();
        $this->assertNotNull($first);
        $this->assertEquals(55000, (float) $first->price);
        $this->assertEquals(275000, (float) $first->total_price);
    }

    public function test_kas_tunai_get_items_endpoint(): void
    {
        $user = User::first() ?? User::factory()->create();

        $kas = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 150000,
            'description' => 'Belanja cat tembok',
            'evidence_number' => 'NOTA-CAT-01',
        ]);

        $kas->items()->create([
            'item_name' => 'Cat Tembok Putih 5kg',
            'quantity' => 1,
            'unit' => 'pail',
            'price' => 150000,
            'total_price' => 150000,
            'notes' => 'Dulux',
        ]);

        $response = $this->actingAs($user)->get(route('bos.kas-tunai.items', $kas->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'kas_tunai' => ['id', 'transaction_date', 'category_label', 'amount', 'formatted_amount', 'description'],
            'items' => [
                '*' => ['index', 'id', 'item_name', 'quantity', 'unit', 'price', 'formatted_price', 'total_price', 'formatted_total_price', 'notes'],
            ],
            'total_items_amount',
            'formatted_total_items_amount',
        ]);

        $json = $response->json();
        $this->assertCount(1, $json['items']);
        $this->assertEquals('Cat Tembok Putih 5kg', $json['items'][0]['item_name']);
        $this->assertEquals(150000, $json['total_items_amount']);
    }

    public function test_kasbon_page_renders_with_aging_tracker_and_person_summary(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Create pending panjar records
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => now()->subDays(2)->format('Y-m-d'),
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 500000,
            'description' => 'Panjar beli konsumsi rapat',
            'person_name' => 'Bu Siti',
            'panjar_status' => 'pending',
        ]);

        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => now()->subDays(10)->format('Y-m-d'),
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 750000,
            'description' => 'Talangan belanja toko offline',
            'person_name' => 'Pak Ahmad',
            'panjar_status' => 'pending',
        ]);

        // Test BOS route
        $responseBos = $this->actingAs($user)->get(route('bos.kas-tunai.kasbon'));
        $responseBos->assertOk();
        $responseBos->assertSee('Tanggungan Kasbon');
        $responseBos->assertSee('Bu Siti');
        $responseBos->assertSee('Pak Ahmad');
        $responseBos->assertSee('500.000');
        $responseBos->assertSee('750.000');

        // Test BPOPP route
        $responseBpopp = $this->actingAs($user)->get(route('bpopp.kas-tunai.kasbon'));
        $responseBpopp->assertOk();
        $responseBpopp->assertSee('Tanggungan Kasbon');
    }

    public function test_kasbon_page_renders_row_click_history_and_payments(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Kasbon awal
        $parentKasbon = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => now()->subDays(5)->format('Y-m-d'),
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 1000000,
            'description' => 'Kasbon Kegiatan Lomba Robotik',
            'person_name' => 'Bapak Joko Santoso',
            'panjar_status' => 'partial',
            'created_by' => $user->id,
        ]);

        // 2. Pembayaran SPJ tahap 1 (Belanja Nota)
        $child1 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => now()->subDays(3)->format('Y-m-d'),
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 600000,
            'description' => 'Nota Pembelian Komponen Sensor',
            'person_name' => 'Bapak Joko Santoso',
            'parent_kas_id' => $parentKasbon->id,
            'panjar_status' => 'settled',
            'evidence_number' => 'NOTA-ROBOT-01',
            'created_by' => $user->id,
        ]);

        // 3. Pembayaran SPJ tahap 2 (Pengembalian Sisa Uang)
        $child2 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => now()->subDays(2)->format('Y-m-d'),
            'transaction_type' => 'masuk',
            'category' => 'sisa_panjar',
            'amount' => 150000,
            'description' => 'Pengembalian sisa kasbon lomba',
            'person_name' => 'Bapak Joko Santoso',
            'parent_kas_id' => $parentKasbon->id,
            'panjar_status' => 'settled',
            'evidence_number' => 'KWT-KEMBALI-01',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('bos.kas-tunai.kasbon'));
        $response->assertOk();

        // Cek baris utama kasbon
        $response->assertSee('Bapak Joko Santoso');
        $response->assertSee('Kasbon Kegiatan Lomba Robotik');
        $response->assertSee('kasbon-row');
        $response->assertSee('kasbon-detail-row');
        $response->assertSee('kasbonDetailRow-'.$parentKasbon->id);

        // Cek riwayat kasbon & pembayaran di dalam sub-baris
        $response->assertSee('Riwayat Kasbon & Pembayaran', false);
        $response->assertSee('Nota Pembelian Komponen Sensor');
        $response->assertSee('NOTA-ROBOT-01');
        $response->assertSee('Pengembalian sisa kasbon lomba');
        $response->assertSee('KWT-KEMBALI-01');
        $response->assertSee('600.000');
        $response->assertSee('150.000');
        // Sisa tanggungan: 1.000.000 - 750.000 = 250.000
        $response->assertSee('250.000');
    }

    public function test_belanja_barang_page_and_datatables_data_endpoint(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Create a belanja transaction with items
        $kas = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => date('Y-m-d'),
            'transaction_type' => 'keluar',
            'category' => 'talangan_belanja',
            'amount' => 350000,
            'description' => 'Belanja ATK dan Kertas',
            'person_name' => 'Toko Barokah',
            'evidence_number' => 'NOTA-778',
            'panjar_status' => 'none',
        ]);

        $kas->items()->create([
            'item_name' => 'Kertas HVS A4 80gr',
            'quantity' => 5,
            'unit' => 'rim',
            'price' => 55000,
            'total_price' => 275000,
            'notes' => 'Sinar Dunia',
        ]);

        $kas->items()->create([
            'item_name' => 'Spidol Whiteboard',
            'quantity' => 10,
            'unit' => 'pcs',
            'price' => 7500,
            'total_price' => 75000,
            'notes' => 'Hitam snowman',
        ]);

        // Test BOS index page
        $responsePage = $this->actingAs($user)->get(route('bos.kas-tunai.belanja-barang'));
        $responsePage->assertOk();
        $responsePage->assertSee('Rekap Belanja Barang Tunai BOSP', false);
        $responsePage->assertSee('Buku Kas Tunai');
        $responsePage->assertSee('Tanggungan Kasbon');

        // Test DataTables AJAX endpoint
        $responseAjax = $this->actingAs($user)->getJson(route('bos.kas-tunai.belanja-barang.data'));
        $responseAjax->assertOk();
        $json = $responseAjax->json();
        $this->assertArrayHasKey('data', $json);
        $this->assertGreaterThanOrEqual(2, count($json['data']));

        // Find created items in data
        $foundKertas = false;
        foreach ($json['data'] as $row) {
            if (str_contains($row['item_name_formatted'], 'Kertas HVS A4 80gr')) {
                $foundKertas = true;
                $this->assertStringContainsString('5 rim', $row['quantity_formatted']);
                $this->assertStringContainsString('275.000', $row['total_price_formatted']);
                break;
            }
        }
        $this->assertTrue($foundKertas, 'Kertas HVS should be found in DataTables response');

        // Test BPOPP index page
        $responseBpopp = $this->actingAs($user)->get(route('bpopp.kas-tunai.belanja-barang'));
        $responseBpopp->assertOk();
        $responseBpopp->assertSee('Rekap Belanja Barang Tunai BPOPP', false);
    }

    public function test_kas_tunai_edit_returns_json_details(): void
    {
        $user = User::first() ?? User::factory()->create();

        $kas = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 150000,
            'description' => 'Beli spidol whiteboard',
            'person_name' => 'Toko Buku ABC',
            'evidence_number' => 'NOTA-123',
        ]);

        $kas->items()->create([
            'item_name' => 'Spidol Hitam',
            'quantity' => 10,
            'unit' => 'pcs',
            'price' => 15000,
            'total_price' => 150000,
        ]);

        $response = $this->actingAs($user)->get(route('bos.kas-tunai.edit', $kas->id));

        $response->assertOk();
        $response->assertJson([
            'id' => $kas->id,
            'funding_source' => 'BOSP',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 150000,
            'description' => 'Beli spidol whiteboard',
            'person_name' => 'Toko Buku ABC',
            'evidence_number' => 'NOTA-123',
        ]);
        $response->assertJsonCount(1, 'items');
    }

    public function test_kas_tunai_update_modifies_record_and_items(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Droping kas awal Rp 5.000.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas Bank Jatim',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'none',
        ]);

        $kas = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-09',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 100000,
            'description' => 'Beli kertas',
            'person_name' => 'Toko Lama',
        ]);

        $kas->items()->create([
            'item_name' => 'Kertas A4',
            'quantity' => 2,
            'unit' => 'rim',
            'price' => 50000,
            'total_price' => 100000,
        ]);

        $response = $this->actingAs($user)->put(route('bos.kas-tunai.update', $kas->id), [
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 160000,
            'description' => 'Beli kertas dan tinta revisi',
            'person_name' => 'Toko Baru Makmur',
            'guru_id' => $this->defaultGuru->id,
            'evidence_number' => 'INV-REV-99',
            'items' => [
                [
                    'item_name' => 'Kertas F4',
                    'quantity' => 2,
                    'unit' => 'rim',
                    'price' => 55000,
                    'total_price' => 110000,
                ],
                [
                    'item_name' => 'Tinta Refill',
                    'quantity' => 1,
                    'unit' => 'botol',
                    'price' => 50000,
                    'total_price' => 50000,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $kas->refresh();
        $this->assertEquals('2026-09-10', $kas->transaction_date->format('Y-m-d'));
        $this->assertEquals(160000, (float) $kas->amount);
        $this->assertEquals('Beli kertas dan tinta revisi', $kas->description);
        $this->assertEquals('Toko Baru Makmur', $kas->person_name);
        $this->assertEquals('INV-REV-99', $kas->evidence_number);

        $this->assertCount(2, $kas->items);
        $this->assertEquals('Kertas F4', $kas->items[0]->item_name);
        $this->assertEquals(110000, (float) $kas->items[0]->total_price);
        $this->assertEquals('Tinta Refill', $kas->items[1]->item_name);
        $this->assertEquals(50000, (float) $kas->items[1]->total_price);
    }

    public function test_kas_tunai_update_allows_placeholder_evidence_numbers(): void
    {
        $user = User::first() ?? User::factory()->create();

        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas Bank',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'none',
        ]);

        $kas1 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-02',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 100000,
            'description' => 'Kasbon 1',
            'guru_id' => $this->defaultGuru->id,
            'evidence_number' => '0',
            'panjar_status' => 'pending',
        ]);

        $kas2 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-03',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 150000,
            'description' => 'Kasbon 2',
            'guru_id' => $this->defaultGuru->id,
            'evidence_number' => '0',
            'panjar_status' => 'pending',
        ]);

        // Mengupdate kas2 dengan nomor bukti placeholder '0' tidak boleh dicekal duplikat
        $response = $this->actingAs($user)->put(route('bos.kas-tunai.update', $kas2->id), [
            'transaction_date' => '2026-09-03',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 150000,
            'description' => 'Kasbon 2 Diperbarui',
            'guru_id' => $this->defaultGuru->id,
            'evidence_number' => '0',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_kas_tunai_requires_guru_penanggung_jawab_and_displays_store_name(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Droping kas awal Rp 5.000.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas Bank Jatim',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'none',
        ]);

        // 1. Gagal jika tanpa guru_id
        $failResponse = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 100000,
            'description' => 'Beli spidol tanpa guru',
            'person_name' => 'Toko Buku Pintar',
        ]);
        $failResponse->assertSessionHasErrors(['guru_id']);

        // 2. Berhasil jika dengan guru_id
        $guru = Guru::create([
            'name' => 'ARMY WIDHI CAHYONO',
            'title_suffix' => 'S.Pd.',
            'is_active' => true,
        ]);

        $successResponse = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 150000,
            'description' => 'Beli spidol dan penghapus',
            'guru_id' => $guru->id,
            'person_name' => 'Toko Buku Pintar',
        ]);

        $successResponse->assertOk();
        $successResponse->assertJson(['success' => true]);

        $created = KasTunai::where('description', 'Beli spidol dan penghapus')->first();
        $this->assertNotNull($created);
        $this->assertEquals($guru->id, $created->guru_id);
        $this->assertEquals('Toko Buku Pintar', $created->person_name);

        // 3. DataTable person_display menampilkan nama guru dan nama toko
        $ajaxResponse = $this->actingAs($user)->get(route('bos.kas-tunai.index', [
            'search' => ['value' => 'Beli spidol dan penghapus'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $ajaxResponse->assertOk();
        $data = $ajaxResponse->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('ARMY WIDHI CAHYONO', $data[0]['person_display']);
        $this->assertStringContainsString('Toko Buku Pintar', $data[0]['person_display']);
    }

    public function test_kas_tunai_sisa_panjar_links_parent_and_marks_panjar_settled(): void
    {
        $user = User::first() ?? User::factory()->create();
        $guru = Guru::create([
            'name' => 'SITI NURHALIZA',
            'title_suffix' => 'M.Pd.',
            'is_active' => true,
        ]);

        // Buat kasbon awal (pending)
        $parentPanjar = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 500000,
            'description' => 'Kasbon Kegiatan Lomba',
            'guru_id' => $guru->id,
            'panjar_status' => 'pending',
            'created_by' => $user->id,
        ]);

        $this->assertEquals('pending', $parentPanjar->fresh()->panjar_status);

        // Catat kembalian sisa kasbon melalui controller
        $res = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-05',
            'transaction_type' => 'masuk',
            'category' => 'sisa_panjar',
            'amount' => 50000,
            'description' => 'Kembalian sisa kasbon #'.$parentPanjar->id,
            'guru_id' => $guru->id,
            'parent_kas_id' => $parentPanjar->id,
        ]);

        $res->assertOk();
        $res->assertJson(['success' => true]);

        $kembalian = KasTunai::where('parent_kas_id', $parentPanjar->id)->first();
        $this->assertNotNull($kembalian);
        $this->assertEquals('sisa_panjar', $kembalian->category);
        $this->assertEquals(50000, $kembalian->amount);

        // Parent kasbon harus otomatis settled
        $this->assertEquals('settled', $parentPanjar->fresh()->panjar_status);
    }

    public function test_kas_tunai_bayar_talangan_links_parent_dana_talangan(): void
    {
        $user = User::first() ?? User::factory()->create();
        $guru = Guru::create([
            'name' => 'BENDAHARA SEKOLAH',
            'title_suffix' => 'S.E.',
            'is_active' => true,
        ]);

        // Buat penerimaan dana talangan awal
        $parentTalangan = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-02',
            'transaction_type' => 'masuk',
            'category' => 'dana_talangan_masuk',
            'amount' => 1000000,
            'description' => 'Penerimaan dana talangan operasional',
            'guru_id' => $guru->id,
            'created_by' => $user->id,
        ]);

        // Catat pengembalian dana talangan
        $res = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-08',
            'transaction_type' => 'keluar',
            'category' => 'bayar_talangan',
            'amount' => 1000000,
            'description' => 'Pengembalian dana talangan #'.$parentTalangan->id,
            'guru_id' => $guru->id,
            'parent_kas_id' => $parentTalangan->id,
        ]);

        $res->assertOk();
        $res->assertJson(['success' => true]);

        $bayar = KasTunai::where('parent_kas_id', $parentTalangan->id)->first();
        $this->assertNotNull($bayar);
        $this->assertEquals('bayar_talangan', $bayar->category);
        $this->assertEquals(1000000, $bayar->amount);
        $this->assertEquals($parentTalangan->id, $bayar->parent_kas_id);
    }

    public function test_kas_tunai_id_display_attribute_and_datatables(): void
    {
        $user = User::first() ?? User::factory()->create();

        $kas = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-11',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 125000,
            'description' => 'Pembelian ATK Tes ID',
        ]);

        $this->assertEquals(2026, $kas->year);
        $this->assertEquals('KT26-'.str_pad((string) $kas->sequence, 4, '0', STR_PAD_LEFT), $kas->id_display);

        $response = $this->actingAs($user)->getJson(route('bos.kas-tunai.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        $found = collect($json['data'])->firstWhere('id', $kas->id);
        $this->assertNotNull($found);
        $this->assertArrayHasKey('id_display', $found);
        $this->assertStringContainsString('KT26-', $found['id_display']);
    }

    public function test_kas_tunai_id_yearly_sequence_reset_and_format(): void
    {
        // 1. Buat transaksi di tahun 2025
        $kas2025_1 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2025-03-10',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 1000000,
            'description' => 'Tarik bank 2025 ke-1',
        ]);
        $kas2025_2 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2025-05-15',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 200000,
            'description' => 'Belanja 2025 ke-2',
        ]);

        $this->assertEquals(2025, $kas2025_1->year);
        $this->assertEquals(1, $kas2025_1->sequence);
        $this->assertEquals('KT25-0001', $kas2025_1->id_display);

        $this->assertEquals(2025, $kas2025_2->year);
        $this->assertEquals(2, $kas2025_2->sequence);
        $this->assertEquals('KT25-0002', $kas2025_2->id_display);

        // 2. Buat transaksi di tahun 2026 (nomor sequence reset mulai dari 1 lagi)
        $kas2026_1 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-01-02',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 3000000,
            'description' => 'Tarik bank 2026 ke-1',
        ]);
        $kas2026_2 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-02-10',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 500000,
            'description' => 'Belanja 2026 ke-2',
        ]);

        $this->assertEquals(2026, $kas2026_1->year);
        $this->assertEquals(1, $kas2026_1->sequence);
        $this->assertEquals('KT26-0001', $kas2026_1->id_display);

        $this->assertEquals(2026, $kas2026_2->year);
        $this->assertEquals(2, $kas2026_2->sequence);
        $this->assertEquals('KT26-0002', $kas2026_2->id_display);
    }

    public function test_kas_tunai_settle_panjar_three_components_nota_lain_sisa(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Kasbon Rp 1.500.000
        $panjar = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-11',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 1500000,
            'description' => 'Panjar perbaikan atap ruang guru',
            'panjar_status' => 'pending',
            'person_name' => 'Pak Budi Tukang',
        ]);

        // Settle: Nota 1.250.000, Biaya lain 50.000, Sisa uang tunai 200.000 (Total = 1.500.000)
        $response = $this->actingAs($user)->post(route('bos.kas-tunai.settle-panjar', $panjar->id), [
            'settle_date' => '2026-09-12',
            'nota_amount' => 1250000,
            'other_amount' => 50000,
            'other_notes' => 'Tips angkut barang & parkir mobil',
            'cash_return_amount' => 200000,
            'evidence_number' => 'NOTA-TB-099',
            'notes' => 'Selesai belanja material seng dan paku',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $panjar->refresh();
        $this->assertEquals('settled', $panjar->panjar_status);
        $this->assertEquals(0, $panjar->remaining_panjar_amount);

        // Periksa belanja nota (belanja_tunai)
        $belanjaNota = KasTunai::where('parent_kas_id', $panjar->id)
            ->where('category', 'belanja_tunai')
            ->first();
        $this->assertNotNull($belanjaNota);
        $this->assertEquals(1250000, (float) $belanjaNota->amount);
        $this->assertEquals('keluar', $belanjaNota->transaction_type);

        // Periksa biaya lain-lain (operasional_laporan)
        $biayaLain = KasTunai::where('parent_kas_id', $panjar->id)
            ->where('category', 'operasional_laporan')
            ->first();
        $this->assertNotNull($biayaLain);
        $this->assertEquals(50000, (float) $biayaLain->amount);
        $this->assertEquals('keluar', $biayaLain->transaction_type);
        $this->assertStringContainsString('Tips angkut barang', $biayaLain->description);

        // Periksa sisa uang masuk kembali (sisa_panjar)
        $sisaUang = KasTunai::where('parent_kas_id', $panjar->id)
            ->where('category', 'sisa_panjar')
            ->first();
        $this->assertNotNull($sisaUang);
        $this->assertEquals(200000, (float) $sisaUang->amount);
        $this->assertEquals('masuk', $sisaUang->transaction_type);
    }

    public function test_kas_tunai_partial_settlement_lifecycle(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Kasbon Rp 1.500.000
        $panjar = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-11',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 1500000,
            'description' => 'Panjar kegiatan pentas seni',
            'panjar_status' => 'pending',
            'person_name' => 'Ibu Siti',
        ]);

        $this->assertEquals(1500000, $panjar->remaining_panjar_amount);
        $this->assertTrue($panjar->isPanjarOpen());

        // Tahap 1: Pengembalian bertahap / cicil (Nota 800.000, Sisa kembali tunai 200.000 = total 1.000.000)
        // Masih menyisakan tanggungan 500.000
        $res1 = $this->actingAs($user)->post(route('bos.kas-tunai.settle-panjar', $panjar->id), [
            'settle_date' => '2026-09-12',
            'is_partial' => '1',
            'nota_amount' => 800000,
            'cash_return_amount' => 200000,
            'notes' => 'SPJ Tahap 1: Belanja kostum dan kembalian sisa tahap 1',
        ]);

        $res1->assertOk();
        $res1->assertJson(['success' => true]);

        $panjar->refresh();
        $this->assertEquals('partial', $panjar->panjar_status);
        $this->assertEquals(1000000, (float) $panjar->settled_panjar_amount);
        $this->assertEquals(500000, (float) $panjar->remaining_panjar_amount);
        $this->assertTrue($panjar->isPanjarOpen());

        // Pastikan masih muncul di scopePendingPanjar
        $pendingPanjars = KasTunai::pendingPanjar()->pluck('id')->all();
        $this->assertContains($panjar->id, $pendingPanjars);

        // Tahap 2: Pelunasan sisa tanggungan Rp 500.000 (Nota 500.000, is_partial = 0)
        $res2 = $this->actingAs($user)->post(route('bos.kas-tunai.settle-panjar', $panjar->id), [
            'settle_date' => '2026-09-15',
            'is_partial' => '0',
            'nota_amount' => 500000,
            'notes' => 'SPJ Tahap 2: Pelunasan konsumsi juri',
        ]);

        $res2->assertOk();
        $res2->assertJson(['success' => true]);

        $panjar->refresh();
        $this->assertEquals('settled', $panjar->panjar_status);
        $this->assertEquals(1500000, (float) $panjar->settled_panjar_amount);
        $this->assertEquals(0, (float) $panjar->remaining_panjar_amount);
        $this->assertFalse($panjar->isPanjarOpen());

        // Pastikan TIDAK LAGI muncul di scopePendingPanjar
        $pendingPanjarsAfter = KasTunai::pendingPanjar()->pluck('id')->all();
        $this->assertNotContains($panjar->id, $pendingPanjarsAfter);
    }

    public function test_save_nota_items_updates_items_and_syncs_amount(): void
    {
        $user = User::first() ?? User::factory()->create();
        $guru = Guru::first() ?? Guru::create(['name' => 'Budi Santoso', 'is_active' => true]);

        $kas = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-12',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 50000,
            'description' => 'Toko ATK Berkah',
            'guru_id' => $guru->id,
            'evidence_number' => 'NOTA-001',
        ]);

        $postData = [
            'sync_amount' => 1,
            'evidence_number' => 'NOTA-001-REV',
            'items' => [
                [
                    'item_name' => 'Kertas A4 70gr',
                    'quantity' => 2,
                    'unit' => 'Rim',
                    'price' => 45000,
                    'total_price' => 90000,
                    'notes' => 'PaperOne',
                ],
                [
                    'item_name' => 'Pulpen Gel Hitam',
                    'quantity' => 5,
                    'unit' => 'Pcs',
                    'price' => 3000,
                    'total_price' => 15000,
                    'notes' => 'Joyko',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('bos.kas-tunai.save-items', $kas->id), $postData);
        $response->assertOk();
        $response->assertJson(['success' => true]);

        $kas->refresh();
        $this->assertEquals(105000, (float) $kas->amount);
        $this->assertEquals('NOTA-001-REV', $kas->evidence_number);
        $this->assertCount(2, $kas->items);
        $this->assertEquals('Kertas A4 70gr', $kas->items->first()->item_name);
    }

    public function test_store_belanja_barang_creates_transaction_and_items(): void
    {
        $user = User::first() ?? User::factory()->create();
        $guru = Guru::first() ?? Guru::create(['name' => 'Siti Rahma', 'is_active' => true]);

        // Droping kas awal Rp 5.000.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas Bank Jatim',
            'guru_id' => $guru->id,
            'panjar_status' => 'none',
        ]);

        $postData = [
            'transaction_date' => '2026-09-12',
            'guru_id' => $guru->id,
            'description' => 'Toko Bangunan Sejahtera',
            'evidence_number' => 'TB-789',
            'sync_amount' => 1,
            'items' => [
                [
                    'item_name' => 'Semen Tiga Roda',
                    'quantity' => 3,
                    'unit' => 'Zak',
                    'price' => 65000,
                    'total_price' => 195000,
                    'notes' => '50kg',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('bos.kas-tunai.belanja-barang.store'), $postData);
        $response->assertOk();
        $response->assertJson(['success' => true]);

        $createdKasId = $response->json('kas_tunai_id');
        $this->assertNotNull($createdKasId);

        $createdKas = KasTunai::find($createdKasId);
        $this->assertNotNull($createdKas);
        $this->assertEquals('BOSP', $createdKas->funding_source);
        $this->assertEquals('keluar', $createdKas->transaction_type);
        $this->assertEquals('belanja_tunai', $createdKas->category);
        $this->assertEquals(195000, (float) $createdKas->amount);
        $this->assertCount(1, $createdKas->items);
        $this->assertEquals('Semen Tiga Roda', $createdKas->items->first()->item_name);
    }

    public function test_kas_tunai_prevents_negative_cash_balance(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Droping kas hanya Rp 500.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 500000,
            'description' => 'Droping Kas Awal',
            'guru_id' => $this->defaultGuru->id,
        ]);

        // 2. Coba catat pengeluaran Rp 750.000 (melebihi saldo 500.000)
        $response = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 750000,
            'description' => 'Belanja Meja Kelas',
            'guru_id' => $this->defaultGuru->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('melampaui sisa saldo kas tunai yang tersedia', $response->json('message'));
        $this->assertStringContainsString('tidak boleh bernilai negatif', $response->json('message'));

        // Pastikan tidak tersimpan di database
        $this->assertDatabaseMissing('kas_tunais', [
            'description' => 'Belanja Meja Kelas',
            'amount' => 750000,
        ]);
    }

    public function test_kas_tunai_prevents_duplicate_evidence_number(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Droping kas cukup
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas',
            'guru_id' => $this->defaultGuru->id,
        ]);

        // 1. Catat transaksi belanja pertama dengan nomor nota NOTA-AUDIT-001
        $res1 = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-05',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 100000,
            'evidence_number' => 'NOTA-AUDIT-001',
            'description' => 'Beli kertas foto',
            'guru_id' => $this->defaultGuru->id,
        ]);
        $res1->assertOk();

        // 2. Coba catat transaksi kedua dengan nomor nota yang sama di tahun yang sama
        $res2 = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-08',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 200000,
            'evidence_number' => 'NOTA-AUDIT-001',
            'description' => 'Beli tinta printer klaim ganda',
            'guru_id' => $this->defaultGuru->id,
        ]);

        $res2->assertStatus(422);
        $res2->assertJson(['success' => false]);
        $this->assertStringContainsString('sudah pernah digunakan', $res2->json('message'));
        $this->assertStringContainsString('mencegah pencatatan ganda', $res2->json('message'));
    }

    public function test_kas_tunai_accounting_panjar_settlement_prevents_double_counting(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Kas masuk droping Rp 10.000.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 10000000,
            'description' => 'Tarik Tunai Bank',
            'guru_id' => $this->defaultGuru->id,
        ]);

        // 2. Guru mengambil panjar Rp 2.000.000 (uang keluar ke tangan guru)
        $panjarRes = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-05',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 2000000,
            'description' => 'Kasbon Perbaikan Atap',
            'person_name' => 'Pak Joko',
            'guru_id' => $this->defaultGuru->id,
        ]);
        $panjarRes->assertOk();

        $panjar = KasTunai::where('description', 'Kasbon Perbaikan Atap')->first();
        $this->assertNotNull($panjar);

        // Pada titik ini, fisik brankas tersisa Rp 8.000.000
        $this->assertEquals(8000000, KasTunai::getAvailableCashBalance('BOSP'));

        // 3. Selesaikan panjar: belanja nota Rp 1.500.000, sisa dikembalikan Rp 500.000
        $settleRes = $this->actingAs($user)->post(route('bos.kas-tunai.settle-panjar', $panjar->id), [
            'settle_date' => '2026-09-10',
            'nota_amount' => 1500000,
            'cash_return_amount' => 500000,
            'evidence_number' => 'NOTA-ATAP-88',
            'notes' => 'Beli genteng dan semen',
        ]);
        $settleRes->assertOk();

        // Verifikasi Akuntansi Mutasi Kas Brankas:
        // Awal: 10.000.000
        // Belanja aktual riil: 1.500.000
        // Saldo fisik riil di brankas seharusnya 10.000.000 - 1.500.000 = Rp 8.500.000!
        // (Bila ada cacat double counting, saldo akan keliru menjadi 10.500.000 - 3.500.000 = 7.000.000)
        $saldoBrankas = KasTunai::getAvailableCashBalance('BOSP');
        $this->assertEquals(8500000, $saldoBrankas, 'Saldo kas fisik brankas tidak boleh terdobel potong saat kasbon diselesaikan.');

        // 4. Verifikasi Tampilan di Dashboard / Index Kas Tunai
        $indexRes = $this->actingAs($user)->get(route('bos.kas-tunai.index', ['year' => 2026]));
        $indexRes->assertOk();
        $indexRes->assertViewHas('saldoKas', 8500000.0);
        $indexRes->assertViewHas('totalPanjarPending', 0.0);
        $indexRes->assertViewHas('kasSiapPakai', 8500000.0);

        // 5. Verifikasi Kas Opname (BA Pemeriksaan Kas)
        $opnameRes = $this->actingAs($user)->post(route('bos.kas-tunai.opname'), [
            'opname_date' => '2026-09-15',
            'pecahan_100000' => 85, // 85 x 100.000 = 8.500.000 fisik
        ]);
        $opnameRes->assertOk();
        $opnameRes->assertViewHas('saldoBuku', 8500000.0);
        $opnameRes->assertViewHas('totalFisik', 8500000.0);
        $opnameRes->assertViewHas('selisih', 0.0);
    }

    public function test_kas_tunai_detail_endpoint_returns_comprehensive_data(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Buat Kasbon
        $kasbon = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 1000000,
            'description' => 'Kasbon belanja perlengkapan kantor',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'pending',
            'created_by' => $user->id,
        ]);

        // 2. Buat Anak Belanja
        $child = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-02',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 700000,
            'description' => 'Belanja ATK Toko Barokah',
            'guru_id' => $this->defaultGuru->id,
            'parent_kas_id' => $kasbon->id,
            'panjar_status' => 'settled',
            'created_by' => $user->id,
        ]);

        $child->items()->create([
            'item_name' => 'Kertas HVS A4',
            'quantity' => 10,
            'unit' => 'rim',
            'price' => 55000,
            'total_price' => 550000,
        ]);

        // 3. Panggil endpoint detail
        $response = $this->actingAs($user)->get(route('bos.kas-tunai.detail', $kasbon->id));
        $response->assertOk();
        $response->assertJsonStructure([
            'kas_tunai' => [
                'id', 'code', 'transaction_date', 'category', 'category_label', 'amount', 'formatted_amount',
                'description', 'person_name', 'is_panjar',
            ],
            'direct_items',
            'child_receipts',
            'child_expenses',
            'child_returns',
            'child_reimburses',
            'panjar_summary',
        ]);

        $data = $response->json();
        $this->assertEquals(1000000, $data['kas_tunai']['amount']);
        $this->assertCount(1, $data['child_receipts']);
        $this->assertEquals(700000, $data['child_receipts'][0]['amount']);
        $this->assertCount(1, $data['child_receipts'][0]['items']);
        $this->assertEquals('Kertas HVS A4', $data['child_receipts'][0]['items'][0]['item_name']);
    }

    public function test_kasbon_settle_with_multi_receipt_expenses_and_cash_return_case(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 0. Droping awal Rp 5.000.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas Bank',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'none',
        ]);

        // Kas awal = 5.000.000
        $this->assertEquals(5000000, KasTunai::getAvailableCashBalance('BOSP'));

        // 1. Guru mengambil kasbon 1.000.000
        $kasbon = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-05',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 1000000,
            'description' => 'Kasbon belanja perlengkapan UKS & kantor',
            'guru_id' => $this->defaultGuru->id,
            'person_name' => 'Bpk. Ahmad Fauzi',
            'panjar_status' => 'pending',
            'created_by' => $user->id,
        ]);

        // Fisik uang di brankas berkurang menjadi 4.000.000
        $this->assertEquals(4000000, KasTunai::getAvailableCashBalance('BOSP'));

        // 2. Guru setor SPJ:
        // - Nota Belanja Toko Barokah: 700.000 (Kertas HVS 500k + Obat UKS 200k)
        // - Biaya Transport: 50.000
        // - Biaya Konsumsi / Makan: 50.000
        // - Sisa Uang Kembalian disetor ke kasir: 200.000
        // Total SPJ = 700k + 50k + 50k + 200k = 1.000.000 (Lunas Pas)
        $response = $this->actingAs($user)->post(route('bos.kas-tunai.settle-panjar', $kasbon->id), [
            'settle_date' => '2026-09-07',
            'is_partial' => '0',
            'receipts' => [
                [
                    'person_name' => 'Toko Barokah',
                    'evidence_number' => 'NOTA-BRK-101',
                    'amount' => 700000,
                    'items' => [
                        [
                            'item_name' => 'Kertas HVS & Alat Tulis',
                            'quantity' => 10,
                            'unit' => 'rim',
                            'price' => 50000,
                            'total_price' => 500000,
                        ],
                        [
                            'item_name' => 'Kotak Obat & P3K',
                            'quantity' => 2,
                            'unit' => 'paket',
                            'price' => 100000,
                            'total_price' => 200000,
                        ],
                    ],
                ],
            ],
            'expenses' => [
                [
                    'title' => 'Biaya Transport Belanja',
                    'amount' => 50000,
                    'notes' => 'Bensin motor staf',
                    'evidence_number' => 'KWT-TRP-01',
                ],
                [
                    'title' => 'Biaya Konsumsi Staf Belanja',
                    'amount' => 50000,
                    'notes' => 'Makan siang staf pembeli',
                    'evidence_number' => 'KWT-MKN-01',
                ],
            ],
            'cash_return_amount' => 200000,
            'notes' => 'Laporan SPJ selesai penuh, sisa uang 200.000 disetor ke kasir bendahara.',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // 3. Verifikasi Status Kasbon
        $kasbon->refresh();
        $this->assertEquals('settled', $kasbon->panjar_status);

        // 4. Verifikasi Catatan Anak SPJ
        $children = KasTunai::where('parent_kas_id', $kasbon->id)->get();
        // Ada: 1 belanja (700k) + 2 expenses (50k + 50k) + 1 sisa kembalian (200k) = 4 records
        $this->assertCount(4, $children);

        $belanjaChild = $children->firstWhere('category', 'belanja_tunai');
        $this->assertNotNull($belanjaChild);
        $this->assertEquals(700000, $belanjaChild->amount);
        $this->assertEquals('Toko Barokah', $belanjaChild->person_name);
        $this->assertCount(2, $belanjaChild->items);

        $expenseChildren = $children->where('category', 'operasional_laporan');
        $this->assertCount(2, $expenseChildren);
        $this->assertEquals(100000, $expenseChildren->sum('amount'));

        $returnChild = $children->firstWhere('category', 'sisa_panjar');
        $this->assertNotNull($returnChild);
        $this->assertEquals(200000, $returnChild->amount);
        $this->assertEquals('masuk', $returnChild->transaction_type);

        // 5. Verifikasi Saldo Kas Tunai Fisik di Brankas Bendahara
        // Kas awal 5.000.000 - Kasbon keluar 1.000.000 + Kembalian sisa uang masuk 200.000 = 4.200.000
        // (Belanja anak 700k dan biaya 100k TIDAK mengurangi kas brankas lagi karena uangnya diambil dari kasbon yang sudah keluar)
        $currentCashBalance = KasTunai::getAvailableCashBalance('BOSP');
        $this->assertEquals(4200000, $currentCashBalance);

        // Pengeluaran kas riil yang tercatat di pembukuan sekolah:
        // Kasbon 1jt keluar - sisa kembali 200k masuk = pengeluaran neto Rp 800.000
        $totalMasuk = KasTunai::funding('BOSP')->where('transaction_type', 'masuk')->sum('amount');
        $totalKeluarFisik = KasTunai::funding('BOSP')->where('transaction_type', 'keluar')->actualCashOutflow()->sum('amount');
        $this->assertEquals(5200000, $totalMasuk); // 5jt bank + 200k sisa panjar
        $this->assertEquals(1000000, $totalKeluarFisik); // 1jt kasbon awal
        $this->assertEquals(4200000, $totalMasuk - $totalKeluarFisik);
    }

    public function test_kasbon_with_multiple_receipts_from_different_stores(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Droping kas 5.000.000
        KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 5000000,
            'description' => 'Droping Kas Bank',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'none',
        ]);

        // Kasbon 800.000
        $kasbon = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-08',
            'transaction_type' => 'keluar',
            'category' => 'panjar_kerja',
            'amount' => 800000,
            'description' => 'Kasbon belanja 2 toko',
            'guru_id' => $this->defaultGuru->id,
            'panjar_status' => 'pending',
            'created_by' => $user->id,
        ]);

        // Settle dengan 2 nota toko berbeda:
        // Nota 1: CV Media Utama (500.000)
        // Nota 2: Toko Sumber Listrik (300.000)
        $response = $this->actingAs($user)->post(route('bos.kas-tunai.settle-panjar', $kasbon->id), [
            'settle_date' => '2026-09-09',
            'is_partial' => '0',
            'receipts' => [
                [
                    'person_name' => 'CV Media Utama',
                    'evidence_number' => 'NOTA-MED-01',
                    'amount' => 500000,
                    'items' => [
                        ['item_name' => 'Buku Referensi', 'quantity' => 5, 'unit' => 'eks', 'price' => 100000, 'total_price' => 500000],
                    ],
                ],
                [
                    'person_name' => 'Toko Sumber Listrik',
                    'evidence_number' => 'NOTA-LST-02',
                    'amount' => 300000,
                    'items' => [
                        ['item_name' => 'Lampu LED Phillips', 'quantity' => 6, 'unit' => 'buah', 'price' => 50000, 'total_price' => 300000],
                    ],
                ],
            ],
            'cash_return_amount' => 0,
        ]);

        $response->assertOk();
        $kasbon->refresh();
        $this->assertEquals('settled', $kasbon->panjar_status);

        $receiptChildren = KasTunai::where('parent_kas_id', $kasbon->id)->where('category', 'belanja_tunai')->get();
        $this->assertCount(2, $receiptChildren);
        $this->assertEquals('CV Media Utama', $receiptChildren[0]->person_name);
        $this->assertEquals('Toko Sumber Listrik', $receiptChildren[1]->person_name);
        $this->assertEquals(800000, $receiptChildren->sum('amount'));
    }

    public function test_penerimaan_dana_talangan_and_repayment(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Kas masuk dari dana talangan (misal dari Kepala Sekolah Rp 2.000.000)
        $response = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'masuk',
            'category' => 'dana_talangan',
            'amount' => 2000000,
            'description' => 'Penerimaan dana talangan dari Kepala Sekolah untuk kegiatan akreditasi',
            'person_name' => 'Drs. H. Mulyono',
            'guru_id' => $this->defaultGuru->id,
            'evidence_number' => 'TLG-2026-01',
        ]);

        $response->assertOk();
        $talangan = KasTunai::where('category', 'dana_talangan')->first();
        $this->assertNotNull($talangan);
        $this->assertEquals(2000000, $talangan->amount);

        // Saldo kas tunai bertambah 2.000.000
        $this->assertEquals(2000000, KasTunai::getAvailableCashBalance('BOSP'));

        // 2. Pelunasan / Pengembalian dana talangan saat dana BOS cair
        $responseRepay = $this->actingAs($user)->post(route('bos.kas-tunai.store'), [
            'transaction_date' => '2026-09-20',
            'transaction_type' => 'keluar',
            'category' => 'bayar_talangan',
            'amount' => 2000000,
            'description' => 'Pengembalian dana talangan Kepala Sekolah',
            'parent_kas_id' => $talangan->id,
            'guru_id' => $this->defaultGuru->id,
            'evidence_number' => 'BYR-TLG-01',
        ]);

        $responseRepay->assertOk();
        $bayarTalangan = KasTunai::where('category', 'bayar_talangan')->first();
        $this->assertNotNull($bayarTalangan);
        $this->assertEquals($talangan->id, $bayarTalangan->parent_kas_id);

        // Saldo kas tunai kembali menjadi 0
        $this->assertEquals(0, KasTunai::getAvailableCashBalance('BOSP'));
    }

    public function test_kas_tunai_datatable_sorting(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Bersihkan data transaksi untuk test ini
        KasTunai::where('funding_source', 'BOSP')->delete();

        $kas1 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-01',
            'transaction_type' => 'masuk',
            'category' => 'tarik_bank',
            'amount' => 1000000,
            'description' => 'A - Penarikan Dana Awal',
            'created_by' => $user->id,
        ]);

        $kas2 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-05',
            'transaction_type' => 'keluar',
            'category' => 'belanja_tunai',
            'amount' => 500000,
            'description' => 'B - Pembelian Spidol',
            'created_by' => $user->id,
        ]);

        $kas3 = KasTunai::create([
            'funding_source' => 'BOSP',
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'masuk',
            'category' => 'dana_talangan',
            'amount' => 2000000,
            'description' => 'C - Dana Talangan',
            'created_by' => $user->id,
        ]);

        $columns = [
            0 => ['data' => 'DT_RowIndex'],
            1 => ['data' => 'id_display', 'name' => 'id'],
            2 => ['data' => 'transaction_date', 'name' => 'transaction_date'],
            3 => ['data' => 'category', 'name' => 'category'],
            4 => ['data' => 'description', 'name' => 'description'],
            5 => ['data' => 'person_display', 'name' => 'person_display'],
            6 => ['data' => 'debit', 'name' => 'debit'],
            7 => ['data' => 'kredit', 'name' => 'kredit'],
            8 => ['data' => 'action'],
        ];

        // 1. Sort by transaction_date ASC (Harus kas1 -> kas2 -> kas3)
        $resDateAsc = $this->actingAs($user)->getJson(route('bos.kas-tunai.index', [
            'columns' => $columns,
            'order' => [['column' => 2, 'dir' => 'asc']],
            'year' => 'all',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $resDateAsc->assertOk();
        $dataDateAsc = $resDateAsc->json('data');
        $this->assertCount(3, $dataDateAsc);
        $this->assertStringContainsString('A - Penarikan Dana Awal', $dataDateAsc[0]['description']);
        $this->assertStringContainsString('B - Pembelian Spidol', $dataDateAsc[1]['description']);
        $this->assertStringContainsString('C - Dana Talangan', $dataDateAsc[2]['description']);

        // 2. Sort by transaction_date DESC (Harus kas3 -> kas2 -> kas1)
        $resDateDesc = $this->actingAs($user)->getJson(route('bos.kas-tunai.index', [
            'columns' => $columns,
            'order' => [['column' => 2, 'dir' => 'desc']],
            'year' => 'all',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $resDateDesc->assertOk();
        $dataDateDesc = $resDateDesc->json('data');
        $this->assertStringContainsString('C - Dana Talangan', $dataDateDesc[0]['description']);
        $this->assertStringContainsString('B - Pembelian Spidol', $dataDateDesc[1]['description']);
        $this->assertStringContainsString('A - Penarikan Dana Awal', $dataDateDesc[2]['description']);

        // 3. Sort by description ASC (A -> B -> C)
        $resDescAsc = $this->actingAs($user)->getJson(route('bos.kas-tunai.index', [
            'columns' => $columns,
            'order' => [['column' => 4, 'dir' => 'asc']],
            'year' => 'all',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $resDescAsc->assertOk();
        $dataDescAsc = $resDescAsc->json('data');
        $this->assertStringContainsString('A - Penarikan Dana Awal', $dataDescAsc[0]['description']);
        $this->assertStringContainsString('C - Dana Talangan', $dataDescAsc[2]['description']);

        // 4. Sort by description DESC (C -> B -> A)
        $resDescDesc = $this->actingAs($user)->getJson(route('bos.kas-tunai.index', [
            'columns' => $columns,
            'order' => [['column' => 4, 'dir' => 'desc']],
            'year' => 'all',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $resDescDesc->assertOk();
        $dataDescDesc = $resDescDesc->json('data');
        $this->assertStringContainsString('C - Dana Talangan', $dataDescDesc[0]['description']);
        $this->assertStringContainsString('A - Penarikan Dana Awal', $dataDescDesc[2]['description']);

        // 5. Sort by debit DESC (kas3 (2jt) -> kas1 (1jt) -> kas2 (0))
        $resDebitDesc = $this->actingAs($user)->getJson(route('bos.kas-tunai.index', [
            'columns' => $columns,
            'order' => [['column' => 6, 'dir' => 'desc']],
            'year' => 'all',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $resDebitDesc->assertOk();
        $dataDebitDesc = $resDebitDesc->json('data');
        $this->assertStringContainsString('C - Dana Talangan', $dataDebitDesc[0]['description']);
        $this->assertStringContainsString('A - Penarikan Dana Awal', $dataDebitDesc[1]['description']);
        $this->assertStringContainsString('B - Pembelian Spidol', $dataDebitDesc[2]['description']);
    }

    private function ensureTablesExist(): void
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
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('gurus')) {
            Schema::create('gurus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('title_prefix')->nullable();
                $table->string('title_suffix')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('jabatans')) {
            Schema::create('jabatans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('guru_jabatans')) {
            Schema::create('guru_jabatans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_id')->nullable();
                $table->foreignId('jabatan_id')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanjas')) {
            Schema::create('bos_rencana_belanjas', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('no_nota')->nullable();
                $table->string('year', 4)->nullable();
                $table->date('planned_date')->nullable();
                $table->string('status')->default('draft');
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('supplier_name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('bos_rencana_belanja_items')) {
            Schema::create('bos_rencana_belanja_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bos_rencana_belanja_id')->nullable();
                $table->string('item_name')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->integer('quantity')->default(1);
                $table->decimal('total_price', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('perjadins')) {
            Schema::create('perjadins', function (Blueprint $table) {
                $table->id();
                $table->string('funding_source', 20)->default('BOSP');
                $table->string('bku_number')->nullable();
                $table->string('activity_purpose')->nullable();
                $table->date('departure_date')->nullable();
                $table->date('payment_date')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('perjadin_items')) {
            Schema::create('perjadin_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('perjadin_id')->nullable();
                $table->foreignId('guru_id')->nullable();
                $table->string('receipt_number')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
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
                $table->string('letter_number')->nullable();
                $table->date('letter_date')->nullable();
                $table->decimal('total_nominal', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
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
                $table->unsignedSmallInteger('year')->nullable()->index();
                $table->unsignedInteger('sequence')->nullable()->index();
                $table->string('transaction_type', 10)->index();
                $table->string('category', 50)->index();
                $table->foreignId('kas_category_id')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->text('description');
                $table->string('person_name')->nullable();
                $table->foreignId('guru_id')->nullable();
                $table->foreignId('pencairan_bank_id')->nullable();
                $table->foreignId('bos_rencana_belanja_id')->nullable();
                $table->foreignId('perjadin_item_id')->nullable();
                $table->foreignId('parent_kas_id')->nullable();
                $table->string('panjar_status', 20)->default('none')->index();
                $table->string('evidence_number')->nullable();
                $table->string('receipt_file')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->foreignId('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('kas_tunai_items')) {
            Schema::create('kas_tunai_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kas_tunai_id')->constrained('kas_tunais')->cascadeOnDelete();
                $table->string('item_name');
                $table->decimal('quantity', 10, 2)->default(1);
                $table->string('unit', 50)->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('total_price', 15, 2)->default(0);
                $table->string('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
}
