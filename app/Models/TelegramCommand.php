<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['command', 'description', 'response_type', 'response_text', 'flow_config', 'is_active', 'sort_order'])]
class TelegramCommand extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'flow_config' => 'array',
        ];
    }

    public function isSystem(): bool
    {
        return $this->response_type === 'system';
    }

    public function hasFlowConfig(): bool
    {
        return in_array($this->command, ['transaksi', 'pengeluaran', 'pemasukan', 'foto', 'kasbon']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }

    public function getTypeBadgeAttribute(): string
    {
        if ($this->isSystem()) {
            return '<span class="badge badge-primary"><i class="fas fa-microchip mr-1"></i>Sistem</span>';
        }

        return '<span class="badge badge-success"><i class="fas fa-comment-dots mr-1"></i>Kustom</span>';
    }

    /**
     * Definisi struktur langkah bawaan (default workflow) untuk perintah bot tertentu.
     *
     * @return array<string, array{key: string, title: string, description: string, is_enabled: bool, is_required: bool, prompt: string, help_text: string}>
     */
    public static function defaultFlow(string $command): array
    {
        if (in_array($command, ['transaksi', 'pengeluaran', 'pemasukan'])) {
            return [
                'year' => [
                    'key' => 'year',
                    'title' => 'Tahun Anggaran Belanja',
                    'description' => 'Memilih tahun anggaran pembukuan untuk ditempatkan pada buku kas dan dokumentasi foto',
                    'is_enabled' => true,
                    'is_required' => true,
                    'prompt' => "📅 <b>PILIH TAHUN ANGGARAN:</b>\nSilakan pilih tahun anggaran untuk transaksi kas ini:",
                    'help_text' => 'Menentukan tahun anggaran pembukuan kas dan penempatan folder dokumentasi foto.',
                ],
                'category' => [
                    'key' => 'category',
                    'title' => 'Pilihan Kategori Transaksi',
                    'description' => 'Menampilkan tombol kategori (Belanja Barang, Talangan, Panjar, dll.)',
                    'is_enabled' => true,
                    'is_required' => true,
                    'prompt' => "📂 <b>PILIH KATEGORI TRANSAKSI</b>\nSilakan pilih kategori yang sesuai di bawah ini:",
                    'help_text' => 'Jika dinonaktifkan, kategori akan otomatis diatur ke Belanja Tunai / Penerimaan Lainnya.',
                ],
                'amount' => [
                    'key' => 'amount',
                    'title' => 'Nominal Transaksi (Rp)',
                    'description' => 'Meminta input angka nominal rupiah transaksi kas',
                    'is_enabled' => true,
                    'is_required' => true,
                    'prompt' => "Masukkan <b>NOMINAL</b> transaksi (Rp):\n<i>(Contoh: 750000 atau bisa digabung: 100.000, Pak Army)</i>",
                    'help_text' => 'Langkah nominal wajib aktif agar pembukuan kas valid. Bisa diisi nominal saja atau digabung dengan nama penanggung jawab.',
                ],
                'description' => [
                    'key' => 'description',
                    'title' => 'Uraian / Keterangan Belanja',
                    'description' => 'Meminta uraian atau keterangan keperluan transaksi',
                    'is_enabled' => true,
                    'is_required' => true,
                    'prompt' => "Sekarang masukkan <b>URAIAN BELANJA / TRANSAKSI</b>:\n<i>(Wajib diisi, contoh: Pembelian Kertas HVS dan Spidol)</i>",
                    'help_text' => 'Keterangan belanja yang akan dicatat di buku kas.',
                ],
                'person' => [
                    'key' => 'person',
                    'title' => 'Nama Orang / Penanggung Jawab Transaksi',
                    'description' => 'Meminta nama guru/orang penanggung jawab yang melakukan transaksi (wajib diisi)',
                    'is_enabled' => true,
                    'is_required' => true,
                    'prompt' => "👤 Masukkan <b>NAMA GURU / PENANGGUNG JAWAB TRANSAKSI</b>:\n<i>(Wajib diisi, contoh: Pak Budi, Ibu Siti, atau nama Anda sendiri)</i>",
                    'help_text' => 'Ditanyakan pada pengeluaran (guru penanggung jawab belanja) maupun penerimaan (penyetor/sumber dana). Langkah ini wajib diisi.',
                ],
                'store_nota' => [
                    'key' => 'store_nota',
                    'title' => 'Foto Nota, Nama Toko & Nomor Nota (Khusus Pengeluaran)',
                    'description' => 'Meminta foto nota/kwitansi belanja beserta nama toko dan nomor nota',
                    'is_enabled' => true,
                    'is_required' => false,
                    'prompt' => "📸 <b>FOTO NOTA, NAMA TOKO & NO. NOTA</b>\nSilakan kirim <b>foto nota / kwitansi belanja</b> dengan <b>caption: Nama Toko, No. Nota</b>:\n<i>(Contoh: <code>Toko Barokah, N26-0050</code>)</i>\n\n• <i>Jika tanpa foto nota:</i> Cukup ketik teks <code>Nama Toko, No. Nota</code>\n• <i>Jika tanpa toko/nota/foto:</i> Ketik <b>/skip</b>",
                    'help_text' => 'Langkah 6: Foto bukti nota/kwitansi, nama toko, dan nomor nota pengeluaran.',
                ],
                'photos' => [
                    'key' => 'photos',
                    'title' => 'Foto Dokumentasi Barang / Bukti Transaksi',
                    'description' => 'Meminta upload foto dokumentasi barang fisik belanja (maks. 3 foto) atau bukti slip penerimaan',
                    'is_enabled' => true,
                    'is_required' => false,
                    'prompt' => "📸 <b>FOTO DOKUMENTASI BARANG FISIK</b>\nSilakan upload <b>foto fisik barang / kegiatan belanja</b> (maksimal 3 foto):\n<i>(Kirim 1 s/d 3 foto langsung dari kamera/galeri, atau ketik <b>/skip</b> jika tidak ada foto dokumentasi)</i>",
                    'help_text' => 'Langkah 7: Foto dokumentasi fisik barang (pengeluaran, maks. 3 foto) atau bukti slip setor/tarik (penerimaan).',
                ],
            ];
        }

        if ($command === 'foto') {
            return [
                'uraian' => [
                    'key' => 'uraian',
                    'title' => 'Uraian Barang / Kegiatan',
                    'description' => 'Meminta keterangan barang yang difoto',
                    'is_enabled' => true,
                    'is_required' => true,
                    'prompt' => "Masukkan <b>URAIAN BARANG / KEGIATAN</b>:\n<i>(Contoh: Pengadaan Meja Kursi Guru)</i>",
                    'help_text' => 'Keterangan untuk dokumentasi barang.',
                ],
                'store' => [
                    'key' => 'store',
                    'title' => 'Nama Toko / Sumber Barang',
                    'description' => 'Meminta nama toko/rekanan',
                    'is_enabled' => true,
                    'is_required' => false,
                    'prompt' => "Masukkan <b>NAMA TOKO / SUMBER BARANG</b>:\n<i>(Ketik <b>/skip</b> jika tidak ada)</i>",
                    'help_text' => 'Bisa diatur nonaktif atau wajib diisi.',
                ],
                'nota' => [
                    'key' => 'nota',
                    'title' => 'Nomor Nota Pembelian',
                    'description' => 'Meminta nomor nota',
                    'is_enabled' => true,
                    'is_required' => false,
                    'prompt' => "Masukkan <b>NOMOR NOTA PEMBELIAN</b>:\n<i>(Ketik <b>/skip</b> jika belum ada nota)</i>",
                    'help_text' => 'Bisa diatur nonaktif atau wajib diisi.',
                ],
            ];
        }

        return [];
    }

    /**
     * Mengambil alur langkah efektif dengan menggabungkan konfigurasi kustom dan default sistem.
     *
     * @return array<string, array{key: string, title: string, description: string, is_enabled: bool, is_required: bool, prompt: string, help_text: string}>
     */
    public function getEffectiveFlow(): array
    {
        $defaults = self::defaultFlow($this->command);
        if (empty($defaults)) {
            return [];
        }

        $saved = $this->flow_config ?: [];

        $effective = [];
        foreach ($defaults as $key => $defaultStep) {
            if (isset($saved[$key]) && is_array($saved[$key])) {
                $effective[$key] = array_merge($defaultStep, $saved[$key]);
            } else {
                $effective[$key] = $defaultStep;
            }
        }

        return $effective;
    }
}
