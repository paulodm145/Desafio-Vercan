<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Headers básicos de hardening ausentes por padrão no Laravel 11+. Uma
 * Content-Security-Policy completa foi deliberadamente deixada de fora: o
 * script inline de resolução de tema em `components/layout.blade.php` (roda
 * antes do primeiro paint, pra evitar flash do tema errado) exigiria nonces
 * em todo Blade ou `unsafe-inline` — o que anularia boa parte do propósito de
 * uma CSP. Os três headers abaixo não têm esse custo/risco.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
