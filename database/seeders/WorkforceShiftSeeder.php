<?php

namespace Database\Seeders;

use App\Enums\ShiftOwnerType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\ShiftRosterPeriod;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Services\Workforce\AssignShiftService;
use App\Services\Workforce\ShiftRosterPeriodService;
use App\Services\Workforce\ShiftTemplateService;
use Illuminate\Database\Seeder;

/**
 * Hand-written duty windows for the retail roster.
 *
 * Names, hours and who sits on each window are fixed PuffMeMate people — not
 * Faker — so the shift board looks like a real September 2026 floor plan.
 */
class WorkforceShiftSeeder extends Seeder
{
    private const WEEKDAYS = [1, 2, 3, 4, 5];

    private const LATE_DAYS = [3, 4, 5, 6];

    private const EVERY_DAY = [1, 2, 3, 4, 5, 6, 7];

    public function run(): void
    {
        $this->replaceOvernightOps();

        $actor = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();
        $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
        $brisbane = Branch::query()->where('slug', BranchSeeder::BRISBANE_QUEEN)->firstOrFail();

        $templates = app(ShiftTemplateService::class);
        $periods = app(ShiftRosterPeriodService::class);
        $assign = app(AssignShiftService::class);

        $networkSupport = $this->template($templates, $actor, [
            'name' => 'Network support',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'owner_type' => ShiftOwnerType::Platform->value,
        ]);
        $this->template($templates, $actor, [
            'name' => 'Evening ops',
            'start_time' => '18:00',
            'end_time' => '22:00',
            'owner_type' => ShiftOwnerType::Platform->value,
        ]);

        $spring = $this->period($periods, $actor, [
            'name' => 'Spring 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-11-30',
            'owner_type' => ShiftOwnerType::Platform->value,
        ]);

        $sydneyFloor = $this->branchWindow($templates, $periods, $actor, $sydney, 'Floor open', '09:00', '17:00');
        $sydneyLate = $this->branchWindow($templates, $periods, $actor, $sydney, 'Late trade', '12:00', '21:00');
        $sydneyDispatch = $this->branchWindow($templates, $periods, $actor, $sydney, 'Dispatch', '07:00', '15:00');

        $melbourneFloor = $this->branchWindow($templates, $periods, $actor, $melbourne, 'Floor open', '09:00', '17:00');
        $melbourneLate = $this->branchWindow($templates, $periods, $actor, $melbourne, 'Late trade', '12:00', '21:00');

        $brisbaneFloor = $this->branchWindow($templates, $periods, $actor, $brisbane, 'Floor open', '09:00', '17:00');
        $brisbaneLate = $this->branchWindow($templates, $periods, $actor, $brisbane, 'Late trade', '12:00', '21:00');

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_1_SUPER_ADMIN,
            'branch' => $sydney,
            'template' => $networkSupport,
            'period' => $spring,
            'days' => self::WEEKDAYS,
            'reason' => 'HQ desk coverage from Sydney CBD.',
        ]);

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_1_STAFF,
            'branch' => $sydney,
            'template' => $sydneyFloor['template'],
            'period' => $sydneyFloor['period'],
            'days' => self::WEEKDAYS,
            'reason' => 'Weekday retail floor.',
        ]);
        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_1_ADMIN,
            'branch' => $sydney,
            'template' => $sydneyLate['template'],
            'period' => $sydneyLate['period'],
            'days' => self::LATE_DAYS,
            'reason' => 'Wednesday–Saturday late trade.',
        ]);
        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::DRIVER,
            'branch' => $sydney,
            'template' => $sydneyDispatch['template'],
            'period' => $sydneyDispatch['period'],
            'days' => self::EVERY_DAY,
            'reason' => 'Morning dispatch from the CBD hub.',
        ]);

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_2_STAFF,
            'branch' => $melbourne,
            'template' => $melbourneFloor['template'],
            'period' => $melbourneFloor['period'],
            'days' => self::WEEKDAYS,
            'reason' => 'Weekday Collins Street floor.',
        ]);
        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_2_ADMIN,
            'branch' => $melbourne,
            'template' => $melbourneLate['template'],
            'period' => $melbourneLate['period'],
            'days' => self::LATE_DAYS,
            'reason' => 'Late trade on Collins Street.',
        ]);

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_3_ADMIN,
            'branch' => $brisbane,
            'template' => $brisbaneFloor['template'],
            'period' => $brisbaneFloor['period'],
            'days' => self::WEEKDAYS,
            'reason' => 'Queen Street weekday floor.',
        ]);
        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_3_SUPER_ADMIN,
            'branch' => $brisbane,
            'template' => $brisbaneLate['template'],
            'period' => $brisbaneLate['period'],
            'days' => self::LATE_DAYS,
            'reason' => 'Queen Street late trade.',
        ]);
    }

    private function replaceOvernightOps(): void
    {
        ShiftTemplate::query()
            ->where('name', 'Overnight ops')
            ->whereNull('branch_id')
            ->update([
                'name' => 'Evening ops',
                'start_time' => '18:00',
                'end_time' => '22:00',
            ]);
    }

    /**
     * @param  array{name: string, start_time: string, end_time: string, owner_type: string, branch_id?: int}  $data
     */
    private function template(ShiftTemplateService $service, ?User $actor, array $data): ShiftTemplate
    {
        $existing = ShiftTemplate::query()
            ->where('name', $data['name'])
            ->where('owner_type', $data['owner_type'])
            ->when(
                isset($data['branch_id']),
                fn ($query) => $query->where('branch_id', $data['branch_id']),
                fn ($query) => $query->whereNull('branch_id'),
            )
            ->first();

        return $existing ?? $service->create($data, $actor);
    }

    /**
     * @param  array{name: string, start_date: string, end_date: string, owner_type: string, branch_id?: int}  $data
     */
    private function period(ShiftRosterPeriodService $service, ?User $actor, array $data): ShiftRosterPeriod
    {
        $existing = ShiftRosterPeriod::query()
            ->where('name', $data['name'])
            ->where('owner_type', $data['owner_type'])
            ->when(
                isset($data['branch_id']),
                fn ($query) => $query->where('branch_id', $data['branch_id']),
                fn ($query) => $query->whereNull('branch_id'),
            )
            ->first();

        return $existing ?? $service->create($data, $actor);
    }

    /**
     * @return array{template: ShiftTemplate, period: ShiftRosterPeriod}
     */
    private function branchWindow(
        ShiftTemplateService $templates,
        ShiftRosterPeriodService $periods,
        ?User $actor,
        Branch $branch,
        string $templateName,
        string $startTime,
        string $endTime,
    ): array {
        $template = $this->template($templates, $actor, [
            'name' => $templateName,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'owner_type' => ShiftOwnerType::Branch->value,
            'branch_id' => $branch->id,
        ]);

        $period = $this->period($periods, $actor, [
            'name' => 'September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'owner_type' => ShiftOwnerType::Branch->value,
            'branch_id' => $branch->id,
        ]);

        $this->period($periods, $actor, [
            'name' => 'October 2026',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'owner_type' => ShiftOwnerType::Branch->value,
            'branch_id' => $branch->id,
        ]);

        return ['template' => $template, 'period' => $period];
    }

    /**
     * @param  array{user_email: string, branch: Branch, template: ShiftTemplate, period: ShiftRosterPeriod, days: list<int>, reason: string}  $data
     */
    private function assignOnce(AssignShiftService $assign, ?User $actor, array $data): void
    {
        $user = User::query()->where('email', $data['user_email'])->first();

        if ($user === null || $user->user_type === UserType::PLATFORM) {
            return;
        }

        $already = UserShiftAssignment::query()
            ->where('user_id', $user->id)
            ->where('shift_template_id', $data['template']->id)
            ->where('shift_roster_period_id', $data['period']->id)
            ->exists();

        if ($already) {
            return;
        }

        $assign->assign([
            'user_id' => $user->id,
            'branch_id' => $data['branch']->id,
            'shift_template_id' => $data['template']->id,
            'shift_roster_period_id' => $data['period']->id,
            'start_date' => $data['period']->start_date->format('Y-m-d'),
            'end_date' => $data['period']->end_date->format('Y-m-d'),
            'days_of_week' => $data['days'],
            'reason' => $data['reason'],
        ], $actor);
    }
}
