<?php

namespace App\DataTables;

use App\Contracts\DataTableConfigurable;
use App\Contracts\ScoutSearchable;
use App\Enums\ExportScope;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @template TModel of Model
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractDataTable
{
    /** @var list<int> */
    protected const PER_PAGE_OPTIONS = [15, 25, 50, 100];

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
     * @return Builder<TModel>
     */
    public function exportQuery(ExportScope $scope): Builder
    {
        $request = $this->requestForExportScope($scope);
        $previous = $this->request;
        $this->request = $request;

        try {
            $query = $this->baseQuery();
            $this->applyScoutSearchConstraint($query);

            $builder = QueryBuilder::for($query, $request)
                ->allowedFilters(...$this->allowedFilters())
                ->allowedSorts(...$this->allowedSorts())
                ->defaultSort($this->defaultSort());

            $builder->orderBy($query->getModel()->getQualifiedKeyName());

            if ($scope === ExportScope::Page) {
                $builder->forPage(
                    max(1, (int) $request->input('page', 1)),
                    $this->resolvePerPage(),
                );
            }

            return $builder->getEloquentBuilder();
        } finally {
            $this->request = $previous;
        }
    }

    /**
     * @return class-string<TModel>
     */
    abstract protected function modelClass(): string;

    /**
     * @return Builder<TModel>
     */
    abstract protected function baseQuery(): Builder;

    /**
     * @param  TModel  $model
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

        $rows = [];

        foreach ($paginator->getCollection() as $model) {
            $rows[] = $this->mapRow($model);
        }

        return $this->formatPaginator($paginator, $rows);
    }

    protected function searchFilter(): AllowedFilter
    {
        return AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
            $term = trim((string) $value);

            if ($term === '' || $this->modelUsesScout()) {
                return;
            }

            $this->applySqlSearchConstraint($query, $term);
        });
    }

    /**
     * @param  Builder<TModel>  $query
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

        if ($this->searchIncludesPhone() && Phone::looksLikePhoneSearch($term)) {
            $this->applySqlSearchConstraint($query, $term);

            return;
        }

        $modelClass = $this->modelClass();

        if (! is_a($modelClass, ScoutSearchable::class, true)) {
            return;
        }

        $ids = [];

        try {
            foreach ($modelClass::search($term)->take(500)->keys() as $key) {
                $id = (int) $key;

                if ($id !== 0) {
                    $ids[] = $id;
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->applySqlSearchConstraint($query, $term);

            return;
        }

        // Empty Meilisearch (or phone/email substrings the engine misses) must
        // not wipe the list — fall back to the same SQL LIKE contract.
        if ($ids === []) {
            $this->applySqlSearchConstraint($query, $term);

            return;
        }

        $query->whereIn($query->getModel()->getQualifiedKeyName(), $ids);
    }

    /**
     * @param  Builder<TModel>  $query
     */
    protected function applySqlSearchConstraint(Builder $query, string $term): void
    {
        $columns = $this->modelSearchableColumns();

        if ($columns === []) {
            return;
        }

        $query->where(function (Builder $builder) use ($columns, $term): void {
            foreach ($columns as $index => $column) {
                if ($this->isPhoneSearchColumn($column)) {
                    Phone::constrainLike($builder, $column, $term);

                    continue;
                }

                $operator = $index === 0 ? 'where' : 'orWhere';
                $builder->{$operator}($column, 'like', '%'.$term.'%');
            }
        });
    }

    /**
     * @param  LengthAwarePaginator<int, TModel>  $paginator
     * @param  list<array<string, mixed>>  $rows  Already mapped page rows.
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
    protected function formatPaginator(LengthAwarePaginator $paginator, array $rows): array
    {
        $links = [];

        foreach ($paginator->linkCollection() as $link) {
            $url = $link['url'] ?? null;

            $links[] = [
                'url' => is_string($url) ? $url : null,
                'label' => (string) ($link['label'] ?? ''),
                'active' => (bool) ($link['active'] ?? false),
            ];
        }

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => $links,
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

    protected function requestForExportScope(ExportScope $scope): Request
    {
        $filters = $this->normalizedFilters();
        $parameters = array_filter([
            'sort' => $this->request->input('sort') ?: null,
            'per_page' => $this->request->input('per_page'),
            'page' => $this->request->input('page'),
            'filter' => $filters === [] ? null : $filters,
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        $parameters = match ($scope) {
            ExportScope::All => Arr::except($parameters, ['filter', 'page']),
            ExportScope::Period => [
                ...Arr::except($parameters, ['filter', 'page']),
                'filter' => array_filter([
                    'from' => $filters['from'] ?? null,
                    'to' => $filters['to'] ?? null,
                ], fn (mixed $value): bool => $value !== null && $value !== ''),
            ],
            ExportScope::Filters => Arr::except($parameters, ['page']),
            ExportScope::Page => $parameters,
        };

        if (($parameters['filter'] ?? null) === []) {
            unset($parameters['filter']);
        }

        return Request::create($this->request->url(), 'GET', $parameters);
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        $modelClass = $this->modelClass();

        return is_a($modelClass, DataTableConfigurable::class, true)
            ? $modelClass::dataTableSearchableColumns()
            : [];
    }

    /**
     * @return list<string>
     */
    protected function modelSortableColumns(): array
    {
        $modelClass = $this->modelClass();

        return is_a($modelClass, DataTableConfigurable::class, true)
            ? $modelClass::dataTableSortableColumns()
            : [];
    }

    /**
     * @return list<string>
     */
    protected function modelFilterableColumns(): array
    {
        $modelClass = $this->modelClass();

        return is_a($modelClass, DataTableConfigurable::class, true)
            ? $modelClass::dataTableFilterableColumns()
            : [];
    }

    protected function modelUsesScout(): bool
    {
        if (config('scout.driver') !== 'meilisearch') {
            return false;
        }

        return is_a($this->modelClass(), ScoutSearchable::class, true);
    }

    protected function searchIncludesPhone(): bool
    {
        foreach ($this->modelSearchableColumns() as $column) {
            if ($this->isPhoneSearchColumn($column)) {
                return true;
            }
        }

        return false;
    }

    protected function isPhoneSearchColumn(string $column): bool
    {
        return $column === 'phone' || str_ends_with($column, '.phone');
    }

    protected function modelUsesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->modelClass()), true);
    }
}
