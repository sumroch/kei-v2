<script setup>
import { Search, X } from 'lucide-vue-next';
import { useT } from '../i18n';

defineProps({ placeholder: { type: String, default: '' } });
const model = defineModel({ type: String, default: '' });
const t = useT();
</script>

<template>
    <label class="input w-full">
        <Search class="size-4 shrink-0 text-base-content/40" aria-hidden="true" />
        <span class="sr-only">{{ t('common.search') }}</span>
        <input v-model="model" type="search" class="grow" :placeholder="placeholder" />
        <button v-if="model" type="button" class="btn btn-ghost btn-xs btn-circle" :aria-label="t('common.clear')" @click="model = ''">
            <X class="size-3.5" aria-hidden="true" />
        </button>
    </label>
</template>
