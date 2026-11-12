<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class UtilityReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'utility_receipts';

    protected $fillable = [
        'utility_profile_id',
        'fiscal_year',
        'billing_month',
        'transaction_date',
        'receipt_number',
        'reference_number',
        'customer_name',
        'customer_number',
        'meter_number',
        'tariff_power',
        'kwh_amount',
        'base_amount',
        'tax_pbjt',
        'tax_ppn',
        'admin_fee',
        'total_amount',
        'token_number',
        'payment_merchant',
        'receipt_file',
        'ocr_raw_data',
        'ocr_status',
        'ocr_attempts',
        'ocr_error_message',
        'ocr_processed_at',
        'bos_kertas_kerja_item_id',
        'bos_rencana_belanja_id',
        'pencairan_bank_item_id',
        'is_paid',
        'payment_date',
        'bku_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'payment_date' => 'date',
        'is_paid' => 'boolean',
        'fiscal_year' => 'integer',
        'kwh_amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'tax_pbjt' => 'decimal:2',
        'tax_ppn' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'ocr_raw_data' => 'array',
        'ocr_attempts' => 'integer',
        'ocr_processed_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(UtilityProfile::class, 'utility_profile_id');
    }

    public function kertasKerjaItem(): BelongsTo
    {
        return $this->belongsTo(BosKertasKerjaItem::class, 'bos_kertas_kerja_item_id');
    }

    public function rencanaBelanja(): BelongsTo
    {
        return $this->belongsTo(BosRencanaBelanja::class, 'bos_rencana_belanja_id');
    }

    public function pencairanBankItem(): BelongsTo
    {
        return $this->belongsTo(PencairanBankItem::class, 'pencairan_bank_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp '.number_format((float) $this->total_amount, 0, ',', '.');
    }

    public function getFormattedBaseAmountAttribute(): string
    {
        return 'Rp '.number_format((float) $this->base_amount, 0, ',', '.');
    }

    public function getFormattedAdminFeeAttribute(): string
    {
        return 'Rp '.number_format((float) $this->admin_fee, 0, ',', '.');
    }

    public function getReceiptUrlAttribute(): ?string
    {
        if (! $this->receipt_file) {
            return null;
        }

        return Storage::disk('public')->url($this->receipt_file);
    }

    public function getCleanTokenNumberAttribute(): ?string
    {
        if (! $this->token_number) {
            return null;
        }

        return preg_replace('/[^0-9]/', '', $this->token_number);
    }

    public function getOcrStatusBadgeAttribute(): string
    {
        return match ($this->ocr_status) {
            'completed' => '<span class="badge badge-success text-white px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-check-circle mr-1"></i>Selesai</span>',
            'processing' => '<span class="badge badge-info text-white px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-spinner fa-spin mr-1"></i>Memindai</span>',
            'pending' => '<span class="badge badge-warning text-dark px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-clock mr-1"></i>Menunggu</span>',
            'failed' => '<span class="badge badge-danger text-white px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-exclamation-triangle mr-1"></i>Perlu Manual</span>',
            'manual' => '<span class="badge badge-secondary text-white px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-keyboard mr-1"></i>Manual</span>',
            default => '<span class="badge badge-light text-muted px-2 py-1" style="font-size: 0.72rem;">'.($this->ocr_status ?: 'Unknown').'</span>',
        };
    }

    /**
     * Find existing receipt that matches duplicate attributes
     */
    public static function findDuplicate(array $attributes, ?int $exceptId = null): ?self
    {
        $token = ! empty($attributes['token_number']) ? preg_replace('/[^0-9]/', '', (string) $attributes['token_number']) : null;
        $receiptNo = ! empty($attributes['receipt_number']) ? trim((string) $attributes['receipt_number']) : null;
        $refNo = ! empty($attributes['reference_number']) ? trim((string) $attributes['reference_number']) : null;
        $profileId = $attributes['utility_profile_id'] ?? $attributes['matched_profile_id'] ?? null;
        if (! $profileId && ! empty($attributes['customer_number'])) {
            $matched = UtilityProfile::where('customer_number', $attributes['customer_number'])->first();
            if ($matched) {
                $profileId = $matched->id;
            }
        }
        if (! $profileId && ! empty($attributes['meter_number'])) {
            $matched = UtilityProfile::where('meter_number', $attributes['meter_number'])->first();
            if ($matched) {
                $profileId = $matched->id;
            }
        }
        $fiscalYear = ! empty($attributes['fiscal_year']) ? (int) $attributes['fiscal_year'] : null;
        $billingMonth = ! empty($attributes['billing_month']) ? trim((string) $attributes['billing_month']) : null;
        $totalAmount = isset($attributes['total_amount']) ? (float) $attributes['total_amount'] : null;
        $transactionDate = null;
        if (! empty($attributes['transaction_date'])) {
            try {
                $transactionDate = Carbon::parse($attributes['transaction_date']);
            } catch (\Exception $e) {
                // Ignore parse errors
            }
        }

        // 1. Check duplicate 20-digit electricity token
        if ($token && strlen($token) >= 16) {
            $query = self::query();
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
            $existing = $query->whereNotNull('token_number')->get()->first(function ($r) use ($token) {
                return preg_replace('/[^0-9]/', '', (string) $r->token_number) === $token;
            });
            if ($existing) {
                return $existing;
            }
        }

        // 2. Check duplicate receipt number or reference number
        if ($receiptNo && strlen($receiptNo) >= 4) {
            $query = self::query();
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
            $query->where(function ($q) use ($receiptNo) {
                $q->where('receipt_number', $receiptNo)
                    ->orWhere('reference_number', $receiptNo);
            });
            if ($totalAmount && $totalAmount > 0) {
                $query->where('total_amount', $totalAmount);
            }
            if ($profileId) {
                $query->where('utility_profile_id', $profileId);
            }
            $existing = $query->first();
            if ($existing) {
                return $existing;
            }
        }

        if ($refNo && strlen($refNo) >= 4) {
            $query = self::query();
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
            $query->where(function ($q) use ($refNo) {
                $q->where('reference_number', $refNo)
                    ->orWhere('receipt_number', $refNo);
            });
            if ($totalAmount && $totalAmount > 0) {
                $query->where('total_amount', $totalAmount);
            }
            $existing = $query->first();
            if ($existing) {
                return $existing;
            }
        }

        // 3. Check duplicate Profile + Year + Month + Total Amount
        if ($profileId && $fiscalYear && $billingMonth && $totalAmount && $totalAmount > 0) {
            $query = self::query();
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
            $query->where('utility_profile_id', $profileId)
                ->where('fiscal_year', $fiscalYear)
                ->where('billing_month', $billingMonth)
                ->where('total_amount', $totalAmount);

            if ($transactionDate) {
                $query->whereDate('transaction_date', $transactionDate->toDateString());
            }

            $existing = $query->first();
            if ($existing) {
                return $existing;
            }
        }

        return null;
    }
}
