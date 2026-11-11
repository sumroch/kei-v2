<script setup>
// Page title row used by every admin page: icon + title + short description, actions on the right.
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
});
</script>

<template>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            <div v-if="icon" class="hidden size-11 shrink-0 place-items-center rounded-box bg-primary/10 text-primary sm:grid">
                <component :is="icon" class="size-5" aria-hidden="true" />
            </div>
            <div class="min-w-0">
                <h1 class="truncate text-xl font-bold tracking-tight sm:text-2xl">{{ title }}</h1>
                <p v-if="description" class="mt-0.5 text-sm text-base-content/60">{{ description }}</p>
            </div>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
