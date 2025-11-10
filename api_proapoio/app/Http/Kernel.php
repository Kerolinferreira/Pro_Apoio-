<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /** Middleware global (mínimo recomendado). */
    protected $middleware = [
        \Illuminate\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /** Grupos. Mantemos o grupo API com throttle e bindings. */
    protected $middlewareGroups = [
        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /** Atalhos por rota. */
    protected $routeMiddleware = [
        'jwt'         => \App\Http\Middleware\JwtMiddleware::class,
        'candidato'   => \App\Http\Middleware\EnsureCandidato::class,
        'instituicao' => \App\Http\Middleware\EnsureInstituicao::class,
        'bindings'    => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'throttle'    => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'signed'      => \Illuminate\Routing\Middleware\ValidateSignature::class,
    ];
}
