<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureMaintenanceModeAllows;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\RequireRole;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    // Authorise websocket subscriptions with the stateless JWT guard.
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['api', 'jwt.auth']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust all proxies so client IPs are correct behind Nginx / a load balancer.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'jwt.auth' => Authenticate::class,
            'permission' => RequirePermission::class,
            'role' => RequireRole::class,
            'maintenance.gate' => EnsureMaintenanceModeAllows::class,
        ]);

        // Security headers + global Redis-backed throttle on the API group.
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\SecurityHeaders::class,
            'throttle:api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Force JSON rendering for the whole API.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (ApiException $e) {
            return $e->render();
        });

        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::error(
                message: 'The given data was invalid.',
                status: 422,
                code: 'validation_failed',
                details: $e->errors(),
            );
        });

        $exceptions->render(function (AuthenticationException $e) {
            return ApiResponse::error('Unauthenticated.', 401, 'unauthenticated');
        });

        $exceptions->render(function (AuthorizationException $e) {
            return ApiResponse::error($e->getMessage() ?: 'This action is unauthorized.', 403, 'forbidden');
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return ApiResponse::error('Resource not found.', 404, 'not_found');
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Endpoint not found.', 404, 'not_found');
            }

            return null;
        });

        $exceptions->render(function (TooManyRequestsHttpException $e) {
            return ApiResponse::error('Too many requests. Please slow down.', 429, 'rate_limited');
        });

        // Catch-all for any other HTTP exception within the API.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();

            return ApiResponse::error(
                message: $e->getMessage() ?: 'Request failed.',
                status: $status >= 400 ? $status : 500,
                code: 'http_error',
            );
        });
    })->create();
