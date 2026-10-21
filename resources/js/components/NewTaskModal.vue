<script setup>
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { api, problem } from '../lib/api';
import { memberLabel, PRIORITIES, STATUSES } from '../lib/format';
import { session } from '../lib/session';
import { projectMembers, store, toast, touch } from '../lib/store';
import Modal from './Modal.vue';

const router = useRouter();
const open = computed(() => store.newTask !== null);
const projects = computed(() => store.projects.filter((p) => !p.archived));

const blank = () => ({ project: '', title: '', description: '', status: 'todo', priority: 'medium', assignee_id: '', due_date: '' });
const form = ref(blank());
const members = ref([]);
const errors = ref({});
const saving = ref(false);
const another = ref(false);

watch(open, (isOpen) => {
    if (!isOpen) return;
    errors.value = {};
    form.value = {
        ...blank(),
        project: store.newTask.project ?? projects.value[0]?.key ?? '',
        status: store.newTask.status ?? 'todo',
        assignee_id: session.user?.id ?? '',
    };
});

watch(
    () => form.value.project,
    async (key) => {
        members.value = key ? await projectMembers(key).catch(() => []) : [];
        if (form.value.assignee_id && !members.value.some((m) => m.id === Number(form.value.assignee_id))) form.value.assignee_id = '';
    },
);

async function submit() {
    saving.value = true;
    errors.value = {};
    try {
        const payload = { ...form.value, assignee_id: form.value.assignee_id ? Number(form.value.assignee_id) : null, due_date: form.value.due_date || null, description: form.value.description || null };
        const { data } = await api.post('/tasks', payload);
        toast(`Created ${data.data.key}`, 'success');
        touch();
        if (another.value) {
            form.value = { ...form.value, title: '', description: '' };
        } else {
            store.newTask = null;
            router.push({ query: { task: data.data.key } });
        }
    } catch (e) {
        const p = problem(e);
        errors.value = p.fields;
        if (!Object.keys(p.fields).length) toast(p.message, 'error');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Modal :open="open" title="New task" width="max-w-xl" @close="store.newTask = null">
        <div v-if="!projects.length" class="text-ink-2">
            <p>Tasks live inside projects. Create your first project to get started.</p>
            <button class="btn btn-primary mt-4" @click="(store.newTask = null), (store.newProject = true)">New project</button>
        </div>
        <form v-else class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="nt-title" class="label">Title</label>
                <input id="nt-title" v-model="form.title" class="field text-base font-medium" maxlength="200" required autofocus placeholder="What needs to happen?" :aria-invalid="!!errors.title" />
                <p v-if="errors.title" class="mt-1 text-[13px] text-danger">{{ errors.title }}</p>
            </div>
            <div>
                <label for="nt-desc" class="label">Description <span class="font-normal text-ink-3">— Markdown, optional</span></label>
                <textarea id="nt-desc" v-model="form.description" rows="4" class="field" placeholder="Context and acceptance criteria. Your AI agent will read this." />
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div>
                    <label for="nt-project" class="label">Project</label>
                    <select id="nt-project" v-model="form.project" class="field">
                        <option v-for="p in projects" :key="p.id" :value="p.key">{{ p.name }}</option>
                    </select>
                </div>
                <div>
                    <label for="nt-status" class="label">Status</label>
                    <select id="nt-status" v-model="form.status" class="field">
                        <option v-for="s in STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                <div>
                    <label for="nt-priority" class="label">Priority</label>
                    <select id="nt-priority" v-model="form.priority" class="field">
                        <option v-for="p in PRIORITIES" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                </div>
                <div>
                    <label for="nt-assignee" class="label">Assignee</label>
                    <select id="nt-assignee" v-model="form.assignee_id" class="field" :aria-invalid="!!errors.assignee_id">
                        <option value="">Unassigned</option>
                        <option v-for="m in members" :key="m.id" :value="m.id">{{ memberLabel(m, session.user?.id) }}</option>
                    </select>
                </div>
                <div>
                    <label for="nt-due" class="label">Due date</label>
                    <input id="nt-due" v-model="form.due_date" type="date" class="field" />
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <label class="flex items-center gap-2 text-[13px] text-ink-2">
                    <input v-model="another" type="checkbox" class="size-4 accent-[var(--brand)]" /> Create another
                </label>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-quiet" @click="store.newTask = null">Cancel</button>
                    <button class="btn btn-primary" :disabled="saving || !form.title.trim()">{{ saving ? 'Creating…' : 'Create task' }}</button>
                </div>
            </div>
        </form>
    </Modal>
</template>
