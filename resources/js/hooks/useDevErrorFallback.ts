export function useDevErrorFallback(): boolean {
    const flag = import.meta.env.VITE_ERROR_BOUNDARY_DEBUG;

    // Safely check if the flag matches truthy/falsy values
    if (flag) {
        if (/^(true|1)$/i.test(flag)) {
            return true;
        }

        if (/^(false|0)$/i.test(flag)) {
            return false;
        }
    }

    // Fallback if unset or invalid: Vite dev server = dev UI
    return import.meta.env.DEV;
}
