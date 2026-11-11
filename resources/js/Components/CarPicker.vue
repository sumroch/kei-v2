<script setup>
import { computed, reactive, ref } from 'vue';
import { ArrowDown, ArrowUp, Car as CarIcon, X } from 'lucide-vue-next';
import SearchInput from './SearchInput.vue';
import { useT } from '../i18n';

/**
 * Ordered car selection for a slide (rebuild of the old dashboard's thumbnail picker).
 *  - Click a thumbnail to add it at the end / click again to remove it (order = click order).
 *  - Reorder the selection with ↑ ↓.
 * v-model: array of car ids in display order.
 */
const props = defineProps({
    cars: { type: Array, required: true }, // candidates (already limited to the slide's site)
    error: { type: String, default: '' },
});
const selected = defineModel({ type: Array, required: true });

const t = useT();
const search = ref('');
const broken = reactive(new Set()); // car ids whose picture failed to load

const byId = computed(() => Object.fromEntries(props.cars.map((c) => [c.id, c])));
const position = computed(() => Object.fromEntries(selected.value.map((id, i) => [id, i + 1])));

const visible = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.cars;
    return props.cars.filter((c) => `${c.maker} ${c.brand} ${c.grade}`.toLowerCase().includes(term));
});

const toggle = (id) => {
    selected.value = position.value[id] ? selected.value.filter((x) => x !== id) : [...selected.value, id];
};
const move = (index, delta) => {
    const target = index + delta;
    if (target < 0 || target >= selected.value.length) return;
    const next = [...selected.value];
    [next[index], next[target]] = [next[target], next[index]];
    selected.value = next;
};
</script>

<template>
    <div class="space-y-3">
        <!-- Selected, in display order -->
        <div>
            <p class="mb-1 text-sm font-semibold">{{ t('slide.selectedCars', { count: selected.length }) }}</p>
            <p v-if="!selected.length" class="rounded-box border border-dashed border-base-300 px-3 py-4 text-center text-sm text-base-content/60">
                {{ t('slide.noCars') }}
            </p>
            <ol v-else class="max-h-56 divide-y divide-base-200 overflow-y-auto rounded-box border border-base-200">
                <li v-for="(id, i) in selected" :key="id" class="flex items-center gap-2 px-2 py-1.5">
                    <span class="badge badge-primary badge-sm w-7 shrink-0">{{ i + 1 }}</span>
                    <img v-if="byId[id]?.picture_url && !broken.has(id)" :src="byId[id].picture_url" alt="" class="h-8 w-11 shrink-0 rounded object-cover" @error="broken.add(id)" />
                    <span v-else class="grid h-8 w-11 shrink-0 place-items-center rounded bg-base-200 text-base-content/30"><CarIcon class="size-4" aria-hidden="true" /></span>
                    <span class="min-w-0 flex-1 truncate text-sm">
                        {{ byId[id]?.brand ?? `#${id}` }} <span class="text-base-content/60">{{ byId[id]?.grade }}</span>
                    </span>
                    <button type="button" class="btn btn-ghost btn-xs btn-square" :disabled="i === 0" :aria-label="t('slide.moveUp')" @click="move(i, -1)"><ArrowUp class="size-3.5" aria-hidden="true" /></button>
                    <button type="button" class="btn btn-ghost btn-xs btn-square" :disabled="i === selected.length - 1" :aria-label="t('slide.moveDown')" @click="move(i, 1)"><ArrowDown class="size-3.5" aria-hidden="true" /></button>
                    <button type="button" class="btn btn-ghost btn-xs btn-square text-error" :aria-label="t('slide.remove')" @click="toggle(id)"><X class="size-3.5" aria-hidden="true" /></button>
                </li>
            </ol>
            <p v-if="error" class="mt-1 text-sm text-error" role="alert">{{ error }}</p>
        </div>

        <!-- Candidates -->
        <SearchInput v-model="search" :placeholder="t('car.searchPlaceholder')" />

        <p v-if="!visible.length" class="py-4 text-center text-sm text-base-content/60">{{ t('common.empty') }}</p>
        <ul v-else class="grid max-h-72 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4" role="list">
            <li v-for="car in visible" :key="car.id">
                <button
                    type="button"
                    class="relative w-full overflow-hidden rounded-box border-2 text-left transition"
                    :class="position[car.id] ? 'border-primary bg-primary/5' : 'border-base-200 hover:border-base-300'"
                    :aria-pressed="!!position[car.id]"
                    @click="toggle(car.id)"
                >
                    <img v-if="car.picture_url && !broken.has(car.id)" :src="car.picture_url" :alt="car.brand" class="aspect-[4/3] w-full bg-base-200 object-cover" loading="lazy" @error="broken.add(car.id)" />
                    <div v-else class="grid aspect-[4/3] w-full place-items-center bg-base-200 text-base-content/25" aria-hidden="true"><CarIcon class="size-6" /></div>
                    <span v-if="position[car.id]" class="badge badge-primary badge-sm absolute top-1 left-1">{{ position[car.id] }}</span>
                    <span v-if="!car.active" class="badge badge-ghost badge-xs absolute top-1 right-1">{{ t('common.inactive') }}</span>
                    <span class="block truncate px-1.5 pt-1 text-xs font-medium">{{ car.brand }}</span>
                    <span class="block truncate px-1.5 pb-1 text-xs text-base-content/60">{{ car.grade }}</span>
                </button>
            </li>
        </ul>
    </div>
</template>
