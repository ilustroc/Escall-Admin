import { reactive } from 'vue';

export function useConfirm() {
    let resolvePending;
    const confirmation = reactive({
        open: false,
        title: 'Confirmar acción',
        message: '¿Deseas continuar?',
        confirmText: 'Confirmar',
        tone: 'danger',
    });

    function ask(options = {}) {
        Object.assign(confirmation, options, { open: true });
        return new Promise((resolve) => {
            resolvePending = resolve;
        });
    }

    function answer(value) {
        confirmation.open = false;
        resolvePending?.(value);
        resolvePending = undefined;
    }

    return { confirmation, ask, confirm: () => answer(true), cancel: () => answer(false) };
}
