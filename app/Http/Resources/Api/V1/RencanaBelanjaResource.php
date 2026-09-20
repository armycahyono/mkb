<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RencanaBelanjaResource extends JsonResource
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
            'title' => $this->title,
            'no_nota' => $this->no_nota,
            'year' => (int) $this->year,
            'month' => (int) $this->month,
            'supplier_name' => $this->supplier_name ?? $this->supplier?->name ?? 'Toko / Rekanan',
            'planned_date' => $this->planned_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'draft' => 'Draft',
                'diajukan_ke_bank' => 'Diajukan ke TNT Bank',
                'selesai' => 'Selesai Realisasi',
                default => ucfirst(str_replace('_', ' ', $this->status ?? 'Draft')),
            },
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'total_amount' => (float) $this->total_amount,
            'items_count' => $this->items_count ?? $this->items->count(),
            'items' => RencanaBelanjaItemResource::collection($this->whenLoaded('items')),
            'notes' => $this->notes,
        ];
    }
}
