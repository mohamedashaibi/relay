<script setup>
// Native select dressed down to read like plain text until hovered — keyboard and screen reader friendly.
defineProps({
    modelValue: { type: [String, Number, null], default: null },
    label: { type: String, required: true },
});
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <label class="group relative flex min-h-9 items-center rounded-md transition-colors duration-150 ease-snap hover:bg-sunken focus-within:bg-sunken">
        <span class="sr-only">{{ label }}</span>
        <span class="pointer-events-none flex min-w-0 flex-1 items-center gap-2 px-2 text-[14px]"><slot /></span>
        <svg class="pointer-events-none mr-2 text-ink-3 opacity-0 transition-opacity duration-150 group-hover:opacity-100" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
        <select
            class="absolute inset-0 w-full cursor-pointer opacity-0"
            :value="modelValue ?? ''"
            @change="emit('update:modelValue', $event.target.value === '' ? null : $event.target.value)"
        >
            <slot name="options" />
        </select>
    </label>
</template>
