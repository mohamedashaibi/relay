<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import TaskDetail from './TaskDetail.vue';

const route = useRoute();
const router = useRouter();
const key = computed(() => (typeof route.query.task === 'string' ? route.query.task : null));
const panel = ref(null);
let returnFocus = null;

function close() {
    const { task, ...rest } = route.query;
    router.push({ query: rest });
}

function onKey(e) {
    if (e.key === 'Escape' && !e.defaultPrevented && !['TEXTAREA', 'INPUT', 'SELECT'].includes(document.activeElement?.tagName)) close();
}

watch(key, async (k, prev) => {
    if (k && !prev) {
        returnFocus = document.activeElement;
        document.addEventListener('keydown', onKey);
        await nextTick();
        panel.value?.focus();
    } else if (!k && prev) {
        document.removeEventListener('keydown', onKey);
        returnFocus?.focus?.();
    }
}, { immediate: true });
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <Transition name="scrim">
            <div v-if="key" class="fixed inset-0 z-30 bg-[#0b1110]/25" @click="close" />
        </Transition>
        <Transition name="drawer">
            <aside
                v-if="key"
                ref="panel"
                tabindex="-1"
                role="dialog"
                aria-modal="true"
                :aria-label="`Task ${key}`"
                class="fixed inset-y-0 right-0 z-40 w-full overflow-y-auto bg-surface outline-none sm:max-w-[660px] sm:border-l sm:border-line"
            >
                <TaskDetail :task-key="key" in-drawer @close="close" />
            </aside>
        </Transition>
    </Teleport>
</template>
