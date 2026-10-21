<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
    width: { type: String, default: 'max-w-lg' },
});
const emit = defineEmits(['close']);
const panel = ref(null);
let returnFocus = null;

function onKey(e) {
    if (e.key === 'Escape') emit('close');
    if (e.key === 'Tab' && panel.value) {
        // Keep focus inside the dialog.
        const items = [...panel.value.querySelectorAll('a,button,input,select,textarea,[tabindex]:not([tabindex="-1"])')].filter((el) => !el.disabled);
        if (!items.length) return;
        const [first, last] = [items[0], items[items.length - 1]];
        if (e.shiftKey && document.activeElement === first) (e.preventDefault(), last.focus());
        else if (!e.shiftKey && document.activeElement === last) (e.preventDefault(), first.focus());
    }
}

watch(
    () => props.open,
    async (open) => {
        if (open) {
            returnFocus = document.activeElement;
            document.addEventListener('keydown', onKey);
            await nextTick();
            panel.value?.querySelector('[autofocus],input,textarea,select,button')?.focus();
        } else {
            document.removeEventListener('keydown', onKey);
            returnFocus?.focus?.();
        }
    },
    { immediate: true },
);
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <Transition name="scrim">
            <div v-if="open" class="fixed inset-0 z-40 bg-[#0b1110]/40 backdrop-blur-[2px]" @click="emit('close')" />
        </Transition>
        <Transition name="pop">
            <div v-if="open" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 pt-[12vh] pb-8" @click.self="emit('close')">
                <div ref="panel" role="dialog" aria-modal="true" :aria-label="title" class="w-full rounded-2xl bg-surface p-6 ring-1 ring-line sm:p-7" :class="width">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <h2 class="text-xl font-bold">{{ title }}</h2>
                        <button class="btn btn-quiet btn-sm -mt-1 -mr-2 w-8 px-0" aria-label="Close" @click="emit('close')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
