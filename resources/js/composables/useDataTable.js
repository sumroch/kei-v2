import { computed, h, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import { route } from 'ziggy-js';

export const PER_PAGE_OPTIONS = [10, 20, 30, 40, 50];

/** Column header that is invisible but still read by screen readers (e.g. the row-actions column). */
export const srOnlyHeader = (label) => () => h('span', { class: 'sr-only' }, label());

/**
 * Server-side data table: TanStack Table holds the UI state, Laravel does the work.
 *
 *   const { table, filters, loading, resetFilters } = useDataTable({
 *       routeName: 'stores.index',
 *       paginator: () => props.stores,   // Laravel LengthAwarePaginator (Inertia prop)
 *       query: () => props.query,         // { sort, filter } echoed by App\Support\DataTable::state()
 *       columns,                          // TanStack column defs
 *       filters: ['brand', 'search'],     // keys sent as filter[key]
 *   });
 *
 * Every sort / page / per-page / filter change becomes one Inertia visit with
 * ?sort=-name&filter[brand]=x&page=2&per_page=20. Only `only` props are reloaded,
 * so dropdown data (stores, brands...) is not sent again.
 */
export function useDataTable({ routeName, routeParams = {}, paginator, query, columns, filters: filterKeys = [], only, debounce = 300 }) {
    const page = computed(paginator);
    const initial = query?.() ?? {};

    const sorting = ref(parseSort(initial.sort));
    const filters = reactive(Object.fromEntries(filterKeys.map((key) => [key, initial.filter?.[key] ?? ''])));
    const loading = ref(false);

    const visit = ({ pageIndex = 0, pageSize = page.value.per_page } = {}) => {
        const filter = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value !== null));
        const params = {
            sort: serializeSort(sorting.value),
            filter: Object.keys(filter).length ? filter : undefined,
            page: pageIndex > 0 ? pageIndex + 1 : undefined,
            per_page: pageSize,
        };

        router.get(route(routeName, routeParams), params, {
            only: only ?? [routeName.split('.')[0], 'query'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        });
    };

    const table = useVueTable({
        get data() {
            return page.value.data;
        },
        columns,
        getCoreRowModel: getCoreRowModel(),
        manualSorting: true,
        manualPagination: true,
        manualFiltering: true,
        enableMultiSort: false,
        get rowCount() {
            return page.value.total;
        },
        state: {
            get sorting() {
                return sorting.value;
            },
            get pagination() {
                return { pageIndex: page.value.current_page - 1, pageSize: page.value.per_page };
            },
        },
        onSortingChange: (updater) => {
            sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
            visit(); // new sort -> back to page 1
        },
        onPaginationChange: (updater) => {
            const current = table.getState().pagination;
            const next = typeof updater === 'function' ? updater(current) : updater;
            if (next.pageSize !== current.pageSize) return visit({ pageSize: next.pageSize }); // new size -> page 1
            // TanStack doesn't clamp manual pagination: keep the index inside 0..last.
            const lastIndex = Math.max(0, Math.ceil(page.value.total / next.pageSize) - 1);
            visit({ pageIndex: Math.min(Math.max(0, next.pageIndex), lastIndex), pageSize: next.pageSize });
        },
        getRowId: (row, index) => String(row.id ?? index),
    });

    let timer;
    watch(filters, () => {
        clearTimeout(timer);
        timer = setTimeout(() => visit(), debounce);
    });

    const resetFilters = () => {
        filterKeys.forEach((key) => (filters[key] = ''));
    };

    // Drives the "reset" button and the filtered empty state.
    const hasFilters = computed(() => filterKeys.some((key) => filters[key] !== '' && filters[key] !== null));

    return { table, filters, loading, resetFilters, hasFilters };
}

/** "-created_at" <-> [{ id: 'created_at', desc: true }] */
function parseSort(sort) {
    if (!sort || typeof sort !== 'string') return [];
    const first = sort.split(',')[0];
    return [{ id: first.replace(/^-/, ''), desc: first.startsWith('-') }];
}

function serializeSort(sorting) {
    const [first] = sorting;
    return first ? `${first.desc ? '-' : ''}${first.id}` : undefined;
}
