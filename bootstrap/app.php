<?php

use App\Console\Commands\SendCourseReminders;
use App\Http\Middleware\Admin;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\Teacher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->alias([
            'ensure.session' => \App\Http\Middleware\EnsureSessionIsValid::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('livewire*')) {
                return redirect()->back()->with('warning', 'Votre session a expiré, veuillez réessayer.');
            }
        });
    })
    ->withCommands([
        SendCourseReminders::class,
    ])
    ->create();
