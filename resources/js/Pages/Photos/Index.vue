<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { CalendarDays, Images, Inbox, LayoutGrid, List, Pencil, Plus, RotateCcw, Trash2 } from 'lucide-vue-next';
import { useConfirm } from '../../composables/useConfirm';
import { srOnlyHeader, useDataTable } from '../../composables/useDataTable';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import DataTable from '../../Components/DataTable/DataTable.vue';
import DataTablePagination from '../../Components/DataTable/DataTablePagination.vue';
import Field from '../../Components/Field.vue';
import IconButton from '../../Components/IconButton.vue';
import ImageUpload from '../../Components/ImageUpload.vue';
import Modal from '../../Components/Modal.vue';
import PageHeader from '../../Components/PageHeader.vue';
import ZoomImage from '../../Components/ZoomImage.vue';
import { useT } from '../../i18n';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    photos: { type: Object, required: true }, // paginator
    query: { type: Object, required: true },
    stores: { type: Array, required: true },
    years: { type: Array, required: true },
});

const t = useT();

// Sortable ids must match allowedSorts() in Admin\PhotoController.
const columns = [
    { id: 'image', header: () => t('common.image'), enableSorting: false, meta: { headerClass: 'w-28' } },
    { accessorKey: 'date', header: () => t('photo.date'), sortDescFirst: true, meta: { headerClass: 'hidden sm:table-cell', cellClass: 'hidden sm:table-cell tabular-nums' } },
    { accessorKey: 'initial', header: () => t('photo.initial') },
    { accessorKey: 'maker', header: () => t('photo.maker'), meta: { headerClass: 'hidden sm:table-cell', cellClass: 'hidden sm:table-cell' } },
    { accessorKey: 'store', header: () => t('common.store'), meta: { headerClass: 'hidden md:table-cell', cellClass: 'hidden md:table-cell text-sm text-base-content/80' } },
    { id: 'actions', header: srOnlyHeader(() => t('common.actions')), enableSorting: false, meta: { headerClass: 'w-24', cellClass: 'text-right' } },
];

const { table, filters, loading, resetFilters, hasFilters } = useDataTable({
    routeName: 'photos.index',
    paginator: () => props.photos,
    query: () => props.query,
    columns,
    filters: ['store', 'year', 'month'],
});

// Table (old dashboard) or photo grid; remembered per browser.
const VIEW_KEY = 'kei.photos.view';
const readView = () => {
    try {
        return localStorage.getItem(VIEW_KEY) === 'grid' ? 'grid' : 'table';
    } catch {
        return 'table';
    }
};
const view = ref(readView());
watch(view, (value) => {
    try {
        localStorage.setItem(VIEW_KEY, value);
    } catch {
        // storage unavailable: keep it for this visit only
    }
});

// Grid has no clickable headers: sort through a select bound to the same TanStack state.
const sortOptions = ['-date', 'date', 'initial', '-initial', 'store', 'maker'];
const currentSort = computed(() => {
    const [first] = table.getState().sorting;
    return first ? `${first.desc ? '-' : ''}${first.id}` : '-date';
});
const setSort = (value) => table.setSorting([{ id: value.replace(/^-/, ''), desc: value.startsWith('-') }]);
const sortLabel = (value) => `${t(value.replace(/^-/, '') === 'store' ? 'common.store' : `photo.${value.replace(/^-/, '')}`)} ${value.startsWith('-') ? '↓' : '↑'}`;

const editing = ref(null); // null = closed, {} = new, photo = edit
const form = useForm({ date: '', initial: '', maker: '', store_id: '', image: null });

const open = (photo = {}) => {
    editing.value = photo;
    form.clearErrors();
    form.defaults({
        date: photo.date ?? new Date().toISOString().slice(0, 10),
        initial: photo.initial ?? '',
        maker: photo.maker ?? '',
        store_id: photo.store_id ?? '',
        image: null,
    });
    form.reset();
};
const close = () => (editing.value = null);

const submit = () => {
    const options = { preserveScroll: true, forceFormData: true, onSuccess: close };
    if (editing.value.id) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('photos.update', editing.value.id), options);
    } else {
        form.transform((data) => data).post(route('photos.store'), options);
    }
};

const confirm = useConfirm();
const destroy = async (photo) => {
    if (!(await confirm({ message: `${photo.initial} / ${photo.date}` }))) return;
    router.delete(route('photos.destroy', photo.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="t('nav.photos')" />

    <PageHeader :title="t('nav.photos')" :description="t('desc.photos')" :icon="Images">
        <template #actions>
            <div class="join" role="group" :aria-label="t('common.view')">
                <button
                    v-for="option in [{ id: 'table', icon: List, label: t('common.viewTable') }, { id: 'grid', icon: LayoutGrid, label: t('common.viewGrid') }]"
                    :key="option.id"
                    type="button"
                    class="btn join-item"
                    :class="view === option.id ? 'btn-neutral' : 'btn-outline border-base-300 bg-base-100'"
                    :aria-pressed="view === option.id"
                    @click="view = option.id"
                >
                    <component :is="option.icon" class="size-4" aria-hidden="true" />
                    <span class="hidden sm:inline">{{ option.label }}</span>
                </button>
            </div>
            <button type="button" class="btn btn-primary" @click="open()">
                <Plus class="size-4" aria-hidden="true" />
                {{ t('photo.add') }}
            </button>
        </template>
    </PageHeader>

    <!-- Filters shared by both views (same TanStack / URL state). -->
    <div class="mb-4 flex flex-col gap-3 rounded-box border border-base-300 bg-base-100 p-4 shadow-sm lg:flex-row lg:items-center">
        <select v-model="filters.store" class="select lg:w-64" :aria-label="t('common.store')">
            <option value="">{{ t('common.store') }}: {{ t('common.all') }}</option>
            <option v-for="s in stores" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
        </select>
        <div class="grid grid-cols-2 gap-3 lg:flex">
            <select v-model="filters.year" class="select lg:w-36" :aria-label="t('common.year')">
                <option value="">{{ t('common.year') }}: {{ t('common.all') }}</option>
                <option v-for="y in years" :key="y" :value="String(y)">{{ y }}</option>
            </select>
            <select v-model="filters.month" class="select lg:w-32" :aria-label="t('common.month')">
                <option value="">{{ t('common.month') }}: {{ t('common.all') }}</option>
                <option v-for="m in 12" :key="m" :value="String(m)">{{ m }}</option>
            </select>
        </div>
        <!-- The table sorts by clicking headers; the grid needs a select. -->
        <select v-if="view === 'grid'" class="select lg:ml-auto lg:w-48" :value="currentSort" :aria-label="t('common.sort')" @change="setSort($event.target.value)">
            <option v-for="option in sortOptions" :key="option" :value="option">{{ t('common.sort') }}: {{ sortLabel(option) }}</option>
        </select>
        <button v-if="hasFilters" type="button" class="btn btn-ghost btn-sm self-start lg:self-auto" @click="resetFilters">
            <RotateCcw class="size-3.5" aria-hidden="true" />
            {{ t('common.reset') }}
        </button>
    </div>

    <DataTable v-if="view === 'table'" :table="table" :paginator="photos" :loading="loading" :filtered="hasFilters" row-numbers @reset="resetFilters">
        <template #cell-image="{ row }">
            <ZoomImage :src="row.image_url" :alt="`${row.initial} ${row.maker ?? ''}`" thumb-class="h-14 w-20 rounded-lg" />
        </template>
        <template #cell-initial="{ row }">
            <div class="font-medium">{{ row.initial }}</div>
            <!-- date / maker columns are hidden on phones -->
            <div class="text-xs whitespace-normal text-base-content/50 sm:hidden">{{ row.maker }}</div>
            <div class="text-xs text-base-content/50 tabular-nums sm:hidden">{{ row.date }}</div>
        </template>
        <template #cell-actions="{ row }">
            <div class="flex justify-end gap-0.5">
                <IconButton :icon="Pencil" :label="t('common.edit')" @click="open(row)" />
                <IconButton :icon="Trash2" :label="t('common.delete')" tone="danger" @click="destroy(row)" />
            </div>
        </template>
    </DataTable>

    <div v-else class="card overflow-hidden border border-base-300 bg-base-100 shadow-sm">
        <div class="relative p-4" :aria-busy="loading">
            <div v-if="loading" class="absolute inset-x-0 top-0 h-0.5 overflow-hidden bg-primary/15">
                <div class="h-full w-1/3 animate-[table-progress_1s_ease-in-out_infinite] bg-primary"></div>
            </div>

            <div v-if="!photos.data.length" class="flex flex-col items-center gap-2 py-14 text-center">
                <div class="grid size-12 place-items-center rounded-full bg-base-200 text-base-content/40">
                    <Inbox class="size-6" aria-hidden="true" />
                </div>
                <p class="font-medium">{{ t('common.empty') }}</p>
                <button v-if="hasFilters" type="button" class="btn btn-sm btn-outline mt-1" @click="resetFilters">
                    <RotateCcw class="size-3.5" aria-hidden="true" />
                    {{ t('common.resetFilters') }}
                </button>
            </div>

            <ul v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5" :class="{ 'opacity-60': loading }">
                <li v-for="p in photos.data" :key="p.id" class="group overflow-hidden rounded-box border border-base-300 bg-base-100 transition-shadow hover:shadow-md">
                    <ZoomImage :src="p.image_url" :alt="`${p.initial} ${p.maker ?? ''}`" thumb-class="aspect-[4/3] w-full rounded-none ring-0" />
                    <div class="space-y-1 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold">{{ p.initial }}</span>
                            <span class="inline-flex items-center gap-1 text-xs text-base-content/50 tabular-nums">
                                <CalendarDays class="size-3" aria-hidden="true" />{{ p.date }}
                            </span>
                        </div>
                        <p class="truncate text-sm">{{ p.maker }}</p>
                        <p class="truncate text-xs text-base-content/50">{{ p.store }}</p>
                        <div class="flex justify-end gap-0.5 pt-1">
                            <IconButton :icon="Pencil" :label="t('common.edit')" @click="open(p)" />
                            <IconButton :icon="Trash2" :label="t('common.delete')" tone="danger" @click="destroy(p)" />
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <div class="border-t border-base-300 px-4 py-3">
            <DataTablePagination :table="table" :paginator="photos" />
        </div>
    </div>

    <Modal :open="!!editing" :title="editing?.id ? t('photo.edit') : t('photo.add')" :description="editing?.id ? `${editing.initial} / ${editing.date}` : ''" @close="close">
        <form class="space-y-2" @submit.prevent="submit">
            <div class="grid grid-cols-2 gap-x-4">
                <Field :label="t('photo.date')" :error="form.errors.date" required>
                    <input v-model="form.date" type="date" class="input w-full" required />
                </Field>
                <Field :label="t('photo.initial')" :error="form.errors.initial" required>
                    <input v-model="form.initial" type="text" class="input w-full" maxlength="128" placeholder="Y" required />
                </Field>
            </div>
            <Field :label="t('photo.maker')" :error="form.errors.maker" required>
                <input v-model="form.maker" type="text" class="input w-full" maxlength="128" placeholder="ホンダ N-BOX" required />
            </Field>
            <Field :label="t('common.store')" :error="form.errors.store_id" required>
                <select v-model="form.store_id" class="select w-full" required>
                    <option value="" disabled>{{ t('common.select') }}</option>
                    <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </Field>
            <div class="pt-2">
                <p class="mb-2 text-sm font-semibold">{{ t('common.image') }}<span v-if="!editing?.id" class="text-error">*</span></p>
                <ImageUpload v-model="form.image" :current-url="editing?.image_url" :required="!editing?.id" :max-kb="4096" />
                <p v-if="form.errors.image" class="mt-1 text-sm text-error" role="alert">{{ form.errors.image }}</p>
            </div>

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
