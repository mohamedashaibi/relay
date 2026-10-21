<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AttachmentPanel from '../components/AttachmentPanel.vue';
import Icon from '../components/Icon.vue';
import Modal from '../components/Modal.vue';
import TaskCard from '../components/TaskCard.vue';
import { api, problem } from '../lib/api';
import { PRIORITIES, STATUSES } from '../lib/format';
import { session } from '../lib/session';
import { store, toast, touch } from '../lib/store';

const route = useRoute();
const key = computed(() => String(route.params.key).toUpperCase());
const project = computed(() => store.projects.find((p) => p.key === key.value));

const tasks = ref([]);
const loaded = ref(false);
const filesOpen = ref(false);
const missing = ref(false);

async function load() {
    try {
        const { data } = await api.get('/tasks', { params: { project: key.value, limit: 500 } });
        tasks.value = data.data;
        missing.value = false;
    } catch (e) {
        if (e.response?.status === 404) missing.value = true;
    } finally {
        loaded.value = true;
    }
}
watch(key, load, { immediate: true });
watch(() => store.version, () => !dragging.value && load());

// ── Filters (client-side; the board already holds every task) ──
const q = ref('');
const mine = ref(false);
const priority = ref('');
const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();
    return tasks.value.filter(
        (t) =>
            (!term || t.title.toLowerCase().includes(term) || t.key.toLowerCase().includes(term) || t.labels.some((l) => l.includes(term))) &&
            (!mine.value || t.assignee?.id === session.user?.id) &&
            (!priority.value || t.priority === priority.value),
    );
});
const columns = computed(() =>
    STATUSES.map((s) => ({ ...s, tasks: filtered.value.filter((t) => t.status === s.value).sort((a, b) => a.position - b.position) })),
);
const openCount = computed(() => tasks.value.filter((t) => t.status !== 'done').length);
const reviewCount = computed(() => tasks.value.filter((t) => t.status === 'in_review').length);

// ── Drag and drop ──
const dragging = ref(null);
const over = ref(null); // { status, index }

function onDragStart(e, task) {
    dragging.value = task;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', task.key);
}

function onDragOver(e, status) {
    if (!dragging.value) return;
    e.preventDefault();
    const cards = [...e.currentTarget.querySelectorAll('[data-card]')].filter((el) => el.dataset.card !== dragging.value.key);
    const index = cards.findIndex((el) => {
        const r = el.getBoundingClientRect();
        return e.clientY < r.top + r.height / 2;
    });
    over.value = { status, index: index === -1 ? cards.length : index };
}

/** Column items plus a drop marker at the hovered slot. `over.index` counts cards excluding the dragged one. */
function withMarker(col) {
    const items = col.tasks.map((t) => ({ id: t.id, task: t }));
    if (over.value?.status !== col.value) return items;
    const anchor = col.tasks.filter((t) => t.id !== dragging.value?.id)[over.value.index];
    const at = anchor ? items.findIndex((i) => i.id === anchor.id) : items.length;
    items.splice(at, 0, { id: 'marker' });
    return items;
}

function onDragLeave(e) {
    if (!e.currentTarget.contains(e.relatedTarget)) over.value = null;
}

async function onDrop(status) {
    const task = dragging.value;
    const target = over.value;
    dragging.value = null;
    over.value = null;
    if (!task || !target) return;

    const list = columns.value.find((c) => c.value === status).tasks.filter((t) => t.id !== task.id);
    const prev = list[target.index - 1];
    const next = list[target.index];
    const position = prev && next ? (prev.position + next.position) / 2 : prev ? prev.position + 1024 : next ? next.position / 2 : 1024;

    if (task.status === status && task.position === position) return;

    const before = { status: task.status, position: task.position };
    Object.assign(task, { status, position });
    try {
        await api.post(`/tasks/${task.key}/status`, { status, position });
        touch();
    } catch (e) {
        Object.assign(task, before);
        toast(problem(e).message, 'error');
    }
}

function onDragEnd() {
    dragging.value = null;
    over.value = null;
}

// ── Quick add ──
const adding = ref(null);
const draft = ref('');
const addInput = ref(null);
async function startAdd(status) {
    adding.value = status;
    draft.value = '';
    await nextTick();
    addInput.value?.[0]?.focus();
}
async function quickAdd(status) {
    const title = draft.value.trim();
    if (!title) return (adding.value = null);
    draft.value = '';
    try {
        await api.post('/tasks', { project: key.value, title, status });
        touch();
    } catch (e) {
        toast(problem(e).message, 'error');
    }
}
</script>

<template>
    <div class="flex min-h-dvh flex-col">
        <div v-if="missing" class="px-5 pt-16 sm:px-10">
            <h1 class="text-4xl font-extrabold tracking-[-0.04em]">Project not found</h1>
            <p class="mt-2 text-ink-2">It doesn’t exist, or you’re not a member.</p>
            <RouterLink to="/projects" class="btn btn-outline mt-6">All projects</RouterLink>
        </div>

        <template v-else>
            <header class="px-5 pt-10 sm:px-10 lg:pt-12">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5">
                            <span class="size-3 rounded-[4px]" :style="{ background: project?.color ?? 'var(--line-strong)' }" aria-hidden="true" />
                            <span class="font-mono text-[12px] font-medium text-ink-3">{{ key }}</span>
                        </div>
                        <h1 class="mt-2 text-[clamp(32px,4.4vw,52px)] leading-none font-extrabold tracking-[-0.045em]">{{ project?.name ?? '…' }}</h1>
                        <p class="mt-3 text-[15px] text-ink-2">
                            <span class="font-semibold text-ink">{{ openCount }}</span> open
                            <template v-if="reviewCount"> · <span class="font-semibold text-signal">{{ reviewCount }}</span> needs review</template>
                            <template v-if="project?.description"> · {{ project.description }}</template>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn btn-outline" @click="filesOpen = true"><Icon name="paperclip" :size="16" /> Files</button>
                        <RouterLink :to="`/projects/${key}/settings`" class="btn btn-outline"><Icon name="sliders" :size="16" /> Settings</RouterLink>
                        <button class="btn btn-primary" @click="store.newTask = { project: key }"><Icon name="plus" :size="16" /> New task</button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mt-8 flex flex-wrap items-center gap-2">
                    <label class="relative">
                        <span class="sr-only">Filter tasks</span>
                        <Icon name="search" :size="15" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-ink-3" />
                        <input v-model="q" class="field h-9 w-56 py-0 pl-9 text-[14px]" placeholder="Filter by title, key, label" />
                    </label>
                    <button class="btn btn-sm h-9" :class="mine ? 'bg-ink text-canvas' : 'btn-outline'" :aria-pressed="mine" @click="mine = !mine">Assigned to me</button>
                    <label class="sr-only" for="prio-filter">Priority</label>
                    <select id="prio-filter" v-model="priority" class="field h-9 w-auto py-0 text-[14px]">
                        <option value="">Any priority</option>
                        <option v-for="p in PRIORITIES" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                    <span class="ml-auto hidden text-[12px] text-ink-3 md:inline">Drag cards between columns · open a card to change status from the keyboard</span>
                </div>
            </header>

            <!-- Board -->
            <div class="mt-6 flex flex-1 snap-x gap-4 overflow-x-auto px-5 pb-10 sm:px-10" :class="!loaded ? 'opacity-0' : 'transition-opacity duration-300 ease-enter'">
                <section
                    v-for="col in columns"
                    :key="col.value"
                    class="flex w-[300px] shrink-0 snap-start flex-col rounded-2xl p-2 transition-colors duration-200 ease-snap xl:w-auto xl:min-w-[230px] xl:flex-1"
                    :class="over?.status === col.value ? 'bg-brand-soft' : col.value === 'in_review' ? 'bg-signal-soft/60' : 'bg-sunken/60'"
                    :aria-label="`${col.label}, ${col.tasks.length} tasks`"
                    @dragover="onDragOver($event, col.value)"
                    @dragleave="onDragLeave"
                    @drop.prevent="onDrop(col.value)"
                >
                    <h2 class="flex h-10 items-center gap-2 px-2 font-sans text-[13px] font-bold tracking-normal" :class="col.value === 'in_review' ? 'text-signal' : 'text-ink'">
                        <span class="size-2 rounded-full" :style="{ background: col.color }" aria-hidden="true" />
                        {{ col.label }}
                        <span class="font-medium text-ink-3">{{ col.tasks.length }}</span>
                        <button class="ml-auto rounded-md p-1 text-ink-3 transition-colors hover:bg-surface hover:text-ink" :aria-label="`Add task to ${col.label}`" @click="startAdd(col.value)">
                            <Icon name="plus" :size="15" />
                        </button>
                    </h2>

                    <TransitionGroup tag="ul" name="list" class="relative flex min-h-24 flex-1 flex-col gap-2">
                        <li v-for="item in withMarker(col)" :key="item.id" :class="item.task ? '' : 'h-0.5 rounded-full bg-brand'" :aria-hidden="item.task ? undefined : 'true'"
                            :data-card="item.task?.key" :draggable="!!item.task" @dragstart="item.task && onDragStart($event, item.task)" @dragend="onDragEnd">
                            <TaskCard v-if="item.task" :task="item.task" :dragging="dragging?.id === item.task.id" />
                        </li>

                        <li v-if="adding === col.value" key="adder">
                            <label :for="`add-${col.value}`" class="sr-only">New task title</label>
                            <textarea
                                :id="`add-${col.value}`"
                                ref="addInput"
                                v-model="draft"
                                rows="2"
                                class="field resize-none text-[14px]"
                                placeholder="Task title — Enter to add, Esc to finish"
                                @keydown.enter.prevent="quickAdd(col.value)"
                                @keydown.esc.stop="adding = null"
                                @blur="!draft.trim() && (adding = null)"
                            />
                        </li>
                    </TransitionGroup>

                    <p v-if="!col.tasks.length && adding !== col.value && !over" class="px-2 pb-3 text-[13px] text-ink-3">
                        {{ col.value === 'in_review' ? 'Work marked ready lands here.' : 'No tasks' }}
                    </p>
                </section>
            </div>
        </template>

        <Modal :open="filesOpen" title="Project files" width="max-w-2xl" @close="filesOpen = false">
            <p class="-mt-2 mb-5 text-[14px] text-ink-2">Briefs, specs, brand guides — context that applies to the whole project. Assistants see these when they open the project.</p>
            <AttachmentPanel v-if="filesOpen" target="project" :ref-key="key" :can-manage="['owner', 'admin'].includes(project?.role)" />
        </Modal>
    </div>
</template>
