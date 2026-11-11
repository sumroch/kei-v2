<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CircleCheck, CircleX } from 'lucide-vue-next';
import { useT } from '../i18n';

// Shows server flash messages: Inertia::flash('success', 'saved') -> t('flash.saved').
const t = useT();
const page = usePage();
const toasts = ref([]);
let nextId = 0;

const push = (type, text) => {
    const id = ++nextId;
    toasts.value.push({ id, type, text });
    setTimeout(() => (toasts.value = toasts.value.filter((toast) => toast.id !== id)), 3500);
};

watch(
    () => page.flash,
    (flash) => {
        if (flash?.success) push('success', t(`flash.${flash.success}`));
        if (flash?.error) push('error', t(`flash.${flash.error}`));
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <div class="toast toast-end z-50" aria-live="polite">
        <div
            v-for="toast in toasts"
            :key="toast.id"
            role="status"
            class="alert min-w-64 border border-base-300 bg-base-100 text-base-content shadow-lg"
        >
            <CircleCheck v-if="toast.type !== 'error'" class="size-5 text-success" aria-hidden="true" />
            <CircleX v-else class="size-5 text-error" aria-hidden="true" />
            <span class="font-medium">{{ toast.text }}</span>
        </div>
    </div>
</template>
