<script setup>
// Label + input slot + validation error, daisyUI fieldset style (same as the login page).
defineProps({
    label: { type: String, required: true },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
});
</script>

<template>
    <fieldset class="fieldset">
        <legend class="fieldset-legend">
            {{ label }}<span v-if="required" class="text-error" aria-hidden="true">*</span>
        </legend>
        <slot />
        <p v-if="error" class="text-error text-sm" role="alert">{{ error }}</p>
        <p v-else-if="hint" class="label">{{ hint }}</p>
    </fieldset>
</template>
