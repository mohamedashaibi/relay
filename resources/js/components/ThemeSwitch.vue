<script setup>
import { ref } from 'vue';
import Icon from './Icon.vue';

const read = () => {
    try {
        return localStorage.getItem('relay.theme') || 'system';
    } catch {
        return 'system';
    }
};
const theme = ref(read());

function set(value) {
    theme.value = value;
    try {
        value === 'system' ? localStorage.removeItem('relay.theme') : localStorage.setItem('relay.theme', value);
    } catch {}
    if (value === 'system') delete document.documentElement.dataset.theme;
    else document.documentElement.dataset.theme = value;
}

const OPTIONS = [
    { value: 'system', icon: 'monitor', label: 'Match system' },
    { value: 'light', icon: 'sun', label: 'Light' },
    { value: 'dark', icon: 'moon', label: 'Dark' },
];
</script>

<template>
    <div class="inline-flex rounded-lg bg-sunken p-0.5" role="radiogroup" aria-label="Theme">
        <button
            v-for="o in OPTIONS"
            :key="o.value"
            role="radio"
            :aria-checked="theme === o.value"
            :aria-label="o.label"
            :title="o.label"
            class="flex size-8 items-center justify-center rounded-md transition-colors duration-150 ease-snap"
            :class="theme === o.value ? 'bg-surface text-ink' : 'text-ink-3 hover:text-ink'"
            @click="set(o.value)"
        >
            <Icon :name="o.icon" :size="15" />
        </button>
    </div>
</template>
