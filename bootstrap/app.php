<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // <-- Rute API DOKU ditambahkan di sini
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, Request $request) {
            $max = ini_get('post_max_size') ?: '8M';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Ukuran data yang diunggah melebihi batas maksimal yang diizinkan server ($max).",
                ], 413);
            }

            return response(
                view('errors.post-too-large', ['max' => $max]),
                413,
                ['Content-Type' => 'text/html'],
            );
        });
    })->create();