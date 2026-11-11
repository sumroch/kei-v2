<script setup>
import { ref, watch } from 'vue';
import { ImageOff, ZoomIn } from 'lucide-vue-next';
import { useT } from '../i18n';

// Thumbnail that opens the full image in a lightbox (the old dashboard's "Zoom Picture").
// Missing / broken files show a neutral placeholder instead of the browser's broken-image icon.
const props = defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: '' },
    thumbClass: { type: String, default: 'h-12 w-16 rounded-lg' },
});

const t = useT();
const dialog = ref(null);
const broken = ref(false);
watch(() => props.src, () => (broken.value = false));
</script>

<template>
    <button
        v-if="src && !broken"
        type="button"
        class="group relative block cursor-zoom-in overflow-hidden bg-base-200 ring-1 ring-base-300"
        :class="thumbClass"
        :aria-label="`${t('common.zoom')}: ${alt}`"
        @click="dialog.showModal()"
    >
        <img :src="src" :alt="alt" class="size-full object-cover transition-transform duration-200 group-hover:scale-105" loading="lazy" @error="broken = true" />
        <span class="absolute inset-0 grid place-items-center bg-black/0 text-white opacity-0 transition group-hover:bg-black/30 group-hover:opacity-100" aria-hidden="true">
            <ZoomIn class="size-4" />
        </span>
    </button>
    <div v-else class="grid place-items-center bg-base-200 text-base-content/30 ring-1 ring-base-300" :class="thumbClass" :title="t('common.noImage')">
        <ImageOff class="size-4" aria-hidden="true" />
        <span class="sr-only">{{ t('common.noImage') }}</span>
    </div>

    <dialog v-if="src && !broken" ref="dialog" class="modal">
        <div class="modal-box max-w-4xl p-2">
            <img :src="src" :alt="alt" class="mx-auto max-h-[80vh] w-auto rounded-lg" />
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="submit">{{ t('common.close') }}</button>
        </form>
    </dialog>
</template>
