<?php

namespace App\Services;

use App\Models\UtilityProfile;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class UtilityReceiptOcrService
{
    protected UtilityReceiptLocalParserService $localParser;

    protected ReceiptImagePreprocessorService $preprocessor;

    public function __construct(
        ?UtilityReceiptLocalParserService $localParser = null,
        ?ReceiptImagePreprocessorService $preprocessor = null
    ) {
        $this->localParser = $localParser ?: app(UtilityReceiptLocalParserService::class);
        $this->preprocessor = $preprocessor ?: app(ReceiptImagePreprocessorService::class);
    }

    /**
     * Scan and extract utility receipt details using 100% Local OCR Parser
     */
    public function scan(UploadedFile|string $image, ?string $rawText = null, string $engine = 'local'): array
    {
        // 0. Pre-process image (Auto-Rotate, Auto-Crop, Contrast Boost, Unsharp Sharpen)
        $enhancedBase64 = null;
        try {
            $prepResult = $this->preprocessor->process($image);
            if ($prepResult['success']) {
                $enhancedBase64 = $prepResult['base64'];
            }
        } catch (Exception $e) {
            Log::warning('UtilityReceiptOcrService image preprocessing skipped: '.$e->getMessage());
        }

        // 1. If raw text provided, parse locally
        if (! empty($rawText)) {
            $localResult = $this->localParser->parse($rawText);
            if ($enhancedBase64) {
                $localResult['enhanced_image'] = $enhancedBase64;
            }

            return $localResult;
        }

        return [
            'success' => false,
            'message' => 'Teks struk belum terdeteksi. Silakan periksa kembali foto atau isi formulir secara manual.',
            'enhanced_image' => $enhancedBase64,
            'fallback_to_offline' => true,
        ];
    }

    /**
     * Smart match extracted receipt data against registered 9 utility profiles
     */
    public function matchProfile(array $data): ?UtilityProfile
    {
        $profiles = UtilityProfile::where('is_active', true)->get();

        $meter = preg_replace('/[^0-9]/', '', (string) ($data['meter_number'] ?? ''));
        $custNo = preg_replace('/[^0-9]/', '', (string) ($data['customer_number'] ?? ''));
        $name = strtolower(trim((string) ($data['customer_name'] ?? '')));

        // 1. Exact match by meter_number or customer_number / IDPEL
        if (! empty($meter)) {
            $match = $profiles->first(function ($p) use ($meter) {
                $pMeter = preg_replace('/[^0-9]/', '', (string) $p->meter_number);
                $pCust = preg_replace('/[^0-9]/', '', (string) $p->customer_number);

                return ($pMeter !== '' && $pMeter === $meter) || ($pCust !== '' && $pCust === $meter);
            });

            if ($match) {
                return $match;
            }
        }

        if (! empty($custNo)) {
            $match = $profiles->first(function ($p) use ($custNo) {
                $pCust = preg_replace('/[^0-9]/', '', (string) $p->customer_number);
                $pIdpel = preg_replace('/[^0-9]/', '', (string) $p->idpel);

                return ($pCust !== '' && $pCust === $custNo) || ($pIdpel !== '' && $pIdpel === $custNo);
            });

            if ($match) {
                return $match;
            }
        }

        // 2. Keyword and entity match by customer_name / payment_merchant
        $merchant = strtolower(trim((string) ($data['payment_merchant'] ?? '')));
        $combinedText = $name.' '.$merchant.' '.strtolower((string) ($data['utility_type'] ?? ''));

        if (! empty($combinedText)) {
            if (str_contains($combinedText, 'menaksopal') || str_contains($combinedText, 'nusantar') || str_contains($combinedText, 'link nusantar') || str_contains($combinedText, 'aliya')) {
                return $profiles->firstWhere('code', 'internet_menaksopal');
            }

            if (str_contains($name, 'kras') || str_contains($name, 'smk pgri')) {
                // If it has a token or meter number, prioritize token_smk_kras
                if (! empty($data['token_number']) || ! empty($meter)) {
                    return $profiles->firstWhere('code', 'token_smk_kras') ?? $profiles->firstWhere('code', 'speedy_smk_kras');
                }

                return $profiles->firstWhere('code', 'speedy_smk_kras') ?? $profiles->firstWhere('code', 'token_smk_kras');
            }

            if (str_contains($name, 'masjid') || str_contains($name, 'annur') || str_contains($name, 'an nur')) {
                return $profiles->firstWhere('code', 'listrik_masjid');
            }

            if (str_contains($name, 'smp')) {
                return $profiles->firstWhere('code', 'listrik_smp');
            }

            if (str_contains($name, 'stm pgri') || (str_contains($name, 'stm') && ! empty($data['token_number']))) {
                return $profiles->firstWhere('code', 'token_stm_pgri');
            }

            if (str_contains($name, 'stm')) {
                return $profiles->firstWhere('code', 'listrik_stm');
            }

            if (str_contains($name, 'imam') || str_contains($name, 'sjadeli')) {
                return $profiles->firstWhere('code', 'speedy_imam');
            }
        }

        return null;
    }
}
