<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { api, problem } from '../lib/api';
import { ago, due, memberLabel, PRIORITIES, STATUSES } from '../lib/format';
import { renderMarkdown } from '../lib/markdown';
import { session } from '../lib/session';
import { projectMembers, toast, touch } from '../lib/store';
import ActivityItem from './ActivityItem.vue';
import AgentChip from './AgentChip.vue';
import AttachmentPanel from './AttachmentPanel.vue';
import Avatar from './Avatar.vue';
import FieldSelect from './FieldSelect.vue';
import Icon from './Icon.vue';
import PriorityMark from './PriorityMark.vue';
import StatusDot from './StatusDot.vue';

const props = defineProps({
    taskKey: { type: String, required: true },
    inDrawer: { type: Boolean, default: false },
});
const emit = defineEmits(['close']);

const task = ref(null);
const members = ref([]);
const loading = ref(true);
const missing = ref(false);
const busy = ref(false);

async function load(quiet = false) {
    if (!quiet) loading.value = true;
    try {
        const { data } = await api.get(`/tasks/${props.taskKey}`);
        task.value = data.data;
        missing.value = false;
        members.value = await projectMembers(data.data.project.key);
    } catch (e) {
        if (e.response?.status === 404) missing.value = true;
        else toast(problem(e).message, 'error');
    } finally {
        loading.value = false;
    }
}
watch(() => props.taskKey, () => load(), { immediate: true });

/** Run a mutation, then refresh this panel and every list that shows tasks. */
async function mutate(request, success) {
    busy.value = true;
    try {
        await request();
        if (success) toast(success, 'success');
        await load(true);
        touch();
        return true;
    } catch (e) {
        toast(problem(e).message, 'error');
        await load(true);
        return false;
    } finally {
        busy.value = false;
    }
}

const patch = (fields) => mutate(() => api.patch(`/tasks/${task.value.key}`, fields));
const setStatus = (status) => mutate(() => api.post(`/tasks/${task.value.key}/status`, { status }));

// ── Title ──────────────────────────────────────────────
const titleDraft = ref('');
watch(task, (t) => t && (titleDraft.value = t.title), { immediate: true });
function saveTitle() {
    const v = titleDraft.value.trim();
    if (!v) return (titleDraft.value = task.value.title);
    if (v !== task.value.title) patch({ title: v });
}

// ── Description ────────────────────────────────────────
const editingDesc = ref(false);
const descDraft = ref('');
const descEl = ref(null);
async function editDescription() {
    descDraft.value = task.value.description ?? '';
    editingDesc.value = true;
    await nextTick();
    descEl.value?.focus();
}
async function saveDescription() {
    if ((descDraft.value || null) !== (task.value.description || null)) await patch({ description: descDraft.value || null });
    editingDesc.value = false;
}

// ── Labels ─────────────────────────────────────────────
const labelDraft = ref('');
function addLabel() {
    const v = labelDraft.value.trim().toLowerCase().replace(/,$/, '');
    labelDraft.value = '';
    if (v && !task.value.labels.includes(v)) patch({ labels: [...task.value.labels, v] });
}
const removeLabel = (l) => patch({ labels: task.value.labels.filter((x) => x !== l) });

// ── Review hand-off ────────────────────────────────────
const reviewForm = ref(false);
const reviewSummary = ref('');
const reviewerId = ref(null);
const decisionComment = ref('');
const otherMembers = computed(() => members.value.filter((m) => m.id !== session.user?.id));

function openReviewForm() {
    reviewerId.value = task.value.reviewer?.id ?? null;
    reviewSummary.value = '';
    reviewForm.value = true;
}
async function submitReview() {
    const ok = await mutate(
        () => api.post(`/tasks/${task.value.key}/review-request`, { summary: reviewSummary.value, reviewer_id: reviewerId.value ? Number(reviewerId.value) : null }),
        'Sent for review',
    );
    if (ok) reviewForm.value = false;
}
async function resolve(decision) {
    const ok = await mutate(
        () => api.post(`/tasks/${task.value.key}/review`, { decision, comment: decisionComment.value || null }),
        decision === 'approve' ? 'Approved and closed' : 'Sent back with changes',
    );
    if (ok) decisionComment.value = '';
}

// ── Comments ───────────────────────────────────────────
const comment = ref('');
async function postComment() {
    if (!comment.value.trim()) return;
    const ok = await mutate(() => api.post(`/tasks/${task.value.key}/comments`, { body: comment.value }));
    if (ok) comment.value = '';
}

// ── Delete (two-step, no browser confirm) ──────────────
const confirmDelete = ref(false);
async function remove() {
    if (!confirmDelete.value) return (confirmDelete.value = true);
    try {
        await api.delete(`/tasks/${task.value.key}`);
        toast(`Deleted ${task.value.key}`);
        touch();
        emit('close');
    } catch (e) {
        toast(problem(e).message, 'error');
    }
}

function copyLink() {
    navigator.clipboard?.writeText(`${location.origin}/tasks/${task.value.key}`);
    toast('Link copied');
}

const showAllActivity = ref(false);
const activity = computed(() => (showAllActivity.value ? task.value.activity : task.value.activity.slice(0, 6)));
const dueInfo = computed(() => due(task.value?.due_date, task.value?.status));
const isMine = computed(() => task.value?.assignee?.id === session.user?.id);
const canManageProject = computed(() => ['owner', 'admin'].includes(members.value.find((m) => m.id === session.user?.id)?.role));
</script>

<template>
    <div v-if="loading" class="space-y-4 p-6 sm:p-8" aria-busy="true">
        <div class="h-4 w-24 animate-pulse rounded bg-sunken" />
        <div class="h-8 w-3/4 animate-pulse rounded bg-sunken" />
        <div class="h-32 animate-pulse rounded bg-sunken" />
    </div>

    <div v-else-if="missing" class="p-8">
        <p class="font-display text-xl font-bold">Task not found</p>
        <p class="mt-1 text-ink-2">It may have been deleted, or you’re not a member of its project.</p>
    </div>

    <article v-else-if="task" class="flex min-h-full flex-col" :aria-busy="busy">
        <!-- Header -->
        <header class="sticky top-0 z-10 flex items-center gap-2 border-b border-line bg-surface/90 px-5 py-3 backdrop-blur sm:px-8">
            <span class="size-2.5 rounded-sm" :style="{ background: task.project.color }" aria-hidden="true" />
            <RouterLink :to="`/projects/${task.project.key}`" class="text-[13px] font-medium text-ink-2 hover:text-ink">{{ task.project.name }}</RouterLink>
            <span class="text-ink-3" aria-hidden="true">/</span>
            <span class="task-key">{{ task.key }}</span>
            <div class="ml-auto flex items-center gap-1">
                <button class="btn btn-quiet btn-sm w-8 px-0" aria-label="Copy link to task" @click="copyLink"><Icon name="link" :size="16" /></button>
                <RouterLink v-if="inDrawer" :to="`/tasks/${task.key}`" class="btn btn-quiet btn-sm w-8 px-0" aria-label="Open full page"><Icon name="external" :size="16" /></RouterLink>
                <button v-if="task.permissions.delete" class="btn btn-sm" :class="confirmDelete ? 'bg-danger text-white' : 'btn-quiet w-8 px-0'" :aria-label="confirmDelete ? 'Confirm delete' : 'Delete task'" @click="remove" @blur="confirmDelete = false">
                    <Icon v-if="!confirmDelete" name="trash" :size="16" /><template v-else>Delete?</template>
                </button>
                <button v-if="inDrawer" class="btn btn-quiet btn-sm w-8 px-0" aria-label="Close" @click="emit('close')"><Icon name="x" :size="18" /></button>
            </div>
        </header>

        <div class="flex-1 px-5 pt-6 pb-10 sm:px-8">
            <!-- Title -->
            <label class="sr-only" for="task-title">Title</label>
            <textarea
                id="task-title"
                v-model="titleDraft"
                rows="1"
                class="field-sizing-content w-full resize-none bg-transparent font-display text-[26px] leading-tight font-bold tracking-[-0.03em] text-ink outline-none sm:text-[30px]"
                @keydown.enter.prevent="$event.target.blur()"
                @blur="saveTitle"
            />

            <!-- Properties -->
            <dl class="mt-5 grid grid-cols-[96px_1fr] items-center gap-x-3 gap-y-0.5 text-[14px] sm:grid-cols-[110px_1fr]">
                <dt class="text-ink-3">Status</dt>
                <dd>
                    <FieldSelect label="Status" :model-value="task.status" @update:model-value="setStatus">
                        <StatusDot :status="task.status" label />
                        <template #options><option v-for="s in STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option></template>
                    </FieldSelect>
                </dd>

                <dt class="text-ink-3">Priority</dt>
                <dd>
                    <FieldSelect label="Priority" :model-value="task.priority" @update:model-value="(v) => patch({ priority: v })">
                        <PriorityMark :priority="task.priority" label />
                        <template #options><option v-for="p in PRIORITIES" :key="p.value" :value="p.value">{{ p.label }}</option></template>
                    </FieldSelect>
                </dd>

                <dt class="text-ink-3">Assignee</dt>
                <dd>
                    <FieldSelect label="Assignee" :model-value="task.assignee?.id ?? null" @update:model-value="(v) => patch({ assignee_id: v ? Number(v) : null })">
                        <Avatar :person="task.assignee" :size="20" />
                        <span :class="task.assignee ? 'text-ink' : 'text-ink-3'">{{ task.assignee?.name ?? 'Unassigned' }}</span>
                        <template #options>
                            <option value="">Unassigned</option>
                            <option v-for="m in members" :key="m.id" :value="m.id">{{ memberLabel(m, session.user?.id) }}</option>
                        </template>
                    </FieldSelect>
                </dd>

                <dt class="text-ink-3">Reviewer</dt>
                <dd>
                    <FieldSelect label="Reviewer" :model-value="task.reviewer?.id ?? null" @update:model-value="(v) => patch({ reviewer_id: v ? Number(v) : null })">
                        <Avatar :person="task.reviewer" :size="20" />
                        <span :class="task.reviewer ? 'text-ink' : 'text-ink-3'">{{ task.reviewer?.name ?? 'Anyone on the project' }}</span>
                        <template #options>
                            <option value="">Anyone on the project</option>
                            <option v-for="m in members" :key="m.id" :value="m.id">{{ memberLabel(m, session.user?.id) }}</option>
                        </template>
                    </FieldSelect>
                </dd>

                <dt class="text-ink-3"><label for="task-due">Due</label></dt>
                <dd class="flex items-center">
                    <input
                        id="task-due"
                        type="date"
                        :value="task.due_date"
                        class="h-9 rounded-md bg-transparent px-2 text-[14px] transition-colors duration-150 ease-snap hover:bg-sunken focus:bg-sunken focus:outline-none"
                        :class="dueInfo?.overdue ? 'text-danger' : task.due_date ? 'text-ink' : 'text-ink-3'"
                        @change="patch({ due_date: $event.target.value || null })"
                    />
                    <span v-if="dueInfo?.overdue" class="text-[12px] font-semibold text-danger">Overdue</span>
                </dd>

                <dt class="text-ink-3"><label for="task-label">Labels</label></dt>
                <dd class="flex flex-wrap items-center gap-1.5 px-2 py-1.5">
                    <span v-for="l in task.labels" :key="l" class="inline-flex items-center gap-1 rounded-md bg-sunken py-0.5 pr-1 pl-2 text-[12px] font-medium text-ink-2">
                        {{ l }}
                        <button class="rounded p-0.5 text-ink-3 hover:text-ink" :aria-label="`Remove label ${l}`" @click="removeLabel(l)"><Icon name="x" :size="12" /></button>
                    </span>
                    <input
                        id="task-label"
                        v-model="labelDraft"
                        placeholder="Add label"
                        class="w-24 bg-transparent text-[13px] text-ink placeholder:text-ink-3 focus:outline-none"
                        maxlength="40"
                        @keydown.enter.prevent="addLabel"
                        @keydown.,.prevent="addLabel"
                    />
                </dd>
            </dl>

            <!-- Review hand-off: the reason this product exists -->
            <section v-if="task.status === 'in_review'" class="mt-7 rounded-xl bg-signal-soft p-5" aria-labelledby="review-heading">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 id="review-heading" class="text-[15px] font-bold text-signal">Needs review</h3>
                    <span class="text-[13px] text-ink-2">{{ task.reviewer ? `by ${task.reviewer.name}` : 'by anyone on the project' }}</span>
                </div>
                <div v-if="task.review_note" class="prose-relay mt-3" v-html="renderMarkdown(task.review_note)" />
                <template v-if="task.permissions.review">
                    <label for="decision-comment" class="sr-only">Feedback</label>
                    <textarea id="decision-comment" v-model="decisionComment" rows="2" class="field mt-4 bg-surface" placeholder="Feedback (required to request changes)" />
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button class="btn btn-primary" :disabled="busy" @click="resolve('approve')"><Icon name="check" :size="16" /> Approve &amp; close</button>
                        <button class="btn btn-outline bg-surface" :disabled="busy || !decisionComment.trim()" @click="resolve('request_changes')">Request changes</button>
                    </div>
                </template>
            </section>

            <section v-else-if="task.status !== 'done'" class="mt-6">
                <button v-if="!reviewForm" class="btn btn-outline btn-sm" @click="openReviewForm">
                    <Icon name="review" :size="16" /> {{ isMine ? 'I’m done — request review' : 'Request review' }}
                </button>
                <Transition name="pop">
                    <form v-if="reviewForm" class="rounded-xl bg-sunken p-4" @submit.prevent="submitReview">
                        <label for="review-summary" class="label">What should the reviewer know?</label>
                        <textarea id="review-summary" v-model="reviewSummary" rows="3" class="field" required minlength="3" placeholder="What changed, how you verified it, what to look at." />
                        <div class="mt-3 flex flex-wrap items-end gap-3">
                            <div class="min-w-48 flex-1">
                                <label for="review-reviewer" class="label">Reviewer</label>
                                <select id="review-reviewer" v-model="reviewerId" class="field">
                                    <option :value="null">Anyone on the project</option>
                                    <option v-for="m in otherMembers" :key="m.id" :value="m.id">{{ memberLabel(m) }}</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-quiet" @click="reviewForm = false">Cancel</button>
                            <button class="btn btn-primary" :disabled="busy || reviewSummary.trim().length < 3">Send for review</button>
                        </div>
                    </form>
                </Transition>
            </section>

            <!-- Description -->
            <section class="mt-8" aria-labelledby="desc-heading">
                <div class="mb-2 flex items-center justify-between">
                    <h3 id="desc-heading" class="eyebrow">Description</h3>
                    <button v-if="!editingDesc && task.description" class="btn btn-quiet btn-sm" @click="editDescription">Edit</button>
                </div>
                <div v-if="editingDesc">
                    <label for="task-desc" class="sr-only">Description (Markdown)</label>
                    <textarea id="task-desc" ref="descEl" v-model="descDraft" rows="8" class="field font-mono text-[13px] leading-relaxed" placeholder="Markdown supported" @keydown.meta.enter="saveDescription" @keydown.ctrl.enter="saveDescription" @keydown.esc.stop="editingDesc = false" />
                    <div class="mt-2 flex justify-end gap-2">
                        <button class="btn btn-quiet btn-sm" @click="editingDesc = false">Cancel</button>
                        <button class="btn btn-primary btn-sm" :disabled="busy" @click="saveDescription">Save</button>
                    </div>
                </div>
                <div v-else-if="task.description" class="prose-relay" v-html="renderMarkdown(task.description)" />
                <button v-else class="w-full rounded-lg border border-dashed border-line-strong px-4 py-5 text-left text-[14px] text-ink-3 transition-colors duration-150 ease-snap hover:border-ink-3 hover:text-ink-2" @click="editDescription">
                    Add context, acceptance criteria, links… your AI reads this too.
                </button>
            </section>

            <!-- Attachments: context for people and for the assistant -->
            <div class="mt-8">
                <AttachmentPanel target="task" :ref-key="task.key" :can-manage="canManageProject" @changed="load(true), touch()" />
            </div>

            <!-- Discussion -->
            <section class="mt-10" aria-labelledby="comments-heading">
                <h3 id="comments-heading" class="eyebrow mb-4">Discussion <span class="ml-1 text-ink-3">{{ task.comments.length || '' }}</span></h3>
                <TransitionGroup name="list" tag="ol" class="relative space-y-5">
                    <li v-for="c in task.comments" :key="c.id" class="flex gap-3">
                        <Avatar :person="c.author" :size="28" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[14px] font-semibold">{{ c.author?.name ?? 'Deleted user' }}</span>
                                <AgentChip v-if="c.channel === 'mcp'" :client="c.client" />
                                <time class="text-[12px] text-ink-3" :datetime="c.created_at">{{ ago(c.created_at) }}</time>
                            </div>
                            <div class="prose-relay mt-1" v-html="renderMarkdown(c.body)" />
                        </div>
                    </li>
                </TransitionGroup>
                <form class="mt-5 flex gap-3" @submit.prevent="postComment">
                    <Avatar :person="session.user" :size="28" />
                    <div class="flex-1">
                        <label for="new-comment" class="sr-only">Write a comment</label>
                        <textarea id="new-comment" v-model="comment" rows="2" class="field" placeholder="Write a comment… (⌘/Ctrl + Enter to send)" @keydown.meta.enter="postComment" @keydown.ctrl.enter="postComment" />
                        <div class="mt-2 flex justify-end">
                            <button class="btn btn-primary btn-sm" :disabled="busy || !comment.trim()">Comment</button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Activity -->
            <section class="mt-10" aria-labelledby="activity-heading">
                <h3 id="activity-heading" class="eyebrow mb-4">Activity</h3>
                <ol class="space-y-4">
                    <li v-for="a in activity" :key="a.id"><ActivityItem :item="a" /></li>
                </ol>
                <button v-if="task.activity.length > 6 && !showAllActivity" class="btn btn-quiet btn-sm mt-3" @click="showAllActivity = true">Show all {{ task.activity.length }}</button>
                <p class="mt-6 text-[12px] text-ink-3">Created by {{ task.reporter?.name ?? 'someone' }} {{ ago(task.created_at) }}</p>
            </section>
        </div>
    </article>
</template>
