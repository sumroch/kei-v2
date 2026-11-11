<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Car, Pencil, Plus, RotateCcw, Trash2 } from 'lucide-vue-next';
import { useConfirm } from '../../composables/useConfirm';
import { srOnlyHeader, useDataTable } from '../../composables/useDataTable';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import DataTable from '../../Components/DataTable/DataTable.vue';
import Field from '../../Components/Field.vue';
import IconButton from '../../Components/IconButton.vue';
import ImageUpload from '../../Components/ImageUpload.vue';
import Modal from '../../Components/Modal.vue';
import PageHeader from '../../Components/PageHeader.vue';
import SearchInput from '../../Components/SearchInput.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import ZoomImage from '../../Components/ZoomImage.vue';
import { useT } from '../../i18n';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    cars: { type: Object, required: true }, // paginator
    query: { type: Object, required: true },
    storeTypes: { type: Array, required: true },
    makers: { type: Array, required: true },
});

const t = useT();

const hideBelow = (bp, extra = '') => ({ headerClass: `hidden ${bp}:table-cell ${extra}`, cellClass: `hidden ${bp}:table-cell ${extra}` });
// Prices are stored as formatted strings ("1,485,000"); "0" means not set.
const yen = (value) => (value && value !== '0' ? `¥${value}` : '—');

// Sortable ids must match allowedSorts() in Admin\CarController.
const columns = [
    { id: 'picture', header: () => t('common.image'), enableSorting: false, meta: { headerClass: 'w-24' } },
    { accessorKey: 'brand', header: () => t('car.brand') },
    { accessorKey: 'maker', header: () => t('car.maker'), meta: hideBelow('md') },
    { accessorKey: 'grade', header: () => t('car.grade'), meta: hideBelow('sm') },
    { accessorKey: 'store_type', header: () => t('car.site'), meta: hideBelow('lg') },
    // monthly (main) + vehicle price (small) in one column to keep the row actions on screen
    { accessorKey: 'monthly', header: () => t('car.priceColumn'), enableSorting: false, meta: hideBelow('xl', 'text-right tabular-nums') },
    { accessorKey: 'new', header: () => t('car.condition'), meta: hideBelow('md') },
    { accessorKey: 'active', header: () => t('common.status'), meta: hideBelow('sm') },
    { accessorKey: 'created_at', header: () => t('common.registeredAt'), meta: hideBelow('2xl', 'text-sm text-base-content/60 tabular-nums') },
    { id: 'actions', header: srOnlyHeader(() => t('common.actions')), enableSorting: false, meta: { headerClass: 'w-24', cellClass: 'text-right' } },
];

const { table, filters, loading, resetFilters, hasFilters } = useDataTable({
    routeName: 'cars.index',
    paginator: () => props.cars,
    query: () => props.query,
    columns,
    filters: ['search', 'store_type', 'maker', 'active'],
});

const editing = ref(null);
const form = useForm({
    store_type: '', brand: '', maker: '', grade: '', fuel: '', price: '', total: '', monthly: '', new: true, active: true, image: null,
});

const open = (car = {}) => {
    editing.value = car;
    form.clearErrors();
    form.defaults({
        store_type: car.store_type || filters.store_type || props.storeTypes[0],
        brand: car.brand ?? '',
        maker: car.maker ?? '',
        grade: car.grade ?? '',
        fuel: car.fuel ?? '',
        price: car.price ?? '',
        total: car.total ?? '',
        monthly: car.monthly ?? '',
        new: car.new ?? true,
        active: car.active ?? true,
        image: null,
    });
    form.reset();
};
const close = () => (editing.value = null);

const submit = () => {
    // Booleans as 1/0: multipart can't carry true/false.
    const payload = (data) => ({ ...data, new: data.new ? 1 : 0, active: data.active ? 1 : 0 });
    const options = { preserveScroll: true, forceFormData: true, onSuccess: close };
    if (editing.value.id) {
        form.transform((data) => ({ ...payload(data), _method: 'put' })).post(route('cars.update', editing.value.id), options);
    } else {
        form.transform(payload).post(route('cars.store'), options);
    }
};

const confirm = useConfirm();
const destroy = async (car) => {
    if (!(await confirm({ message: `${car.brand} ${car.grade}` }))) return;
    router.delete(route('cars.destroy', car.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="t('nav.cars')" />

    <PageHeader :title="t('nav.cars')" :description="t('desc.cars')" :icon="Car">
        <template #actions>
            <button type="button" class="btn btn-primary" @click="open()">
                <Plus class="size-4" aria-hidden="true" />
                {{ t('car.add') }}
            </button>
        </template>
    </PageHeader>

    <DataTable :table="table" :paginator="cars" :loading="loading" :filtered="hasFilters" row-numbers @reset="resetFilters">
        <template #toolbar>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="lg:max-w-xs lg:flex-1">
                    <SearchInput v-model="filters.search" :placeholder="t('car.searchPlaceholder')" />
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:flex">
                    <select v-model="filters.store_type" class="select col-span-2 sm:col-span-1 lg:w-52" :aria-label="t('car.site')">
                        <option value="">{{ t('car.site') }}: {{ t('common.all') }}</option>
                        <option v-for="type in storeTypes" :key="type" :value="type">{{ t(`site.${type}`) }}</option>
                    </select>
                    <select v-model="filters.maker" class="select lg:w-44" :aria-label="t('car.maker')">
                        <option value="">{{ t('car.maker') }}: {{ t('common.all') }}</option>
                        <option v-for="m in makers" :key="m" :value="m">{{ m }}</option>
                    </select>
                    <select v-model="filters.active" class="select lg:w-40" :aria-label="t('common.status')">
                        <option value="">{{ t('common.status') }}: {{ t('common.all') }}</option>
                        <option value="1">{{ t('common.active') }}</option>
                        <option value="0">{{ t('common.inactive') }}</option>
                    </select>
                </div>
                <button v-if="hasFilters" type="button" class="btn btn-ghost btn-sm self-start lg:self-auto" @click="resetFilters">
                    <RotateCcw class="size-3.5" aria-hidden="true" />
                    {{ t('common.reset') }}
                </button>
            </div>
        </template>

        <template #cell-picture="{ row }">
            <ZoomImage :src="row.picture_url" :alt="`${row.brand} ${row.grade}`" thumb-class="h-12 w-16 rounded-lg" />
        </template>
        <template #cell-brand="{ row }">
            <div class="font-medium whitespace-normal">{{ row.brand }}</div>
            <!-- maker / grade / status columns are hidden on small screens: show them here instead -->
            <div class="text-xs whitespace-normal text-base-content/50 md:hidden"><span class="capitalize">{{ row.maker }}</span> · {{ row.grade }}</div>
            <div class="mt-1 sm:hidden"><StatusBadge :active="row.active" /></div>
        </template>
        <template #cell-maker="{ value }"><span class="capitalize">{{ value }}</span></template>
        <template #cell-grade="{ value }"><span class="text-sm text-base-content/80">{{ value }}</span></template>
        <template #cell-store_type="{ value }">
            <span class="badge badge-sm badge-ghost">{{ t(`site.${value}`) }}</span>
        </template>
        <template #cell-monthly="{ row }">
            <div class="font-medium">{{ yen(row.monthly) }}<span v-if="yen(row.monthly) !== '—'" class="text-xs font-normal text-base-content/50"> /{{ t('car.perMonth') }}</span></div>
            <div v-if="yen(row.price) !== '—'" class="text-xs text-base-content/50">{{ t('car.price') }} {{ yen(row.price) }}</div>
        </template>
        <template #cell-new="{ value }">
            <span class="badge badge-sm badge-outline">{{ value ? t('car.new') : t('car.used') }}</span>
        </template>
        <template #cell-active="{ value }"><StatusBadge :active="value" /></template>
        <template #cell-actions="{ row }">
            <div class="flex justify-end gap-0.5">
                <IconButton :icon="Pencil" :label="t('common.edit')" @click="open(row)" />
                <IconButton :icon="Trash2" :label="t('common.delete')" tone="danger" @click="destroy(row)" />
            </div>
        </template>
    </DataTable>

    <Modal
        :open="!!editing"
        :title="editing?.id ? t('car.edit') : t('car.add')"
        :description="editing?.id ? `${editing.brand} ${editing.grade}` : ''"
        wide
        @close="close"
    >
        <form class="space-y-6" @submit.prevent="submit">
            <datalist id="car-makers">
                <option v-for="m in makers" :key="m" :value="m" />
            </datalist>

            <section>
                <h4 class="mb-1 text-sm font-semibold text-base-content/70">{{ t('car.sectionBasic') }}</h4>
                <div class="grid grid-cols-1 gap-x-4 sm:grid-cols-2">
                    <Field :label="t('car.site')" :error="form.errors.store_type" required>
                        <select v-model="form.store_type" class="select w-full" required>
                            <option v-for="type in storeTypes" :key="type" :value="type">{{ t(`site.${type}`) }}</option>
                        </select>
                    </Field>
                    <Field :label="t('car.maker')" :error="form.errors.maker" required>
                        <input v-model="form.maker" list="car-makers" type="text" class="input w-full" maxlength="128" placeholder="daihatsu" required />
                    </Field>
                    <Field :label="t('car.brand')" :error="form.errors.brand" required>
                        <input v-model="form.brand" type="text" class="input w-full" maxlength="128" required />
                    </Field>
                    <Field :label="t('car.grade')" :error="form.errors.grade" required>
                        <input v-model="form.grade" type="text" class="input w-full" maxlength="128" required />
                    </Field>
                    <Field :label="t('car.fuel')" :error="form.errors.fuel">
                        <input v-model="form.fuel" type="text" class="input w-full" maxlength="128" placeholder="29.4km/L" />
                    </Field>
                    <Field :label="t('car.condition')">
                        <div class="join w-full" role="radiogroup" :aria-label="t('car.condition')">
                            <button type="button" class="btn join-item flex-1" :class="form.new ? 'btn-primary' : 'btn-outline border-base-300'" :aria-checked="form.new" role="radio" @click="form.new = true">
                                {{ t('car.new') }}
                            </button>
                            <button type="button" class="btn join-item flex-1" :class="!form.new ? 'btn-primary' : 'btn-outline border-base-300'" :aria-checked="!form.new" role="radio" @click="form.new = false">
                                {{ t('car.used') }}
                            </button>
                        </div>
                    </Field>
                </div>
            </section>

            <section>
                <h4 class="mb-1 text-sm font-semibold text-base-content/70">{{ t('car.sectionPrice') }}</h4>
                <div class="grid grid-cols-1 gap-x-4 sm:grid-cols-3">
                    <Field v-for="key in ['price', 'total', 'monthly']" :key="key" :label="t(`car.${key}`)" :error="form.errors[key]">
                        <label class="input w-full">
                            <span class="text-base-content/40">¥</span>
                            <input v-model="form[key]" type="text" inputmode="numeric" class="grow text-right tabular-nums" maxlength="32" placeholder="0" />
                        </label>
                    </Field>
                </div>
            </section>

            <section>
                <h4 class="mb-2 text-sm font-semibold text-base-content/70">{{ t('common.image') }}<span v-if="!editing?.id" class="text-error">*</span></h4>
                <ImageUpload v-model="form.image" :current-url="editing?.picture_url" :required="!editing?.id" :max-kb="2048" />
                <p v-if="form.errors.image" class="mt-1 text-sm text-error" role="alert">{{ form.errors.image }}</p>
            </section>

            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-box border border-base-300 p-4">
                <span>
                    <span class="block font-medium">{{ t('car.publish') }}</span>
                    <span class="text-sm text-base-content/60">{{ t('car.publishHint') }}</span>
                </span>
                <input v-model="form.active" type="checkbox" class="toggle toggle-success" />
            </label>

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
