import { useCallback, useState } from 'react';

const DRAG_THRESHOLD_PX = 4;

type ScrollEdges = {
    atStart: boolean;
    atEnd: boolean;
};

/**
 * Lets a horizontal overflow row scroll by mouse drag and vertical wheel,
 * on top of native touch scrolling, and reports which edges are reached.
 */
export function useDragScroll<T extends HTMLElement>(): [
    (element: T | null) => (() => void) | undefined,
    ScrollEdges,
] {
    const [edges, setEdges] = useState<ScrollEdges>({
        atStart: true,
        atEnd: true,
    });

    const ref = useCallback((element: T | null) => {
        if (!element) {
            return undefined;
        }

        let pointerId: number | null = null;
        let startX = 0;
        let startScrollLeft = 0;
        let dragged = false;

        const updateEdges = (): void => {
            const maxScroll = element.scrollWidth - element.clientWidth;

            setEdges({
                atStart: element.scrollLeft <= 1,
                atEnd: element.scrollLeft >= maxScroll - 1,
            });
        };

        const onPointerMove = (event: PointerEvent): void => {
            if (pointerId !== event.pointerId) {
                return;
            }

            const distance = event.clientX - startX;

            if (!dragged && Math.abs(distance) > DRAG_THRESHOLD_PX) {
                dragged = true;
                element.style.cursor = 'grabbing';
            }

            if (dragged) {
                element.scrollLeft = startScrollLeft - distance;
            }
        };

        const onPointerEnd = (event: PointerEvent): void => {
            if (pointerId !== event.pointerId) {
                return;
            }

            pointerId = null;
            element.style.cursor = '';
            stopTracking();
        };

        const stopTracking = (): void => {
            window.removeEventListener('pointermove', onPointerMove);
            window.removeEventListener('pointerup', onPointerEnd);
            window.removeEventListener('pointercancel', onPointerEnd);
        };

        const onPointerDown = (event: PointerEvent): void => {
            if (event.pointerType !== 'mouse' || event.button !== 0) {
                return;
            }

            pointerId = event.pointerId;
            startX = event.clientX;
            startScrollLeft = element.scrollLeft;
            dragged = false;
            window.addEventListener('pointermove', onPointerMove);
            window.addEventListener('pointerup', onPointerEnd);
            window.addEventListener('pointercancel', onPointerEnd);
        };

        const onClickCapture = (event: MouseEvent): void => {
            if (dragged) {
                event.preventDefault();
                event.stopPropagation();
                dragged = false;
            }
        };

        const onWheel = (event: WheelEvent): void => {
            const canScroll = element.scrollWidth > element.clientWidth;

            if (canScroll && Math.abs(event.deltaY) > Math.abs(event.deltaX)) {
                event.preventDefault();
                element.scrollLeft += event.deltaY;
            }
        };

        const resizeObserver = new ResizeObserver(updateEdges);

        element.addEventListener('pointerdown', onPointerDown);
        element.addEventListener('click', onClickCapture, true);
        element.addEventListener('wheel', onWheel, { passive: false });
        element.addEventListener('scroll', updateEdges, { passive: true });
        resizeObserver.observe(element);
        updateEdges();

        return () => {
            stopTracking();
            element.removeEventListener('pointerdown', onPointerDown);
            element.removeEventListener('click', onClickCapture, true);
            element.removeEventListener('wheel', onWheel);
            element.removeEventListener('scroll', updateEdges);
            resizeObserver.disconnect();
        };
    }, []);

    return [ref, edges];
}
