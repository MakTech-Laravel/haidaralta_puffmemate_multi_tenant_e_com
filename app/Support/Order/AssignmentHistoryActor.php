<?php

namespace App\Support\Order;

use App\Enums\UserType;
use App\Models\User;

final class AssignmentHistoryActor
{
    /**
     * @return array{name: string|null, url: string|null}
     */
    public static function changedBy(
        ?User $changer,
        ?int $scopedBranchId = null,
        ?string $branchSlug = null,
        bool $platformPortal = false,
    ): array {
        if ($changer === null) {
            return ['name' => null, 'url' => null];
        }

        if ($changer->user_type === UserType::PLATFORM) {
            return ['name' => 'System', 'url' => null];
        }

        if ($changer->user_type !== UserType::BRANCH) {
            return ['name' => $changer->name, 'url' => null];
        }

        if ($platformPortal) {
            return [
                'name' => $changer->name,
                'url' => route('platform.branch-staff.show', $changer),
            ];
        }

        if (
            $scopedBranchId === null
            || $branchSlug === null
            || $changer->branch_id !== $scopedBranchId
        ) {
            return ['name' => $changer->name, 'url' => null];
        }

        return [
            'name' => $changer->name,
            'url' => route('branch.staff.show', [
                'branch' => $branchSlug,
                'user' => $changer,
            ]),
        ];
    }
}
