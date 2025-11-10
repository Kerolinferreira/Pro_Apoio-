<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registrar middlewares de segregação de permissões por tipo de usuário
        $middleware->alias([
            'candidato' => \App\Http\Middleware\EnsureCandidato::class,
            'instituicao' => \App\Http\Middleware\EnsureInstituicao::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Garante que erros de validação sejam retornados em JSON para APIs
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Handler para ModelNotFoundException (findOrFail) - retorna 404 JSON
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Recurso não encontrado.',
                ], 404);
            }
        });

        // Handler para NotFoundHttpException (rotas inexistentes) - retorna 404 JSON
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Rota não encontrada.',
                ], 404);
            }
        });

        // Handler para HttpException genérica - retorna JSON com status code
        $exceptions->render(function (HttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Erro no servidor.',
                ], $e->getStatusCode());
            }
        });
    })->create();
