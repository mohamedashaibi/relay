<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Avatar from '../components/Avatar.vue';
import Icon from '../components/Icon.vue';
import { api, problem } from '../lib/api';
import { SWATCHES } from '../lib/format';
import { session } from '../lib/session';
import { forgetMembers, loadProjects, toast } from '../lib/store';

const route = useRoute();
const router = useRouter();
const key = computed(() => String(route.params.key).toUpperCase());

const project = ref(null);
const form = ref({ name: '', description: '', color: '' });
const saving = ref(false);

async function load() {
    const { data } = await api.get(`/projects/${key.value}`);
    project.value = data.data;
    form.value = { name: data.data.name, description: data.data.description ?? '', color: data.data.color };
}
watch(key, load, { immediate: true });

const canManage = computed(() => ['owner', 'admin'].includes(project.value?.role));
const isOwner = computed(() => project.value?.role === 'owner');

async function save() {
    saving.value = true;
    try {
        await api.patch(`/projects/${key.value}`, { ...form.value, description: form.value.description || null });
        await Promise.all([load(), loadProjects()]);
        toast('Project updated', 'success');
    } catch (e) {
        toast(problem(e).message, 'error');
    } finally {
        saving.value = false;
    }
}

// Members
const invite = ref({ email: '', role: 'member' });
const inviteError = ref('');
async function addMember() {
    inviteError.value = '';
    try {
        await api.post(`/projects/${key.value}/members`, invite.value);
        forgetMembers(key.value);
        invite.value = { email: '', role: 'member' };
        await load();
        toast('Member added', 'success');
    } catch (e) {
        const p = problem(e);
        inviteError.value = p.fields.email || p.message;
    }
}
async function changeRole(member, role) {
    try {
        await api.post(`/projects/${key.value}/members`, { email: member.email, role });
        forgetMembers(key.value);
        await load();
    } catch (e) {
        toast(problem(e).message, 'error');
    }
}
const removing = ref(null);
async function removeMember(member) {
    if (removing.value !== member.id) return (removing.value = member.id);
    try {
        await api.delete(`/projects/${key.value}/members/${member.id}`);
        forgetMembers(key.value);
        removing.value = null;
        if (member.id === session.user.id) {
            await loadProjects();
            return router.push('/projects');
        }
        await load();
    } catch (e) {
        toast(problem(e).message, 'error');
    }
}

// Danger zone
async function toggleArchive() {
    try {
        await api.patch(`/projects/${key.value}`, { archived: !project.value.archived });
        await Promise.all([load(), loadProjects()]);
        toast(project.value.archived ? 'Project archived' : 'Project restored');
    } catch (e) {
        toast(problem(e).message, 'error');
    }
}
const confirmKey = ref('');
async function destroy() {
    try {
        await api.delete(`/projects/${key.value}`);
        await loadProjects();
        toast('Project deleted');
        router.push('/projects');
    } catch (e) {
        toast(problem(e).message, 'error');
    }
}
</script>

<template>
    <div v-if="project" class="mx-auto max-w-[760px] px-5 pt-10 pb-24 sm:px-10 lg:pt-14">
        <RouterLink :to="`/projects/${key}`" class="inline-flex items-center gap-1.5 text-[13px] font-medium text-ink-2 hover:text-ink">
            <Icon name="arrow-left" :size="15" /> Back to board
        </RouterLink>
        <h1 class="mt-5 text-[clamp(32px,4.4vw,48px)] leading-none font-extrabold tracking-[-0.045em]">Settings</h1>
        <p class="mt-3 text-ink-2">{{ project.name }} · <span class="font-mono text-[13px]">{{ project.key }}</span></p>

        <!-- General -->
        <section class="mt-14" aria-labelledby="general-h">
            <h2 id="general-h" class="text-xl font-bold">General</h2>
            <form class="mt-6 space-y-5" @submit.prevent="save">
                <fieldset :disabled="!canManage" class="space-y-5">
                    <div>
                        <label for="ps-name" class="label">Name</label>
                        <input id="ps-name" v-model="form.name" class="field" required maxlength="120" />
                    </div>
                    <div>
                        <label for="ps-desc" class="label">Description</label>
                        <textarea id="ps-desc" v-model="form.description" rows="3" class="field" maxlength="2000" />
                    </div>
                    <fieldset>
                        <legend class="label">Color</legend>
                        <div class="flex gap-2">
                            <label v-for="c in SWATCHES" :key="c" class="relative size-8 rounded-lg" :style="{ background: c }">
                                <input v-model="form.color" type="radio" :value="c" class="peer absolute inset-0 cursor-pointer opacity-0" :aria-label="`Color ${c}`" />
                                <span class="pointer-events-none absolute inset-0 rounded-lg ring-2 ring-transparent ring-offset-2 ring-offset-canvas peer-checked:ring-ink peer-focus-visible:ring-brand" />
                            </label>
                        </div>
                    </fieldset>
                </fieldset>
                <p v-if="!canManage" class="text-[13px] text-ink-3">Only owners and admins can change project settings.</p>
                <button v-else class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save changes' }}</button>
            </form>
        </section>

        <!-- Members -->
        <section class="mt-16" aria-labelledby="members-h">
            <h2 id="members-h" class="text-xl font-bold">Members <span class="font-sans text-base font-normal text-ink-3">{{ project.members.length }}</span></h2>
            <p class="mt-1 text-[14px] text-ink-2">Members can see and edit every task here. AI assistants act with the permissions of the person who connected them.</p>

            <form v-if="canManage" class="mt-6 flex flex-wrap items-start gap-2" @submit.prevent="addMember">
                <div class="min-w-56 flex-1">
                    <label for="invite-email" class="sr-only">Email</label>
                    <input id="invite-email" v-model="invite.email" type="email" class="field" placeholder="teammate@company.com" required :aria-invalid="!!inviteError" aria-describedby="invite-error" />
                    <p v-if="inviteError" id="invite-error" class="mt-1.5 text-[13px] text-danger">{{ inviteError }}</p>
                </div>
                <label for="invite-role" class="sr-only">Role</label>
                <select id="invite-role" v-model="invite.role" class="field w-auto">
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                </select>
                <button class="btn btn-primary">Add</button>
            </form>

            <ul class="mt-6 divide-y divide-line border-y border-line">
                <li v-for="m in project.members" :key="m.id" class="flex flex-wrap items-center gap-3 py-3.5">
                    <Avatar :person="m" :size="32" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[14px] font-semibold">{{ m.name }} <span v-if="m.id === session.user.id" class="font-normal text-ink-3">(you)</span></p>
                        <p class="truncate text-[13px] text-ink-3">{{ m.email }}</p>
                    </div>
                    <span v-if="m.role === 'owner' || !canManage" class="text-[13px] font-medium text-ink-2 capitalize">{{ m.role }}</span>
                    <template v-else>
                        <label :for="`role-${m.id}`" class="sr-only">Role for {{ m.name }}</label>
                        <select :id="`role-${m.id}`" :value="m.role" class="field h-9 w-auto py-0 text-[13px]" @change="changeRole(m, $event.target.value)">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                        </select>
                    </template>
                    <button
                        v-if="m.role !== 'owner' && (canManage || m.id === session.user.id)"
                        class="btn btn-sm"
                        :class="removing === m.id ? 'bg-danger text-white' : 'btn-danger'"
                        @click="removeMember(m)"
                        @blur="removing = null"
                    >
                        {{ removing === m.id ? 'Confirm' : m.id === session.user.id ? 'Leave' : 'Remove' }}
                    </button>
                </li>
            </ul>
        </section>

        <!-- Danger zone -->
        <section v-if="canManage" class="mt-16" aria-labelledby="danger-h">
            <h2 id="danger-h" class="text-xl font-bold">Archive or delete</h2>
            <div class="mt-6 space-y-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="max-w-md">
                        <p class="font-semibold">{{ project.archived ? 'Restore project' : 'Archive project' }}</p>
                        <p class="text-[14px] text-ink-2">Archived projects leave the sidebar but keep every task. AI assistants won’t list them by default.</p>
                    </div>
                    <button class="btn btn-outline" @click="toggleArchive"><Icon name="archive" :size="16" /> {{ project.archived ? 'Restore' : 'Archive' }}</button>
                </div>
                <form v-if="isOwner" class="rounded-xl bg-danger-soft p-5" @submit.prevent="destroy">
                    <p class="font-semibold text-danger">Delete permanently</p>
                    <p class="mt-1 text-[14px] text-ink-2">Removes the project, its tasks, comments and history. This can’t be undone.</p>
                    <label for="confirm-key" class="label mt-4">Type <span class="font-mono">{{ project.key }}</span> to confirm</label>
                    <div class="flex flex-wrap gap-2">
                        <input id="confirm-key" v-model="confirmKey" class="field w-40 bg-surface font-mono uppercase" autocomplete="off" />
                        <button class="btn bg-danger text-white hover:opacity-90" :disabled="confirmKey.toUpperCase() !== project.key">Delete project</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</template>
