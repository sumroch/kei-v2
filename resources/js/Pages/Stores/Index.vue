<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Mail, Pencil, Phone, Plus, RotateCcw, Store, Trash2, X } from 'lucide-vue-next';
import { useConfirm } from '../../composables/useConfirm';
import { srOnlyHeader, useDataTable } from '../../composables/useDataTable';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import DataTable from '../../Components/DataTable/DataTable.vue';
import Field from '../../Components/Field.vue';
import IconButton from '../../Components/IconButton.vue';
import Modal from '../../Components/Modal.vue';
import PageHeader from '../../Components/PageHeader.vue';
import SearchInput from '../../Components/SearchInput.vue';
import { useT } from '../../i18n';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    stores: { type: Object, required: true }, // paginator
    query: { type: Object, required: true },
    brands: { type: Array, required: true },
});

const t = useT();
const brandTone = { flat7: 'badge-primary', joycal: 'badge-info', keigayasui: 'badge-warning' };

// Sortable ids must match allowedSorts() in Admin\StoreController.
const columns = [
    { accessorKey: 'name', header: () => t('store.name') },
    { accessorKey: 'brand', header: () => t('store.brand'), meta: { headerClass: 'hidden sm:table-cell', cellClass: 'hidden sm:table-cell' } },
    { accessorKey: 'phone_number', header: () => t('store.phone'), meta: { headerClass: 'hidden md:table-cell', cellClass: 'hidden md:table-cell' } },
    { accessorKey: 'email', header: () => t('store.email'), enableSorting: false, meta: { headerClass: 'hidden lg:table-cell', cellClass: 'hidden lg:table-cell' } },
    { accessorKey: 'reservations_count', header: () => t('store.reservations'), meta: { headerClass: 'hidden md:table-cell text-right', cellClass: 'hidden md:table-cell text-right tabular-nums' } },
    { id: 'actions', header: srOnlyHeader(() => t('common.actions')), enableSorting: false, meta: { headerClass: 'w-24', cellClass: 'text-right' } },
];

const { table, filters, loading, resetFilters, hasFilters } = useDataTable({
    routeName: 'stores.index',
    paginator: () => props.stores,
    query: () => props.query,
    columns,
    filters: ['search', 'brand'],
});

const fields = ['name', 'name_id', 'slug', 'brand', 'email', 'postal_code', 'address', 'phone_number', 'phone_number_2', 'operational'];

const editing = ref(null);
const form = useForm({ ...Object.fromEntries(fields.map((f) => [f, ''])), email_bcc: [] });

const open = (store = {}) => {
    editing.value = store;
    form.clearErrors();
    form.defaults({
        ...Object.fromEntries(fields.map((f) => [f, store[f] ?? ''])),
        brand: store.brand ?? props.brands[0],
        email_bcc: [...(store.email_bcc ?? [])],
    });
    form.reset();
};
const close = () => (editing.value = null);

const bccError = (i) => form.errors[`email_bcc.${i}`];

const submit = () => {
    const options = { preserveScroll: true, onSuccess: close };
    editing.value.id ? form.put(route('stores.update', editing.value.id), options) : form.post(route('stores.store'), options);
};

const confirm = useConfirm();
const destroy = async (store) => {
    if (!(await confirm({ message: store.name }))) return;
    router.delete(route('stores.destroy', store.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="t('nav.stores')" />

    <PageHeader :title="t('nav.stores')" :description="t('desc.stores')" :icon="Store">
        <template #actions>
            <button type="button" class="btn btn-primary" @click="open()">
                <Plus class="size-4" aria-hidden="true" />
                {{ t('store.add') }}
            </button>
        </template>
    </PageHeader>

    <DataTable :table="table" :paginator="stores" :loading="loading" :filtered="hasFilters" row-numbers @reset="resetFilters">
        <template #toolbar>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="sm:max-w-sm sm:flex-1">
                    <SearchInput v-model="filters.search" :placeholder="t('store.searchPlaceholder')" />
                </div>
                <select v-model="filters.brand" class="select sm:w-48" :aria-label="t('store.brand')">
                    <option value="">{{ t('store.brand') }}: {{ t('common.all') }}</option>
                    <option v-for="b in brands" :key="b" :value="b">{{ t(`brand.${b}`) }}</option>
                </select>
                <button v-if="hasFilters" type="button" class="btn btn-ghost btn-sm self-start sm:self-auto" @click="resetFilters">
                    <RotateCcw class="size-3.5" aria-hidden="true" />
                    {{ t('common.reset') }}
                </button>
            </div>
        </template>

        <template #cell-name="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="font-mono text-xs text-base-content/50">{{ row.slug }}</div>
        </template>
        <template #cell-brand="{ value }">
            <span class="badge badge-sm badge-soft" :class="brandTone[value] ?? 'badge-neutral'">{{ t(`brand.${value}`) }}</span>
        </template>
        <template #cell-phone_number="{ row }">
            <span class="inline-flex items-center gap-1.5 text-sm"><Phone class="size-3.5 text-base-content/40" aria-hidden="true" />{{ row.phone_number }}</span>
        </template>
        <template #cell-email="{ value }">
            <span class="inline-flex items-center gap-1.5 text-sm text-base-content/80"><Mail class="size-3.5 text-base-content/40" aria-hidden="true" />{{ value }}</span>
        </template>
        <template #cell-reservations_count="{ value }">{{ value.toLocaleString() }}</template>
        <template #cell-actions="{ row }">
            <div class="flex justify-end gap-0.5">
                <IconButton :icon="Pencil" :label="t('common.edit')" @click="open(row)" />
                <IconButton :icon="Trash2" :label="t('common.delete')" tone="danger" @click="destroy(row)" />
            </div>
        </template>
    </DataTable>

    <Modal :open="!!editing" :title="editing?.id ? t('store.edit') : t('store.add')" :description="editing?.id ? editing.name : ''" wide @close="close">
        <form class="space-y-6" @submit.prevent="submit">
            <section>
                <h4 class="mb-1 text-sm font-semibold text-base-content/70">{{ t('store.sectionBasic') }}</h4>
                <div class="grid grid-cols-1 gap-x-4 sm:grid-cols-2">
                    <Field :label="t('store.name')" :error="form.errors.name" required>
                        <input v-model="form.name" type="text" class="input w-full" maxlength="64" required />
                    </Field>
                    <Field :label="t('store.brand')" :error="form.errors.brand" required>
                        <select v-model="form.brand" class="select w-full" required>
                            <option v-for="b in brands" :key="b" :value="b">{{ t(`brand.${b}`) }}</option>
                        </select>
                    </Field>
                    <Field :label="t('store.slug')" :error="form.errors.slug" :hint="t('store.slugHint')" required>
                        <input v-model="form.slug" type="text" class="input w-full font-mono" maxlength="32" pattern="[A-Za-z0-9_\-]+" placeholder="flat7_sayama" required />
                    </Field>
                    <Field :label="t('store.nameId')" :error="form.errors.name_id" required>
                        <input v-model="form.name_id" type="text" class="input w-full font-mono" maxlength="64" placeholder="sayama" required />
                    </Field>
                </div>
            </section>

            <section>
                <h4 class="mb-1 text-sm font-semibold text-base-content/70">{{ t('store.sectionContact') }}</h4>
                <div class="grid grid-cols-1 gap-x-4 sm:grid-cols-2">
                    <Field :label="t('store.email')" :error="form.errors.email" required>
                        <input v-model="form.email" type="email" class="input w-full" maxlength="32" required />
                    </Field>
                    <Field :label="t('store.postalCode')" :error="form.errors.postal_code" required>
                        <input v-model="form.postal_code" type="text" class="input w-full" maxlength="32" placeholder="350-1300" required />
                    </Field>
                    <Field :label="t('store.phone')" :error="form.errors.phone_number" required>
                        <input v-model="form.phone_number" type="tel" class="input w-full" maxlength="32" required />
                    </Field>
                    <Field :label="t('store.phone2')" :error="form.errors.phone_number_2">
                        <input v-model="form.phone_number_2" type="tel" class="input w-full" maxlength="64" />
                    </Field>
                </div>
                <Field :label="t('store.address')" :error="form.errors.address" required>
                    <input v-model="form.address" type="text" class="input w-full" maxlength="256" required />
                </Field>
                <Field :label="t('store.operational')" :error="form.errors.operational" required>
                    <input v-model="form.operational" type="text" class="input w-full" maxlength="64" required />
                </Field>
            </section>

            <section>
                <h4 class="text-sm font-semibold text-base-content/70">{{ t('store.emailBcc') }}</h4>
                <p class="mb-2 text-xs text-base-content/50">{{ t('store.bccHint') }}</p>
                <p v-if="form.errors.email_bcc" class="text-sm text-error" role="alert">{{ form.errors.email_bcc }}</p>
                <div class="space-y-2">
                    <div v-for="(_, i) in form.email_bcc" :key="i">
                        <div class="flex gap-2">
                            <label class="input w-full">
                                <Mail class="size-4 text-base-content/40" aria-hidden="true" />
                                <input v-model="form.email_bcc[i]" type="email" class="grow" :aria-label="`${t('store.emailBcc')} ${i + 1}`" />
                            </label>
                            <button type="button" class="btn btn-ghost btn-square" :aria-label="t('slide.remove')" @click="form.email_bcc.splice(i, 1)">
                                <X class="size-4" aria-hidden="true" />
                            </button>
                        </div>
                        <p v-if="bccError(i)" class="mt-1 text-sm text-error" role="alert">{{ bccError(i) }}</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-dash w-full" @click="form.email_bcc.push('')">
                        <Plus class="size-4" aria-hidden="true" />
                        {{ t('store.addBcc') }}
                    </button>
                </div>
            </section>

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
