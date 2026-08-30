<?php

use App\Exceptions\ConsentRequiredException;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTeamUrlDefaults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetTeamUrlDefaults::class,
        ]);

        $middleware->alias(['role' => EnsureRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ConsentRequiredException $e, Request $request) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        // Defensive fallback: CreateMakeupSessionAction/CreateAdvanceSessionAction throw
        // InvalidArgumentException when linked_session_id is missing. The FormRequest
        // rules already validate this (required_if:type,makeup,advance) — this handler
        // only guards against edge cases that slip past validation, converting the
        // exception into a 422 with an inline error instead of a raw 500.
        $exceptions->render(function (InvalidArgumentException $e, Request $request) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['linked_session_id' => $e->getMessage()]);
            }

            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            return match ($response->getStatusCode()) {
                401 => Inertia::render('errors/AccessDenied', ['status' => 401])
                    ->toResponse($request)->setStatusCode(401),
                403 => Inertia::render('errors/AccessDenied', ['status' => 403])
                    ->toResponse($request)->setStatusCode(403),
                404 => Inertia::render('errors/NotFound')
                    ->toResponse($request)->setStatusCode(404),
                500 => Inertia::render('errors/ServerError')
                    ->toResponse($request)->setStatusCode(500),
                default => $response,
            };
        });
    })->create();
