<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AuthLayout from '../layouts/AuthLayout.vue';
import { problem } from '../lib/api';
import { login } from '../lib/session';

const route = useRoute();
const router = useRouter();
const form = ref({ email: '', password: '', remember: true });
const errors = ref({});
const message = ref('');
const busy = ref(false);

function safeNext(next) {
    return typeof next === 'string' && next.startsWith('/') && !next.startsWith('//') ? next : '/';
}

async function submit() {
    busy.value = true;
    errors.value = {};
    message.value = '';
    try {
        const { redirect } = await login(form.value);
        // Server-side intended URL (e.g. an OAuth consent screen) wins over SPA routes.
        if (redirect) location.assign(redirect);
        else router.replace(safeNext(route.query.next));
    } catch (e) {
        const p = problem(e);
        errors.value = p.fields;
        if (!Object.keys(p.fields).length) message.value = p.message;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <AuthLayout>
        <h2 class="text-[34px] font-extrabold tracking-[-0.04em]">Welcome back.</h2>
        <p class="mt-2 text-ink-2">Sign in to pick up where you — or your AI — left off.</p>

        <form class="mt-9 space-y-5" novalidate @submit.prevent="submit">
            <p v-if="route.query.reset && !message" class="rounded-lg bg-brand-soft px-3 py-2.5 text-[14px] text-ink" role="status">Your password was reset. Sign in with the new one.</p>
            <p v-if="message" class="rounded-lg bg-danger-soft px-3 py-2.5 text-[14px] text-danger" role="alert">{{ message }}</p>
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="email" class="field" required autofocus :aria-invalid="!!errors.email" aria-describedby="email-error" />
                <p v-if="errors.email" id="email-error" class="mt-1.5 text-[13px] text-danger">{{ errors.email }}</p>
            </div>
            <div>
                <div class="flex items-baseline justify-between">
                    <label for="password" class="label">Password</label>
                    <RouterLink to="/forgot-password" class="text-[13px] font-medium text-brand hover:underline">Forgot password?</RouterLink>
                </div>
                <input id="password" v-model="form.password" type="password" autocomplete="current-password" class="field" required :aria-invalid="!!errors.password" />
                <p v-if="errors.password" class="mt-1.5 text-[13px] text-danger">{{ errors.password }}</p>
            </div>
            <label class="flex items-center gap-2 text-[14px] text-ink-2">
                <input v-model="form.remember" type="checkbox" class="size-4 accent-[var(--brand)]" /> Keep me signed in
            </label>
            <button class="btn btn-primary h-11 w-full text-[15px]" :disabled="busy">{{ busy ? 'Signing in…' : 'Sign in' }}</button>
        </form>

        <p class="mt-8 text-[14px] text-ink-2">New to Relay? Accounts are by invite: ask a project owner or admin to add your email.</p>
    </AuthLayout>
</template>
