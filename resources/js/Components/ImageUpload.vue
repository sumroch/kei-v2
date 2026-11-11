<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { ImageOff, ImagePlus, RefreshCw, X } from 'lucide-vue-next';
import { useT } from '../i18n';

/**
 * Image field with preview: click or drop a file. Shows the current image when editing.
 * v-model: the selected File (null = keep current image).
 */
const props = defineProps({
    currentUrl: { type: String, default: null },
    required: { type: Boolean, default: false },
    maxKb: { type: Number, default: 2048 },
});
const file = defineModel({ type: [Object, null], default: null });

const t = useT();
const input = ref(null);
const dragging = ref(false);
const objectUrl = ref(null);

watch(file, (value) => {
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
    objectUrl.value = value ? URL.createObjectURL(value) : null;
    if (!value && input.value) input.value.value = '';
});
onBeforeUnmount(() => objectUrl.value && URL.revokeObjectURL(objectUrl.value));

const preview = computed(() => objectUrl.value ?? props.currentUrl);
const broken = ref(false); // current image file missing on disk
watch(preview, () => (broken.value = false));
const sizeLabel = computed(() => (file.value ? `${Math.round(file.value.size / 1024).toLocaleString()} KB` : ''));

const pick = (event) => (file.value = event.target.files[0] ?? null);
const drop = (event) => {
    dragging.value = false;
    const dropped = event.dataTransfer.files[0];
    if (dropped?.type.startsWith('image/')) file.value = dropped;
};
</script>

<template>
    <div>
        <input ref="input" type="file" accept="image/*" class="sr-only" :required="required && !currentUrl && !file" @change="pick" />

        <div v-if="preview" class="flex items-center gap-4 rounded-box border border-base-300 p-3">
            <img v-if="!broken" :src="preview" alt="" class="h-20 w-28 shrink-0 rounded-lg bg-base-200 object-cover" @error="broken = true" />
            <span v-else class="grid h-20 w-28 shrink-0 place-items-center rounded-lg bg-base-200 text-base-content/30">
                <ImageOff class="size-5" aria-hidden="true" />
            </span>
            <div class="min-w-0 flex-1 text-sm">
                <p class="truncate font-medium">{{ file ? file.name : t('upload.current') }}</p>
                <p class="text-base-content/50">{{ file ? sizeLabel : t('common.keepImage') }}</p>
            </div>
            <div class="flex shrink-0 gap-1">
                <button type="button" class="btn btn-sm btn-ghost" @click="input.click()">
                    <RefreshCw class="size-3.5" aria-hidden="true" />
                    {{ t('upload.change') }}
                </button>
                <button v-if="file" type="button" class="btn btn-sm btn-ghost btn-square" :aria-label="t('upload.undo')" @click="file = null">
                    <X class="size-4" aria-hidden="true" />
                </button>
            </div>
        </div>

        <button
            v-else
            type="button"
            class="flex w-full flex-col items-center gap-2 rounded-box border-2 border-dashed px-4 py-8 text-center transition-colors"
            :class="dragging ? 'border-primary bg-primary/5' : 'border-base-300 hover:border-primary/60 hover:bg-base-200/50'"
            @click="input.click()"
            @dragover.prevent="dragging = true"
            @dragleave="dragging = false"
            @drop.prevent="drop"
        >
            <span class="grid size-10 place-items-center rounded-full bg-primary/10 text-primary">
                <ImagePlus class="size-5" aria-hidden="true" />
            </span>
            <span class="text-sm font-medium">{{ t('upload.choose') }}</span>
            <span class="text-xs text-base-content/50">{{ t('upload.hint', { max: (maxKb / 1024).toFixed(0) }) }}</span>
        </button>
    </div>
</template>
