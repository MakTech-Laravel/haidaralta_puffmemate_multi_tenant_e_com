import {
    accountButtonClass,
    accountSectionTitleClass,
} from '@/components/customer/account-field-styles';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type AccountTwoFactorSectionProps = {
    canManageTwoFactor: boolean;
    twoFactorEnabled: boolean;
    className?: string;
};

export function AccountTwoFactorSection({
    canManageTwoFactor,
    twoFactorEnabled,
    className,
}: AccountTwoFactorSectionProps) {
    return (
        <section
            className={cn(
                'bg-muted w-full min-w-0 space-y-4 p-5 sm:p-6',
                className,
            )}
        >
            <h2 className={accountSectionTitleClass}>
                Two Factor Authentication
            </h2>

            <Button
                type="button"
                className={accountButtonClass}
                disabled={!canManageTwoFactor}
            >
                {twoFactorEnabled ? 'Enabled' : 'Enable'}
            </Button>
        </section>
    );
}
