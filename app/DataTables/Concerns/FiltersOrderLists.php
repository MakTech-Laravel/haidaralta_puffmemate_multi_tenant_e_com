<?php

namespace App\DataTables\Concerns;

use App\Support\DateTimeFormat;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Shared search and placed-on filters for platform orders and branch fulfillments.
 */
trait FiltersOrderLists
{
    private bool $placedDateApplied = false;

    /**
     * @return list<AllowedFilter>
     */
    protected function placedDateFilters(bool $throughOrder): array
    {
        $apply = function (Builder $query) use ($throughOrder): void {
            if ($this->placedDateApplied) {
                return;
            }

            $this->placedDateApplied = true;
            $this->applyPlacedDate($query, $throughOrder);
        };

        return [
            AllowedFilter::callback('from', $apply),
            AllowedFilter::callback('to', $apply),
        ];
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyPlacedDate(Builder $query, bool $throughOrder): void
    {
        [$from, $to] = $this->placedDateBounds();

        if ($from === null && $to === null) {
            return;
        }

        $constrain = function (Builder $orders) use ($from, $to): void {
            if ($from !== null && $to === null) {
                $orders->whereDate('created_at', $from);

                return;
            }

            if ($from === null && $to !== null) {
                $orders->whereDate('created_at', $to);

                return;
            }

            $orders->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to);
        };

        if ($throughOrder) {
            $query->whereHas('order', $constrain);

            return;
        }

        $constrain($query);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function placedDateBounds(): array
    {
        /** @var Request $request */
        $request = $this->request;
        $filters = $request->input('filter', []);
        $from = $this->placedDate($filters['from'] ?? null);
        $to = $this->placedDate($filters['to'] ?? null);
        $today = DateTimeFormat::now()->toDateString();

        if ($from !== null && $from > $today) {
            $from = null;
        }

        if ($to !== null && $to > $today) {
            $to = null;
        }

        if ($from !== null && $to !== null && $from > $to) {
            return [null, null];
        }

        return [$from, $to];
    }

    protected function placedDate(mixed $value): ?string
    {
        $date = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return null;
        }

        return $date;
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function searchOrderRelations(Builder $query, string $term, bool $includeBranch): void
    {
        $like = '%'.$term.'%';

        $query->where(function (Builder $orders) use ($term, $like, $includeBranch): void {
            $orders->where('order_number', 'like', $like)
                ->orWhereHas('customer', function (Builder $customer) use ($term, $like): void {
                    $customer->where(function (Builder $match) use ($term, $like): void {
                        $match->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);

                        if (Phone::looksLikePhoneSearch($term)) {
                            Phone::constrainLike($match, 'phone', $term);
                        }
                    });
                });

            if ($includeBranch) {
                $orders->orWhereHas('fulfillment.branch', function (Builder $branch) use ($like): void {
                    $branch->where('name', 'like', $like);
                });
            }
        });
    }

    protected function orderSearchFilter(bool $includeBranch, bool $fulfillment = false): AllowedFilter
    {
        return AllowedFilter::callback('search', function (Builder $query, mixed $value) use ($includeBranch, $fulfillment): void {
            $term = trim((string) $value);

            if ($term === '') {
                return;
            }

            $like = '%'.$term.'%';

            if (! $fulfillment) {
                $this->searchOrderRelations($query, $term, $includeBranch);

                return;
            }

            $query->where(function (Builder $rows) use ($term, $like): void {
                $rows->where('fulfillment_number', 'like', $like)
                    ->orWhereHas('order', function (Builder $orders) use ($term): void {
                        $this->searchOrderRelations($orders, $term, false);
                    });
            });
        });
    }
}
