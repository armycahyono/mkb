<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengajuanBarangStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare data before validation by cleaning thousand separators.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('items') && is_array($this->input('items'))) {
            $items = $this->input('items');
            foreach ($items as $k => $item) {
                if (isset($item['harga']) && is_string($item['harga'])) {
                    $items[$k]['harga'] = str_replace('.', '', $item['harga']);
                }
                if (isset($item['volume']) && is_string($item['volume'])) {
                    $items[$k]['volume'] = str_replace('.', '', $item['volume']);
                }
                if (isset($item['jumlah']) && is_string($item['jumlah'])) {
                    $items[$k]['jumlah'] = str_replace('.', '', $item['jumlah']);
                }
            }
            $this->merge(['items' => $items]);
        } else {
            $merge = [];
            if ($this->has('harga') && is_string($this->input('harga'))) {
                $merge['harga'] = str_replace('.', '', $this->input('harga'));
            }
            if ($this->has('volume') && is_string($this->input('volume'))) {
                $merge['volume'] = str_replace('.', '', $this->input('volume'));
            }
            if ($this->has('jumlah') && is_string($this->input('jumlah'))) {
                $merge['jumlah'] = str_replace('.', '', $this->input('jumlah'));
            }
            if (! empty($merge)) {
                $this->merge($merge);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if ($this->has('items') && is_array($this->input('items'))) {
            return [
                'tahun_anggaran' => 'required|digits:4|integer',
                'guru_id' => 'nullable|exists:gurus,id',
                'pemohon_id' => 'nullable|exists:users,id',
                'kegiatan' => 'nullable|string|max:255',
                'bulan_pakai' => 'nullable|string|max:50',
                'items' => 'required|array|min:1',
                'items.*.nama_barang' => 'required|string|max:255',
                'items.*.spesifikasi' => 'required|string',
                'items.*.volume' => 'required|numeric|min:0.01',
                'items.*.satuan' => 'required|string|max:50',
                'items.*.harga' => 'required|numeric|min:0',
                'items.*.jumlah' => 'nullable|numeric|min:0',
                'items.*.kegiatan' => 'nullable|string|max:255',
                'items.*.bulan_pakai' => 'nullable|string|max:50',
                'items.*.keterangan' => 'nullable|string',
            ];
        }

        return [
            'tahun_anggaran' => 'required|digits:4|integer',
            'guru_id' => 'nullable|exists:gurus,id',
            'pemohon_id' => 'nullable|exists:users,id',
            'nama_barang' => 'required|string|max:255',
            'spesifikasi' => 'required|string',
            'volume' => 'required|numeric|min:0.01',
            'satuan' => 'required|string|max:50',
            'harga' => 'required|numeric|min:0',
            'jumlah' => 'required|numeric|min:0',
            'kegiatan' => 'required|string|max:255',
            'bulan_pakai' => 'required|string|max:50',
            'keterangan' => 'nullable|string',
            'realisasi' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tahun_anggaran' => 'Tahun Anggaran',
            'pemohon_id' => 'Pemohon',
            'kegiatan' => 'Kegiatan',
            'bulan_pakai' => 'Bulan Pakai',
            'items.*.nama_barang' => 'Nama Barang',
            'items.*.spesifikasi' => 'Spesifikasi',
            'items.*.volume' => 'Volume',
            'items.*.satuan' => 'Satuan',
            'items.*.harga' => 'Harga Satuan',
            'items.*.jumlah' => 'Jumlah Total',
            'items.*.kegiatan' => 'Kegiatan',
            'items.*.bulan_pakai' => 'Bulan Pakai',
        ];
    }
}
