<script setup>
import { useT } from '../i18n';

// Published / unpublished, with a dot so it doesn't rely on color alone.
defineProps({ active: { type: Boolean, required: true } });
const t = useT();
</script>

<template>
    <span class="badge badge-sm badge-soft gap-1.5 whitespace-nowrap" :class="active ? 'badge-success' : 'badge-neutral'">
        <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
        {{ active ? t('common.active') : t('common.inactive') }}
    </span>
</template>
