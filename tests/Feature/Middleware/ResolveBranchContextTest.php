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
    $user = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    expect(fn () => runResolveBranchContext('missing-branch', $user))
        ->toThrow(NotFoundHttpException::class);
});

test('inactive branch slug returns not found', function () {
    Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->update(['status' => false]);

    $user = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    expect(fn () => runResolveBranchContext(BranchSeeder::MELBOURNE_COLLINS, $user))
        ->toThrow(NotFoundHttpException::class);
});

test('wrong branch for authenticated user returns forbidden', function () {
    $user = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    expect(fn () => runResolveBranchContext(BranchSeeder::MELBOURNE_COLLINS, $user))
        ->toThrow(HttpException::class, 'You do not have access to this branch.');
});

test('correct branch sets team id and binds current branch', function () {
    $user = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $response = runResolveBranchContext(BranchSeeder::SYDNEY_CBD, $user);

    expect($response->getStatusCode())->toBe(200)
        ->and(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe($branch->id)
        ->and(app('currentBranch'))->toBeInstanceOf(Branch::class)
        ->and(app('currentBranch')->is($branch))->toBeTrue();
});
