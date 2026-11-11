<script setup>
import { useT } from '../i18n';

// Reservation type: contact / raiten (visit) / shinsa (credit check).
defineProps({ type: { type: String, required: true } });
const t = useT();
const tones = { contact: 'badge-info', raiten: 'badge-success', shinsa: 'badge-warning' };
</script>

<template>
    <span class="badge badge-sm badge-soft whitespace-nowrap" :class="tones[type] ?? 'badge-neutral'">{{ t(`type.${type}`) }}</span>
</template>
