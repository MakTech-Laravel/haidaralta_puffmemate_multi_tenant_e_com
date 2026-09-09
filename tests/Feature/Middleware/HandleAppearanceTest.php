<?php

use App\Http\Middleware\HandleAppearance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

test('always shares light appearance even when cookie is dark', function () {
    $request = Request::create('/', 'GET');
    $request->cookies->set('appearance', 'dark');

    $middleware = new HandleAppearance;
    $middleware->handle($request, fn () => response('ok'));

    expect(View::shared('appearance'))->toBe('light');
});

test('always shares light appearance even when cookie is system', function () {
    $request = Request::create('/', 'GET');
    $request->cookies->set('appearance', 'system');

    $middleware = new HandleAppearance;
    $middleware->handle($request, fn () => response('ok'));

    expect(View::shared('appearance'))->toBe('light');
});
