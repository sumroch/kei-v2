<script setup>
// Square icon-only button for row actions. `label` is both the tooltip and the accessible name.
// Listeners/attrs (@click, :disabled...) go to the <button>, not the tooltip wrapper.
defineOptions({ inheritAttrs: false });
defineProps({
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    tone: { type: String, default: 'default' }, // default | danger | primary
    loading: { type: Boolean, default: false },
});
</script>

<template>
    <span class="tooltip tooltip-left sm:tooltip-top" :data-tip="label">
        <button
            type="button"
            class="btn btn-ghost btn-sm btn-square"
            :class="{
                'text-base-content/60 hover:text-base-content': tone === 'default',
                'text-error/80 hover:bg-error/10 hover:text-error': tone === 'danger',
                'text-primary hover:bg-primary/10': tone === 'primary',
            }"
            :aria-label="label"
            :disabled="loading"
            v-bind="$attrs"
        >
            <span v-if="loading" class="loading loading-spinner loading-xs"></span>
            <component :is="icon" v-else class="size-4" aria-hidden="true" />
        </button>
    </span>
</template>
