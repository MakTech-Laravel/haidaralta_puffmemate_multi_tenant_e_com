import { CircleDot } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

export function DriverHeader() {
    return (
        <header className="border-border bg-background border-b pt-[env(safe-area-inset-top)]">
            <div className="container mx-auto flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <Link
                    href="/driver/dashboard"
                    prefetch
                    className="text-foreground min-w-0 text-lg font-bold tracking-tight"
                >
                    PuffMeMate
                    <span className="text-muted-foreground ml-2 text-xs font-semibold tracking-[0.12em] uppercase">
                        Driver
                    </span>
                </Link>

                <div className="flex shrink-0 items-center gap-2">
                    <Badge
                        variant="secondary"
                        className="h-8 gap-1.5 rounded-full px-2.5 font-normal"
                    >
                        <CircleDot className="size-3 text-emerald-600" />
                        On duty
                    </Badge>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        className="h-8 rounded-full px-3 text-xs font-semibold"
                    >
                        End shift
                    </Button>
                </div>
            </div>
        </header>
    );
}
