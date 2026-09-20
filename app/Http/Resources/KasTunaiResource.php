<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KasTunaiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'funding_source' => $this->funding_source,
            'transaction_date' => $this->transaction_date,
            'transaction_type' => $this->transaction_type,
            'category' => $this->category,
            'kas_category_id' => $this->kas_category_id,
            'amount' => $this->amount,
            'description' => $this->description,
            'person_name' => $this->person_name,
            'guru_id' => $this->guru_id,
            'pencairan_bank_id' => $this->pencairan_bank_id,
            'bos_rencana_belanja_id' => $this->bos_rencana_belanja_id,
            'perjadin_item_id' => $this->perjadin_item_id,
            'parent_kas_id' => $this->parent_kas_id,
            'panjar_status' => $this->panjar_status,
            'year' => $this->year,
            'sequence' => $this->sequence,
            'evidence_number' => $this->evidence_number,
            'receipt_file' => $this->receipt_file,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
