<?php

namespace Database\Factories;

use App\Models\PengajuanBarang;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengajuanBarang>
 */
class PengajuanBarangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $volume = fake()->numberBetween(1, 20);
        $harga = fake()->numberBetween(10000, 500000);

        return [
            'tahun_anggaran' => (int) date('Y'),
            'pemohon_id' => User::factory(),
            'nama_barang' => fake()->words(3, true),
            'spesifikasi' => fake()->sentence(),
            'volume' => $volume,
            'satuan' => fake()->randomElement(['Rim', 'Pcs', 'Box', 'Unit', 'Pak']),
            'harga' => $harga,
            'jumlah' => $volume * $harga,
            'kegiatan' => 'Kegiatan '.fake()->word(),
            'bulan_pakai' => fake()->randomElement(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni']),
            'keterangan' => fake()->optional()->sentence(),
            'realisasi' => null,
        ];
    }
}
