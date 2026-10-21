<script setup>
defineProps({
    title: { type: String, required: true },
    body: { type: String, default: '' },
});
</script>

<template>
    <div class="py-10">
        <p class="font-display text-lg font-bold text-ink">{{ title }}</p>
        <p v-if="body" class="mt-1 max-w-md text-[14px] text-ink-2">{{ body }}</p>
        <div v-if="$slots.default" class="mt-4 flex flex-wrap gap-2"><slot /></div>
    </div>
</template>
