<script setup>
import { computed } from 'vue';
import { hue, initials } from '../lib/format';

const props = defineProps({
    person: { type: Object, default: null },
    size: { type: Number, default: 24 },
});

const style = computed(() => {
    const h = hue(props.person?.id ?? 0);
    return {
        width: `${props.size}px`,
        height: `${props.size}px`,
        fontSize: `${Math.max(9, Math.round(props.size * 0.4))}px`,
        background: `oklch(0.9 0.06 ${h})`,
        color: `oklch(0.35 0.09 ${h})`,
    };
});
</script>

<template>
    <span
        v-if="person"
        class="inline-flex shrink-0 items-center justify-center rounded-full font-semibold tracking-tight"
        :style="style"
        :title="person.name"
    >
        {{ initials(person.name) }}
        <span class="sr-only">{{ person.name }}</span>
    </span>
    <span
        v-else
        class="inline-flex shrink-0 items-center justify-center rounded-full border border-dashed border-line-strong text-ink-3"
        :style="{ width: `${size}px`, height: `${size}px` }"
        title="Unassigned"
    >
        <span class="sr-only">Unassigned</span>
    </span>
</template>
