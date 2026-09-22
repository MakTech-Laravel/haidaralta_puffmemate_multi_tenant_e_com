<?php

use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftAudience;
use App\Enums\ShiftDayKind;
use App\Enums\ShiftOwnerType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftDay;
use App\Models\UserShiftHistory;
use App\Services\Workforce\AssignShiftService;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

function phaseTwoStaff(Branch $branch): User
{
    return User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $branch->id,
        'is_active' => true,
    ]);
}

function phaseTwoAssignment(
    User $user,
    Branch $branch,
    ShiftTemplate $template,
    string $startDate,
    string $endDate,
): UserShiftAssignment {
    return UserShiftAssignment::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'shift_template_id' => $template->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);
}

beforeEach(function () {
    Carbon::setTestNow('2026-09-22 10:00:00');
    config(['shifts.allow_multiple_per_day' => false]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('creates one canonical assignment per consecutive date run', function () {
    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $template = ShiftTemplate::factory()->forStaff()->create();

    app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $template,
        ['2026-09-27', '2026-09-24', '2026-09-25'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    );

    $assignments = UserShiftAssignment::query()
        ->where('user_id', $staff->id)
        ->where('status', ShiftAssignmentStatus::Scheduled)
        ->orderBy('start_date')
        ->get();

    expect($assignments)->toHaveCount(2)
        ->and($assignments[0]->start_date->toDateString())->toBe('2026-09-24')
        ->and($assignments[0]->end_date->toDateString())->toBe('2026-09-25')
        ->and($assignments[0]->days)->toHaveCount(2)
        ->and($assignments[1]->start_date->toDateString())->toBe('2026-09-27')
        ->and($assignments[1]->end_date->toDateString())->toBe('2026-09-27')
        ->and($assignments[1]->days)->toHaveCount(1)
        ->and(UserShiftHistory::query()->where('user_id', $staff->id)->count())->toBe(2);
});

it('splits a canonical assignment when a middle date is removed', function () {
    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $template = ShiftTemplate::factory()->create();
    $assignment = phaseTwoAssignment($staff, $branch, $template, '2026-09-23', '2026-09-25');
    $middleDay = $assignment->days()->whereDate('duty_on', '2026-09-24')->firstOrFail();

    app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        null,
        [],
        [],
        [$middleDay->id],
        expectedOwner: ShiftOwnerType::Platform,
    );

    $scheduled = UserShiftAssignment::query()
        ->where('user_id', $staff->id)
        ->where('status', ShiftAssignmentStatus::Scheduled)
        ->orderBy('start_date')
        ->get();

    expect($scheduled)->toHaveCount(2)
        ->and($scheduled[0]->id)->toBe($assignment->id)
        ->and($scheduled->map(fn (UserShiftAssignment $item): string => $item->start_date->toDateString())->all())
        ->toBe(['2026-09-23', '2026-09-25'])
        ->and(UserShiftDay::query()->where('user_id', $staff->id)->whereDate('duty_on', '2026-09-24')->exists())
        ->toBeFalse();
});

it('shrinks the original group and creates a new group when an end date changes shift', function () {
    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $oldTemplate = ShiftTemplate::factory()->create(['start_time' => '08:00', 'end_time' => '12:00']);
    $newTemplate = ShiftTemplate::factory()->create(['start_time' => '12:00', 'end_time' => '18:00']);
    $assignment = phaseTwoAssignment($staff, $branch, $oldTemplate, '2026-09-23', '2026-09-25');
    $endDay = $assignment->days()->whereDate('duty_on', '2026-09-25')->firstOrFail();

    app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $newTemplate,
        [],
        [$endDay->id],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    );

    expect($assignment->refresh()->end_date->toDateString())->toBe('2026-09-24')
        ->and(UserShiftDay::query()->where('user_id', $staff->id)->whereDate('duty_on', '2026-09-25')->value('shift_template_id'))
        ->toBe($newTemplate->id)
        ->and(UserShiftHistory::query()->where('user_id', $staff->id)->value('previous_shift_template_id'))
        ->toBe($oldTemplate->id);
});

it('cancels an assignment when every canonical date is removed', function () {
    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $template = ShiftTemplate::factory()->create();
    $assignment = phaseTwoAssignment($staff, $branch, $template, '2026-09-23', '2026-09-24');

    app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        null,
        [],
        [],
        $assignment->days()->pluck('id')->all(),
        expectedOwner: ShiftOwnerType::Platform,
    );

    expect($assignment->refresh()->status)->toBe(ShiftAssignmentStatus::Cancelled)
        ->and($assignment->days()->exists())->toBeFalse();
});

it('merges adjacent canonical groups with the same branch and shift', function () {
    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $template = ShiftTemplate::factory()->create();
    $assignment = phaseTwoAssignment($staff, $branch, $template, '2026-09-23', '2026-09-24');

    app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $template,
        ['2026-09-25'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    );

    $scheduled = UserShiftAssignment::query()
        ->where('user_id', $staff->id)
        ->where('status', ShiftAssignmentStatus::Scheduled)
        ->get();

    expect($scheduled)->toHaveCount(1)
        ->and($scheduled->first()->id)->toBe($assignment->id)
        ->and($scheduled->first()->end_date->toDateString())->toBe('2026-09-25')
        ->and($scheduled->first()->days)->toHaveCount(3);
});

it('archives a touched legacy assignment and rebuilds its on-duty rows canonically', function () {
    config(['shifts.allow_multiple_per_day' => true]);

    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $defaultTemplate = ShiftTemplate::factory()->create(['start_time' => '08:00', 'end_time' => '12:00']);
    $overrideTemplate = ShiftTemplate::factory()->create(['start_time' => '12:00', 'end_time' => '18:00']);
    $legacy = phaseTwoAssignment($staff, $branch, $defaultTemplate, '2026-09-23', '2026-09-27');
    $legacy->days()->whereDate('duty_on', '2026-09-24')->firstOrFail()->update([
        'kind' => ShiftDayKind::Leave,
        'shift_template_id' => null,
    ]);
    $legacy->days()->whereDate('duty_on', '2026-09-25')->firstOrFail()->update([
        'kind' => ShiftDayKind::Override,
        'shift_template_id' => $overrideTemplate->id,
    ]);
    $removeDay = $legacy->days()->whereDate('duty_on', '2026-09-23')->firstOrFail();

    app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        null,
        [],
        [],
        [$removeDay->id],
        expectedOwner: ShiftOwnerType::Platform,
    );

    $leave = UserShiftDay::query()->where('user_id', $staff->id)->whereDate('duty_on', '2026-09-24')->firstOrFail();
    $rebuilt = UserShiftAssignment::query()
        ->where('user_id', $staff->id)
        ->where('status', ShiftAssignmentStatus::Scheduled)
        ->orderBy('start_date')
        ->get();

    expect($legacy->refresh()->status)->toBe(ShiftAssignmentStatus::Cancelled)
        ->and($leave->user_shift_assignment_id)->toBe($legacy->id)
        ->and($leave->kind)->toBe(ShiftDayKind::Leave)
        ->and($rebuilt)->toHaveCount(2)
        ->and($rebuilt->flatMap->days->every(fn (UserShiftDay $day): bool => $day->kind === ShiftDayKind::Scheduled))
        ->toBeTrue();
});

it('is idempotent when the target shift already exists for the date', function () {
    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $template = ShiftTemplate::factory()->create();
    phaseTwoAssignment($staff, $branch, $template, '2026-09-23', '2026-09-23');

    app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $template,
        ['2026-09-23'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    );

    expect(UserShiftDay::query()->where('user_id', $staff->id)->count())->toBe(1)
        ->and(UserShiftAssignment::query()->where('user_id', $staff->id)->count())->toBe(1)
        ->and(UserShiftHistory::query()->where('user_id', $staff->id)->count())->toBe(0);
});

it('reuses an existing target when replacing another same-day shift', function () {
    config(['shifts.allow_multiple_per_day' => true]);

    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $first = ShiftTemplate::factory()->create(['start_time' => '05:00', 'end_time' => '10:00']);
    $target = ShiftTemplate::factory()->create(['start_time' => '10:00', 'end_time' => '15:00']);
    $firstAssignment = phaseTwoAssignment($staff, $branch, $first, '2026-09-23', '2026-09-23');
    phaseTwoAssignment($staff, $branch, $target, '2026-09-23', '2026-09-23');

    app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $target,
        [],
        [$firstAssignment->days()->value('id')],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    );

    expect(UserShiftDay::query()->where('user_id', $staff->id)->whereDate('duty_on', '2026-09-23')->count())->toBe(1)
        ->and(UserShiftDay::query()->where('user_id', $staff->id)->value('shift_template_id'))->toBe($target->id)
        ->and(UserShiftHistory::query()->where('user_id', $staff->id)->value('previous_shift_template_id'))->toBe($first->id);
});

it('rejects a second daily shift across branches when multiple shifts are disabled', function () {
    $firstBranch = Branch::factory()->create();
    $secondBranch = Branch::factory()->create();
    $driver = User::factory()->create(['user_type' => UserType::DRIVER, 'branch_id' => null]);
    DriverBranchAssignment::factory()->create(['driver_id' => $driver->id, 'branch_id' => $firstBranch->id]);
    DriverBranchAssignment::factory()->create(['driver_id' => $driver->id, 'branch_id' => $secondBranch->id]);
    $existingTemplate = ShiftTemplate::factory()->create(['start_time' => '05:00', 'end_time' => '15:00']);
    $targetTemplate = ShiftTemplate::factory()->create(['start_time' => '15:00', 'end_time' => '22:00']);
    phaseTwoAssignment($driver, $firstBranch, $existingTemplate, '2026-09-23', '2026-09-23');

    expect(fn () => app(AssignShiftService::class)->applyChanges(
        $driver,
        $secondBranch,
        $targetTemplate,
        ['2026-09-23'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    ))->toThrow(InvalidArgumentException::class, 'This person already has a shift on 2026-09-23.');

    expect(UserShiftDay::query()->where('user_id', $driver->id)->count())->toBe(1);
});

it('allows adjacent windows but rejects overlap when multiple shifts are enabled', function () {
    config(['shifts.allow_multiple_per_day' => true]);

    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $morning = ShiftTemplate::factory()->create(['start_time' => '05:00', 'end_time' => '15:00']);
    $evening = ShiftTemplate::factory()->create(['start_time' => '15:00', 'end_time' => '22:00']);
    $overlap = ShiftTemplate::factory()->create(['start_time' => '14:00', 'end_time' => '18:00']);
    phaseTwoAssignment($staff, $branch, $morning, '2026-09-23', '2026-09-23');

    app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $evening,
        ['2026-09-23'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    );

    expect(fn () => app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $overlap,
        ['2026-09-23'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    ))->toThrow(InvalidArgumentException::class, 'This person already has an overlapping shift on 2026-09-23.');

    expect(UserShiftDay::query()->where('user_id', $staff->id)->count())->toBe(2);
});

it('rolls back a replacement when the final schedule is invalid', function () {
    config(['shifts.allow_multiple_per_day' => true]);

    $firstBranch = Branch::factory()->create();
    $secondBranch = Branch::factory()->create();
    $driver = User::factory()->create(['user_type' => UserType::DRIVER, 'branch_id' => null]);
    DriverBranchAssignment::factory()->create(['driver_id' => $driver->id, 'branch_id' => $firstBranch->id]);
    DriverBranchAssignment::factory()->create(['driver_id' => $driver->id, 'branch_id' => $secondBranch->id]);
    $first = ShiftTemplate::factory()->create(['start_time' => '05:00', 'end_time' => '10:00']);
    $second = ShiftTemplate::factory()->create(['start_time' => '10:00', 'end_time' => '15:00']);
    $replacement = ShiftTemplate::factory()->create(['start_time' => '15:00', 'end_time' => '20:00']);
    $firstAssignment = phaseTwoAssignment($driver, $firstBranch, $first, '2026-09-23', '2026-09-23');
    phaseTwoAssignment($driver, $secondBranch, $second, '2026-09-23', '2026-09-23');
    $replaceDay = $firstAssignment->days()->firstOrFail();
    config(['shifts.allow_multiple_per_day' => false]);

    expect(fn () => app(AssignShiftService::class)->applyChanges(
        $driver,
        $firstBranch,
        $replacement,
        [],
        [$replaceDay->id],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    ))->toThrow(InvalidArgumentException::class, 'This person already has a shift on 2026-09-23.');

    expect($replaceDay->fresh()->shift_template_id)->toBe($first->id)
        ->and(UserShiftHistory::query()->where('user_id', $driver->id)->exists())->toBeFalse();
});

it('rejects stale and ambiguous replacement references', function () {
    config(['shifts.allow_multiple_per_day' => true]);

    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $first = ShiftTemplate::factory()->create(['start_time' => '05:00', 'end_time' => '10:00']);
    $second = ShiftTemplate::factory()->create(['start_time' => '10:00', 'end_time' => '15:00']);
    $target = ShiftTemplate::factory()->create(['start_time' => '15:00', 'end_time' => '20:00']);
    $firstAssignment = phaseTwoAssignment($staff, $branch, $first, '2026-09-23', '2026-09-23');
    $secondAssignment = phaseTwoAssignment($staff, $branch, $second, '2026-09-23', '2026-09-23');

    expect(fn () => app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $target,
        [],
        [$firstAssignment->days()->value('id'), $secondAssignment->days()->value('id')],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    ))->toThrow(InvalidArgumentException::class, 'Only one scheduled shift per date can be changed at a time.')
        ->and(fn () => app(AssignShiftService::class)->applyChanges(
            $staff,
            $branch,
            $target,
            [],
            [999999],
            [],
            expectedOwner: ShiftOwnerType::Platform,
        ))->toThrow(InvalidArgumentException::class, 'The schedule changed before it could be saved. Reload and try again.');
});

it('enforces editable dates, active eligibility, and template audience', function () {
    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $staffTemplate = ShiftTemplate::factory()->create(['audience' => ShiftAudience::Staff]);
    $driverTemplate = ShiftTemplate::factory()->create(['audience' => ShiftAudience::Driver]);

    expect(fn () => app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $staffTemplate,
        ['2026-09-21'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    ))->toThrow(InvalidArgumentException::class, 'Past shifts cannot be changed.');

    $staff->update(['is_active' => false]);

    expect(fn () => app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $driverTemplate,
        ['2026-09-22'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    ))->toThrow(InvalidArgumentException::class, 'Only active people can be assigned shifts.');

    $staff->update(['is_active' => true]);

    expect(fn () => app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $driverTemplate,
        ['2026-09-22'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    ))->toThrow(InvalidArgumentException::class, 'This shift is not available for the selected person type.');
});

it('uses the viewer timezone boundary while keeping today editable', function () {
    Carbon::setTestNow('2026-09-22 15:30:00 UTC');
    request()->headers->set('X-Timezone', 'Australia/Sydney');

    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $template = ShiftTemplate::factory()->create();

    expect(fn () => app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $template,
        ['2026-09-22'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    ))->toThrow(InvalidArgumentException::class, 'Past shifts cannot be changed.');

    app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        $template,
        ['2026-09-23'],
        [],
        [],
        expectedOwner: ShiftOwnerType::Platform,
    );

    expect(UserShiftDay::query()->where('user_id', $staff->id)->value('duty_on')->toDateString())
        ->toBe('2026-09-23');
});

it('rejects duplicate references and contradictory operations before writing', function () {
    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $template = ShiftTemplate::factory()->create();
    $assignment = phaseTwoAssignment($staff, $branch, $template, '2026-09-23', '2026-09-23');
    $day = $assignment->days()->firstOrFail();

    expect(fn () => app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        null,
        [],
        [],
        [$day->id, $day->id],
        expectedOwner: ShiftOwnerType::Platform,
    ))->toThrow(InvalidArgumentException::class, 'Removed shift days must be distinct.')
        ->and(fn () => app(AssignShiftService::class)->applyChanges(
            $staff,
            $branch,
            $template,
            ['2026-09-23'],
            [$day->id],
            [],
            expectedOwner: ShiftOwnerType::Platform,
        ))->toThrow(InvalidArgumentException::class, 'A date cannot be assigned and changed or removed in the same save.');

    expect($day->fresh())->not->toBeNull();
});

it('prevents a workspace from mutating a foreign-owned shift day', function () {
    $branch = Branch::factory()->create();
    $staff = phaseTwoStaff($branch);
    $branchTemplate = ShiftTemplate::factory()->forBranch($branch)->create();
    $assignment = phaseTwoAssignment($staff, $branch, $branchTemplate, '2026-09-23', '2026-09-23');
    $day = $assignment->days()->firstOrFail();

    expect(fn () => app(AssignShiftService::class)->applyChanges(
        $staff,
        $branch,
        null,
        [],
        [],
        [$day->id],
        expectedOwner: ShiftOwnerType::Platform,
    ))->toThrow(HttpException::class);

    expect($day->fresh())->not->toBeNull()
        ->and($assignment->fresh()->status)->toBe(ShiftAssignmentStatus::Scheduled);
});
