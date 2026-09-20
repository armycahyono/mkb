<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TntResource extends JsonResource
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
            'funding_source' => $this->funding_source,
            'letter_number' => $this->letter_number,
            'letter_date' => $this->letter_date?->format('Y-m-d'),
            'bank_name' => $this->bank_name,
            'bank_branch' => $this->bank_branch,
            'source_account_number' => $this->source_account_number,
            'source_account_name' => $this->source_account_name,
            'total_nominal' => (float) $this->total_nominal,
            'total_accounts' => (int) $this->total_accounts,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'cair' => 'Sudah Cair',
                'diajukan' => 'Diajukan ke Bank',
                default => ucfirst($this->status ?? 'Draft'),
            },
            'cair_date' => $this->cair_date?->format('Y-m-d'),
            'items_count' => $this->items_count ?? $this->items->count(),
            'items' => TntItemResource::collection($this->whenLoaded('items')),
            'notes' => $this->notes,
        ];
    }
}
