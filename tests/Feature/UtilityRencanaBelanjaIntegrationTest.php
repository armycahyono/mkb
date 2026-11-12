<?php

namespace Tests\Feature;

use App\Models\BosKertasKerjaFile;
use App\Models\BosKertasKerjaItem;
use App\Models\BosRealisasi;
use App\Models\BosRencanaBelanja;
use App\Models\PencairanBank;
use App\Models\UtilityProfile;
use App\Models\UtilityReceipt;

class UtilityRencanaBelanjaIntegrationTest extends UtilityLifecycleTest
{
    protected UtilityProfile $profileListrik;

    protected UtilityProfile $profileWifi;

    protected BosKertasKerjaItem $kkListrik;

    protected BosKertasKerjaItem $kkWifi;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Utility Profiles
        $this->profileListrik = UtilityProfile::firstOrCreate(
            ['code' => 'listrik_stm_test'],
            [
                'name' => 'Listrik Gedung STM Test',
                'category' => 'listrik',
                'customer_name' => 'SMK PGRI KRAS',
                'customer_number' => '5111222333',
                'is_budgeted' => true,
                'is_active' => true,
                'default_reimburse_guru_id' => $this->guruReimburse->id,
            ]
        );

        $this->profileWifi = UtilityProfile::firstOrCreate(
            ['code' => 'wifi_indihome_test'],
            [
                'name' => 'Internet Indihome Test',
                'category' => 'internet',
                'customer_name' => 'SMK PGRI KRAS',
                'customer_number' => '1522334455',
                'is_budgeted' => true,
                'is_active' => true,
                'default_reimburse_guru_id' => $this->guruReimburse->id,
            ]
        );

        // Setup Active Kertas Kerja File
        $kkFile = BosKertasKerjaFile::firstOrCreate(
            ['year' => 2026],
            ['file_name' => 'KK_2026_TEST.xlsx', 'is_active' => true]
        );
        $kkFile->update(['is_active' => true]);

        // Setup KK Items for Utility
        $this->kkListrik = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Juni',
            'account_code' => '5.1.02.02.01.0061',
            'program_code' => '07.06.01',
            'kegiatan' => 'Pembayaran Daya Listrik Sekolah',
            'description' => 'Langganan Listrik PLN Gedung Utama',
            'volume' => 1,
            'unit' => 'Bulan',
            'price' => 2000000,
            'total_amount' => 2000000,
            'utility_profile_id' => $this->profileListrik->id,
        ]);

        $this->kkWifi = BosKertasKerjaItem::create([
            'bos_kertas_kerja_file_id' => $kkFile->id,
            'year' => 2026,
            'month' => 'Juni',
            'account_code' => '5.1.02.02.01.0063',
            'program_code' => '07.06.04',
            'kegiatan' => 'Pembayaran Langganan Internet',
            'description' => 'Langganan Jasa Internet Dedicated',
            'volume' => 1,
            'unit' => 'Bulan',
            'price' => 1000000,
            'total_amount' => 1000000,
            'utility_profile_id' => $this->profileWifi->id,
        ]);
    }

    public function test_panduan_index_page_renders_successfully(): void
    {
        $response = $this->get(route('bos.panduan.index'));
        $response->assertOk();
        $response->assertSee('Bayar Utilitas');
        $response->assertSee('modalBayarUtilitas');
    }

    public function test_get_utility_payment_modal_data_returns_receipts_and_kk_items(): void
    {
        // Buat 2 struk utilitas bulan Juni
        $receipt1 = UtilityReceipt::create([
            'utility_profile_id' => $this->profileListrik->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Juni',
            'customer_name' => 'SMK PGRI KRAS',
            'customer_number' => '5111222333',
            'total_amount' => 1500000,
            'is_paid' => false,
        ]);

        $receipt2 = UtilityReceipt::create([
            'utility_profile_id' => $this->profileWifi->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Juni',
            'customer_name' => 'SMK PGRI KRAS',
            'customer_number' => '1522334455',
            'total_amount' => 850000,
            'is_paid' => false,
        ]);

        $response = $this->getJson(route('bos.panduan.utility-modal-data', [
            'year' => 2026,
            'month' => 'Juni',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'year', 'month', 'no_nota', 'receipts', 'kertas_kerja_items', 'gurus', 'default_guru_id',
        ]);

        $receipts = $response->json('receipts');
        $this->assertCount(2, $receipts);
        $this->assertEquals(1500000, $receipts[0]['total_amount']);
        $this->assertEquals($this->kkListrik->id, $receipts[0]['suggested_kk_id']);
        $this->assertEquals($this->guruReimburse->id, $response->json('default_guru_id'));
    }

    public function test_store_utility_payment_group_bundles_receipts_into_rencana_belanja(): void
    {
        $receipt1 = UtilityReceipt::create([
            'utility_profile_id' => $this->profileListrik->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Juni',
            'customer_name' => 'SMK PGRI KRAS',
            'total_amount' => 1500000,
            'is_paid' => false,
        ]);

        $receipt2 = UtilityReceipt::create([
            'utility_profile_id' => $this->profileWifi->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Juni',
            'customer_name' => 'SMK PGRI KRAS',
            'total_amount' => 850000,
            'is_paid' => false,
        ]);

        $payload = [
            'year' => 2026,
            'month' => 'Juni',
            'no_nota' => 'RB26-UTIL-01',
            'title' => 'Pembayaran Utilitas Juni 2026',
            'guru_id' => $this->guruReimburse->id,
            'bank_name' => 'Bank Jatim',
            'account_number' => '0233445566',
            'planned_date' => '2026-06-25',
            'receipts' => [
                [
                    'id' => $receipt1->id,
                    'bos_kertas_kerja_item_id' => $this->kkListrik->id,
                    'nominal' => 1500000,
                ],
                [
                    'id' => $receipt2->id,
                    'bos_kertas_kerja_item_id' => $this->kkWifi->id,
                    'nominal' => 850000,
                ],
            ],
        ];

        $response = $this->postJson(route('bos.panduan.store-utility-group'), $payload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $groupId = $response->json('group_id');
        $group = BosRencanaBelanja::with(['items', 'utilityReceipts'])->find($groupId);

        $this->assertNotNull($group);
        $this->assertEquals('utilitas', $group->type);
        $this->assertEquals($this->guruReimburse->id, $group->guru_id);
        $this->assertEquals('RB26-UTIL-01', $group->no_nota);
        $this->assertEquals(2350000, (float) $group->total_amount);
        $this->assertCount(2, $group->items);
        $this->assertCount(2, $group->utilityReceipts);

        // Verifikasi receipt linked
        $this->assertEquals($group->id, $receipt1->fresh()->bos_rencana_belanja_id);
        $this->assertEquals($group->id, $receipt2->fresh()->bos_rencana_belanja_id);
    }

    public function test_unpaid_rencana_notas_endpoint_detects_utility_plan_with_guru_account(): void
    {
        $group = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Juni',
            'no_nota' => 'RB26-UTIL-02',
            'title' => 'Pembayaran Utilitas Juni 2026',
            'type' => 'utilitas',
            'guru_id' => $this->guruReimburse->id,
            'supplier_name' => $this->guruReimburse->name,
            'bank_name' => 'Bank Jatim',
            'account_number' => '0233445566',
            'planned_date' => '2026-06-25',
            'status' => 'draft',
        ]);

        $response = $this->getJson(route('bos.api.unpaid-rencana-notas', ['year' => 2026]));
        $response->assertStatus(200);

        $notas = collect($response->json());
        $matched = $notas->firstWhere('id', $group->id);

        $this->assertNotNull($matched);
        $this->assertEquals($this->guruReimburse->name, $matched['recipient_name']);
        $this->assertEquals('0233445566', $matched['account_number']);
        $this->assertEquals('Bank Jatim', $matched['bank_name']);
    }

    public function test_pencairan_tnt_realizes_utility_group_and_marks_receipts_paid(): void
    {
        $receipt1 = UtilityReceipt::create([
            'utility_profile_id' => $this->profileListrik->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Juni',
            'customer_name' => 'SMK PGRI KRAS',
            'total_amount' => 1500000,
            'is_paid' => false,
        ]);

        $group = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Juni',
            'no_nota' => 'RB26-UTIL-03',
            'title' => 'Pembayaran Utilitas Juni 2026',
            'type' => 'utilitas',
            'guru_id' => $this->guruReimburse->id,
            'supplier_name' => $this->guruReimburse->name,
            'bank_name' => 'Bank Jatim',
            'account_number' => '0233445566',
            'planned_date' => '2026-06-25',
            'status' => 'draft',
        ]);

        $receipt1->update([
            'bos_rencana_belanja_id' => $group->id,
            'bos_kertas_kerja_item_id' => $this->kkListrik->id,
        ]);

        $group->items()->create([
            'bos_kertas_kerja_item_id' => $this->kkListrik->id,
            'volume_plan' => 1,
            'unit' => 'Bulan',
            'price' => 1500000,
            'total_amount' => 1500000,
        ]);

        // Buat Surat TNT
        $pencairanBank = PencairanBank::create([
            'funding_source' => 'BOSP',
            'letter_number' => '001/TNT-BOSP/VI/2026',
            'letter_date' => '2026-06-26',
            'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR',
            'bank_branch' => 'CABANG KEDIRI',
            'source_account_number' => '1001003336',
            'source_account_name' => '20511923 SMKS PGRI KRAS KEDIRI (BOSP)',
            'total_nominal' => 1500000,
            'total_accounts' => 1,
            'status' => 'draft',
        ]);

        $bankItem = $pencairanBank->items()->create([
            'type' => 'belanja',
            'recipient_name' => $this->guruReimburse->name,
            'bank_name' => 'Bank Jatim',
            'account_number' => '0233445566',
            'amount' => 1500000,
            'bos_rencana_belanja_id' => $group->id,
        ]);

        $group->update([
            'pencairan_bank_item_id' => $bankItem->id,
            'status' => 'diajukan_ke_bank',
        ]);

        // Eksekusi pencairan TNT
        $response = $this->post(route('bos.pencairan-bank.cairkan', $pencairanBank->id), [
            'cair_date' => '2026-06-27',
            'bku_number' => 'BKU-2026-06-0099',
        ]);

        $response->assertStatus(200);

        // Verifikasi status TNT
        $this->assertEquals('dicairkan', $pencairanBank->fresh()->status);

        // Verifikasi Rencana Belanja berstatus realized
        $this->assertEquals('realized', $group->fresh()->status);

        // Verifikasi receipt lunas
        $freshReceipt = $receipt1->fresh();
        $this->assertTrue((bool) $freshReceipt->is_paid);
        $this->assertEquals('2026-06-27', $freshReceipt->payment_date->format('Y-m-d'));
        $this->assertEquals('BKU-2026-06-0099', $freshReceipt->bku_number);

        // Verifikasi BosRealisasi terbuat
        $realisasi = BosRealisasi::where('bos_rencana_belanja_id', $group->id)->first();
        $this->assertNotNull($realisasi);
        $this->assertEquals('BKU-2026-06-0099', $realisasi->no_transaksi);
        $this->assertEquals(1500000, (float) $realisasi->total_amount);
        $this->assertEquals($this->kkListrik->id, $realisasi->bos_kertas_kerja_item_id);
    }

    public function test_destroy_utility_group_unlinks_receipts(): void
    {
        $receipt = UtilityReceipt::create([
            'utility_profile_id' => $this->profileListrik->id,
            'fiscal_year' => 2026,
            'billing_month' => 'Juni',
            'customer_name' => 'SMK PGRI KRAS',
            'total_amount' => 1500000,
            'is_paid' => false,
        ]);

        $group = BosRencanaBelanja::create([
            'year' => 2026,
            'month' => 'Juni',
            'no_nota' => 'RB26-UTIL-DEL',
            'title' => 'Pembayaran Utilitas Test Hapus',
            'type' => 'utilitas',
            'status' => 'draft',
        ]);

        $receipt->update(['bos_rencana_belanja_id' => $group->id]);

        $response = $this->deleteJson(route('bos.panduan.destroy-group', $group->id));
        $response->assertStatus(200);

        // Pastikan group terhapus dan receipt unlinked
        $this->assertSoftDeleted('bos_rencana_belanjas', ['id' => $group->id]);
        $this->assertNull($receipt->fresh()->bos_rencana_belanja_id);
    }
}
