<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;

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
            'single.device'   => \App\Http\Middleware\SingleDeviceSession::class,
            'portal.access'   => \App\Http\Middleware\PortalAccessCheck::class,
        ]);

        // Exclude Razorpay webhook from CSRF — verified via Razorpay signature
        $middleware->validateCsrfTokens(except: [
            'webhook/razorpay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
