<?php

namespace App\Support\Sorts;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * ?sort=store for tables with a store_id column: orders by the store's name.
 * Uses a scalar subquery instead of a join, so the selected columns / eager loads stay untouched.
 *
 *   AllowedSort::custom('store', new SortByStoreName)
 */
class SortByStoreName implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $table = $query->getModel()->getTable();

        $query->orderBy(
            Store::withTrashed()->select('name')->whereColumn('stores.id', "{$table}.store_id")->limit(1),
            $descending ? 'desc' : 'asc',
        );
    }
}
