<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../lib/api';
import { store } from '../lib/store';
import StatusDot from './StatusDot.vue';

const router = useRouter();
const query = ref('');
const tasks = ref([]);
const active = ref(0);
const input = ref(null);

const ACTIONS = [
    { id: 'new-task', label: 'Create task', hint: 'C', run: () => (store.newTask = {}) },
    { id: 'new-project', label: 'Create project', run: () => (store.newProject = true) },
    { id: 'home', label: 'Go to Home', run: () => router.push('/') },
    { id: 'reviews', label: 'Go to Reviews', run: () => router.push('/reviews') },
    { id: 'projects', label: 'Go to Projects', run: () => router.push('/projects') },
    { id: 'connections', label: 'Connect an AI assistant', run: () => router.push('/connections') },
];

const items = computed(() => {
    const q = query.value.trim().toLowerCase();
    const actions = ACTIONS.filter((a) => !q || a.label.toLowerCase().includes(q)).map((a) => ({ ...a, group: 'Actions' }));
    const projects = store.projects
        .filter((p) => q && (p.name.toLowerCase().includes(q) || p.key.toLowerCase() === q))
        .map((p) => ({ id: `p${p.id}`, group: 'Projects', label: p.name, key: p.key, color: p.color, run: () => router.push(`/projects/${p.key}`) }));
    const found = tasks.value.map((t) => ({ id: `t${t.id}`, group: 'Tasks', label: t.title, key: t.key, status: t.status, run: () => router.push({ query: { task: t.key } }) }));
    return [...found, ...projects, ...actions];
});

let timer;
watch(query, (q) => {
    active.value = 0;
    clearTimeout(timer);
    if (q.trim().length < 2) return (tasks.value = []);
    timer = setTimeout(async () => {
        const isKey = /^[a-z][a-z0-9]{1,5}-\d+$/i.test(q.trim());
        if (isKey) {
            const { data } = await api.get(`/tasks/${q.trim()}`).catch(() => ({ data: null }));
            tasks.value = data ? [data.data] : [];
        } else {
            const { data } = await api.get('/tasks', { params: { q: q.trim(), limit: 8 } });
            tasks.value = data.data;
        }
    }, 160);
});

watch(
    () => store.palette,
    async (open) => {
        if (!open) return;
        query.value = '';
        tasks.value = [];
        await nextTick();
        input.value?.focus();
    },
);

function run(item) {
    store.palette = false;
    item?.run();
}

function onKey(e) {
    if (e.key === 'ArrowDown') (e.preventDefault(), (active.value = Math.min(active.value + 1, items.value.length - 1)));
    if (e.key === 'ArrowUp') (e.preventDefault(), (active.value = Math.max(active.value - 1, 0)));
    if (e.key === 'Enter') (e.preventDefault(), run(items.value[active.value]));
    if (e.key === 'Escape') store.palette = false;
}
</script>

<template>
    <Teleport to="body">
        <Transition name="scrim">
            <div v-if="store.palette" class="fixed inset-0 z-40 bg-[#0b1110]/40 backdrop-blur-[2px]" @click="store.palette = false" />
        </Transition>
        <Transition name="pop">
            <div v-if="store.palette" class="fixed inset-x-0 top-[14vh] z-50 mx-auto w-[min(640px,calc(100%-2rem))]" role="dialog" aria-modal="true" aria-label="Search and commands">
                <div class="overflow-hidden rounded-2xl bg-surface ring-1 ring-line">
                    <div class="flex items-center gap-3 border-b border-line px-4">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="text-ink-3" aria-hidden="true"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zm9 2-4-4" /></svg>
                        <input
                            ref="input"
                            v-model="query"
                            class="h-14 flex-1 bg-transparent text-base text-ink placeholder:text-ink-3 focus:outline-none"
                            placeholder="Search tasks, jump to a project, or run a command…"
                            role="combobox"
                            aria-expanded="true"
                            aria-controls="palette-list"
                            :aria-activedescendant="items[active] ? `pi-${items[active].id}` : undefined"
                            @keydown="onKey"
                        />
                        <span class="kbd">esc</span>
                    </div>
                    <ul id="palette-list" role="listbox" class="max-h-[50vh] overflow-y-auto p-2">
                        <template v-for="(item, i) in items" :key="item.id">
                            <li v-if="i === 0 || items[i - 1].group !== item.group" class="eyebrow px-3 pt-3 pb-1.5" role="presentation">{{ item.group }}</li>
                            <li
                                :id="`pi-${item.id}`"
                                role="option"
                                :aria-selected="i === active"
                                class="flex h-11 cursor-pointer items-center gap-3 rounded-lg px-3 text-[14px]"
                                :class="i === active ? 'bg-sunken text-ink' : 'text-ink-2'"
                                @mousemove="active = i"
                                @click="run(item)"
                            >
                                <StatusDot v-if="item.status" :status="item.status" />
                                <span v-else-if="item.color" class="size-2.5 rounded-sm" :style="{ background: item.color }" aria-hidden="true" />
                                <span v-if="item.key" class="task-key">{{ item.key }}</span>
                                <span class="flex-1 truncate font-medium">{{ item.label }}</span>
                                <span v-if="item.hint" class="kbd">{{ item.hint }}</span>
                            </li>
                        </template>
                        <li v-if="!items.length" class="px-3 py-8 text-center text-ink-3">Nothing matches “{{ query }}”.</li>
                    </ul>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
