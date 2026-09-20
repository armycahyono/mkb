<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KasTunaiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_display' => $this->id_display ?? 'TRX-'.str_pad($this->id, 5, '0', STR_PAD_LEFT),
            'funding_source' => $this->funding_source,
            'transaction_date' => $this->transaction_date,
            'transaction_type' => $this->transaction_type,
            'category' => $this->category,
            'category_label' => $this->category_label ?? ($this->kasCategory ? $this->kasCategory->name : ucfirst(str_replace('_', ' ', $this->category))),
            'kas_category_id' => $this->kas_category_id,
            'amount' => $this->amount,
            'description' => $this->description,
            'person_name' => $this->person_name,
            'guru_id' => $this->guru_id,
            'guru_name' => $this->whenLoaded('guru', fn () => $this->guru->name),
            'parent_kas_id' => $this->parent_kas_id,
            'panjar_status' => $this->panjar_status,
            'remaining_panjar_amount' => $this->when($this->category === 'panjar_kerja', $this->remaining_panjar_amount ?? 0),
            'evidence_number' => $this->evidence_number,
            'year' => $this->year,
            'sequence' => $this->sequence,
            'items_count' => $this->whenLoaded('items', fn () => $this->items->count(), 0),
            'created_at' => $this->created_at,
        ];
    }
}
