<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TntItemResource extends JsonResource
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
            'type' => $this->type,
            'recipient_name' => $this->recipient_name,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'amount' => (float) $this->amount,
            'description' => $this->description,
        ];
    }
}
