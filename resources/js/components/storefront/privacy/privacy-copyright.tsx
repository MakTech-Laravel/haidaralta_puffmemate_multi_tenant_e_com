import { cn } from '@/lib/utils';

type PrivacyCopyrightProps = {
    className?: string;
};

export function PrivacyCopyright({ className }: PrivacyCopyrightProps) {
    return (
        <section className={cn('bg-background pb-14 sm:pb-16', className)}>
            <div className="container px-4 sm:px-6">
                {/* <hr className="border-border mb-8" /> */}
                <p className="text-muted-foreground text-center text-xs sm:text-sm">
                    © 2026 PuffMeMate Australia Pty Ltd. All rights reserved.
                </p>
            </div>
        </section>
    );
}
