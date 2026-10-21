<script setup>
import { computed } from 'vue';
import { priorityMeta } from '../lib/format';

const props = defineProps({
    priority: { type: String, required: true },
    label: { type: Boolean, default: false },
});
const meta = computed(() => priorityMeta(props.priority));
</script>

<template>
    <span class="inline-flex items-center gap-1.5 text-[13px] text-ink-2" :title="`${meta.label} priority`">
        <svg v-if="priority === 'urgent'" width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
            <rect width="14" height="14" rx="3.5" fill="var(--signal)" />
            <path d="M7 3.5v4.2M7 10.2v.1" stroke="var(--surface)" stroke-width="1.8" stroke-linecap="round" />
        </svg>
        <svg v-else width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
            <rect v-for="i in 3" :key="i" :x="(i - 1) * 5" :y="11 - i * 3" width="3.5" :height="i * 3 + 1" rx="1" :fill="i <= meta.bars ? 'var(--ink-2)' : 'var(--line-strong)'" />
        </svg>
        <span :class="label ? '' : 'sr-only'">{{ meta.label }}</span>
    </span>
</template>
