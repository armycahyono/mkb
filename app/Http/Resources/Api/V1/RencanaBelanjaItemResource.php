<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RencanaBelanjaItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_name' => $this->item_name,
            'specification' => $this->specification,
            'volume' => (float) $this->volume,
            'unit' => $this->unit,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->total_price,
            'kegiatan' => $this->kertasKerjaItem?->kegiatan,
            'rekening' => $this->kertasKerjaItem?->account_code,
        ];
    }
}
