import { Check, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';

const AGE_GATE_STORAGE_KEY = 'storefront.age_gate_acknowledged';

export function AgeGateDialog() {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        try {
            if (localStorage.getItem(AGE_GATE_STORAGE_KEY) !== '1') {
                setOpen(true);
            }
        } catch {
            setOpen(true);
        }
    }, []);

    const acknowledge = () => {
        try {
            localStorage.setItem(AGE_GATE_STORAGE_KEY, '1');
        } catch {
            // Ignore storage failures; still dismiss for this session.
        }

        setOpen(false);
    };

    return (
        <Dialog open={open}>
            <DialogContent
                className="max-h-[min(90vh,40rem)] gap-0 overflow-y-auto rounded-2xl border-none p-6 text-center shadow-xl sm:max-w-md sm:p-8 [&>button]:hidden"
                onInteractOutside={(event) => event.preventDefault()}
                onEscapeKeyDown={(event) => event.preventDefault()}
            >
                <div className="border-primary text-primary mx-auto flex size-16 items-center justify-center rounded-xl border-2">
                    <span className="text-2xl font-extrabold tracking-tight">
                        18+
                    </span>
                </div>

                <div className="bg-primary/5 text-foreground mx-auto mt-4 inline-flex items-center gap-1.5 rounded-full px-3 py-1">
                    <ShieldCheck
                        className="text-primary size-3.5 shrink-0"
                        aria-hidden
                    />
                    <span className="text-[0.65rem] font-extrabold tracking-wider uppercase">
                        Statutory compliance
                    </span>
                </div>

                <DialogTitle className="text-foreground mt-5 text-center text-xl font-bold tracking-tight sm:text-2xl">
                    Are you 18 years or older?
                </DialogTitle>

                <DialogDescription className="text-muted-foreground mt-3 text-center text-sm leading-relaxed">
                    In accordance with Australian tobacco and vapor regulations,
                    you must be 18 years of age or older to enter this platform
                    and complete order handovers. Valid government-issued photo
                    ID is strictly required upon courier delivery.
                </DialogDescription>

                <div className="mt-6 flex flex-col gap-3">
                    <Button
                        type="button"
                        size="lg"
                        className="h-12 w-full rounded-xl text-sm font-bold"
                        onClick={acknowledge}
                    >
                        <span className="bg-primary-foreground/20 flex size-5 items-center justify-center rounded-full">
                            <Check className="size-3.5" aria-hidden />
                        </span>
                        Yes, I am 18 or older
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        className="h-12 w-full rounded-xl text-sm font-bold"
                        onClick={acknowledge}
                    >
                        No, I am under 18
                    </Button>
                </div>

                <p className="text-muted-foreground mt-5 text-center text-[0.7rem] leading-relaxed">
                    By selecting Yes, you confirm under penalty of law that you
                    meet the statutory legal age requirement under applicable
                    Commonwealth, State, and Territory legislation.
                </p>
            </DialogContent>
        </Dialog>
    );
}
