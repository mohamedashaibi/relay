<script setup>
import { computed, ref, watch } from 'vue';
import EmptyState from '../components/EmptyState.vue';
import Icon from '../components/Icon.vue';
import { api } from '../lib/api';
import { STATUSES } from '../lib/format';
import { store } from '../lib/store';

const showArchived = ref(false);
const archived = ref([]);

watch(showArchived, async (on) => {
    if (!on) return;
    const { data } = await api.get('/projects', { params: { archived: 1 } });
    archived.value = data.data.filter((p) => p.archived);
});

const total = (p) => Object.values(p.status_counts).reduce((a, b) => a + b, 0);
const projects = computed(() => (showArchived.value ? archived.value : store.projects));
</script>

<template>
    <div class="mx-auto max-w-[1080px] px-5 pt-10 pb-20 sm:px-10 lg:pt-16">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="eyebrow">Workspace</p>
                <h1 class="mt-3 text-[clamp(36px,5vw,56px)] leading-none font-extrabold tracking-[-0.045em]">Projects</h1>
            </div>
            <div class="flex items-center gap-2">
                <button class="btn btn-quiet btn-sm" :aria-pressed="showArchived" @click="showArchived = !showArchived">
                    <Icon name="archive" :size="15" /> {{ showArchived ? 'Show active' : 'Archived' }}
                </button>
                <button class="btn btn-primary" @click="store.newProject = true"><Icon name="plus" :size="16" /> New project</button>
            </div>
        </div>

        <ul v-if="projects.length" class="mt-12 divide-y divide-line border-y border-line">
            <li v-for="(p, i) in projects" :key="p.id" class="rise" :style="{ '--i': i }">
                <RouterLink :to="`/projects/${p.key}`" class="group grid gap-x-8 gap-y-3 py-7 sm:grid-cols-[minmax(0,1fr)_260px] sm:items-center">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <span class="size-3 rounded-[4px]" :style="{ background: p.color }" aria-hidden="true" />
                            <span class="font-mono text-[12px] font-medium text-ink-3">{{ p.key }}</span>
                            <span v-if="p.role !== 'member'" class="text-[11px] font-semibold text-ink-3 uppercase">{{ p.role }}</span>
                        </div>
                        <p class="mt-1.5 font-display text-[26px] leading-tight font-bold tracking-[-0.03em] transition-colors duration-150 ease-snap group-hover:text-brand">{{ p.name }}</p>
                        <p v-if="p.description" class="mt-1 line-clamp-1 max-w-xl text-[14px] text-ink-2">{{ p.description }}</p>
                    </div>
                    <div>
                        <!-- Segmented progress: one bar per status, width = share of tasks -->
                        <div class="flex h-1.5 overflow-hidden rounded-full bg-sunken" role="img" :aria-label="`${p.status_counts.done ?? 0} of ${total(p)} tasks done`">
                            <span v-for="s in STATUSES" :key="s.value" :style="{ width: total(p) ? `${((p.status_counts[s.value] ?? 0) / total(p)) * 100}%` : '0', background: s.color }" />
                        </div>
                        <p class="mt-2 flex justify-between text-[13px] text-ink-3">
                            <span>{{ (p.status_counts.done ?? 0) }}/{{ total(p) }} done<template v-if="p.status_counts.in_review"> · <span class="font-semibold text-signal">{{ p.status_counts.in_review }} in review</span></template></span>
                            <span>{{ p.member_count }} {{ p.member_count === 1 ? 'member' : 'members' }}</span>
                        </p>
                    </div>
                </RouterLink>
            </li>
        </ul>

        <EmptyState v-else-if="store.projectsLoaded" class="mt-8" :title="showArchived ? 'No archived projects.' : 'No projects yet.'" :body="showArchived ? '' : 'Create a project to start adding tasks.'">
            <button v-if="!showArchived" class="btn btn-primary btn-sm" @click="store.newProject = true">New project</button>
        </EmptyState>
    </div>
</template>
