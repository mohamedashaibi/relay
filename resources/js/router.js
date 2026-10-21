import { createRouter, createWebHistory } from 'vue-router';
import { session } from './lib/session';

const routes = [
    { path: '/login', component: () => import('./pages/Login.vue'), meta: { guest: true, title: 'Sign in' } },
    { path: '/register', component: () => import('./pages/Register.vue'), meta: { guest: true, title: 'Create account' } },
    { path: '/forgot-password', component: () => import('./pages/ForgotPassword.vue'), meta: { guest: true, title: 'Forgot password' } },
    { path: '/reset-password/:token', component: () => import('./pages/ResetPassword.vue'), meta: { guest: true, title: 'Reset password' } },
    {
        path: '/',
        component: () => import('./layouts/AppShell.vue'),
        children: [
            { path: '', name: 'home', component: () => import('./pages/Dashboard.vue'), meta: { title: 'Home' } },
            { path: 'reviews', name: 'reviews', component: () => import('./pages/Reviews.vue'), meta: { title: 'Reviews' } },
            { path: 'projects', name: 'projects', component: () => import('./pages/Projects.vue'), meta: { title: 'Projects' } },
            { path: 'projects/:key', name: 'board', component: () => import('./pages/ProjectBoard.vue'), meta: { title: 'Board' } },
            { path: 'projects/:key/settings', name: 'project-settings', component: () => import('./pages/ProjectSettings.vue'), meta: { title: 'Project settings' } },
            { path: 'tasks/:key', name: 'task', component: () => import('./pages/TaskPage.vue'), meta: { title: 'Task' } },
            { path: 'account', name: 'account', component: () => import('./pages/Account.vue'), meta: { title: 'Account settings' } },
            { path: 'connections', name: 'connections', component: () => import('./pages/Connections.vue'), meta: { title: 'AI connections' } },
            { path: ':pathMatch(.*)*', component: () => import('./pages/NotFound.vue'), meta: { title: 'Not found' } },
        ],
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: (to, from, saved) => saved ?? (to.path === from.path ? false : { top: 0 }),
});

router.beforeEach((to) => {
    if (!to.meta.guest && !session.user) return { path: '/login', query: { next: to.fullPath } };
    if (to.meta.guest && session.user) return '/';
});

router.afterEach((to) => {
    document.title = `${to.query.task ? `${to.query.task} · ` : ''}${to.meta.title ?? ''} · Relay`;
});
