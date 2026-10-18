<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (shared hosting / LiteSpeed / Cloudflare)
        $middleware->trustProxies(at: '*', headers:
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX
        );

        // Register named middleware aliases
        $middleware->alias([
            'vendor.only'        => \App\Http\Middleware\EnsureVendor::class,
            'vendor.portal.auth' => \App\Http\Middleware\VendorPortalAuth::class,
            'teacher.only'  => \App\Http\Middleware\TeacherOnly::class,
            'creator.access' => \App\Http\Middleware\CreatorAccess::class,
            'admin.only'    => \App\Http\Middleware\AdminOnly::class,
            'single.device'   => \App\Http\Middleware\SingleDeviceSession::class,
            'portal.access'   => \App\Http\Middleware\PortalAccessCheck::class,
        ]);

        // Exclude Razorpay webhook from CSRF — verified via Razorpay signature
        $middleware->validateCsrfTokens(except: [
            'webhook/razorpay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'The file exceeds the server upload limit.',
                ], 413);
            }
        });
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($exception->getStatusCode() === 403 && ! $request->expectsJson()) {
                return response()->view('errors.403', status: 403);
            }
        });
    })->create();
