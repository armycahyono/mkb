<?php

namespace App\Http\Middleware;

use App\Helpers\FiscalYearHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetFiscalYear
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('set_fiscal_year')) {
            $reqYear = (int) $request->get('set_fiscal_year');
            if ($reqYear >= 2020 && $reqYear <= ((int) date('Y') + 3)) {
                FiscalYearHelper::setActiveYear($reqYear);
            }
        } elseif (! $request->session()->has('fiscal_year')) {
            FiscalYearHelper::setActiveYear((int) date('Y'));
        }

        $activeYear = FiscalYearHelper::getActiveYear();
        $availableYears = FiscalYearHelper::getAvailableYears();
        $isCurrent = FiscalYearHelper::isCurrentYear($activeYear);

        View::share('activeFiscalYear', $activeYear);
        View::share('availableFiscalYears', $availableYears);
        View::share('isCurrentFiscalYear', $isCurrent);

        return $next($request);
    }
}
