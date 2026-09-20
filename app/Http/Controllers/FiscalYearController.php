<?php

namespace App\Http\Controllers;

use App\Helpers\FiscalYearHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    /**
     * Beralih ke tahun anggaran yang dipilih
     */
    public function switch(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
        ]);

        $year = (int) $validated['year'];
        FiscalYearHelper::setActiveYear($year);

        $message = "Tahun Anggaran aktif dialihkan ke {$year}.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'year' => $year,
                'is_current' => FiscalYearHelper::isCurrentYear($year),
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
