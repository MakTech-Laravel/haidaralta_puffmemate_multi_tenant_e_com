<?php

namespace Database\Seeders;

use App\Enums\ShiftAudience;
use App\Enums\ShiftDayKind;
use App\Enums\ShiftOwnerType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Services\Workforce\AssignShiftService;
use App\Services\Workforce\ShiftTemplateService;
use Illuminate\Database\Seeder;

/**
 * Hand-written duty windows for the retail roster.
 *
 * Names, hours and who sits on each window are fixed PuffMeMate people — not
 * Faker — so the shift board looks like a real September floor plan.
 */
class WorkforceShiftSeeder extends Seeder
{
    private const WEEKDAYS = [1, 2, 3, 4, 5];

    private const LATE_DAYS = [1, 2, 3, 4, 5, 6];

    private const EXCEPT_SATURDAY = [1, 2, 3, 4, 5, 7];

    public function run(): void
    {
        $this->replaceOvernightOps();

        $actor = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();
        $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
        $brisbane = Branch::query()->where('slug', BranchSeeder::BRISBANE_QUEEN)->firstOrFail();

        $templates = app(ShiftTemplateService::class);
        $assign = app(AssignShiftService::class);

        $networkSupport = $this->template($templates, $actor, [
            'name' => 'Network support',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'audience' => ShiftAudience::Staff->value,
            'owner_type' => ShiftOwnerType::Platform->value,
        ]);
        $this->template($templates, $actor, [
            'name' => 'Evening ops',
            'start_time' => '18:00',
            'end_time' => '22:00',
            'audience' => ShiftAudience::All->value,
            'owner_type' => ShiftOwnerType::Platform->value,
        ]);

        $sydneyFloor = $this->branchTemplate($templates, $actor, $sydney, 'Floor open', '09:00', '17:00', ShiftAudience::Staff);
        $sydneyLate = $this->branchTemplate($templates, $actor, $sydney, 'Late trade', '16:00', '23:00', ShiftAudience::Staff);
        $sydneyDispatch = $this->branchTemplate($templates, $actor, $sydney, 'Dispatch', '07:00', '15:00', ShiftAudience::Driver);

        $melbourneFloor = $this->branchTemplate($templates, $actor, $melbourne, 'Floor open', '09:00', '17:00', ShiftAudience::Staff);
        $melbourneLate = $this->branchTemplate($templates, $actor, $melbourne, 'Late trade', '12:00', '21:00', ShiftAudience::Staff);

        $brisbaneFloor = $this->branchTemplate($templates, $actor, $brisbane, 'Floor open', '09:00', '17:00', ShiftAudience::Staff);
        $brisbaneLate = $this->branchTemplate($templates, $actor, $brisbane, 'Late trade', '12:00', '21:00', ShiftAudience::Staff);

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_1_SUPER_ADMIN,
            'branch' => $sydney,
            'template' => $networkSupport,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-10',
            'days_of_week' => self::WEEKDAYS,
            'reason' => 'HQ desk coverage from Sydney CBD.',
        ]);

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_1_STAFF,
            'branch' => $sydney,
            'template' => $sydneyFloor,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'days_of_week' => self::WEEKDAYS,
            'reason' => 'Weekday retail floor.',
        ]);

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::DRIVER,
            'branch' => $sydney,
            'template' => $sydneyDispatch,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-10',
            'days_of_week' => self::EXCEPT_SATURDAY,
            'days' => [
                [
                    'date' => '2026-09-10',
                    'kind' => ShiftDayKind::Leave->value,
                ],
            ],
            'reason' => 'Morning dispatch from the CBD hub.',
        ]);

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_1_ADMIN,
            'branch' => $sydney,
            'template' => $sydneyLate,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'days_of_week' => self::LATE_DAYS,
            'reason' => 'Late trade, Sunday off.',
        ]);

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_1_ADMIN,
            'branch' => $sydney,
            'template' => $sydneyDispatch,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'days_of_week' => self::EXCEPT_SATURDAY,
            'reason' => 'Covering dispatch while Daniel is on leave.',
        ]);

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_2_STAFF,
            'branch' => $melbourne,
            'template' => $melbourneFloor,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'days_of_week' => self::WEEKDAYS,
            'reason' => 'Weekday Collins Street floor.',
        ]);
        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_2_ADMIN,
            'branch' => $melbourne,
            'template' => $melbourneLate,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'days_of_week' => self::LATE_DAYS,
            'reason' => 'Late trade on Collins Street.',
        ]);

        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_3_ADMIN,
            'branch' => $brisbane,
            'template' => $brisbaneFloor,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'days_of_week' => self::WEEKDAYS,
            'reason' => 'Queen Street weekday floor.',
        ]);
        $this->assignOnce($assign, $actor, [
            'user_email' => UserSeeder::BRANCH_3_SUPER_ADMIN,
            'branch' => $brisbane,
            'template' => $brisbaneLate,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'days_of_week' => self::LATE_DAYS,
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
     * @param  array{name: string, start_time: string, end_time: string, audience?: string, owner_type: string, branch_id?: int}  $data
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

        if ($existing === null) {
            return $service->create($data, $actor);
        }

        if (isset($data['audience']) && $existing->audience->value !== $data['audience']) {
            $existing->update(['audience' => $data['audience']]);
        }

        return $existing->refresh();
    }

    private function branchTemplate(
        ShiftTemplateService $templates,
        ?User $actor,
        Branch $branch,
        string $name,
        string $startTime,
        string $endTime,
        ShiftAudience $audience = ShiftAudience::All,
    ): ShiftTemplate {
        return $this->template($templates, $actor, [
            'name' => $name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'audience' => $audience->value,
            'owner_type' => ShiftOwnerType::Branch->value,
            'branch_id' => $branch->id,
        ]);
    }

    /**
     * @param  array{user_email: string, branch: Branch, template: ShiftTemplate, start_date: string, end_date: string, days_of_week: list<int>, days?: list<array{date: string, kind: string, shift_template_id?: int|null}>, reason: string}  $data
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
            ->whereDate('start_date', $data['start_date'])
            ->whereDate('end_date', $data['end_date'])
            ->exists();

        if ($already) {
            return;
        }

        $payload = [
            'user_id' => $user->id,
            'branch_id' => $data['branch']->id,
            'shift_template_id' => $data['template']->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_of_week' => $data['days_of_week'],
            'reason' => $data['reason'],
        ];

        if (isset($data['days'])) {
            $payload['days'] = $data['days'];
        }

        $assign->assign($payload, $actor);
    }
}
