<?php

use App\Exceptions\ServicoExternoIndisponivelException;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->web(append: [SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Centralizado aqui (em vez de try/catch repetido em CepController/
        // CnpjController) — qualquer integração futura em app/Services/Integracoes
        // que lance essa exceção já ganha o 503 correto de graça.
        $exceptions->render(function (ServicoExternoIndisponivelException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['erro' => 'Serviço externo indisponível. Tente novamente em instantes.'], 503);
            }
        });
    })->create();
