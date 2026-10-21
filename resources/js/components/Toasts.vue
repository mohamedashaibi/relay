<script setup>
import { dismissToast, store } from '../lib/store';
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4 sm:right-6 sm:left-auto sm:items-end" role="status" aria-live="polite">
        <TransitionGroup name="pop">
            <button
                v-for="t in store.toasts"
                :key="t.id"
                class="pointer-events-auto flex max-w-sm items-center gap-3 rounded-xl bg-ink px-4 py-3 text-left text-sm font-medium text-canvas"
                @click="dismissToast(t.id)"
            >
                <span class="size-2 shrink-0 rounded-full" :class="t.tone === 'error' ? 'bg-[var(--danger)]' : t.tone === 'success' ? 'bg-[var(--st-done)]' : 'bg-[var(--brand)]'" aria-hidden="true" />
                {{ t.message }}
            </button>
        </TransitionGroup>
    </div>
</template>
