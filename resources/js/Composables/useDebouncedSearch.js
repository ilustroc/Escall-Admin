import { onBeforeUnmount, watch } from 'vue';

export function useDebouncedSearch(source, callback, delay = 400) {
    let timer;
    const stop = watch(source, (value, oldValue) => {
        clearTimeout(timer);
        timer = setTimeout(() => callback(value, oldValue), delay);
    });

    onBeforeUnmount(() => {
        clearTimeout(timer);
        stop();
    });

    return stop;
}
