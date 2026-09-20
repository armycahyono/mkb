<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UtilityReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fileUrl = null;
        if ($this->receipt_file) {
            $fileUrl = Storage::disk('public')->exists($this->receipt_file)
                ? asset('storage/'.$this->receipt_file)
                : null;
        }

        return [
            'id' => $this->id,
            'profile_name' => $this->utilityProfile?->name,
            'profile_category' => $this->utilityProfile?->category,
            'fiscal_year' => (int) $this->fiscal_year,
            'billing_month' => (int) $this->billing_month,
            'transaction_date' => $this->transaction_date?->format('Y-m-d H:i'),
            'receipt_number' => $this->receipt_number,
            'customer_name' => $this->customer_name,
            'customer_number' => $this->customer_number,
            'token_number' => $this->token_number,
            'total_amount' => (float) $this->total_amount,
            'is_paid' => (bool) $this->is_paid,
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'file_url' => $fileUrl,
            'notes' => $this->notes,
        ];
    }
}
