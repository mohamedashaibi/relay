<script setup>
import { computed } from 'vue';
import { statusMeta } from '../lib/format';

const props = defineProps({
    status: { type: String, required: true },
    label: { type: Boolean, default: false },
});
const meta = computed(() => statusMeta(props.status));
</script>

<template>
    <span class="inline-flex items-center gap-1.5 text-[13px] font-medium whitespace-nowrap text-ink-2">
        <!-- Ring for open, filled for done, half for review: shape carries meaning, not just color. -->
        <svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" :style="{ color: meta.color }">
            <circle cx="6" cy="6" r="4.75" fill="none" stroke="currentColor" stroke-width="1.5" :stroke-dasharray="status === 'backlog' ? '2 2' : null" />
            <path v-if="status === 'in_progress'" d="M6 2.5a3.5 3.5 0 0 1 0 7z" fill="currentColor" />
            <path v-else-if="status === 'in_review'" d="M6 2.5a3.5 3.5 0 1 1-3.5 3.5H6z" fill="currentColor" />
            <circle v-else-if="status === 'done'" cx="6" cy="6" r="3.5" fill="currentColor" />
        </svg>
        <span :class="label ? '' : 'sr-only'">{{ meta.label }}</span>
    </span>
</template>
