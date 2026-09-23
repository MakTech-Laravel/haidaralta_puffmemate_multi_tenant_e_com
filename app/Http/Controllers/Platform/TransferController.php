<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\TransferDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\TransferBranchStaffRequest;
use App\Http\Requests\Platform\TransferDriverBranchRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\Branch\BranchStaffService;
use App\Services\Workforce\TransferEmployeeService;
use App\Support\DriverPayload;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class TransferController extends Controller
{
    public function __construct(
        private TransferEmployeeService $transfers,
        private BranchStaffService $branchStaff,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('platform/reports/transfer-history', [
            'table' => TransferDataTable::make($request),
        ]);
    }

    public function storeStaff(TransferBranchStaffRequest $request, User $user): RedirectResponse
    {
        $staff = $this->branchStaff->resolveBranchStaff($user);
        $to = Branch::query()->findOrFail($request->integer('to_branch_id'));

        try {
            $this->transfers->transferBranchStaff(
                $staff,
                $to,
                $request->validated('roles', []),
                $request->validated('effective_date'),
                $request->validated('reason'),
                $request->user('platform'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Staff member transferred.');

        return redirect()->back();
    }

    public function storeDriver(TransferDriverBranchRequest $request, User $user): RedirectResponse
    {
        $driver = DriverPayload::resolveDriver($user);
        $to = Branch::query()->findOrFail($request->integer('to_branch_id'));

        try {
            $this->transfers->transferDriverBranch(
                $driver,
                $to,
                $request->validated('effective_date'),
                $request->validated('reason'),
                $request->user('platform'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Driver transferred.');

        return redirect()->back();
    }
}
