<?php

namespace App\DataTables;

use App\Models\Concerns\HasDataTableConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

abstract class AbstractDataTable
{
    /** @var list<int> */
    protected const PER_PAGE_OPTIONS = [15, 25, 50];

    protected const DEFAULT_PER_PAGE = 15;

    public function __construct(protected Request $request) {}

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int, from: ?int, to: ?int},
     *     links: list<array{url: ?string, label: string, active: bool}>,
     *     filters: array<string, mixed>,
     *     filterOptions: array<string, mixed>,
     *     sort: ?string,
     *     per_page: int
     * }
     */
    public static function make(Request $request): array
    {
        return (new static($request))->toArray();
    }

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * @return Builder<Model>
     */
    abstract protected function baseQuery(): Builder;

    /**
     * @return array<string, mixed>
     */
    abstract protected function mapRow(Model $model): array;

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        $filters = [];

        foreach ($this->modelFilterableColumns() as $column) {
            $filters[] = AllowedFilter::exact($column);
        }

        $filters[] = $this->searchFilter();

        if ($this->modelUsesSoftDeletes()) {
            $filters[] = AllowedFilter::trashed();
        }

        return $filters;
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return $this->modelSortableColumns();
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [];
    }

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
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
        $perPage = $this->resolvePerPage();
        $query = $this->baseQuery();

        $this->applyScoutSearchConstraint($query);

        $paginator = QueryBuilder::for($query, $this->request)
            ->allowedFilters(...$this->allowedFilters())
            ->allowedSorts(...$this->allowedSorts())
            ->defaultSort($this->defaultSort())
            ->paginate($perPage)
            ->appends($this->request->query());

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Model $model): array => $this->mapRow($model)),
        );

        return $this->formatPaginator($paginator);
    }

    protected function searchFilter(): AllowedFilter
    {
        return AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
            $term = trim((string) $value);

            if ($term === '' || $this->modelUsesScout()) {
                return;
            }

            $columns = $this->modelSearchableColumns();

            if ($columns === []) {
                return;
            }

            $query->where(function (Builder $builder) use ($columns, $term): void {
                foreach ($columns as $index => $column) {
                    if ($index === 0) {
                        $builder->where($column, 'like', '%'.$term.'%');

                        continue;
                    }

                    $builder->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        });
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyScoutSearchConstraint(Builder $query): void
    {
        if (! $this->modelUsesScout()) {
            return;
        }

        $term = trim((string) data_get($this->request->input('filter'), 'search', ''));

        if ($term === '') {
            return;
        }

        /** @var class-string<Model&Searchable> $modelClass */
        $modelClass = $this->modelClass();

        $ids = $modelClass::search($term)->take(500)->keys();

        if ($ids instanceof Collection) {
            $ids = $ids->all();
        }

        $ids = array_values(array_filter(array_map('intval', (array) $ids)));

        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($query->getModel()->getQualifiedKeyName(), $ids);
    }

    /**
     * @param  LengthAwarePaginator<int, array<string, mixed>>  $paginator
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int, from: ?int, to: ?int},
     *     links: list<array{url: ?string, label: string, active: bool}>,
     *     filters: array<string, mixed>,
     *     filterOptions: array<string, mixed>,
     *     sort: ?string,
     *     per_page: int
     * }
     */
    protected function formatPaginator(LengthAwarePaginator $paginator): array
    {
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
            'filters' => $this->normalizedFilters(),
            'filterOptions' => $this->filterOptions(),
            'sort' => $this->request->string('sort')->toString() ?: null,
            'per_page' => $paginator->perPage(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizedFilters(): array
    {
        $filters = $this->request->input('filter', []);

        return is_array($filters) ? $filters : [];
    }

    protected function resolvePerPage(): int
    {
        $perPage = (int) $this->request->input('per_page', self::DEFAULT_PER_PAGE);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true)
            ? $perPage
            : self::DEFAULT_PER_PAGE;
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        $modelClass = $this->modelClass();

        if (in_array(HasDataTableConfig::class, class_uses_recursive($modelClass), true)) {
            return $modelClass::dataTableSearchableColumns();
        }

        return [];
    }

    /**
     * @return list<string>
     */
    protected function modelSortableColumns(): array
    {
        $modelClass = $this->modelClass();

        if (in_array(HasDataTableConfig::class, class_uses_recursive($modelClass), true)) {
            return $modelClass::dataTableSortableColumns();
        }

        return [];
    }

    /**
     * @return list<string>
     */
    protected function modelFilterableColumns(): array
    {
        $modelClass = $this->modelClass();

        if (in_array(HasDataTableConfig::class, class_uses_recursive($modelClass), true)) {
            return $modelClass::dataTableFilterableColumns();
        }

        return [];
    }

    protected function modelUsesScout(): bool
    {
        return in_array(Searchable::class, class_uses_recursive($this->modelClass()), true);
    }

    protected function modelUsesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->modelClass()), true);
    }
}
