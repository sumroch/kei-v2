<script setup>
import { nextTick, ref, watch } from 'vue';
import { TriangleAlert } from 'lucide-vue-next';
import { confirmState, settleConfirm } from '../composables/useConfirm';
import { useT } from '../i18n';

const t = useT();
const dialog = ref(null);
const confirmButton = ref(null);

watch(
    () => confirmState.open,
    async (open) => {
        if (!dialog.value) return;
        if (open) {
            dialog.value.showModal();
            await nextTick();
            confirmButton.value?.focus();
        } else if (dialog.value.open) {
            dialog.value.close();
        }
    },
    { flush: 'post' },
);
</script>

<template>
    <dialog ref="dialog" class="modal" aria-labelledby="confirm-title" @close="confirmState.open && settleConfirm(false)">
        <div class="modal-box max-w-sm">
            <div class="flex gap-4">
                <div class="grid size-10 shrink-0 place-items-center rounded-full" :class="confirmState.danger ? 'bg-error/10 text-error' : 'bg-primary/10 text-primary'">
                    <TriangleAlert class="size-5" aria-hidden="true" />
                </div>
                <div class="min-w-0">
                    <h3 id="confirm-title" class="font-semibold">{{ confirmState.title || t('common.confirmDelete') }}</h3>
                    <p v-if="confirmState.message" class="mt-1 text-sm break-words text-base-content/70">{{ confirmState.message }}</p>
                </div>
            </div>
            <div class="modal-action mt-5">
                <button type="button" class="btn" @click="settleConfirm(false)">{{ t('common.cancel') }}</button>
                <button
                    ref="confirmButton"
                    type="button"
                    class="btn"
                    :class="confirmState.danger ? 'btn-error' : 'btn-primary'"
                    @click="settleConfirm(true)"
                >
                    {{ confirmState.confirmLabel || t('common.delete') }}
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="submit">{{ t('common.close') }}</button>
        </form>
    </dialog>
</template>
