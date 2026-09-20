<?php

namespace App\Services;

use App\Models\UtilityProfile;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UtilityReceiptOcrService
{
    protected ?string $apiKey;

    protected string $model;

    protected UtilityReceiptLocalParserService $localParser;

    protected ReceiptImagePreprocessorService $preprocessor;

    public function __construct(
        ?UtilityReceiptLocalParserService $localParser = null,
        ?ReceiptImagePreprocessorService $preprocessor = null
    ) {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-2.5-flash');
        $this->localParser = $localParser ?: app(UtilityReceiptLocalParserService::class);
        $this->preprocessor = $preprocessor ?: app(ReceiptImagePreprocessorService::class);
    }

    /**
     * Scan and extract utility receipt details using Dual-Engine (Local Regex Matcher + Gemini Vision API)
     */
    public function scan(UploadedFile|string $image, ?string $rawText = null, string $engine = 'auto'): array
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

        // 1. If raw text provided, parse locally first
        $localResult = null;
        if (! empty($rawText)) {
            $localResult = $this->localParser->parse($rawText);
            if ($enhancedBase64) {
                $localResult['enhanced_image'] = $enhancedBase64;
            }
            // If local result succeeded or engine is explicitly local, return local result immediately
            if ($localResult['success'] && (! empty($localResult['data']['matched_profile_id']) || ! empty($localResult['data']['total_amount']) || ! empty($localResult['data']['token_number']))) {
                return $localResult;
            }
            if ($engine === 'local') {
                return $localResult;
            }
        }

        if (empty($this->apiKey)) {
            // If local parsed result exists, use it instead of failing
            if ($localResult) {
                return $localResult;
            }

            return [
                'success' => false,
                'message' => 'Teks struk belum terdeteksi. Silakan periksa kembali foto atau isi formulir secara manual.',
                'enhanced_image' => $enhancedBase64,
                'fallback_to_offline' => true,
            ];
        }

        try {
            $base64Image = '';
            $mimeType = 'image/jpeg';

            if ($enhancedBase64 && str_starts_with($enhancedBase64, 'data:image')) {
                [$meta, $data] = explode(',', $enhancedBase64, 2);
                $mimeType = 'image/jpeg';
                $base64Image = $data;
            } elseif ($image instanceof UploadedFile) {
                $mimeType = $image->getMimeType() ?: 'image/jpeg';
                $base64Image = base64_encode(file_get_contents($image->getRealPath()));
            } elseif (is_string($image) && file_exists($image)) {
                $mimeType = mime_content_type($image) ?: 'image/jpeg';
                $base64Image = base64_encode(file_get_contents($image));
            } elseif (is_string($image) && str_starts_with($image, 'data:image')) {
                [$meta, $data] = explode(',', $image, 2);
                if (preg_match('/data:(image\/[a-zA-Z+]+);base64/', $meta, $matches)) {
                    $mimeType = $matches[1];
                }
                $base64Image = $data;
            } else {
                return [
                    'success' => false,
                    'message' => 'Format berkas foto struk tidak valid.',
                    'fallback_to_offline' => true,
                ];
            }

            $systemPrompt = 'Anda adalah asisten AI OCR spesialis pembacaan struk pembayaran utilitas resmi di Indonesia, meliputi:
1. Struk PT POS INDONESIA / Loket PPOB / Bank Jatim / PLN Mobile (Listrik Pasca & Token Prabayar 20 digit).
2. Struk Telkom / Speedy / Indihome.
3. Struk / Bukti Transfer Bank & ATM (Link, ATM BRI, ATM BCA, ATM Mandiri, Bank Jatim, M-Banking, dll) untuk pembayaran internet (seperti Menaksopal Link Nusantara).

Analisis gambar foto struk ini dan ekstrak seluruh data dengan presisi tinggi ke dalam JSON murni tanpa kutip markdown (tanpa ```json).

Skema JSON yang wajib dihasilkan:
{
  "payment_merchant": "Nama Mitra/Loket/ATM/Channel (contoh: 9843-BRI UNIT KRAS / PT POS INDONESIA Kpc. Kras 64172 / BANK JATIM / ATM BCA)",
  "receipt_number": "Nomor Resi / No. Bukti / NO.REF / No. Transaksi (contoh: 07205 / 64172-03/2026/805315)",
  "reference_number": "Nomor Referensi / No. Ref / ATM ID (contoh: 07205 / 91461)",
  "transaction_date": "YYYY-MM-DD HH:MM:SS (format tanggal dan jam bayar. PENTING: jika tanggal tercetak format DD/MM/YY seperti 07/07/26 ubah menjadi 2026-07-07 13:38:18)",
  "billing_month": "Nama bulan tagihan dalam Bahasa Indonesia (contoh: Januari, Februari, Juli, dll)",
  "utility_type": "listrik_pra" atau "listrik_pasca" atau "telkom_speedy" atau "internet" atau "lainnya",
  "meter_number": "Nomor Meter jika prabayar / token (contoh: 32118573479, jika transfer bank isi null)",
  "customer_number": "ID Pelanggan (IDPEL) atau No. Telepon atau No. Rekening Tujuan Transfer (contoh: 3243258444 / 514031084530 / 0354-000479487)",
  "customer_name": "Nama Pelanggan / Penerima Transfer pada struk (contoh: MENAKSOPAL LINK NUSANTAR / SMK PGRI KRAS / STM / MASJID AN NUR / SMP PGRI / IMAM SJADELI)",
  "tariff_power": "Tarif dan Daya listrik jika ada (contoh: S1/5500 VA, R1/2200 VA, atau null jika internet/transfer)",
  "kwh_amount": 0.0 (jumlah kWh energi jika ada, misal: 1010.2, atau null),
  "base_amount": 0 (Nilai transfer pokok / tagihan pokok / nominal token tanpa admin dan pajak),
  "tax_pbjt": 0 (Nilai Pajak PBJT-TL / PPJ jika ada, 0 jika transfer bank/internet),
  "tax_ppn": 0 (Nilai PPN jika ada),
  "admin_fee": 0 (Biaya admin transfer / loket / pos / bank jika ada),
  "total_amount": 0 (Total RP BAYAR / JUMLAH transfer yang dibayarkan, contoh: 1800000),
  "token_number": "20 digit nomor token stroom jika listrik prabayar (format: 4 digit spasi 4 digit, contoh: 5001 0208 1701 5895 0864, null jika bukan token listrik)"
}

Peraturan penting:
1. Seluruh nilai uang (base_amount, tax_pbjt, admin_fee, total_amount) harus berupa integer atau angka murni tanpa titik atau koma.
2. Untuk struk transfer ATM / Bank internet (seperti Link / BRI / BCA kepada MENAKSOPAL):
   - Ambil tanggal & jam transaksi (misal: 07/07/26 13:38:18 -> 2026-07-07 13:38:18).
   - Ambil nomor rekening tujuan (contoh: REKENING 3243258444) ke customer_number.
   - Ambil nama penerima (contoh: MENAKSOPAL LINK NUSANTAR) ke customer_name.
   - Ambil jumlah transfer (contoh: JUMLAH RP 1.800.000 -> 1800000) ke total_amount & base_amount.
   - utility_type = "internet".
3. Jika ada nomor token listrik (20 digit angka), pastikan tidak salah baca dan masukkan ke token_number.
4. Untuk struk Listrik Prabayar (PLN Prepaid / Token / PT POS INDONESIA / Loket Pembayaran):
   - "RP BAYAR" adalah TOTAL BAYAR (masukkan langsung ke total_amount). JANGAN PERNAH menambahkan biaya admin lagi ke RP BAYAR karena baris "RP BAYAR" di struk POS/PLN sudah merupakan total akhir yang dibayarkan pelanggan!
   - "RP STROOM/TOKEN" atau "RUPIAH TOKEN" masukkan ke base_amount.
   - "ADMIN POS / ADMIN BANK" masukkan ke admin_fee.
   - "PBJT-TL / PPJ" masukkan ke tax_pbjt.';

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Image,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature' => 0.1,
                ],
            ];

            $candidateModels = array_values(array_unique(array_filter([
                $this->model ?: null,
                'gemini-2.5-flash',
                'gemini-2.0-flash',
                'gemini-2.5-flash-lite',
                'gemini-1.5-flash',
                'gemini-2.5-pro',
            ])));

            $response = null;
            $usedModel = null;
            $lastErrorStatus = null;
            $lastErrorMessage = '';

            foreach ($candidateModels as $candidate) {
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$candidate}:generateContent?key={$this->apiKey}";

                try {
                    $res = Http::retry(2, 300, throw: false)->timeout(25)->post($endpoint, $payload);
                    if ($res->successful()) {
                        $response = $res;
                        $usedModel = $candidate;
                        break;
                    }

                    $lastErrorStatus = $res->status();
                    $lastErrorMessage = $res->body();
                    Log::warning("Gemini OCR model [{$candidate}] failed with status {$lastErrorStatus}: {$lastErrorMessage}");

                    // If 503 (high demand), 429 (quota), or 404 (model not found), try next model
                    if (in_array($lastErrorStatus, [404, 429, 500, 502, 503, 504])) {
                        continue;
                    }

                    break;
                } catch (Exception $e) {
                    Log::warning("Gemini OCR model [{$candidate}] exception: ".$e->getMessage());

                    continue;
                }
            }

            if (! $response || ! $response->successful()) {
                if ($localResult && $localResult['success']) {
                    return $localResult;
                }

                return [
                    'success' => false,
                    'message' => 'Layanan AI Cloud Gemini sedang mengalami antrean/gangguan (status '.($lastErrorStatus ?: '503').'). Silakan masukkan data manual atau gunakan pemindaian lokal.',
                    'enhanced_image' => $enhancedBase64,
                    'fallback_to_offline' => true,
                ];
            }

            $result = $response->json();
            $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Clean markdown block
            $cleanedJson = trim($rawText);
            if (str_starts_with($cleanedJson, '```json')) {
                $cleanedJson = substr($cleanedJson, 7);
            }
            if (str_starts_with($cleanedJson, '```')) {
                $cleanedJson = substr($cleanedJson, 3);
            }
            if (str_ends_with($cleanedJson, '```')) {
                $cleanedJson = substr($cleanedJson, 0, -3);
            }
            $cleanedJson = trim($cleanedJson);

            $data = json_decode($cleanedJson, true);

            if (! is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Format respons AI tidak dapat diparsing. Silakan masukkan data manual.',
                    'enhanced_image' => $enhancedBase64,
                    'fallback_to_offline' => true,
                ];
            }

            // Normalisasi angka
            $totalAmount = (float) ($data['total_amount'] ?? 0);
            $baseAmount = (float) ($data['base_amount'] ?? 0);
            $adminFee = (float) ($data['admin_fee'] ?? 0);
            $taxPbjt = (float) ($data['tax_pbjt'] ?? 0);
            $taxPpn = (float) ($data['tax_ppn'] ?? 0);

            // Khusus Listrik Prabayar: jika totalAmount sama dengan baseAmount padahal ada admin atau pajak,
            // itu berarti baseAmount keliru diisi dengan total bayar (RP BAYAR). Hitung ulang baseAmount = total - admin - pajak.
            if (($data['utility_type'] ?? '') === 'listrik_pra' || ! empty($data['token_number'])) {
                if ($totalAmount > 0 && $totalAmount == $baseAmount && ($adminFee > 0 || $taxPbjt > 0)) {
                    $baseAmount = max(0, $totalAmount - ($adminFee + $taxPbjt + $taxPpn));
                }
            }

            if ($totalAmount <= 0 && ($baseAmount > 0 || $adminFee > 0)) {
                $totalAmount = $baseAmount + $adminFee + $taxPbjt + $taxPpn;
            }

            if ($baseAmount <= 0 && $totalAmount > 0) {
                $baseAmount = max(0, $totalAmount - ($adminFee + $taxPbjt + $taxPpn));
            }

            $expectedTotal = $baseAmount + $adminFee + $taxPbjt + $taxPpn;
            $isVerified = ($totalAmount > 0 && $expectedTotal > 0 && abs($expectedTotal - $totalAmount) < 1);
            if (! $isVerified && $totalAmount > 0 && ($baseAmount + $taxPbjt) > 0 && abs($totalAmount - ($baseAmount + $taxPbjt)) < 1) {
                $expectedTotal = $baseAmount + $taxPbjt;
                $isVerified = true;
            }
            $discrepancy = $totalAmount - $expectedTotal;

            // Format tanggal transaksi
            $transDate = null;
            if (! empty($data['transaction_date'])) {
                $rawDate = trim($data['transaction_date']);
                // Cek format DD/MM/YY atau DD/MM/YYYY (contoh: 07/07/26 atau 07/07/2026)
                if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})(?:\s+(\d{1,2}:\d{2}(?::\d{2})?))?$/', $rawDate, $m)) {
                    $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                    $year = strlen($m[3]) === 2 ? '20'.$m[3] : $m[3];
                    $time = ! empty($m[4]) ? $m[4] : '12:00:00';
                    if (strlen($time) === 5) {
                        $time .= ':00';
                    }
                    $transDate = "{$year}-{$month}-{$day} {$time}";
                } else {
                    $ts = strtotime($rawDate);
                    $transDate = ($ts && $ts > 0) ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
                }
            } else {
                $transDate = date('Y-m-d H:i:s');
            }

            $billingMonth = $data['billing_month'] ?? null;
            if (! $billingMonth) {
                $indonesianMonths = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ];
                $monthNum = (int) date('n', strtotime($transDate));
                $billingMonth = $indonesianMonths[$monthNum] ?? date('F');
            }

            // Smart Matching to UtilityProfile
            $matchedProfile = $this->matchProfile($data);

            return [
                'success' => true,
                'engine' => 'gemini',
                'enhanced_image' => $enhancedBase64,
                'data' => [
                    'matched_profile_id' => $matchedProfile?->id,
                    'matched_profile_name' => $matchedProfile?->name,
                    'matched_profile_code' => $matchedProfile?->code,
                    'utility_type' => $data['utility_type'] ?? ($matchedProfile?->category ?? 'listrik_pra'),
                    'payment_merchant' => $data['payment_merchant'] ?? ($matchedProfile?->payment_method ?? 'PT POS INDONESIA'),
                    'receipt_number' => $data['receipt_number'] ?? null,
                    'reference_number' => $data['reference_number'] ?? null,
                    'transaction_date' => $transDate,
                    'fiscal_year' => (int) date('Y', strtotime($transDate)),
                    'billing_month' => $billingMonth,
                    'meter_number' => $data['meter_number'] ?? ($matchedProfile?->meter_number ?? null),
                    'customer_number' => $data['customer_number'] ?? ($matchedProfile?->customer_number ?? null),
                    'customer_name' => $data['customer_name'] ?? ($matchedProfile?->customer_name ?? null),
                    'tariff_power' => $data['tariff_power'] ?? ($matchedProfile?->tariff_power ?? null),
                    'kwh_amount' => ! empty($data['kwh_amount']) ? (float) $data['kwh_amount'] : null,
                    'base_amount' => $baseAmount,
                    'tax_pbjt' => $taxPbjt,
                    'tax_ppn' => $taxPpn,
                    'admin_fee' => $adminFee,
                    'total_amount' => $totalAmount,
                    'formatted_total_amount' => 'Rp '.number_format($totalAmount, 0, ',', '.'),
                    'is_amount_verified' => $isVerified,
                    'expected_total' => $expectedTotal,
                    'amount_discrepancy' => $discrepancy,
                    'token_number' => $data['token_number'] ?? null,
                    'raw_ocr' => $data,
                ],
            ];
        } catch (Exception $e) {
            Log::warning('Utility Receipt OCR Exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kendala koneksi AI OCR: '.$e->getMessage(),
                'enhanced_image' => $enhancedBase64,
                'fallback_to_offline' => true,
            ];
        }
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
