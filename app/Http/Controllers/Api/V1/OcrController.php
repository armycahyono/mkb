<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReceiptOcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OcrController extends Controller
{
    /**
     * Scan a receipt image and return extracted information.
     */
    public function scan(Request $request, ReceiptOcrService $ocrService): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240', // max 10MB
        ]);

        $file = $request->file('image');
        $result = $ocrService->scan($file);

        if (! empty($result['success'])) {
            return response()->json([
                'status' => 'success',
                'message' => 'Nota berhasil dipindai.',
                'data' => [
                    'store_name' => $result['store_name'] ?? null,
                    'date' => $result['date'] ?? date('Y-m-d'),
                    'evidence_number' => $result['evidence_number'] ?? null,
                    'total_amount' => (float) ($result['total_amount'] ?? 0),
                    'items' => $result['items'] ?? [],
                ],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message'] ?? 'Gagal membaca nota. Silakan input manual.',
            'fallback' => $result['fallback_to_offline'] ?? true,
        ], 422);
    }
}
