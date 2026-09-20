<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FiscalYearHelper;
use App\Http\Controllers\Controller;

class FiscalYearController extends Controller
{
    public function index()
    {
        $years = FiscalYearHelper::getAvailableYears();

        return response()->json($years);
    }
}
