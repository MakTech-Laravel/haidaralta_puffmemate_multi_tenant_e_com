/**
 * Glides a value pair from one point to another so a marker moves smoothly between the
 * sparse GPS updates instead of jumping. Returns a function that cancels the glide.
 */
export function glide(
    from: [number, number],
    to: [number, number],
    durationMs: number,
    step: (lat: number, lng: number) => void,
): () => void {
    let frame = 0;
    const start = performance.now();

    const tick = (now: number) => {
        const progress = Math.min((now - start) / durationMs, 1);
        const eased = 1 - (1 - progress) ** 3;

        step(
            from[0] + (to[0] - from[0]) * eased,
            from[1] + (to[1] - from[1]) * eased,
        );

        if (progress < 1) {
            frame = requestAnimationFrame(tick);
        }
    };

    frame = requestAnimationFrame(tick);

    return () => cancelAnimationFrame(frame);
}
