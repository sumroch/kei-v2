<script setup>
import { ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import { useT } from '../i18n';

// daisyUI modal on a native <dialog> (focus trap + Esc for free).
// Layout: fixed header, scrolling body; a `.modal-action` placed in the body sticks to the bottom (app.css).
const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    wide: { type: Boolean, default: false },
});
const emit = defineEmits(['close']);
const t = useT();
const dialog = ref(null);

watch(
    () => props.open,
    (open) => {
        if (!dialog.value) return;
        if (open && !dialog.value.open) dialog.value.showModal();
        if (!open && dialog.value.open) dialog.value.close();
    },
    { flush: 'post' },
);
</script>

<template>
    <dialog ref="dialog" class="modal modal-bottom sm:modal-middle" aria-labelledby="modal-title" @close="emit('close')">
        <!-- autofocus on the box: opening doesn't put a focus ring on the close button; Tab still reaches it -->
        <div class="modal-box flex max-h-[92dvh] flex-col p-0 focus:outline-none" :class="wide ? 'sm:max-w-3xl' : 'sm:max-w-lg'" tabindex="-1" autofocus>
            <div class="flex items-start justify-between gap-4 border-b border-base-300 px-6 py-4">
                <div class="min-w-0">
                    <h3 id="modal-title" class="truncate text-lg font-semibold">{{ title }}</h3>
                    <p v-if="description" class="mt-0.5 text-sm text-base-content/60">{{ description }}</p>
                </div>
                <button type="button" class="btn btn-sm btn-circle btn-ghost -mr-2" :aria-label="t('common.close')" @click="emit('close')">
                    <X class="size-4" aria-hidden="true" />
                </button>
            </div>
            <div class="modal-body overflow-y-auto px-6 py-5">
                <slot v-if="open" />
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="submit">{{ t('common.close') }}</button>
        </form>
    </dialog>
</template>
