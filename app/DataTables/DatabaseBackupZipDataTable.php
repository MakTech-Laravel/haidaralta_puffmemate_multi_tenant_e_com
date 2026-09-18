<?php

namespace App\DataTables;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Filesystem zip inventory as a DataTable payload (not Eloquent).
 *
 * Query keys are namespaced (archives_*) so they do not collide with the
 * operator history table on the same page (filter[], sort, per_page, page).
 */
class DatabaseBackupZipDataTable
{
    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [15, 25, 50, 100];

    private const DEFAULT_PER_PAGE = 15;

    /**
     * @param  list<array{path: string, filename: string, size_bytes: int, date: string|null}>  $zips
     * @return array{
     *     data: list<array{path: string, filename: string, size_bytes: int, date: string|null}>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int, from: ?int, to: ?int},
     *     links: list<array{url: ?string, label: string, active: bool}>,
     *     filters: array<string, mixed>,
     *     filterOptions: array<string, mixed>,
     *     sort: ?string,
     *     per_page: int
     * }
     */
    public static function make(Request $request, array $zips): array
    {
        return (new self($request, $zips))->toArray();
    }

    /**
     * @param  list<array{path: string, filename: string, size_bytes: int, date: string|null}>  $zips
     */
    public function __construct(
        private Request $request,
        private array $zips,
    ) {}

    /**
     * @return array{
     *     data: list<array{path: string, filename: string, size_bytes: int, date: string|null}>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int, from: ?int, to: ?int},
     *     links: list<array{url: ?string, label: string, active: bool}>,
     *     filters: array<string, mixed>,
     *     filterOptions: array<string, mixed>,
     *     sort: ?string,
     *     per_page: int
     * }
     */
    public function toArray(): array
    {
        $search = trim((string) $this->request->input('archives_search', ''));
        $sort = (string) $this->request->input('archives_sort', '-date');
        $perPage = (int) $this->request->input('archives_per_page', self::DEFAULT_PER_PAGE);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $page = max(1, (int) $this->request->input('archives_page', 1));

        /** @var Collection<int, array{path: string, filename: string, size_bytes: int, date: string|null}> $collection */
        $collection = collect($this->zips);

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $collection = $collection
                ->filter(fn (array $zip): bool => str_contains(mb_strtolower($zip['filename']), $needle))
                ->values();
        }

        $collection = $this->applySort($collection, $sort);

        $paginator = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values()->all(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => $this->request->url(),
                'pageName' => 'archives_page',
            ],
        );

        $paginator->appends(
            collect($this->request->query())
                ->except('archives_page')
                ->all(),
        );

        return [
            'data' => array_values($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => collect($paginator->toArray()['links'] ?? [])->map(fn (array $link): array => [
                'url' => $link['url'] ?? null,
                'label' => (string) ($link['label'] ?? ''),
                'active' => (bool) ($link['active'] ?? false),
            ])->values()->all(),
            'filters' => [
                'search' => $search !== '' ? $search : null,
            ],
            'filterOptions' => [],
            'sort' => $sort !== '' ? $sort : null,
            'per_page' => $paginator->perPage(),
        ];
    }

    /**
     * @param  Collection<int, array{path: string, filename: string, size_bytes: int, date: string|null}>  $collection
     * @return Collection<int, array{path: string, filename: string, size_bytes: int, date: string|null}>
     */
    private function applySort(Collection $collection, string $sort): Collection
    {
        $descending = str_starts_with($sort, '-');
        $column = ltrim($sort, '-');

        if (! in_array($column, ['date', 'filename', 'size_bytes'], true)) {
            $column = 'date';
            $descending = true;
        }

        return $collection
            ->sortBy(
                fn (array $zip): mixed => $zip[$column] ?? null,
                SORT_REGULAR,
                $descending,
            )
            ->values();
    }
}
