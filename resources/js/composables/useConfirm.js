import { reactive } from 'vue';

// Single app-wide confirm dialog (rendered once by <ConfirmDialog /> in the layout).
// Replaces window.confirm() / sweetalert:  if (await confirm({ title, message })) { ... }
export const confirmState = reactive({
    open: false,
    title: '',
    message: '',
    confirmLabel: '',
    danger: true,
    resolve: null,
});

export function useConfirm() {
    return ({ title = '', message = '', confirmLabel = '', danger = true } = {}) =>
        new Promise((resolve) => {
            confirmState.resolve?.(false);
            Object.assign(confirmState, { open: true, title, message, confirmLabel, danger, resolve });
        });
}

export function settleConfirm(result) {
    confirmState.resolve?.(result);
    Object.assign(confirmState, { open: false, resolve: null });
}
