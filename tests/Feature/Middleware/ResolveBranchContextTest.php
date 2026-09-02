<?php

use App\Http\Middleware\ResolveBranchContext;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

function runResolveBranchContext(string $slug, User $user): Response
{
    $request = Request::create("/branch/{$slug}/dashboard", 'GET');
    $route = new Route('GET', 'branch/{branch}/dashboard', fn () => response('ok'));
    $route->bind($request);
    $route->setParameter('branch', $slug);
    $request->setRouteResolver(fn () => $route);

    auth('branch')->setUser($user);

    $middleware = new ResolveBranchContext;

    return $middleware->handle($request, fn () => response('ok'));
}

test('unknown branch slug returns not found', function () {
    $user = User::query()->where('email', 'branch@test.com')->firstOrFail();

    expect(fn () => runResolveBranchContext('missing-branch', $user))
        ->toThrow(NotFoundHttpException::class);
});

test('inactive branch slug returns not found', function () {
    Branch::query()->where('slug', 'uptown')->update(['is_active' => false]);

    $user = User::query()->where('email', 'branch@test.com')->firstOrFail();

    expect(fn () => runResolveBranchContext('uptown', $user))
        ->toThrow(NotFoundHttpException::class);
});

test('wrong branch for authenticated user returns forbidden', function () {
    $user = User::query()->where('email', 'branch@test.com')->firstOrFail();

    expect(fn () => runResolveBranchContext('uptown', $user))
        ->toThrow(HttpException::class, 'You do not have access to this branch.');
});

test('correct branch sets team id and binds current branch', function () {
    $user = User::query()->where('email', 'branch@test.com')->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $response = runResolveBranchContext('downtown', $user);

    expect($response->getStatusCode())->toBe(200)
        ->and(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe($branch->id)
        ->and(app('currentBranch'))->toBeInstanceOf(Branch::class)
        ->and(app('currentBranch')->is($branch))->toBeTrue();
});
