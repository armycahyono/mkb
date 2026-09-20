<?php

namespace Database\Seeders;

use App\Models\UtilityProfile;
use Illuminate\Database\Seeder;

class UtilityProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profiles = [
            [
                'code' => 'listrik_stm',
                'name' => 'Pembayaran Listrik atas nama STM',
                'category' => 'listrik_pasca',
                'customer_name' => 'STM',
                'customer_number' => '514031084530',
                'idpel' => '514031084530',
                'meter_number' => null,
                'tariff_power' => 'S1/16500 VA',
                'provider' => 'PLN',
                'payment_method' => 'PT POS INDONESIA / Loket PPOB',
                'description' => 'Listrik Pascaprabayar Gedung STM (S1/16500 VA)',
                'sort_order' => 1,
            ],
            [
                'code' => 'listrik_masjid',
                'name' => 'Pembayaran Listrik atas nama MASJID AN NUR',
                'category' => 'listrik_pasca',
                'customer_name' => 'MASJID AN NUR',
                'customer_number' => '514030561937',
                'idpel' => '514030561937',
                'meter_number' => null,
                'tariff_power' => 'R1/450 VA',
                'provider' => 'PLN',
                'payment_method' => 'PT POS INDONESIA / Loket PPOB',
                'description' => 'Listrik Pascaprabayar Masjid An Nur (R1/450 VA)',
                'sort_order' => 2,
            ],
            [
                'code' => 'listrik_smp',
                'name' => 'Pembayaran Listrik atas nama SMP PGRI',
                'category' => 'listrik_pasca',
                'customer_name' => 'SMP PGRI',
                'customer_number' => '514030561945',
                'idpel' => '514030561945',
                'meter_number' => null,
                'tariff_power' => 'S1/900 VA',
                'provider' => 'PLN',
                'payment_method' => 'PT POS INDONESIA / Loket PPOB',
                'description' => 'Listrik Pascaprabayar SMP PGRI (S1/900 VA)',
                'sort_order' => 3,
            ],
            [
                'code' => 'token_smk_kras',
                'name' => 'Pembelian Listrik Prabayar atas nama SMK PGRI KRAS',
                'category' => 'listrik_pra',
                'customer_name' => 'SMK PGRI KRAS',
                'customer_number' => '514031897945',
                'meter_number' => '32118573479',
                'idpel' => '514031897945',
                'tariff_power' => 'S1/5500 VA',
                'provider' => 'PLN',
                'payment_method' => 'PT POS INDONESIA',
                'description' => 'Listrik Prabayar Token SMK PGRI KRAS (5500 VA)',
                'sort_order' => 4,
            ],
            [
                'code' => 'token_stm_pgri',
                'name' => 'Pembelian Listrik Prabayar atas nama STM PGRI',
                'category' => 'listrik_pra',
                'customer_name' => 'STM PGRI',
                'customer_number' => '514031736766',
                'meter_number' => '45038124785',
                'idpel' => '514031736766',
                'tariff_power' => 'S1/5500 VA',
                'provider' => 'PLN',
                'payment_method' => 'PT POS INDONESIA / Loket PPOB',
                'description' => 'Listrik Prabayar Token STM PGRI (5500 VA)',
                'sort_order' => 5,
            ],
            [
                'code' => 'speedy_imam',
                'name' => 'Pembayaran Telkom Speedy atas nama IMAM SADELI',
                'category' => 'telkom_speedy',
                'customer_name' => 'IMAM SADELI',
                'customer_number' => '0354-000479487',
                'idpel' => '0354000479487',
                'meter_number' => null,
                'tariff_power' => null,
                'provider' => 'Telkom',
                'payment_method' => 'PT POS INDONESIA / Bank',
                'description' => 'Tagihan Telkom / Indihome / Speedy a.n. Imam Sadeli / Imam Sjadeli',
                'sort_order' => 6,
            ],
            [
                'code' => 'speedy_smk_kras',
                'name' => 'Pembayaran Telkom Speedy atas nama SMK PGRI KRAS',
                'category' => 'telkom_speedy',
                'customer_name' => 'SMK PGRI KRAS',
                'customer_number' => '0354-004740750',
                'idpel' => '0354004740750',
                'meter_number' => null,
                'tariff_power' => null,
                'provider' => 'Telkom',
                'payment_method' => 'PT POS INDONESIA / Bank',
                'description' => 'Tagihan Telkom / Indihome / Speedy a.n. SMK PGRI Kras',
                'sort_order' => 7,
            ],
            [
                'code' => 'internet_menaksopal',
                'name' => 'Pembayaran Internet Menaksopal Link Nusantara (transfer bank)',
                'category' => 'internet',
                'customer_name' => 'MENAKSOPAL LINK NUSANTAR',
                'customer_number' => '3243258444',
                'idpel' => '3243258444',
                'meter_number' => null,
                'tariff_power' => null,
                'provider' => 'Menaksopal Link Nusantara',
                'payment_method' => 'Transfer Bank BCA (3243258444)',
                'description' => 'Langganan Internet Dedicated Menaksopal Link Nusantara via Transfer BCA 3243258444',
                'sort_order' => 8,
            ],
            [
                'code' => 'cadangan_utilitas',
                'name' => 'Cadangan / Tagihan Utilitas Lainnya',
                'category' => 'lainnya',
                'customer_name' => 'Utilitas Lainnya',
                'customer_number' => null,
                'idpel' => null,
                'meter_number' => null,
                'tariff_power' => null,
                'provider' => null,
                'payment_method' => 'Fleksibel',
                'description' => 'Slot fleksibel untuk pembayaran utilitas tambahan (PDAM, Domain, Hosting, dll)',
                'sort_order' => 9,
            ],
        ];

        foreach ($profiles as $p) {
            UtilityProfile::updateOrCreate(
                ['code' => $p['code']],
                $p
            );
        }
    }
}
