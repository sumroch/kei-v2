<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { CalendarCheck, Download, Eye, Mail, MapPin, Phone, RotateCcw, Trash2 } from 'lucide-vue-next';
import { useConfirm } from '../../composables/useConfirm';
import { srOnlyHeader, useDataTable } from '../../composables/useDataTable';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import DataTable from '../../Components/DataTable/DataTable.vue';
import IconButton from '../../Components/IconButton.vue';
import Modal from '../../Components/Modal.vue';
import PageHeader from '../../Components/PageHeader.vue';
import SearchInput from '../../Components/SearchInput.vue';
import TypeBadge from '../../Components/TypeBadge.vue';
import { useT } from '../../i18n';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    reservations: { type: Object, required: true }, // paginator (list columns only)
    query: { type: Object, required: true },
    detail: { type: Object, default: null }, // optional prop, loaded by openDetail()
    stores: { type: Array, required: true },
    years: { type: Array, required: true },
    types: { type: Array, required: true },
});

const t = useT();

// Sortable ids must match allowedSorts() in Admin\ReservationController.
const columns = [
    // On phones only name (+ type/date underneath) and the action stay visible.
    { accessorKey: 'created_at', header: () => t('reservation.receivedAt'), sortDescFirst: true, meta: { headerClass: 'hidden sm:table-cell', cellClass: 'hidden sm:table-cell text-sm tabular-nums text-base-content/70' } },
    { accessorKey: 'type_reservation', header: () => t('reservation.type'), meta: { headerClass: 'hidden sm:table-cell', cellClass: 'hidden sm:table-cell' } },
    { accessorKey: 'name', header: () => t('reservation.name') },
    { accessorKey: 'phone_number', header: () => t('reservation.phone'), enableSorting: false, meta: { headerClass: 'hidden md:table-cell', cellClass: 'hidden md:table-cell text-sm tabular-nums' } },
    { accessorKey: 'email', header: () => t('reservation.email'), meta: { headerClass: 'hidden xl:table-cell', cellClass: 'hidden xl:table-cell text-sm text-base-content/80' } },
    { accessorKey: 'store', header: () => t('common.store'), meta: { headerClass: 'hidden sm:table-cell', cellClass: 'hidden sm:table-cell text-sm' } },
    { id: 'actions', header: srOnlyHeader(() => t('common.actions')), enableSorting: false, meta: { headerClass: 'w-16', cellClass: 'text-right' } },
];

const { table, filters, loading, resetFilters, hasFilters } = useDataTable({
    routeName: 'reservations.index',
    paginator: () => props.reservations,
    query: () => props.query,
    columns,
    filters: ['search', 'type', 'store', 'year', 'month'],
});

// Detail modal: the row is shown at once, the full form data is fetched as the `detail` prop.
const selected = ref(null);
const detailLoading = ref(false);
const detail = computed(() => (selected.value && props.detail?.id === selected.value.id ? props.detail : null));

const openDetail = (row) => {
    selected.value = row;
    if (props.detail?.id === row.id) return;
    detailLoading.value = true;
    router.reload({
        only: ['detail'],
        data: { detail: row.id },
        preserveUrl: true, // keep ?detail= out of the address bar / table URL
        onFinish: () => (detailLoading.value = false),
    });
};

// Request details: lists render as chips, empty entries are dropped.
const requestRows = computed(() => {
    const d = detail.value;
    if (!d) return [];
    return [
        ['reservation.preferred', d.preferred],
        ['reservation.car', d.car],
        ['reservation.otherCar', d.other_car],
        ['reservation.canContacted', d.can_contacted],
        ['reservation.canContactedDay', d.can_contacted_day],
        ['reservation.typeQuestion', d.type_question],
        ['reservation.typeCustomer', d.type_customer],
        ['reservation.usage', d.usage],
        ['reservation.buyer', d.buyer],
    ].filter(([, value]) => (Array.isArray(value) ? value.length : value));
});
const messageRows = computed(() => {
    const d = detail.value;
    if (!d) return [];
    return [
        ['reservation.question', d.question],
        ['reservation.otherQuestion', d.other_question],
        ['reservation.message', d.message],
    ].filter(([, value]) => value);
});

const deleting = ref(false);
const confirm = useConfirm();
const destroy = async (row) => {
    if (!(await confirm({ message: `${row.name} (${row.created_at})` }))) return;
    router.delete(route('reservations.destroy', row.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onSuccess: () => (selected.value = null),
        onFinish: () => (deleting.value = false),
    });
};

// CSV export (date range): a plain download link, not an Inertia visit.
const today = new Date().toISOString().slice(0, 10);
const exportForm = reactive({ date_start: today.slice(0, 8) + '01', date_end: today, type: '', store: '' });
const exportValid = computed(() => exportForm.date_start && exportForm.date_end && exportForm.date_start <= exportForm.date_end);
const exportUrl = computed(() => route('reservations.export', Object.fromEntries(Object.entries(exportForm).filter(([, v]) => v))));
</script>

<template>
    <Head :title="t('nav.reservations')" />

    <PageHeader :title="t('nav.reservations')" :description="t('desc.reservations')" :icon="CalendarCheck" />

    <!-- CSV export panel (inline, like the old dashboard) -->
    <section class="mb-4 rounded-box border border-base-300 bg-base-100 p-4 shadow-sm" :aria-labelledby="'export-title'">
        <div class="mb-3 flex items-center gap-2">
            <Download class="size-4 text-success" aria-hidden="true" />
            <h2 id="export-title" class="text-sm font-semibold">{{ t('reservation.exportTitle') }}</h2>
        </div>
        <div class="grid grid-cols-2 items-end gap-3 md:grid-cols-[repeat(4,minmax(0,1fr))_auto]">
            <label class="flex flex-col gap-1">
                <span class="text-xs font-medium text-base-content/60">{{ t('reservation.dateStart') }}</span>
                <input v-model="exportForm.date_start" type="date" class="input input-sm w-full" required />
            </label>
            <label class="flex flex-col gap-1">
                <span class="text-xs font-medium text-base-content/60">{{ t('reservation.dateEnd') }}</span>
                <input v-model="exportForm.date_end" type="date" class="input input-sm w-full" :min="exportForm.date_start" required />
            </label>
            <label class="flex flex-col gap-1">
                <span class="text-xs font-medium text-base-content/60">{{ t('reservation.type') }}</span>
                <select v-model="exportForm.type" class="select select-sm w-full">
                    <option value="">{{ t('common.all') }}</option>
                    <option v-for="type in types" :key="type" :value="type">{{ t(`type.${type}`) }}</option>
                </select>
            </label>
            <label class="flex flex-col gap-1">
                <span class="text-xs font-medium text-base-content/60">{{ t('common.store') }}</span>
                <select v-model="exportForm.store" class="select select-sm w-full">
                    <option value="">{{ t('common.all') }}</option>
                    <option v-for="s in stores" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                </select>
            </label>
            <a
                :href="exportValid ? exportUrl : undefined"
                class="btn btn-success btn-sm col-span-2 md:col-span-1"
                :class="{ 'btn-disabled': !exportValid }"
                :aria-disabled="!exportValid"
            >
                <Download class="size-4" aria-hidden="true" />
                {{ t('reservation.export') }}
            </a>
        </div>
    </section>

    <DataTable :table="table" :paginator="reservations" :loading="loading" :filtered="hasFilters" @reset="resetFilters">
        <template #toolbar>
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                <div class="xl:max-w-xs xl:flex-1">
                    <SearchInput v-model="filters.search" :placeholder="t('reservation.searchPlaceholder')" />
                </div>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:flex">
                    <select v-model="filters.type" class="select xl:w-40" :aria-label="t('reservation.type')">
                        <option value="">{{ t('reservation.type') }}: {{ t('common.all') }}</option>
                        <option v-for="type in types" :key="type" :value="type">{{ t(`type.${type}`) }}</option>
                    </select>
                    <select v-model="filters.store" class="select xl:w-56" :aria-label="t('common.store')">
                        <option value="">{{ t('common.store') }}: {{ t('common.all') }}</option>
                        <option v-for="s in stores" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                    </select>
                    <select v-model="filters.year" class="select xl:w-32" :aria-label="t('common.year')">
                        <option value="">{{ t('common.year') }}: {{ t('common.all') }}</option>
                        <option v-for="y in years" :key="y" :value="String(y)">{{ y }}</option>
                    </select>
                    <select v-model="filters.month" class="select xl:w-28" :aria-label="t('common.month')">
                        <option value="">{{ t('common.month') }}: {{ t('common.all') }}</option>
                        <option v-for="m in 12" :key="m" :value="String(m)">{{ m }}</option>
                    </select>
                </div>
                <button v-if="hasFilters" type="button" class="btn btn-ghost btn-sm self-start xl:self-auto" @click="resetFilters">
                    <RotateCcw class="size-3.5" aria-hidden="true" />
                    {{ t('common.reset') }}
                </button>
            </div>
        </template>

        <template #cell-type_reservation="{ value }"><TypeBadge :type="value" /></template>
        <template #cell-name="{ row }">
            <button type="button" class="text-left hover:text-primary" @click="openDetail(row)">
                <span class="block font-medium whitespace-normal">{{ row.name }}</span>
                <span v-if="row.how_to_read" class="block text-xs text-base-content/50">{{ row.how_to_read }}</span>
            </button>
            <span class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 sm:hidden">
                <TypeBadge :type="row.type_reservation" />
                <span class="text-xs text-base-content/50 tabular-nums">{{ row.created_at }}</span>
            </span>
        </template>
        <template #cell-actions="{ row }">
            <IconButton :icon="Eye" :label="t('common.detail')" tone="primary" @click="openDetail(row)" />
        </template>
    </DataTable>

    <Modal
        :open="!!selected"
        :title="selected?.name ?? ''"
        :description="selected ? `${selected.created_at} · ${selected.store ?? ''}` : ''"
        wide
        @close="selected = null"
    >
        <div v-if="!detail && detailLoading" class="space-y-3" role="status">
            <div class="skeleton h-5 w-1/3"></div>
            <div class="skeleton h-4 w-2/3"></div>
            <div class="skeleton h-4 w-1/2"></div>
            <div class="skeleton h-20 w-full"></div>
            <span class="sr-only">{{ t('common.loading') }}</span>
        </div>
        <!-- deleted in the meantime -->
        <p v-else-if="!detail" class="py-10 text-center text-base-content/60">{{ t('common.empty') }}</p>

        <div v-else class="space-y-6">
            <div class="flex flex-wrap items-center gap-2">
                <TypeBadge :type="detail.type_reservation" />
                <span v-if="detail.how_to_read" class="text-sm text-base-content/60"><span class="sr-only">{{ t('reservation.howToRead') }}: </span>{{ detail.how_to_read }}</span>
            </div>

            <!-- Customer -->
            <section>
                <h4 class="mb-2 text-xs font-semibold tracking-wide text-base-content/50 uppercase">{{ t('reservation.sectionCustomer') }}</h4>
                <div class="grid gap-2 sm:grid-cols-2">
                    <a v-if="detail.email" :href="`mailto:${detail.email}`" class="flex items-center gap-2 rounded-field border border-base-300 px-3 py-2 text-sm hover:border-primary hover:text-primary">
                        <Mail class="size-4 shrink-0 text-base-content/40" aria-hidden="true" /><span class="truncate">{{ detail.email }}</span>
                    </a>
                    <a v-if="detail.phone_number" :href="`tel:${detail.phone_number}`" class="flex items-center gap-2 rounded-field border border-base-300 px-3 py-2 text-sm tabular-nums hover:border-primary hover:text-primary">
                        <Phone class="size-4 shrink-0 text-base-content/40" aria-hidden="true" />{{ detail.phone_number }}
                    </a>
                    <p v-if="detail.address" class="flex items-center gap-2 rounded-field bg-base-200/60 px-3 py-2 text-sm sm:col-span-2">
                        <MapPin class="size-4 shrink-0 text-base-content/40" aria-hidden="true" /><span class="sr-only">{{ t('reservation.address') }}: </span>{{ detail.address }}
                    </p>
                </div>
            </section>

            <!-- Request -->
            <section v-if="requestRows.length">
                <h4 class="mb-2 text-xs font-semibold tracking-wide text-base-content/50 uppercase">{{ t('reservation.sectionRequest') }}</h4>
                <dl class="divide-y divide-base-200 rounded-box border border-base-300">
                    <div v-for="[label, value] in requestRows" :key="label" class="grid gap-1 px-4 py-2.5 sm:grid-cols-[11rem_1fr]">
                        <dt class="text-sm text-base-content/60">{{ t(label) }}</dt>
                        <dd class="text-sm">
                            <span v-if="Array.isArray(value)" class="flex flex-wrap gap-1.5">
                                <span v-for="item in value" :key="item" class="badge badge-ghost">{{ item }}</span>
                            </span>
                            <span v-else>{{ value }}</span>
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- Free text -->
            <section v-for="[label, value] in messageRows" :key="label">
                <h4 class="mb-2 text-xs font-semibold tracking-wide text-base-content/50 uppercase">{{ t(label) }}</h4>
                <p class="rounded-box bg-base-200/60 px-4 py-3 text-sm leading-relaxed whitespace-pre-line break-words">{{ value }}</p>
            </section>
        </div>

        <div class="modal-action justify-between">
            <button type="button" class="btn btn-ghost text-error hover:bg-error/10" :disabled="deleting || !selected" @click="destroy(selected)">
                <span v-if="deleting" class="loading loading-spinner loading-sm"></span>
                <Trash2 v-else class="size-4" aria-hidden="true" />
                {{ t('common.delete') }}
            </button>
            <button type="button" class="btn" @click="selected = null">{{ t('common.close') }}</button>
        </div>
    </Modal>
</template>
