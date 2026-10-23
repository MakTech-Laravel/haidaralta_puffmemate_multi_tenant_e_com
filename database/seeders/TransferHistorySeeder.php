<?php

namespace Database\Seeders;

use App\Enums\TransferSubjectType;
use App\Enums\TransferType;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserTransferHistory;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

/**
 * Hand-written transfer history so Reports → Transfer history looks populated.
 * Rows are archive-only; people already sit on their destination branches.
 */
class TransferHistorySeeder extends Seeder
{
    use ReportsSeedProgress;

    public function run(): void
    {
        $actor = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();
        $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
        $brisbane = Branch::query()->where('slug', BranchSeeder::BRISBANE_QUEEN)->firstOrFail();
        $perth = Branch::query()->where('slug', BranchSeeder::PERTH_HAY)->firstOrFail();
        $ipswich = Branch::query()->where('slug', BranchSeeder::IPSWICH_BRISBANE_ST)->firstOrFail();

        $rows = [
            [
                'email' => 'amelia.ruiz@brisbane-queen.dev',
                'subject' => TransferSubjectType::BranchStaff,
                'from' => $sydney,
                'to' => $brisbane,
                'from_role' => 'Staff',
                'to_role' => 'Staff',
                'date' => '2026-06-15',
                'reason' => 'Relocated to Brisbane for university; kept retail hours on Queen Street.',
            ],
            [
                'email' => 'nina.volkov@melbourne-collins.dev',
                'subject' => TransferSubjectType::BranchStaff,
                'from' => $sydney,
                'to' => $melbourne,
                'from_role' => 'Staff',
                'to_role' => 'Staff',
                'date' => '2026-07-01',
                'reason' => 'Collins Street needed evening floor cover after winter roster reshape.',
            ],
            [
                'email' => 'piper.hughes@ipswich-brisbane-st.dev',
                'subject' => TransferSubjectType::BranchStaff,
                'from' => $brisbane,
                'to' => $ipswich,
                'from_role' => 'Staff',
                'to_role' => 'Staff',
                'date' => '2026-08-12',
                'reason' => 'Opened Brisbane Street Ipswich with experienced Queen Street floor staff.',
            ],
            [
                'email' => 'freya.sutton@perth-hay.dev',
                'subject' => TransferSubjectType::BranchStaff,
                'from' => $melbourne,
                'to' => $perth,
                'from_role' => 'Staff',
                'to_role' => 'Staff',
                'date' => '2026-08-28',
                'reason' => 'Temporary cover for Perth peak trade became permanent.',
            ],
            [
                'email' => 'kai.nguyen@puffmemate.dev',
                'subject' => TransferSubjectType::Driver,
                'from' => $sydney,
                'to' => $melbourne,
                'from_role' => 'Driver',
                'to_role' => 'Driver',
                'date' => '2026-07-20',
                'reason' => 'Melbourne dispatch volume needed a second motorcycle courier.',
            ],
            [
                'email' => 'nina.blake@puffmemate.dev',
                'subject' => TransferSubjectType::Driver,
                'from' => $melbourne,
                'to' => $brisbane,
                'from_role' => 'Driver',
                'to_role' => 'Driver',
                'date' => '2026-08-05',
                'reason' => 'Transferred to Queen Street after CBD car courier hire.',
            ],
            [
                'email' => 'felix.drake@puffmemate.dev',
                'subject' => TransferSubjectType::Driver,
                'from' => $brisbane,
                'to' => $ipswich,
                'from_role' => 'Driver',
                'to_role' => 'Driver',
                'date' => '2026-08-18',
                'reason' => 'Ipswich Brisbane Street deliveries spun up with store opening.',
            ],
            [
                'email' => UserSeeder::BRANCH_2_ADMIN,
                'subject' => TransferSubjectType::BranchStaff,
                'from' => $melbourne,
                'to' => $melbourne,
                'from_role' => 'Staff',
                'to_role' => 'Admin',
                'type' => TransferType::RoleChange,
                'date' => '2026-05-10',
                'reason' => 'Promoted to store admin after six months on Collins Street floor.',
            ],
            [
                'email' => 'benjamin.shaw@sydney-cbd.dev',
                'subject' => TransferSubjectType::BranchStaff,
                'from' => $sydney,
                'to' => $sydney,
                'from_role' => 'Staff',
                'to_role' => 'Admin',
                'type' => TransferType::RoleChange,
                'date' => '2026-06-01',
                'reason' => 'Promoted to second admin for CBD peak weekends.',
            ],
            [
                'email' => 'jade.osullivan@ipswich-brisbane-st.dev',
                'subject' => TransferSubjectType::BranchStaff,
                'from' => $brisbane,
                'to' => $ipswich,
                'from_role' => 'Admin',
                'to_role' => 'Admin',
                'date' => '2026-08-01',
                'reason' => 'Moved from Queen Street admin desk to open Ipswich store leadership.',
            ],
        ];

        $this->withSeedProgress('Transfers', $rows, function (array $row) use ($actor): void {
            $user = User::query()->where('email', $row['email'])->first();

            if ($user === null) {
                return;
            }

            $exists = UserTransferHistory::query()
                ->where('user_id', $user->id)
                ->whereDate('effective_date', $row['date'])
                ->where('from_branch_id', $row['from']->id)
                ->where('to_branch_id', $row['to']->id)
                ->where('reason', $row['reason'])
                ->exists();

            if ($exists) {
                return;
            }

            UserTransferHistory::query()->create([
                'user_id' => $user->id,
                'subject_type' => $row['subject'],
                'transfer_type' => $row['type'] ?? TransferType::BranchChange,
                'from_branch_id' => $row['from']->id,
                'to_branch_id' => $row['to']->id,
                'from_role' => $row['from_role'],
                'to_role' => $row['to_role'],
                'effective_date' => $row['date'],
                'reason' => $row['reason'],
                'transferred_by' => $actor?->id,
            ]);
        }, fn (): string => sprintf('%d transfer history rows on file.', UserTransferHistory::query()->count()));
    }
}
