<?php

namespace App\Http;

use App\Http\Middleware\VerifyTelegramSignature;
use Illuminate\Foundation\Http\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    /**
     * The application's route middleware.
     *
     * @var array<string, class-string>
     */
    protected $routeMiddleware = [
        'verify.telegram' => VerifyTelegramSignature::class,
    ];
}
