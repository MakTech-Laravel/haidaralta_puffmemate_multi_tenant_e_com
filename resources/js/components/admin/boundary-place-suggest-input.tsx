import { useEffect, useId, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverAnchor,
    PopoverContent,
} from '@/components/ui/popover';
import {
    ringFromGeoJson,
    searchPlacesWithGeometry,
    type LatLngPoint,
    type NominatimPlaceWithGeometry,
} from '@/lib/nominatim';
import { cn } from '@/lib/utils';

export type BoundaryPlaceSelection =
    | {
          kind: 'boundary';
          displayName: string;
          ring: LatLngPoint[];
      }
    | {
          kind: 'point_only';
          displayName: string;
      };

type BoundaryPlaceSuggestInputProps = {
    id?: string;
    placeholder?: string;
    className?: string;
    onSelect: (selection: BoundaryPlaceSelection) => void;
};

export function BoundaryPlaceSuggestInput({
    id,
    placeholder,
    className,
    onSelect,
}: BoundaryPlaceSuggestInputProps) {
    const listId = useId();
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [results, setResults] = useState<NominatimPlaceWithGeometry[]>([]);
    const skipSearchRef = useRef(false);

    useEffect(() => {
        if (skipSearchRef.current) {
            skipSearchRef.current = false;
            setResults([]);
            setError(null);
            setLoading(false);

            return;
        }

        const trimmed = query.trim();

        if (trimmed.length < 3) {
            setResults([]);
            setError(null);
            setLoading(false);

            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            setLoading(true);
            setError(null);

            void searchPlacesWithGeometry(trimmed, controller.signal)
                .then((places) => {
                    setResults(places);
                    setOpen(true);
                })
                .catch((err: unknown) => {
                    if (err instanceof DOMException && err.name === 'AbortError') {
                        return;
                    }

                    setResults([]);
                    setError('Could not load place suggestions.');
                    setOpen(true);
                })
                .finally(() => {
                    if (!controller.signal.aborted) {
                        setLoading(false);
                    }
                });
        }, 400);

        return () => {
            controller.abort();
            window.clearTimeout(timer);
        };
    }, [query]);

    const showList =
        open &&
        (loading || error !== null || results.length > 0 || query.trim().length >= 3);

    return (
        <Popover
            open={showList}
            onOpenChange={(next) => {
                if (!next) {
                    setOpen(false);
                }
            }}
        >
            <PopoverAnchor asChild>
                <div className="relative w-full">
                    <Input
                        id={id}
                        value={query}
                        autoComplete="off"
                        role="combobox"
                        aria-expanded={showList}
                        aria-controls={listId}
                        aria-autocomplete="list"
                        placeholder={placeholder}
                        className={className}
                        onFocus={() => {
                            if (
                                query.trim().length >= 3 &&
                                (results.length > 0 || error !== null || loading)
                            ) {
                                setOpen(true);
                            }
                        }}
                        onChange={(event) => {
                            setQuery(event.target.value);
                            setOpen(true);
                        }}
                        onKeyDown={(event) => {
                            if (event.key === 'Escape') {
                                setOpen(false);
                            }
                        }}
                    />
                </div>
            </PopoverAnchor>
            <PopoverContent
                id={listId}
                align="start"
                sideOffset={4}
                className="w-(--radix-popover-trigger-width) rounded-none border p-0 shadow-md"
                onOpenAutoFocus={(event) => event.preventDefault()}
            >
                <ul className="max-h-60 overflow-y-auto py-1" role="listbox">
                    {loading ? (
                        <li className="text-muted-foreground px-3 py-2 text-sm">
                            Searching…
                        </li>
                    ) : null}
                    {!loading && error ? (
                        <li className="text-destructive px-3 py-2 text-sm">
                            {error}
                        </li>
                    ) : null}
                    {!loading &&
                    !error &&
                    results.length === 0 &&
                    query.trim().length >= 3 ? (
                        <li className="text-muted-foreground px-3 py-2 text-sm">
                            No places found. Try a suburb or area name.
                        </li>
                    ) : null}
                    {!loading &&
                        !error &&
                        results.map((place) => {
                            const ring = ringFromGeoJson(place.geojson);
                            const hasBoundary = ring !== null;

                            return (
                                <li
                                    key={`${place.latitude},${place.longitude},${place.displayName}`}
                                >
                                    <button
                                        type="button"
                                        role="option"
                                        className={cn(
                                            'hover:bg-accent hover:text-accent-foreground w-full cursor-pointer px-3 py-2 text-left text-sm',
                                        )}
                                        onClick={() => {
                                            skipSearchRef.current = true;
                                            setQuery('');
                                            setResults([]);
                                            setOpen(false);

                                            if (hasBoundary && ring) {
                                                onSelect({
                                                    kind: 'boundary',
                                                    displayName: place.displayName,
                                                    ring,
                                                });
                                            } else {
                                                onSelect({
                                                    kind: 'point_only',
                                                    displayName: place.displayName,
                                                });
                                            }
                                        }}
                                    >
                                        <span className="block">
                                            {place.displayName}
                                        </span>
                                        <span className="text-muted-foreground mt-0.5 block text-xs">
                                            {hasBoundary
                                                ? 'Has zone boundary'
                                                : 'Point only — no boundary'}
                                        </span>
                                    </button>
                                </li>
                            );
                        })}
                </ul>
            </PopoverContent>
        </Popover>
    );
}
