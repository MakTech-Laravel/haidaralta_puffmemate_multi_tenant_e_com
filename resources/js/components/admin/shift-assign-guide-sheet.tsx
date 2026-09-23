import {
    CalendarCheck,
    CalendarRange,
    CircleHelp,
    Eraser,
    Keyboard,
    MousePointerClick,
    Palette,
    Save,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

function GuideSection({
    icon: Icon,
    title,
    children,
}: {
    icon: React.ComponentType<{ className?: string }>;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="space-y-2.5">
            <div className="flex items-center gap-2">
                <span className="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg">
                    <Icon className="size-4" />
                </span>
                <h3 className="text-sm font-semibold tracking-tight">
                    {title}
                </h3>
            </div>
            <div className="text-muted-foreground space-y-2 pl-10 text-sm leading-relaxed">
                {children}
            </div>
        </section>
    );
}

function Swatch({
    className,
    label,
    detail,
}: {
    className: string;
    label: string;
    detail: string;
}) {
    return (
        <div className="flex items-start gap-3">
            <span
                className={`mt-0.5 size-4 shrink-0 rounded-sm border ${className}`}
                aria-hidden
            />
            <div>
                <p className="text-foreground text-sm font-medium">{label}</p>
                <p className="text-muted-foreground text-xs leading-snug">
                    {detail}
                </p>
            </div>
        </div>
    );
}

export function ShiftAssignGuideSheet({
    allowMultiplePerDay,
}: {
    allowMultiplePerDay: boolean;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="text-muted-foreground hover:text-foreground size-8"
                    aria-label="How assign shifts works"
                    title="How assign shifts works"
                >
                    <CircleHelp className="size-4.5" />
                </Button>
            </SheetTrigger>
            <SheetContent
                side="right"
                className="flex w-full flex-col gap-0 p-0 sm:max-w-lg"
            >
                <SheetHeader className="border-border/80 border-b px-6 py-5 text-left">
                    <SheetTitle className="text-xl">
                        How assign shifts works
                    </SheetTitle>
                    <SheetDescription>
                        Stage every change on the calendar, then save once.
                        Nothing is written until you confirm.
                    </SheetDescription>
                </SheetHeader>

                <ScrollArea className="min-h-0 flex-1">
                    <div className="space-y-8 px-6 py-5">
                        <GuideSection icon={CalendarCheck} title="The flow">
                            <ol className="list-decimal space-y-1.5 pl-4">
                                <li>
                                    Choose branch, user type, and person above
                                    the calendar.
                                </li>
                                <li>
                                    Select dates to add, change, or remove
                                    shifts.
                                </li>
                                <li>
                                    Review the pending counts, then use{' '}
                                    <strong className="text-foreground">
                                        Save changes
                                    </strong>
                                    .
                                </li>
                                <li>
                                    Pick the target shift in the confirmation
                                    dialog when you are adding or changing.
                                </li>
                            </ol>
                        </GuideSection>

                        <GuideSection
                            icon={Palette}
                            title="What the colors mean"
                        >
                            <div className="space-y-3">
                                <Swatch
                                    className="border-primary/40 bg-primary"
                                    label="Solid red chip"
                                    detail="Already saved on that day."
                                />
                                <Swatch
                                    className="border-amber-600 bg-amber-100"
                                    label="Amber · New / Unsaved"
                                    detail="Empty days you selected to assign. Not saved yet."
                                />
                                <Swatch
                                    className="border-sky-700 bg-sky-100"
                                    label="Sky · Changing / Unsaved"
                                    detail="Assigned days marked to switch to another shift."
                                />
                                <Swatch
                                    className="border-destructive/50 bg-destructive/10"
                                    label="Muted red · Removing"
                                    detail="Assigned days staged for removal."
                                />
                                <Swatch
                                    className="border-muted-foreground/40 bg-muted/50 border-dashed"
                                    label="Grey dashed cell"
                                    detail="Belongs to the previous or next month in the grid."
                                />
                            </div>
                        </GuideSection>

                        <GuideSection
                            icon={MousePointerClick}
                            title="Selecting dates"
                        >
                            <ul className="list-disc space-y-1.5 pl-4">
                                <li>
                                    Click an empty day to stage a new assignment
                                    (or to finish a range).
                                </li>
                                <li>
                                    Click an assigned day to stage a change, or
                                    open the ⋯ menu to change or remove that
                                    shift.
                                </li>
                                <li>
                                    Toggle{' '}
                                    <strong className="text-foreground">
                                        One-by-one
                                    </strong>{' '}
                                    vs{' '}
                                    <strong className="text-foreground">
                                        Group selection
                                    </strong>{' '}
                                    for the default click behavior.
                                </li>
                                <li>
                                    Past dates are never selectable and cannot
                                    be changed.
                                </li>
                            </ul>
                        </GuideSection>

                        <GuideSection
                            icon={Keyboard}
                            title="Keyboard shortcuts"
                        >
                            <ul className="list-disc space-y-1.5 pl-4">
                                <li>
                                    <kbd className="bg-muted rounded px-1.5 py-0.5 text-xs font-medium">
                                        Shift
                                    </kbd>
                                    -click — always range / group select, even
                                    if one-by-one mode is on. Works for empty
                                    days (add) and assigned days (change).
                                </li>
                                <li>
                                    <kbd className="bg-muted rounded px-1.5 py-0.5 text-xs font-medium">
                                        Ctrl
                                    </kbd>{' '}
                                    (Windows) or{' '}
                                    <kbd className="bg-muted rounded px-1.5 py-0.5 text-xs font-medium">
                                        Ctrl
                                    </kbd>
                                    /
                                    <kbd className="bg-muted rounded px-1.5 py-0.5 text-xs font-medium">
                                        ⌘
                                    </kbd>{' '}
                                    (Mac) — always one-by-one, even if group
                                    mode is on.
                                </li>
                                <li>
                                    Arrow keys move focus between day cells.
                                </li>
                            </ul>
                        </GuideSection>

                        <GuideSection
                            icon={CalendarRange}
                            title="Quick actions"
                        >
                            <ul className="list-disc space-y-1.5 pl-4">
                                <li>
                                    <strong className="text-foreground">
                                        Select remaining days
                                    </strong>{' '}
                                    — stages every day from today through the
                                    end of the viewed month. Empty days become
                                    new; single assigned days become change.
                                    Disabled when the whole month is past.
                                </li>
                                <li>
                                    <strong className="text-foreground inline-flex items-center gap-1">
                                        <Eraser className="size-3.5" /> Clear
                                        selection
                                    </strong>{' '}
                                    — drops all staged adds, changes, and
                                    removals without saving.
                                </li>
                            </ul>
                        </GuideSection>

                        <GuideSection icon={Save} title="Saving">
                            <ul className="list-disc space-y-1.5 pl-4">
                                <li>
                                    Nothing is permanent until you confirm in{' '}
                                    <strong className="text-foreground">
                                        Save changes
                                    </strong>
                                    .
                                </li>
                                <li>
                                    Leaving the page, changing person/month, or
                                    navigating away with pending work asks you
                                    to discard first.
                                </li>
                                <li>
                                    Closing or refreshing the browser tab still
                                    uses the browser’s leave warning.
                                </li>
                            </ul>
                        </GuideSection>

                        {allowMultiplePerDay ? (
                            <GuideSection
                                icon={CircleHelp}
                                title="Multiple shifts per day"
                            >
                                <p>
                                    Multiple shifts on one day are allowed. Use{' '}
                                    <strong className="text-foreground">
                                        Add another
                                    </strong>{' '}
                                    on an assigned day, and the ⋯ menu when a
                                    day has more than one shift.
                                </p>
                            </GuideSection>
                        ) : (
                            <GuideSection
                                icon={CircleHelp}
                                title="One shift per day"
                            >
                                <p>
                                    Only one shift can be assigned per day.
                                    Selecting an already-assigned day stages a
                                    change for that shift.
                                </p>
                            </GuideSection>
                        )}
                    </div>
                </ScrollArea>
            </SheetContent>
        </Sheet>
    );
}
