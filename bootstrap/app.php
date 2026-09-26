<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureFeature;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureStorageQuota;
use App\Http\Middleware\EnsureTokenProjectAccess;
use App\Http\Middleware\SetCurrency;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'role' => EnsureRole::class,
            'token.project' => EnsureTokenProjectAccess::class,
            'feature' => EnsureFeature::class,
            'storage.quota' => EnsureStorageQuota::class,
            'onboarding' => EnsureOnboardingComplete::class,
        ]);
        $middleware->web(append: [
            SetLocale::class,
            SetCurrency::class,
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
        Integration::handles($exceptions);

        // Ruido que no es un fallo del sistema y agotaria la cuota:
        // paginas que no existen, formularios mal rellenados, sesiones caducadas
        // y peticiones sin permiso. Siguen quedando en el log de Laravel.
        $exceptions->dontReportDuplicates();
        $exceptions->dontReport([
            NotFoundHttpException::class,
            ModelNotFoundException::class,
            ValidationException::class,
            AuthenticationException::class,
            AuthorizationException::class,
            TokenMismatchException::class,
            MethodNotAllowedHttpException::class,
            ThrottleRequestsException::class,
        ]);
    })->create();
