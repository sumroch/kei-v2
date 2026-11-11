<script setup>
import { FlexRender } from '@tanstack/vue-table';
import { ArrowDown, ArrowUp, ChevronsUpDown, Inbox, RotateCcw } from 'lucide-vue-next';
import DataTablePagination from './DataTablePagination.vue';
import { useT } from '../../i18n';

/**
 * Renders a TanStack table created by useDataTable().
 *
 * Cells: column value by default, or a slot named `cell-<columnId>` ({ row, value, index }).
 * Column meta: { headerClass, cellClass, wrap } (cells don't wrap unless meta.wrap).
 * `filtered` + @reset: the empty state offers "reset filters" when filters are active.
 */
const props = defineProps({
    table: { type: Object, required: true },
    paginator: { type: Object, required: true },
    loading: { type: Boolean, default: false },
    rowNumbers: { type: Boolean, default: false },
    filtered: { type: Boolean, default: false },
});
defineEmits(['reset']);

const t = useT();

const ariaSort = (column) => ({ asc: 'ascending', desc: 'descending' })[column.getIsSorted()] ?? 'none';
const columnCount = () => props.table.getVisibleLeafColumns().length + (props.rowNumbers ? 1 : 0);
</script>

<template>
    <div class="card overflow-hidden border border-base-300 bg-base-100 shadow-sm">
        <div v-if="$slots.toolbar" class="border-b border-base-300 p-4">
            <slot name="toolbar" />
        </div>

        <div class="relative overflow-x-auto" :aria-busy="loading">
            <!-- thin progress bar instead of covering the rows -->
            <div v-if="loading" class="absolute inset-x-0 top-0 z-10 h-0.5 overflow-hidden bg-primary/15">
                <div class="h-full w-1/3 animate-[table-progress_1s_ease-in-out_infinite] bg-primary"></div>
            </div>

            <table class="table max-sm:table-sm" :class="{ 'opacity-60 transition-opacity': loading }">
                <thead>
                    <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id" class="border-base-300 bg-base-200/60 text-xs text-base-content/60">
                        <!-- row numbers give way to the row actions on phones -->
                        <th v-if="rowNumbers" class="hidden w-14 font-semibold sm:table-cell">{{ t('common.rowNumber') }}</th>
                        <th
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            class="font-semibold whitespace-nowrap"
                            :class="header.column.columnDef.meta?.headerClass"
                            :aria-sort="header.column.getCanSort() ? ariaSort(header.column) : undefined"
                        >
                            <button
                                v-if="header.column.getCanSort()"
                                type="button"
                                class="group -mx-1 inline-flex items-center gap-1 rounded px-1 py-0.5 hover:bg-base-300/60 hover:text-base-content"
                                :class="{ 'text-primary': header.column.getIsSorted() }"
                                @click="header.column.getToggleSortingHandler()($event)"
                            >
                                <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                                <ArrowUp v-if="header.column.getIsSorted() === 'asc'" class="size-3.5" aria-hidden="true" />
                                <ArrowDown v-else-if="header.column.getIsSorted() === 'desc'" class="size-3.5" aria-hidden="true" />
                                <ChevronsUpDown v-else class="size-3.5 opacity-40 group-hover:opacity-80" aria-hidden="true" />
                            </button>
                            <FlexRender v-else :render="header.column.columnDef.header" :props="header.getContext()" />
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="!table.getRowModel().rows.length">
                        <td :colspan="columnCount()">
                            <div class="flex flex-col items-center gap-2 py-14 text-center">
                                <div class="grid size-12 place-items-center rounded-full bg-base-200 text-base-content/40">
                                    <Inbox class="size-6" aria-hidden="true" />
                                </div>
                                <p class="font-medium">{{ t('common.empty') }}</p>
                                <p v-if="filtered" class="text-sm text-base-content/60">{{ t('common.emptyFiltered') }}</p>
                                <button v-if="filtered" type="button" class="btn btn-sm btn-outline mt-1" @click="$emit('reset')">
                                    <RotateCcw class="size-3.5" aria-hidden="true" />
                                    {{ t('common.resetFilters') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-for="(row, index) in table.getRowModel().rows" :key="row.id" class="border-base-200 transition-colors hover:bg-primary/[0.03]">
                        <td v-if="rowNumbers" class="hidden text-sm text-base-content/50 tabular-nums sm:table-cell">{{ paginator.from + index }}</td>
                        <td
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            :class="[cell.column.columnDef.meta?.wrap ? '' : 'whitespace-nowrap', cell.column.columnDef.meta?.cellClass]"
                        >
                            <slot :name="`cell-${cell.column.id}`" :row="row.original" :value="cell.getValue()" :index="index">
                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-base-300 px-4 py-3">
            <DataTablePagination :table="table" :paginator="paginator" />
        </div>
    </div>
</template>
