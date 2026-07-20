<?php

use App\Http\Middleware\BindCurrentTenant;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureSuper;
use App\Http\Middleware\EnsureTenantWritable;
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
        $middleware->web(append: [BindCurrentTenant::class]);
        $middleware->api(append: [BindCurrentTenant::class]);
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'super' => EnsureSuper::class,
            'writable' => EnsureTenantWritable::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/paystack/webhook',
            'api/texttango/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            $limit = ini_get('post_max_size') ?: '8M';
            $message = "Upload is larger than the {$limit} server limit. Please shrink your images or upload one image at a time.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }

            return redirect()->back()->with('branding_flash', [
                'ok' => false,
                'message' => $message,
            ]);
        });
    })->create();
