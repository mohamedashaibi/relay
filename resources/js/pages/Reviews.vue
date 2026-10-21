<script setup>
import { onMounted, ref, watch } from 'vue';
import Avatar from '../components/Avatar.vue';
import EmptyState from '../components/EmptyState.vue';
import { api, problem } from '../lib/api';
import { ago } from '../lib/format';
import { store, toast, touch } from '../lib/store';

const tab = ref('mine');
const tasks = ref(null);

async function load() {
    const params = tab.value === 'mine' ? { awaiting_my_review: 1 } : { status: 'in_review' };
    const { data } = await api.get('/tasks', { params: { ...params, limit: 200 } });
    tasks.value = data.data;
}
onMounted(load);
watch([tab, () => store.version], load);

const approving = ref(null);
async function approve(t) {
    approving.value = t.key;
    try {
        await api.post(`/tasks/${t.key}/review`, { decision: 'approve' });
        toast(`${t.key} approved`, 'success');
        touch();
    } catch (e) {
        toast(problem(e).message, 'error');
    } finally {
        approving.value = null;
    }
}
</script>

<template>
    <div class="mx-auto max-w-[920px] px-5 pt-10 pb-20 sm:px-10 lg:pt-16">
        <p class="eyebrow">Review queue</p>
        <h1 class="mt-3 text-[clamp(36px,5vw,56px)] leading-none font-extrabold tracking-[-0.045em]">Work waiting for a human.</h1>
        <p class="mt-4 max-w-xl text-[17px] font-light text-ink-2">Tasks land here when someone — or their AI assistant — marks them ready. Read the hand-off, then approve or send back.</p>

        <div class="mt-10 inline-flex rounded-lg bg-sunken p-1" role="tablist" aria-label="Filter reviews">
            <button
                v-for="t in [{ v: 'mine', l: 'Assigned to me' }, { v: 'all', l: 'All in review' }]"
                :key="t.v"
                role="tab"
                :aria-selected="tab === t.v"
                class="h-8 rounded-md px-3.5 text-[13px] font-semibold transition-colors duration-150 ease-snap"
                :class="tab === t.v ? 'bg-surface text-ink' : 'text-ink-2 hover:text-ink'"
                @click="tab = t.v"
            >
                {{ t.l }}
            </button>
        </div>

        <TransitionGroup v-if="tasks?.length" name="list" tag="ul" class="relative mt-8 divide-y divide-line">
            <li v-for="t in tasks" :key="t.id" class="py-6">
                <div class="flex flex-wrap items-start gap-x-4 gap-y-3">
                    <RouterLink :to="{ query: { task: t.key } }" class="min-w-0 flex-1 group">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="size-2 rounded-sm" :style="{ background: t.project.color }" aria-hidden="true" />
                            <span class="task-key">{{ t.key }}</span>
                            <span class="text-[12px] text-ink-3">updated {{ ago(t.updated_at) }}</span>
                        </div>
                        <p class="mt-1 font-display text-[20px] leading-snug font-bold tracking-[-0.02em] group-hover:text-brand">{{ t.title }}</p>
                        <p v-if="t.review_note" class="mt-2 line-clamp-3 max-w-2xl text-[14px] whitespace-pre-line text-ink-2">{{ t.review_note }}</p>
                        <div class="mt-3 flex items-center gap-2 text-[13px] text-ink-3">
                            <Avatar :person="t.assignee" :size="20" />
                            {{ t.assignee ? `Done by ${t.assignee.name}` : 'Unassigned' }}
                            <span v-if="t.reviewer" aria-hidden="true">·</span>
                            <span v-if="t.reviewer">Reviewer: {{ t.reviewer.name }}</span>
                        </div>
                    </RouterLink>
                    <div v-if="tab === 'mine'" class="flex gap-2">
                        <RouterLink :to="{ query: { task: t.key } }" class="btn btn-outline btn-sm">Open</RouterLink>
                        <button class="btn btn-primary btn-sm" :disabled="approving === t.key" @click="approve(t)">{{ approving === t.key ? 'Approving…' : 'Approve' }}</button>
                    </div>
                </div>
            </li>
        </TransitionGroup>

        <EmptyState
            v-else-if="tasks"
            class="mt-6"
            :title="tab === 'mine' ? 'Nothing needs your review.' : 'Nothing is in review.'"
            body="When work is marked “needs review” — from the board or by an AI assistant — it appears here with its hand-off notes."
        />
    </div>
</template>
