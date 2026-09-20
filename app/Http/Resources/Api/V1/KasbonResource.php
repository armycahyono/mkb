<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KasbonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $daysElapsed = $this->transaction_date ? Carbon::parse($this->transaction_date)->diffInDays(now()) : 0;

        $agingStatus = 'baru';
        if ($daysElapsed > 7) {
            $agingStatus = 'menunggak';
        } elseif ($daysElapsed > 3) {
            $agingStatus = 'perhatian';
        }

        return [
            'id' => $this->id,
            'id_display' => $this->id_display,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'person_name' => $this->person_name,
            'guru_name' => $this->whenLoaded('guru', fn () => $this->guru?->name),
            'panjar_status' => $this->panjar_status,
            'settled_amount' => (float) $this->settled_panjar_amount,
            'remaining_amount' => (float) $this->remaining_panjar_amount,
            'days_elapsed' => (int) $daysElapsed,
            'aging_status' => $agingStatus,
            'children' => KasTunaiResource::collection($this->whenLoaded('childKass')),
        ];
    }
}
