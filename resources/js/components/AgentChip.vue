<script setup>
// Marks anything done through an AI connection — the product's signature detail.
defineProps({ client: { type: String, default: null } });
</script>

<template>
    <span class="inline-flex items-center gap-1 rounded-full bg-signal-soft px-2 py-0.5 text-[11px] font-semibold text-signal">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2.5 14 9l6.5 2-6.5 2-2 6.5-2-6.5L3.5 11 10 9z" />
        </svg>
        via {{ client || 'AI agent' }}
    </span>
</template>
