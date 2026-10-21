<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AuthLayout from '../layouts/AuthLayout.vue';
import { problem } from '../lib/api';
import { register } from '../lib/session';

const route = useRoute();
const router = useRouter();
const form = ref({ name: '', email: '', password: '' });
const errors = ref({});
const message = ref('');
const busy = ref(false);

async function submit() {
    busy.value = true;
    errors.value = {};
    message.value = '';
    try {
        const { redirect } = await register(form.value);
        const next = route.query.next;
        if (redirect) location.assign(redirect);
        else router.replace(typeof next === 'string' && next.startsWith('/') && !next.startsWith('//') ? next : '/');
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
        <h2 class="text-[34px] font-extrabold tracking-[-0.04em]">Start with Relay.</h2>
        <p class="mt-2 text-ink-2">One account for you and every assistant you connect.</p>

        <form class="mt-9 space-y-5" novalidate @submit.prevent="submit">
            <p v-if="message" class="rounded-lg bg-danger-soft px-3 py-2.5 text-[14px] text-danger" role="alert">{{ message }}</p>
            <div>
                <label for="name" class="label">Your name</label>
                <input id="name" v-model="form.name" autocomplete="name" class="field" required autofocus :aria-invalid="!!errors.name" />
                <p v-if="errors.name" class="mt-1.5 text-[13px] text-danger">{{ errors.name }}</p>
            </div>
            <div>
                <label for="email" class="label">Work email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="email" class="field" required :aria-invalid="!!errors.email" />
                <p v-if="errors.email" class="mt-1.5 text-[13px] text-danger">{{ errors.email }}</p>
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" v-model="form.password" type="password" autocomplete="new-password" class="field" required minlength="10" aria-describedby="pw-help" :aria-invalid="!!errors.password" />
                <p id="pw-help" class="mt-1.5 text-[13px]" :class="errors.password ? 'text-danger' : 'text-ink-3'">{{ errors.password || 'At least 10 characters, with letters and numbers.' }}</p>
            </div>
            <button class="btn btn-primary h-11 w-full text-[15px]" :disabled="busy">{{ busy ? 'Creating account…' : 'Create account' }}</button>
        </form>

        <p class="mt-8 text-[14px] text-ink-2">
            Already have an account?
            <RouterLink :to="{ path: '/login', query: route.query }" class="font-semibold text-brand hover:underline">Sign in</RouterLink>
        </p>
    </AuthLayout>
</template>
