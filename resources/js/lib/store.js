import { reactive } from 'vue';
import { api } from './api';

/**
 * Small shared state: the project list (sidebar + pages), a change counter that
 * pages watch to refetch after a task is edited anywhere, and toasts.
 */
export const store = reactive({
    projects: [],
    projectsLoaded: false,
    reviewCount: 0,
    version: 0,
    toasts: [],
    // Global overlays: { project, status } opens the new-task dialog.
    newTask: null,
    newProject: false,
    palette: false,
});

const membersCache = new Map();
/** Project members for assignee/reviewer pickers, cached per project key. */
export async function projectMembers(key, fresh = false) {
    if (fresh || !membersCache.has(key)) {
        const { data } = await api.get(`/projects/${key}`);
        membersCache.set(key, data.data.members);
    }
    return membersCache.get(key);
}
export function forgetMembers(key) {
    membersCache.delete(key);
}

export async function loadProjects() {
    const { data } = await api.get('/projects');
    store.projects = data.data;
    store.projectsLoaded = true;
}

export async function loadReviewCount() {
    const { data } = await api.get('/tasks', { params: { awaiting_my_review: 1, limit: 100 } });
    store.reviewCount = data.data.length;
}

/** Call after any task mutation so open views refresh. */
export function touch() {
    store.version++;
    loadReviewCount().catch(() => {});
    loadProjects().catch(() => {});
}

let toastId = 0;
export function toast(message, tone = 'neutral') {
    const id = ++toastId;
    store.toasts.push({ id, message, tone });
    setTimeout(() => dismissToast(id), 3800);
}

export function dismissToast(id) {
    const i = store.toasts.findIndex((t) => t.id === id);
    if (i !== -1) store.toasts.splice(i, 1);
}
