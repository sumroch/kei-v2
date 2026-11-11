<script setup>
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';
import { PER_PAGE_OPTIONS } from '../../composables/useDataTable';
import { useT } from '../../i18n';

const props = defineProps({
    table: { type: Object, required: true },
    paginator: { type: Object, required: true },
});

const t = useT();

const current = computed(() => props.paginator.current_page);
const last = computed(() => props.paginator.last_page);

// Window of up to 5 page numbers around the current page.
const pages = computed(() => {
    const start = Math.max(1, Math.min(current.value - 2, last.value - 4));
    const end = Math.min(last.value, start + 4);
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const goTo = (pageNumber) => props.table.setPageIndex(pageNumber - 1);
</script>

<template>
    <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm text-base-content/60">
            <label class="flex items-center gap-2">
                <select
                    class="select select-sm w-20"
                    :value="paginator.per_page"
                    :aria-label="t('common.perPage')"
                    @change="table.setPageSize(Number($event.target.value))"
                >
                    <option v-for="size in PER_PAGE_OPTIONS" :key="size" :value="size">{{ size }}</option>
                </select>
                <span>{{ t('common.perPage') }}</span>
            </label>
            <span v-if="paginator.total > 0" class="tabular-nums">{{ t('common.showing', { from: paginator.from, to: paginator.to, total: paginator.total.toLocaleString() }) }}</span>
        </div>

        <nav v-if="last > 1" class="flex items-center gap-1" :aria-label="t('common.pagination')">
            <button type="button" class="btn btn-ghost btn-sm btn-square" :disabled="current <= 1" :aria-label="t('common.first')" @click="goTo(1)">
                <ChevronsLeft class="size-4" aria-hidden="true" />
            </button>
            <button type="button" class="btn btn-ghost btn-sm btn-square" :disabled="current <= 1" :aria-label="t('common.prev')" @click="goTo(current - 1)">
                <ChevronLeft class="size-4" aria-hidden="true" />
            </button>
            <button
                v-for="n in pages"
                :key="n"
                type="button"
                class="btn btn-sm min-w-9 tabular-nums"
                :class="[n === current ? 'btn-primary' : 'btn-ghost', { 'hidden sm:inline-flex': Math.abs(n - current) > 1 }]"
                :aria-current="n === current ? 'page' : undefined"
                @click="n !== current && goTo(n)"
            >
                {{ n }}
            </button>
            <button type="button" class="btn btn-ghost btn-sm btn-square" :disabled="current >= last" :aria-label="t('common.next')" @click="goTo(current + 1)">
                <ChevronRight class="size-4" aria-hidden="true" />
            </button>
            <button type="button" class="btn btn-ghost btn-sm btn-square" :disabled="current >= last" :aria-label="t('common.last')" @click="goTo(last)">
                <ChevronsRight class="size-4" aria-hidden="true" />
            </button>
        </nav>
    </div>
</template>
