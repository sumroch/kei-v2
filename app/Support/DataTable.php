<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Server side of the admin data tables (resources/js/composables/useDataTable.js).
 *
 * URL contract, same for every table:
 *   ?sort=-created_at          one column, "-" = descending
 *   &filter[store]=3           whitelisted filters
 *   &page=2&per_page=20        pagination
 *
 *   $rows = DataTable::query(Store::query(), $request,
 *       sorts: ['name', 'brand'], defaultSort: ['brand', 'name'],
 *       filters: [AllowedFilter::exact('brand')]);
 *
 *   return Inertia::render('Stores/Index', [
 *       'stores' => DataTable::paginate($rows, $request),
 *       'query'  => DataTable::state($request),
 *   ]);
 */
final class DataTable
{
    public const PER_PAGE_OPTIONS = [10, 20, 30, 40, 50];

    private const APPLIED_SORT = 'datatable.sort';

    /**
     * @param  list<string|AllowedSort>  $sorts
     * @param  list<string|AllowedSort>  $defaultSort
     * @param  list<string|AllowedFilter>  $filters
     * @param  list<string>  $tiebreaker  ("-" = desc) appended after the user's sort. Without a unique last column,
     *                                    rows with equal values (e.g. same type) can move between pages
     *                                    and show up twice / never while paginating.
     */
    public static function query(Builder|Relation|string $subject, Request $request, array $sorts, array $defaultSort, array $filters = [], array $tiebreaker = ['id']): QueryBuilder
    {
        // An unknown ?sort= would make spatie skip the default sort (rows come back unordered),
        // so it is dropped here and the default applies.
        $requested = $request->input('sort');
        $allowed = array_map(fn ($sort) => $sort instanceof AllowedSort ? $sort->getName() : $sort, $sorts);
        $valid = is_string($requested) && in_array(ltrim($requested, '-'), $allowed, true);

        // Remembered on the incoming request for state().
        $request->attributes->set(self::APPLIED_SORT, $valid ? $requested : null);

        $builderRequest = $valid ? $request : $request->duplicate(query: array_diff_key($request->query(), ['sort' => true]));

        $query = QueryBuilder::for($subject, $builderRequest)
            ->allowedFilters(...$filters)
            ->allowedSorts(...$sorts)
            ->defaultSort(...$defaultSort);

        foreach ($tiebreaker as $column) {
            $query->orderBy($query->getModel()->qualifyColumn(ltrim($column, '-')), str_starts_with($column, '-') ? 'desc' : 'asc');
        }

        return $query;
    }

    public static function paginate(QueryBuilder $query, Request $request, int $defaultPerPage = 20): LengthAwarePaginator
    {
        return $query->paginate(self::perPage($request, $defaultPerPage))->withQueryString();
    }

    /**
     * Options for a year filter, newest first, e.g. [2026, 2025, ..., 2016].
     * Takes min/max of a date column (portable: no DB-specific year()).
     */
    public static function years(?string $first, ?string $last): array
    {
        if (! $first || ! $last) {
            return [];
        }

        return range((int) substr($last, 0, 4), (int) substr($first, 0, 4));
    }

    /** filter[year] / filter[month] on a date column; out-of-range values are ignored. */
    public static function yearMonthFilters(string $column): array
    {
        return [
            AllowedFilter::callback('year', function ($query, $year) use ($column) {
                if (preg_match('/^\d{4}$/', (string) $year)) {
                    $query->whereYear($column, (int) $year);
                }
            }),
            AllowedFilter::callback('month', function ($query, $month) use ($column) {
                if (ctype_digit((string) $month) && $month >= 1 && $month <= 12) {
                    $query->whereMonth($column, (int) $month);
                }
            }),
        ];
    }

    /** Unknown values fall back to the default instead of allowing e.g. per_page=100000. */
    public static function perPage(Request $request, int $default = 20): int
    {
        $perPage = $request->integer('per_page', $default);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : $default;
    }

    /**
     * Sort + filters as applied, so the UI shows exactly what the server did.
     *
     * @return array{sort: ?string, filter: array<string, string>}
     */
    public static function state(Request $request): array
    {
        $filter = $request->input('filter', []);

        return [
            'sort' => $request->attributes->get(self::APPLIED_SORT),
            'filter' => is_array($filter) ? array_filter($filter, fn ($value) => is_scalar($value) && $value !== '') : [],
        ];
    }
}
