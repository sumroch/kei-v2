<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useConfirm } from '../../composables/useConfirm';
import { srOnlyHeader, useDataTable } from '../../composables/useDataTable';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import { GalleryHorizontalEnd, Pencil, Plus, RotateCcw, Trash2 } from 'lucide-vue-next';
import CarPicker from '../../Components/CarPicker.vue';
import DataTable from '../../Components/DataTable/DataTable.vue';
import Field from '../../Components/Field.vue';
import IconButton from '../../Components/IconButton.vue';
import Modal from '../../Components/Modal.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useT } from '../../i18n';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    slides: { type: Object, required: true }, // paginator
    query: { type: Object, required: true },
    storeTypes: { type: Array, required: true },
    cars: { type: Array, required: true }, // car picker candidates (all sites)
});

const t = useT();

// Sortable ids must match allowedSorts() in Admin\SlideController.
const columns = [
    { accessorKey: 'order_slide', header: () => t('slide.order'), meta: { headerClass: 'w-24', cellClass: 'tabular-nums' } },
    { accessorKey: 'store_type', header: () => t('slide.site'), meta: { headerClass: 'hidden sm:table-cell', cellClass: 'hidden sm:table-cell whitespace-nowrap' } },
    { accessorKey: 'title1', header: () => t('slide.title') },
    { id: 'cars', header: () => t('slide.cars'), enableSorting: false, meta: { headerClass: 'hidden md:table-cell', cellClass: 'hidden md:table-cell', wrap: true } },
    { accessorKey: 'active', header: () => t('common.status'), meta: { headerClass: 'hidden sm:table-cell', cellClass: 'hidden sm:table-cell' } },
    { id: 'actions', header: srOnlyHeader(() => t('common.actions')), enableSorting: false, meta: { headerClass: 'w-24', cellClass: 'text-right' } },
];

const { table, filters, loading, resetFilters, hasFilters } = useDataTable({
    routeName: 'slides.index',
    paginator: () => props.slides,
    query: () => props.query,
    columns,
    filters: ['store_type', 'active'],
});

const editing = ref(null);
const form = useForm({ store_type: '', title1: '', title2: '', maker: '', color: '', order_slide: '', active: true, cars: [] });
const isKeigayasui = computed(() => form.store_type === 'keigayasui');
const siteCars = computed(() => props.cars.filter((c) => c.store_type === form.store_type));
const carsError = computed(() => form.errors.cars || Object.entries(form.errors).find(([key]) => key.startsWith('cars.'))?.[1] || '');

const open = (slide = {}) => {
    editing.value = slide;
    form.clearErrors();
    form.defaults({
        store_type: slide.store_type || filters.store_type || props.storeTypes[0],
        title1: slide.title1 ?? '',
        title2: slide.title2 ?? '',
        maker: slide.maker ?? '',
        color: slide.color ?? '#3A88FE',
        order_slide: slide.order_slide ?? '',
        active: slide.active ?? true,
        cars: (slide.cars ?? []).map((c) => c.id),
    });
    form.reset();
};
const close = () => (editing.value = null);
const changeType = () => (form.cars = []); // cars belong to one site

const submit = () => {
    const options = { preserveScroll: true, onSuccess: close };
    form.transform((data) => ({ ...data, order_slide: data.order_slide === '' ? null : data.order_slide }));
    editing.value.id ? form.put(route('slides.update', editing.value.id), options) : form.post(route('slides.store'), options);
};

const confirm = useConfirm();
const destroy = async (slide) => {
    if (!(await confirm({ message: slide.title1 }))) return;
    router.delete(route('slides.destroy', slide.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="t('nav.slides')" />

    <PageHeader :title="t('nav.slides')" :description="t('desc.slides')" :icon="GalleryHorizontalEnd">
        <template #actions>
            <button type="button" class="btn btn-primary" @click="open()">
                <Plus class="size-4" aria-hidden="true" />
                {{ t('slide.add') }}
            </button>
        </template>
    </PageHeader>

    <DataTable :table="table" :paginator="slides" :loading="loading" :filtered="hasFilters" @reset="resetFilters">
        <template #toolbar>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="grid grid-cols-2 gap-3 sm:flex">
                    <select v-model="filters.store_type" class="select sm:w-56" :aria-label="t('slide.site')">
                        <option value="">{{ t('slide.site') }}: {{ t('common.all') }}</option>
                        <option v-for="type in storeTypes" :key="type" :value="type">{{ t(`site.${type}`) }}</option>
                    </select>
                    <select v-model="filters.active" class="select sm:w-44" :aria-label="t('common.status')">
                        <option value="">{{ t('common.status') }}: {{ t('common.all') }}</option>
                        <option value="1">{{ t('common.active') }}</option>
                        <option value="0">{{ t('common.inactive') }}</option>
                    </select>
                </div>
                <button v-if="hasFilters" type="button" class="btn btn-ghost btn-sm self-start sm:self-auto" @click="resetFilters">
                    <RotateCcw class="size-3.5" aria-hidden="true" />
                    {{ t('common.reset') }}
                </button>
            </div>
        </template>

        <template #cell-order_slide="{ value }">
            <span class="inline-grid size-7 place-items-center rounded-full bg-base-200 text-sm font-semibold tabular-nums">{{ value ?? '–' }}</span>
        </template>
        <template #cell-store_type="{ value }">
            <span class="badge badge-sm badge-ghost">{{ t(`site.${value}`) }}</span>
        </template>
        <template #cell-title1="{ row }">
            <div class="flex items-center gap-2">
                <span v-if="row.color" class="size-3.5 shrink-0 rounded-full ring-2 ring-base-100 ring-offset-1 ring-offset-base-300" :style="{ background: row.color }" aria-hidden="true"></span>
                <span class="font-medium">{{ row.title1 }}</span>
                <span v-if="row.maker" class="text-sm text-base-content/60">{{ row.maker }}</span>
            </div>
            <div v-if="row.title2" class="text-xs whitespace-normal text-base-content/60">{{ row.title2 }}</div>
            <div class="font-mono text-[11px] text-base-content/40">{{ row.owl }}</div>
            <!-- status column is hidden on phones -->
            <div class="mt-1 sm:hidden"><StatusBadge :active="row.active" /></div>
        </template>
        <template #cell-cars="{ row }">
            <div class="flex max-w-md flex-wrap items-center gap-1">
                <span class="badge badge-sm badge-primary badge-soft">{{ t('slide.carsCount', { count: row.cars.length }) }}</span>
                <span v-for="car in row.cars.slice(0, 4)" :key="car.id" class="badge badge-sm badge-ghost">{{ car.brand }}</span>
                <span v-if="row.cars.length > 4" class="text-xs text-base-content/50">+{{ row.cars.length - 4 }}</span>
            </div>
        </template>
        <template #cell-active="{ value }"><StatusBadge :active="value" /></template>
        <template #cell-actions="{ row }">
            <div class="flex justify-end gap-0.5">
                <IconButton :icon="Pencil" :label="t('common.edit')" @click="open(row)" />
                <IconButton :icon="Trash2" :label="t('common.delete')" tone="danger" @click="destroy(row)" />
            </div>
        </template>
    </DataTable>

    <Modal :open="!!editing" :title="editing?.id ? t('slide.edit') : t('slide.add')" :description="editing?.id ? editing.title1 : ''" wide @close="close">
        <form class="space-y-5" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-x-4 sm:grid-cols-2">
                <Field :label="t('slide.site')" :error="form.errors.store_type" required>
                    <select v-model="form.store_type" class="select w-full" :disabled="!!editing?.id" required @change="changeType">
                        <option v-for="type in storeTypes" :key="type" :value="type">{{ t(`site.${type}`) }}</option>
                    </select>
                </Field>
                <Field :label="t('slide.order')" :error="form.errors.order_slide">
                    <input v-model="form.order_slide" type="number" min="0" max="127" class="input w-full" />
                </Field>

                <template v-if="isKeigayasui">
                    <Field :label="t('slide.title1Keigayasui')" :error="form.errors.title1" :hint="editing?.id ? t('slide.titleLocked') : ''" required>
                        <input v-model="form.title1" type="text" class="input w-full uppercase" maxlength="64" :disabled="!!editing?.id" required />
                    </Field>
                    <Field :label="t('slide.maker')" :error="form.errors.maker" required>
                        <input v-model="form.maker" type="text" class="input w-full" maxlength="32" required />
                    </Field>
                </template>
                <template v-else>
                    <Field :label="t('slide.title1Flat')" :error="form.errors.title1" required>
                        <input v-model="form.title1" type="text" class="input w-full" maxlength="64" placeholder="11,000" required />
                    </Field>
                    <Field :label="t('slide.title2')" :error="form.errors.title2">
                        <input v-model="form.title2" type="text" class="input w-full" maxlength="64" />
                    </Field>
                    <Field :label="t('slide.color')" :error="form.errors.color">
                        <div class="flex gap-2">
                            <input v-model="form.color" type="color" class="h-10 w-14 cursor-pointer rounded border border-base-300" :aria-label="t('slide.color')" />
                            <input v-model="form.color" type="text" class="input w-full" maxlength="32" />
                        </div>
                    </Field>
                </template>
            </div>

            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-box border border-base-300 p-4">
                <span>
                    <span class="block font-medium">{{ t('slide.publish') }}</span>
                    <span class="text-sm text-base-content/60">{{ t('slide.publishHint') }}</span>
                </span>
                <input v-model="form.active" type="checkbox" class="toggle toggle-success" />
            </label>

            <CarPicker v-model="form.cars" :cars="siteCars" :error="carsError" />

            <div class="modal-action">
                <button type="button" class="btn btn-ghost" @click="close">{{ t('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary min-w-28" :disabled="form.processing">
                    <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                    {{ t('common.save') }}
                </button>
            </div>
        </form>
    </Modal>
</template>
