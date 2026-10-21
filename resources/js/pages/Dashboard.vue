<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import ActivityItem from '../components/ActivityItem.vue';
import EmptyState from '../components/EmptyState.vue';
import Icon from '../components/Icon.vue';
import TaskRow from '../components/TaskRow.vue';
import { api } from '../lib/api';
import { firstName, greeting, STATUSES } from '../lib/format';
import { session } from '../lib/session';
import { store } from '../lib/store';

const data = ref(null);

async function load() {
    const res = await api.get('/dashboard');
    data.value = res.data;
}
onMounted(load);
watch(() => store.version, load);

const today = new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
const open = computed(() => {
    const s = data.value?.stats.by_status ?? {};
    return (s.todo ?? 0) + (s.in_progress ?? 0) + (s.in_review ?? 0);
});
const groups = computed(() =>
    ['in_progress', 'todo', 'in_review']
        .map((status) => ({ ...STATUSES.find((s) => s.value === status), tasks: (data.value?.my_tasks ?? []).filter((t) => t.status === status) }))
        .filter((g) => g.tasks.length),
);
const noProjects = computed(() => store.projectsLoaded && !store.projects.length);
</script>

<template>
    <div class="mx-auto max-w-[1180px] px-5 pt-10 pb-20 sm:px-10 lg:pt-16">
        <!-- Typographic hero -->
        <header class="max-w-3xl">
            <p class="eyebrow">{{ today }}</p>
            <h1 class="mt-3 text-[clamp(40px,6vw,72px)] leading-[0.95] font-extrabold tracking-[-0.05em]">
                {{ greeting() }}, {{ firstName(session.user?.name) }}.
            </h1>
            <p v-if="data && !noProjects" class="mt-5 text-[clamp(18px,2vw,22px)] leading-snug font-light text-ink-2">
                <template v-if="open || data.stats.awaiting_my_review">
                    You have <strong class="font-semibold text-ink">{{ open }} open {{ open === 1 ? 'task' : 'tasks' }}</strong><template v-if="data.stats.awaiting_my_review">, and
                        <RouterLink to="/reviews" class="font-semibold text-signal underline decoration-2 underline-offset-4 hover:decoration-4">{{ data.stats.awaiting_my_review }} waiting on your review</RouterLink></template><template v-if="data.stats.overdue">
                        — <span class="font-semibold text-danger">{{ data.stats.overdue }} overdue</span></template>.
                </template>
                <template v-else>Nothing assigned to you right now. A good moment to plan the next thing.</template>
            </p>
        </header>

        <!-- First run -->
        <section v-if="noProjects" class="mt-14 grid gap-10 md:grid-cols-2">
            <div>
                <p class="font-display text-[28px] leading-tight font-bold tracking-[-0.03em]">Create a project</p>
                <p class="mt-2 max-w-sm text-ink-2">Projects hold tasks. Each gets a short key, so tasks read like <span class="task-key text-[14px]">WEB-12</span> — easy for you and your AI to reference.</p>
                <button class="btn btn-primary mt-5" @click="store.newProject = true"><Icon name="plus" :size="16" /> New project</button>
            </div>
            <div>
                <p class="font-display text-[28px] leading-tight font-bold tracking-[-0.03em]">Connect your AI</p>
                <p class="mt-2 max-w-sm text-ink-2">Give Claude or ChatGPT access over MCP. It can fetch your tasks, update statuses and hand work back for review.</p>
                <RouterLink to="/connections" class="btn btn-outline mt-5"><Icon name="plug" :size="16" /> Set up a connection</RouterLink>
            </div>
        </section>

        <div v-else-if="data" class="mt-14 grid gap-14 lg:grid-cols-[minmax(0,1fr)_340px]">
            <!-- Your work -->
            <section aria-labelledby="work-heading">
                <div class="flex items-baseline justify-between">
                    <h2 id="work-heading" class="text-[22px] font-bold">Your work</h2>
                    <button class="btn btn-quiet btn-sm" @click="store.newTask = {}"><Icon name="plus" :size="15" /> New task</button>
                </div>
                <div v-for="(g, gi) in groups" :key="g.value" class="rise mt-7" :style="{ '--i': gi }">
                    <h3 class="mb-1.5 flex items-center gap-2 text-[13px] font-semibold text-ink-2">
                        <span class="size-1.5 rounded-full" :style="{ background: g.color }" aria-hidden="true" />{{ g.label }}
                        <span class="font-normal text-ink-3">{{ g.tasks.length }}</span>
                    </h3>
                    <ul>
                        <li v-for="t in g.tasks" :key="t.id"><TaskRow :task="t" /></li>
                    </ul>
                </div>
                <EmptyState v-if="!groups.length" title="You’re clear." body="Nothing is assigned to you. Pick something from a board, or ask your assistant to plan the week.">
                    <RouterLink to="/projects" class="btn btn-outline btn-sm">Browse projects</RouterLink>
                </EmptyState>

                <dl class="mt-14 grid grid-cols-3 gap-6 border-t border-line pt-8">
                    <div>
                        <dt class="text-[13px] text-ink-3">Done this week</dt>
                        <dd class="mt-1 font-display text-[40px] leading-none font-extrabold tracking-[-0.04em]">{{ data.stats.completed_this_week }}</dd>
                    </div>
                    <div>
                        <dt class="text-[13px] text-ink-3">In progress</dt>
                        <dd class="mt-1 font-display text-[40px] leading-none font-extrabold tracking-[-0.04em]">{{ data.stats.by_status.in_progress ?? 0 }}</dd>
                    </div>
                    <div>
                        <dt class="text-[13px] text-ink-3">Overdue</dt>
                        <dd class="mt-1 font-display text-[40px] leading-none font-extrabold tracking-[-0.04em]" :class="data.stats.overdue ? 'text-danger' : ''">{{ data.stats.overdue }}</dd>
                    </div>
                </dl>
            </section>

            <aside class="space-y-14">
                <section aria-labelledby="review-heading">
                    <div class="flex items-baseline justify-between">
                        <h2 id="review-heading" class="text-[22px] font-bold">Waiting on you</h2>
                        <RouterLink v-if="data.review_queue.length" to="/reviews" class="text-[13px] font-semibold text-brand hover:underline">All reviews</RouterLink>
                    </div>
                    <ul v-if="data.review_queue.length" class="mt-4 space-y-1">
                        <li v-for="t in data.review_queue" :key="t.id">
                            <RouterLink :to="{ query: { task: t.key } }" class="-mx-3 block rounded-lg px-3 py-2.5 transition-colors duration-150 ease-snap hover:bg-signal-soft">
                                <span class="task-key">{{ t.key }}</span>
                                <span class="mt-0.5 block font-medium text-ink">{{ t.title }}</span>
                                <span v-if="t.review_note" class="mt-1 line-clamp-2 block text-[13px] text-ink-2">{{ t.review_note }}</span>
                            </RouterLink>
                        </li>
                    </ul>
                    <p v-else class="mt-3 text-[14px] text-ink-3">No reviews assigned to you.</p>
                </section>

                <section aria-labelledby="activity-heading">
                    <h2 id="activity-heading" class="text-[22px] font-bold">Activity</h2>
                    <ol v-if="data.activity.length" class="mt-5 space-y-5">
                        <li v-for="a in data.activity" :key="a.id"><ActivityItem :item="a" show-task /></li>
                    </ol>
                    <p v-else class="mt-3 text-[14px] text-ink-3">Changes from you, teammates and AI agents show up here.</p>
                </section>
            </aside>
        </div>

        <div v-else class="mt-14 space-y-3" aria-busy="true">
            <div v-for="i in 5" :key="i" class="h-11 animate-pulse rounded-lg bg-sunken" />
        </div>
    </div>
</template>
