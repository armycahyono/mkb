<?php

namespace App\Jobs;

use App\Http\Controllers\UtilityReceiptController;
use App\Models\UtilityReceipt;
use App\Services\UtilityReceiptOcrService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessUtilityReceiptOcrJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public array $backoff = [15, 30, 60, 120];

    public function __construct(public UtilityReceipt $receipt) {}

    public function handle(UtilityReceiptOcrService $ocrService): void
    {
        $this->receipt->refresh();

        if ($this->receipt->trashed() || in_array($this->receipt->ocr_status, ['completed', 'manual'])) {
            return;
        }

        $this->receipt->update([
            'ocr_status' => 'processing',
            'ocr_attempts' => $this->receipt->ocr_attempts + 1,
        ]);

        if (empty($this->receipt->receipt_file) || ! Storage::disk('public')->exists($this->receipt->receipt_file)) {
            $this->receipt->update([
                'ocr_status' => 'failed',
                'ocr_error_message' => 'Berkas foto struk tidak ditemukan di server.',
            ]);

            return;
        }

        try {
            $filePath = Storage::disk('public')->path($this->receipt->receipt_file);
            $rawText = null;
            if (! empty($this->receipt->ocr_raw_data)) {
                $rawText = is_string($this->receipt->ocr_raw_data) ? $this->receipt->ocr_raw_data : json_encode($this->receipt->ocr_raw_data);
            }
            $result = $ocrService->scan($filePath, $rawText);

            // Update stored receipt image if enhanced by preprocessor
            if (! empty($result['enhanced_image']) && str_starts_with($result['enhanced_image'], 'data:image')) {
                [, $rawBase64] = explode(',', $result['enhanced_image'], 2);
                $decoded = base64_decode($rawBase64);
                if ($decoded) {
                    Storage::disk('public')->put($this->receipt->receipt_file, $decoded);
                }
            }

            if ($result['success'] && ! empty($result['data'])) {
                $d = $result['data'];

                $updateData = [
                    'ocr_status' => 'completed',
                    'ocr_processed_at' => now(),
                    'ocr_error_message' => null,
                    'ocr_raw_data' => $d['raw_ocr'] ?? $d,
                ];

                if (! empty($d['matched_profile_id'])) {
                    $updateData['utility_profile_id'] = $d['matched_profile_id'];
                }

                if (! empty($d['transaction_date'])) {
                    $updateData['transaction_date'] = $d['transaction_date'];
                }

                if (! empty($d['fiscal_year'])) {
                    $updateData['fiscal_year'] = $d['fiscal_year'];
                }

                if (! empty($d['billing_month'])) {
                    $updateData['billing_month'] = $d['billing_month'];
                }

                if (! empty($d['customer_name'])) {
                    $updateData['customer_name'] = $d['customer_name'];
                }

                if (! empty($d['customer_number'])) {
                    $updateData['customer_number'] = $d['customer_number'];
                }

                if (! empty($d['meter_number'])) {
                    $updateData['meter_number'] = $d['meter_number'];
                }

                if (! empty($d['tariff_power'])) {
                    $updateData['tariff_power'] = $d['tariff_power'];
                }

                if (! empty($d['kwh_amount'])) {
                    $updateData['kwh_amount'] = $d['kwh_amount'];
                }

                if (isset($d['base_amount'])) {
                    $updateData['base_amount'] = $d['base_amount'];
                }

                if (isset($d['tax_pbjt'])) {
                    $updateData['tax_pbjt'] = $d['tax_pbjt'];
                }

                if (isset($d['tax_ppn'])) {
                    $updateData['tax_ppn'] = $d['tax_ppn'];
                }

                if (isset($d['admin_fee'])) {
                    $updateData['admin_fee'] = $d['admin_fee'];
                }

                if (! empty($d['total_amount'])) {
                    $updateData['total_amount'] = $d['total_amount'];
                }

                if (! empty($d['token_number'])) {
                    $updateData['token_number'] = $d['token_number'];
                }

                if (! empty($d['receipt_number'])) {
                    $updateData['receipt_number'] = $d['receipt_number'];
                }

                if (! empty($d['reference_number'])) {
                    $updateData['reference_number'] = $d['reference_number'];
                }

                if (! empty($d['payment_merchant'])) {
                    $updateData['payment_merchant'] = $d['payment_merchant'];
                }

                // Check for duplicate with existing completed receipts
                $duplicate = UtilityReceipt::findDuplicate($updateData, $this->receipt->id);
                if ($duplicate) {
                    $this->receipt->update([
                        'ocr_status' => 'failed',
                        'ocr_processed_at' => now(),
                        'ocr_error_message' => 'Duplikat: Struk ini terdeteksi sama dengan data ID #'.$duplicate->id.' ('.$duplicate->billing_month.' '.$duplicate->fiscal_year.').',
                    ]);
                    Log::warning("Utility Receipt #{$this->receipt->id} skipped because duplicate of #{$duplicate->id}");

                    return;
                }

                // Auto-link ke BosKertasKerjaItem jika profil bertipe anggaran BOS
                $profileId = $updateData['utility_profile_id'] ?? $this->receipt->utility_profile_id;
                $fiscalYear = (int) ($updateData['fiscal_year'] ?? $this->receipt->fiscal_year ?? date('Y'));
                $billingMonth = (string) ($updateData['billing_month'] ?? $this->receipt->billing_month ?? date('m'));
                $totalAmount = (float) ($updateData['total_amount'] ?? $this->receipt->total_amount ?? 0);

                if ($profileId && empty($this->receipt->bos_kertas_kerja_item_id)) {
                    $controller = app(UtilityReceiptController::class);
                    $matchedKkId = $controller->findMatchingKertasKerjaItemId($profileId, $fiscalYear, $billingMonth, $totalAmount);
                    if ($matchedKkId) {
                        $updateData['bos_kertas_kerja_item_id'] = $matchedKkId;
                    }
                }

                $this->receipt->update($updateData);

                Log::info("Utility Receipt #{$this->receipt->id} successfully processed by background OCR.");
            } else {
                $errorMsg = $result['message'] ?? 'OCR gagal mengekstrak teks dari struk.';
                $attempts = $this->receipt->ocr_attempts;

                $this->receipt->update([
                    'ocr_status' => ($attempts >= $this->tries) ? 'failed' : 'pending',
                    'ocr_error_message' => $errorMsg,
                ]);

                if ($attempts < $this->tries) {
                    $delaySec = $this->backoff[$attempts - 1] ?? 60;
                    $this->release($delaySec);
                }
            }
        } catch (Exception $e) {
            Log::warning("ProcessUtilityReceiptOcrJob error for Receipt #{$this->receipt->id}: ".$e->getMessage());

            $attempts = $this->receipt->ocr_attempts;
            $this->receipt->update([
                'ocr_status' => ($attempts >= $this->tries) ? 'failed' : 'pending',
                'ocr_error_message' => $e->getMessage(),
            ]);

            if ($attempts < $this->tries) {
                $delaySec = $this->backoff[$attempts - 1] ?? 60;
                $this->release($delaySec);
            }
        }
    }
}
