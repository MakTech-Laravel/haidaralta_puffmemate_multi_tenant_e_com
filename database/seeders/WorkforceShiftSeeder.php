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
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

/**
 * Hand-written duty windows for the retail roster.
 *
 * Names, hours and who sits on each window are fixed PuffMeMate people — not
 * Faker — so the shift board looks like a real September floor plan.
 */
class WorkforceShiftSeeder extends Seeder
{
    use ReportsSeedProgress;

    private const WEEKDAYS = [1, 2, 3, 4, 5];

    private const LATE_DAYS = [1, 2, 3, 4, 5, 6];

    private const EXCEPT_SATURDAY = [1, 2, 3, 4, 5, 7];

    private const ALL_DAYS = [1, 2, 3, 4, 5, 6, 7];

    public function run(): void
    {
        $this->replaceOvernightOps();

        $actor = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();
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

        $branchPlans = [
            BranchSeeder::SYDNEY_CBD => [
                'floor' => ['09:00', '17:00'],
                'late' => ['16:00', '23:00'],
                'dispatch' => ['07:00', '15:00'],
                'floor_emails' => [
                    UserSeeder::BRANCH_1_STAFF,
                    'harper.cole@sydney-cbd.dev',
                    'owen.fitzgerald@sydney-cbd.dev',
                ],
                'late_emails' => [
                    UserSeeder::BRANCH_1_ADMIN,
                    'zara.malik@sydney-cbd.dev',
                    'benjamin.shaw@sydney-cbd.dev',
                ],
                'dispatch_emails' => [
                    'marcus.reid@puffmemate.dev',
                ],
            ],
            BranchSeeder::MELBOURNE_COLLINS => [
                'floor' => ['09:00', '17:00'],
                'late' => ['12:00', '21:00'],
                'dispatch' => ['08:00', '16:00'],
                'floor_emails' => [
                    UserSeeder::BRANCH_2_STAFF,
                    'isla.thompson@melbourne-collins.dev',
                    'marcus.delgado@melbourne-collins.dev',
                ],
                'late_emails' => [
                    UserSeeder::BRANCH_2_ADMIN,
                    'nina.volkov@melbourne-collins.dev',
                ],
                'dispatch_emails' => [
                    'kai.nguyen@puffmemate.dev',
                    'bella.hart@puffmemate.dev',
                ],
            ],
            BranchSeeder::BRISBANE_QUEEN => [
                'floor' => ['09:00', '17:00'],
                'late' => ['12:00', '21:00'],
                'dispatch' => ['07:30', '15:30'],
                'floor_emails' => [
                    UserSeeder::BRANCH_3_ADMIN,
                    'sophie.grant@brisbane-queen.dev',
                    'ethan.park@brisbane-queen.dev',
                ],
                'late_emails' => [
                    UserSeeder::BRANCH_3_SUPER_ADMIN,
                    'amelia.ruiz@brisbane-queen.dev',
                ],
                'dispatch_emails' => [
                    'ryan.cho@puffmemate.dev',
                    'nina.blake@puffmemate.dev',
                ],
            ],
            BranchSeeder::PERTH_HAY => [
                'floor' => ['09:00', '17:00'],
                'late' => ['13:00', '20:00'],
                'dispatch' => ['08:00', '16:00'],
                'floor_emails' => ['harvey.knox@perth-hay.dev', 'freya.sutton@perth-hay.dev'],
                'late_emails' => ['lucia.bennett@perth-hay.dev', 'tate.morrison@perth-hay.dev'],
                'dispatch_emails' => ['hugo.price@puffmemate.dev', 'aria.wells@puffmemate.dev'],
            ],
            BranchSeeder::IPSWICH_BRISBANE_ST => [
                'floor' => ['09:00', '17:00'],
                'late' => ['12:00', '20:00'],
                'dispatch' => ['08:00', '16:00'],
                'floor_emails' => ['noah.carr@ipswich-brisbane-st.dev', 'piper.hughes@ipswich-brisbane-st.dev'],
                'late_emails' => ['jade.osullivan@ipswich-brisbane-st.dev', 'callum.reid@ipswich-brisbane-st.dev'],
                'dispatch_emails' => ['felix.drake@puffmemate.dev', 'olivia.kent@puffmemate.dev'],
            ],
        ];

        $this->startSeedProgress('Shifts', count($branchPlans) + 2);
        $this->advanceSeedProgress();

        foreach ($branchPlans as $slug => $plan) {
            $branch = Branch::query()->where('slug', $slug)->firstOrFail();

            $floor = $this->branchTemplate(
                $templates,
                $actor,
                $branch,
                'Floor open',
                $plan['floor'][0],
                $plan['floor'][1],
                ShiftAudience::Staff,
            );
            $late = $this->branchTemplate(
                $templates,
                $actor,
                $branch,
                'Late trade',
                $plan['late'][0],
                $plan['late'][1],
                ShiftAudience::Staff,
            );
            $dispatch = $this->branchTemplate(
                $templates,
                $actor,
                $branch,
                'Dispatch',
                $plan['dispatch'][0],
                $plan['dispatch'][1],
                ShiftAudience::Driver,
            );

            foreach ($plan['floor_emails'] as $email) {
                $this->assignOnce($assign, $actor, [
                    'user_email' => $email,
                    'branch' => $branch,
                    'template' => $floor,
                    'start_date' => '2026-09-01',
                    'end_date' => '2026-10-31',
                    'days_of_week' => self::WEEKDAYS,
                    'reason' => 'Weekday retail floor at '.$branch->name.'.',
                ]);
            }

            foreach ($plan['late_emails'] as $email) {
                $this->assignOnce($assign, $actor, [
                    'user_email' => $email,
                    'branch' => $branch,
                    'template' => $late,
                    'start_date' => '2026-09-01',
                    'end_date' => '2026-10-31',
                    'days_of_week' => self::LATE_DAYS,
                    'reason' => 'Late trade coverage at '.$branch->name.'.',
                ]);
            }

            foreach ($plan['dispatch_emails'] as $email) {
                $this->assignOnce($assign, $actor, [
                    'user_email' => $email,
                    'branch' => $branch,
                    'template' => $dispatch,
                    'start_date' => '2026-09-01',
                    'end_date' => '2026-10-31',
                    'days_of_week' => self::EXCEPT_SATURDAY,
                    'reason' => 'Morning dispatch from '.$branch->name.'.',
                ]);
            }

            $this->advanceSeedProgress();
        }

        $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $sydneyDispatch = ShiftTemplate::query()
            ->where('branch_id', $sydney->id)
            ->where('name', 'Dispatch')
            ->firstOrFail();

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
            'template' => $sydneyDispatch,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'days_of_week' => self::ALL_DAYS,
            'reason' => 'Covering dispatch while Daniel is on leave.',
        ]);

        $this->finishSeedProgress(fn (): string => sprintf(
            '%d shift templates and %d assignments ready.',
            ShiftTemplate::query()->count(),
            UserShiftAssignment::query()->count(),
        ));
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
