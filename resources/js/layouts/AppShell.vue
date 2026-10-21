<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import CommandPalette from '../components/CommandPalette.vue';
import Icon from '../components/Icon.vue';
import NewProjectModal from '../components/NewProjectModal.vue';
import NewTaskModal from '../components/NewTaskModal.vue';
import TaskDrawer from '../components/TaskDrawer.vue';
import ThemeSwitch from '../components/ThemeSwitch.vue';
import Toasts from '../components/Toasts.vue';
import Avatar from '../components/Avatar.vue';
import { logout, session } from '../lib/session';
import { loadProjects, loadReviewCount, store } from '../lib/store';

const route = useRoute();
const navOpen = ref(false);
const userMenu = ref(false);
const isMac = navigator.platform?.toUpperCase().includes('MAC');

watch(() => route.path, () => (navOpen.value = false));

function typing(e) {
    const el = e.target;
    return el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName);
}

function onKey(e) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        store.palette = !store.palette;
    } else if (e.key.toLowerCase() === 'c' && !e.metaKey && !e.ctrlKey && !e.altKey && !typing(e) && !store.palette && !store.newTask) {
        e.preventDefault();
        store.newTask = { project: route.name === 'board' ? route.params.key : undefined };
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKey);
    loadProjects().catch(() => {});
    loadReviewCount().catch(() => {});
});
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));

const NAV = [
    { to: '/', label: 'Home', icon: 'home', exact: true },
    { to: '/reviews', label: 'Reviews', icon: 'review', badge: true },
    { to: '/projects', label: 'Projects', icon: 'folder', exact: true },
    { to: '/connections', label: 'AI connections', icon: 'plug' },
];
const isActive = (item) => (item.exact ? route.path === item.to : route.path.startsWith(item.to));
</script>

<template>
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:rounded-lg focus:bg-surface focus:px-4 focus:py-2 focus:ring-2 focus:ring-brand">Skip to content</a>

    <!-- Mobile top bar -->
    <div class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-line bg-canvas/90 px-4 backdrop-blur lg:hidden">
        <button class="btn btn-quiet w-10 px-0" aria-label="Open navigation" :aria-expanded="navOpen" @click="navOpen = true"><Icon name="menu" :size="20" /></button>
        <RouterLink to="/" class="font-display text-xl font-extrabold tracking-[-0.04em]">Relay</RouterLink>
        <div class="ml-auto flex gap-1">
            <button class="btn btn-quiet w-10 px-0" aria-label="Search" @click="store.palette = true"><Icon name="search" :size="19" /></button>
            <button class="btn btn-primary w-10 px-0" aria-label="New task" @click="store.newTask = {}"><Icon name="plus" :size="19" /></button>
        </div>
    </div>

    <Transition name="scrim">
        <div v-if="navOpen" class="fixed inset-0 z-30 bg-[#0b1110]/40 lg:hidden" @click="navOpen = false" />
    </Transition>

    <div class="lg:grid lg:grid-cols-[264px_1fr]">
        <!-- Sidebar -->
        <nav
            aria-label="Main"
            class="fixed inset-y-0 left-0 z-40 flex w-[280px] flex-col border-r border-line bg-canvas px-4 pt-5 pb-4 transition-transform duration-300 ease-enter lg:sticky lg:top-0 lg:z-0 lg:h-dvh lg:w-auto lg:translate-x-0"
            :class="navOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center justify-between px-2">
                <RouterLink to="/" class="font-display text-[26px] leading-none font-extrabold tracking-[-0.05em]">
                    Relay<span class="text-brand">.</span>
                </RouterLink>
                <button class="btn btn-quiet w-9 px-0 lg:hidden" aria-label="Close navigation" @click="navOpen = false"><Icon name="x" :size="18" /></button>
            </div>

            <div class="mt-6 flex gap-2">
                <button class="flex h-10 flex-1 items-center gap-2.5 rounded-lg bg-surface px-3 text-[14px] text-ink-3 ring-1 ring-line transition-colors duration-150 ease-snap hover:text-ink-2 hover:ring-line-strong" @click="store.palette = true">
                    <Icon name="search" :size="16" /> Search
                    <span class="ml-auto flex gap-0.5"><span class="kbd">{{ isMac ? '⌘' : 'Ctrl' }}</span><span class="kbd">K</span></span>
                </button>
                <button class="btn btn-primary w-10 px-0" aria-label="New task (C)" title="New task (C)" @click="store.newTask = { project: route.name === 'board' ? route.params.key : undefined }">
                    <Icon name="plus" :size="18" />
                </button>
            </div>

            <ul class="mt-5 space-y-0.5">
                <li v-for="item in NAV" :key="item.to">
                    <RouterLink
                        :to="item.to"
                        class="flex h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium transition-colors duration-150 ease-snap"
                        :class="isActive(item) ? 'bg-surface text-ink ring-1 ring-line' : 'text-ink-2 hover:bg-sunken hover:text-ink'"
                        :aria-current="isActive(item) ? 'page' : undefined"
                    >
                        <Icon :name="item.icon" :size="17" />
                        {{ item.label }}
                        <span v-if="item.badge && store.reviewCount" class="ml-auto rounded-full bg-signal px-1.5 text-[11px] leading-5 font-bold text-[var(--surface)]">
                            {{ store.reviewCount }}<span class="sr-only"> tasks waiting for your review</span>
                        </span>
                    </RouterLink>
                </li>
            </ul>

            <div class="mt-8 flex items-center justify-between px-3">
                <span class="eyebrow">Projects</span>
                <button class="rounded p-1 text-ink-3 transition-colors hover:bg-sunken hover:text-ink" aria-label="New project" @click="store.newProject = true"><Icon name="plus" :size="15" /></button>
            </div>
            <ul class="mt-2 min-h-0 flex-1 space-y-0.5 overflow-y-auto">
                <li v-for="p in store.projects" :key="p.id">
                    <RouterLink
                        :to="`/projects/${p.key}`"
                        class="flex h-9 items-center gap-3 rounded-lg px-3 text-[14px] transition-colors duration-150 ease-snap"
                        :class="route.params.key === p.key ? 'bg-surface font-semibold text-ink ring-1 ring-line' : 'text-ink-2 hover:bg-sunken hover:text-ink'"
                    >
                        <span class="size-2.5 shrink-0 rounded-[3px]" :style="{ background: p.color }" aria-hidden="true" />
                        <span class="truncate">{{ p.name }}</span>
                        <span class="ml-auto font-mono text-[11px] text-ink-3">{{ p.key }}</span>
                    </RouterLink>
                </li>
                <li v-if="store.projectsLoaded && !store.projects.length" class="px-3 py-2 text-[13px] text-ink-3">No projects yet.</li>
            </ul>

            <!-- User -->
            <div class="relative mt-3 border-t border-line pt-3">
                <button class="flex w-full items-center gap-3 rounded-lg p-2 text-left transition-colors duration-150 ease-snap hover:bg-sunken" :aria-expanded="userMenu" aria-haspopup="true" @click="userMenu = !userMenu">
                    <Avatar :person="session.user" :size="30" />
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[14px] font-semibold">{{ session.user?.name }}</span>
                        <span class="block truncate text-[12px] text-ink-3">{{ session.user?.email }}</span>
                    </span>
                    <Icon name="chevron-down" :size="15" class="text-ink-3" />
                </button>
                <Transition name="pop">
                    <div v-if="userMenu" class="absolute right-0 bottom-full left-0 mb-2 rounded-xl bg-surface p-3 ring-1 ring-line" @keydown.esc="userMenu = false">
                        <div class="flex items-center justify-between">
                            <span class="text-[13px] font-medium text-ink-2">Theme</span>
                            <ThemeSwitch />
                        </div>
                        <RouterLink to="/account" class="btn btn-quiet mt-2 w-full justify-start" @click="userMenu = false"><Icon name="user" :size="16" /> Account &amp; email settings</RouterLink>
                        <button class="btn btn-quiet w-full justify-start" @click="logout"><Icon name="logout" :size="16" /> Sign out</button>
                    </div>
                </Transition>
            </div>
        </nav>

        <main id="main" class="min-w-0" @click="userMenu = false">
            <RouterView v-slot="{ Component, route: r }">
                <Transition name="page" mode="out-in">
                    <component :is="Component" :key="r.path" />
                </Transition>
            </RouterView>
        </main>
    </div>

    <TaskDrawer />
    <NewTaskModal />
    <NewProjectModal />
    <CommandPalette />
    <Toasts />
</template>
