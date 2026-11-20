<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 405 mặc định không được ghi log và trang lỗi của Symfony không nói gì: ghi lại phương thức + URL (và Referer)
        // để biết yêu cầu nào sai, rồi hiện trang lỗi tiếng Việt kèm phương thức được phép.
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            $allowed = $e->getHeaders()['Allow'] ?? null;
            Log::warning('405 Method Not Allowed', [
                'method' => $request->method(), 'url' => $request->fullUrl(), 'allowed' => $allowed, 'referer' => $request->header('referer'),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => "Sai phương thức yêu cầu ({$request->method()}); cho phép: {$allowed}."], 405, $e->getHeaders());
            }

            return null; // trang HTML: để Laravel dựng resources/views/errors/405.blade.php (nó mới đăng ký namespace errors::)
        });
    })->create();
