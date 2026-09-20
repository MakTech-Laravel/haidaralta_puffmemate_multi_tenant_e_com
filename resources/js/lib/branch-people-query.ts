import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';

export function filteredBranchStaffUrl(branchId: number): string {
    return BranchStaffController.index.url({
        query: { 'filter[branch_id]': branchId },
    });
}

export function filteredBranchDriversUrl(branchId: number): string {
    return DriverController.index.url({
        query: { 'filter[branch_id]': branchId },
    });
}
