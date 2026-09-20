<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UtilityProfileResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'category_label' => $this->category_label ?? ucfirst($this->category),
            'customer_name' => $this->customer_name,
            'customer_number' => $this->customer_number,
            'meter_number' => $this->meter_number,
            'idpel' => $this->idpel,
            'provider' => $this->provider,
        ];
    }
}
