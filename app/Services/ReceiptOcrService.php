<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReceiptOcrService
{
    protected ?string $apiKey;

    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Scan and extract receipt details using Gemini Vision API
     */
    public function scan(UploadedFile|string $image): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'GEMINI_API_KEY belum dikonfigurasi di file .env. Mengalihkan ke scanner offline.',
                'fallback_to_offline' => true,
            ];
        }

        try {
            $base64Image = '';
            $mimeType = 'image/jpeg';

            if ($image instanceof UploadedFile) {
                $mimeType = $image->getMimeType() ?: 'image/jpeg';
                $base64Image = base64_encode(file_get_contents($image->getRealPath()));
            } elseif (is_string($image) && file_exists($image)) {
                $mimeType = mime_content_type($image) ?: 'image/jpeg';
                $base64Image = base64_encode(file_get_contents($image));
            } elseif (is_string($image) && str_starts_with($image, 'data:image')) {
                // Handle base64 data URL
                [$meta, $data] = explode(',', $image, 2);
                if (preg_match('/data:(image\/[a-zA-Z+]+);base64/', $meta, $matches)) {
                    $mimeType = $matches[1];
                }
                $base64Image = $data;
            } else {
                return [
                    'success' => false,
                    'message' => 'Format berkas foto nota tidak valid.',
                    'fallback_to_offline' => true,
                ];
            }

            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $systemPrompt = 'Anda adalah asisten AI OCR untuk pembukuan keuangan sekolah di Indonesia. Analisis gambar nota fisik / kuitansi toko ini. Ekstrak informasinya dalam format JSON valid tanpa tanda kutip markdown (tanpa ```json). Skema JSON wajib:
{
  "store_name": "Nama Toko / Merchant (atau null bila tidak ada)",
  "date": "YYYY-MM-DD (tanggal belanja atau tanggal hari ini jika tidak terbaca)",
  "evidence_number": "Nomor Nota / Kuitansi (atau null)",
  "total_amount": 0,
  "items": [
    {
      "item_name": "Nama Barang / Jasa",
      "quantity": 1,
      "unit": "pcs/rim/buah/lembar/pak",
      "price": 0,
      "total_price": 0
    }
  ]
}
Pastikan total_amount adalah angka murni tanpa tanda titik atau Rp. Jika total_amount tidak tertulis jelas, jumlahkan seluruh total_price item barang.';

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $systemPrompt,
                            ],
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

            $response = Http::timeout(15)->post($endpoint, $payload);

            if (! $response->successful() && $response->status() === 503 && $this->model !== 'gemini-3.5-flash') {
                $fallbackEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$this->apiKey}";
                $response = Http::timeout(15)->post($fallbackEndpoint, $payload);
            }

            if (! $response->successful()) {
                Log::warning('Gemini OCR API Error: '.$response->body());

                return [
                    'success' => false,
                    'message' => 'Layanan AI Cloud Gemini tidak dapat merespons ('.$response->status().'). Mengalihkan ke pemindai offline.',
                    'fallback_to_offline' => true,
                ];
            }

            $result = $response->json();
            $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Clean markdown blocks if present
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
                    'message' => 'Format respon AI tidak dapat diparsing. Mengalihkan ke pemindai offline.',
                    'fallback_to_offline' => true,
                ];
            }

            $totalAmount = (float) ($data['total_amount'] ?? 0);
            if ($totalAmount <= 0 && ! empty($data['items'])) {
                foreach ($data['items'] as $it) {
                    $totalAmount += (float) ($it['total_price'] ?? 0);
                }
            }

            return [
                'success' => true,
                'engine' => 'gemini',
                'data' => [
                    'store_name' => $data['store_name'] ?? null,
                    'date' => $data['date'] ?? date('Y-m-d'),
                    'evidence_number' => $data['evidence_number'] ?? null,
                    'total_amount' => $totalAmount,
                    'formatted_total_amount' => 'Rp '.number_format($totalAmount, 0, ',', '.'),
                    'items' => $data['items'] ?? [],
                ],
            ];
        } catch (Exception $e) {
            Log::warning('Receipt OCR Exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kendala koneksi ke server AI ('.$e->getMessage().'). Mengalihkan ke pemindai offline.',
                'fallback_to_offline' => true,
            ];
        }
    }
}
