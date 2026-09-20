<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KertasKerjaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $realizedVolume = (float) $this->realisasis->sum('volume');
        $remainingVolume = max(0, (float) $this->volume - $realizedVolume);
        $totalAmount = (float) $this->total_amount;
        $price = (float) $this->price;
        $realizedAmount = $realizedVolume * $price;
        $remainingAmount = max(0, $totalAmount - $realizedAmount);

        $status = 'Belum Realisasi';
        $statusColor = 'secondary';
        if ($realizedVolume >= (float) $this->volume && (float) $this->volume > 0) {
            $status = 'Selesai Realisasi';
            $statusColor = 'success';
        } elseif ($realizedVolume > 0) {
            $status = 'Sebagian';
            $statusColor = 'warning';
        }

        return [
            'id' => $this->id,
            'year' => (int) $this->year,
            'month' => (int) $this->month,
            'month_name' => $this->month ? date('F', mktime(0, 0, 0, (int) $this->month, 10)) : '-',
            'account_code' => $this->account_code,
            'program_code' => $this->program_code,
            'kegiatan' => $this->kegiatan,
            'description' => $this->description,
            'volume' => (float) $this->volume,
            'unit' => $this->unit,
            'price' => $price,
            'total_amount' => $totalAmount,
            'realized_volume' => $realizedVolume,
            'remaining_volume' => $remainingVolume,
            'realized_amount' => $realizedAmount,
            'remaining_amount' => $remainingAmount,
            'status' => $status,
            'status_color' => $statusColor,
        ];
    }
}
