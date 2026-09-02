import { AnimatePresence, motion } from 'motion/react';
import { createPortal } from 'react-dom';

type IconPickerHoverTipProps = {
    open: boolean;
    label: string;
    iconKey: string;
    x: number;
    y: number;
};

export function IconPickerHoverTip({
    open,
    label,
    iconKey,
    x,
    y,
}: IconPickerHoverTipProps) {
    if (typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <AnimatePresence>
            {open ? (
                <motion.div
                    key={`${iconKey}-${Math.round(x)}-${Math.round(y)}`}
                    role="tooltip"
                    initial={{ opacity: 0, y: 6, scale: 0.96 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    exit={{ opacity: 0, y: 4, scale: 0.98 }}
                    transition={{
                        type: 'spring',
                        stiffness: 520,
                        damping: 32,
                        mass: 0.6,
                    }}
                    className="pointer-events-none fixed z-[200] max-w-[14rem] -translate-x-1/2 -translate-y-[calc(100%+10px)] rounded-lg border border-border bg-popover px-2.5 py-1.5 text-popover-foreground shadow-md"
                    style={{ left: x, top: y }}
                >
                    <p className="text-xs font-medium text-foreground">
                        {label}
                    </p>
                    <p className="mt-0.5 font-mono text-[10px] text-muted-foreground">
                        {iconKey}
                    </p>
                </motion.div>
            ) : null}
        </AnimatePresence>,
        document.body,
    );
}
