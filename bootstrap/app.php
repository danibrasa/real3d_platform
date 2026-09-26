<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'token.project' => \App\Http\Middleware\EnsureTokenProjectAccess::class,
            'feature' => \App\Http\Middleware\EnsureFeature::class,
            'storage.quota' => \App\Http\Middleware\EnsureStorageQuota::class,
            'onboarding' => \App\Http\Middleware\EnsureOnboardingComplete::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SetCurrency::class,
        ]);
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'api/viewer-events',
            'api/projects/*/chat',
            'api/projects/*/chat/lead',
            'stripe/webhook',
            'mcp',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        \Sentry\Laravel\Integration::handles($exceptions);

        // Ruido que no es un fallo del sistema y agotaria la cuota:
        // paginas que no existen, formularios mal rellenados, sesiones caducadas
        // y peticiones sin permiso. Siguen quedando en el log de Laravel.
        $exceptions->dontReportDuplicates();
        $exceptions->dontReport([
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            \Illuminate\Validation\ValidationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Illuminate\Session\TokenMismatchException::class,
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
            \Illuminate\Http\Exceptions\ThrottleRequestsException::class,
        ]);
    })->create();
