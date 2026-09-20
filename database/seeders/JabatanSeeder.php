<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPositions = [
            ['name' => 'Kepala Sekolah', 'code' => 'KS', 'is_signatory' => true, 'description' => 'Penanggung Jawab dan Kuasa Pengguna Anggaran Sekolah'],
            ['name' => 'Bendahara BOSP', 'code' => 'BEND_BOSP', 'is_signatory' => true, 'description' => 'Bendahara Pengelola Dana BOSP'],
            ['name' => 'Bendahara Sekolah', 'code' => 'BEND_SEKOLAH', 'is_signatory' => true, 'description' => 'Bendahara Pengelola Keuangan Sekolah / Komite'],
            ['name' => 'Waka Kurikulum', 'code' => 'WAKA_KURIKULUM', 'is_signatory' => false, 'description' => 'Wakil Kepala Bidang Kurikulum & Pembelajaran'],
            ['name' => 'Waka Kesiswaan', 'code' => 'WAKA_KESISWAAN', 'is_signatory' => false, 'description' => 'Wakil Kepala Bidang Kesiswaan'],
            ['name' => 'Waka Sarana & Prasarana', 'code' => 'WAKA_SARPRAS', 'is_signatory' => false, 'description' => 'Wakil Kepala Bidang Sarana & Prasarana'],
            ['name' => 'Waka Humas', 'code' => 'WAKA_HUMAS', 'is_signatory' => false, 'description' => 'Wakil Kepala Bidang Hubungan Masyarakat'],
            ['name' => 'Pembina OSIS', 'code' => 'PEMBINA_OSIS', 'is_signatory' => false, 'description' => 'Pembina Organisasi Siswa Intra Sekolah'],
            ['name' => 'Pembina Pramuka', 'code' => 'PEMBINA_PRAMUKA', 'is_signatory' => false, 'description' => 'Pembina Gerakan Pramuka'],
            ['name' => 'Pengurus Barang / Aset', 'code' => 'PENGELOLA_ASET', 'is_signatory' => false, 'description' => 'Pengelola Inventaris & Aset Milik Sekolah'],
            ['name' => 'Kepala Tata Usaha', 'code' => 'KTU', 'is_signatory' => false, 'description' => 'Kepala Tenaga Administrasi Sekolah'],
            ['name' => 'Guru Mata Pelajaran / Kelas', 'code' => 'GURU', 'is_signatory' => false, 'description' => 'Tenaga Pendidik / Pengajar'],
        ];

        foreach ($defaultPositions as $pos) {
            DB::table('jabatans')->updateOrInsert(
                ['name' => $pos['name']],
                [
                    'code' => $pos['code'],
                    'is_signatory' => $pos['is_signatory'],
                    'description' => $pos['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
