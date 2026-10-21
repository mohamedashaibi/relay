<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AuthLayout from '../layouts/AuthLayout.vue';
import { api, problem } from '../lib/api';

const route = useRoute();
const router = useRouter();
const form = ref({ email: typeof route.query.email === 'string' ? route.query.email : '', password: '', confirm: '' });
const errors = ref({});
const message = ref('');
const busy = ref(false);

const mismatch = computed(() => form.value.confirm.length > 0 && form.value.confirm !== form.value.password);

async function submit() {
    if (mismatch.value) return;
    busy.value = true;
    errors.value = {};
    message.value = '';
    try {
        await api.post('/auth/reset-password', { token: route.params.token, email: form.value.email, password: form.value.password });
        router.replace({ path: '/login', query: { reset: '1' } });
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
        <h2 class="text-[34px] font-extrabold tracking-[-0.04em]">Choose a new password.</h2>
        <p class="mt-2 text-ink-2">You’ll be signed out everywhere else once it’s changed.</p>

        <form class="mt-9 space-y-5" novalidate @submit.prevent="submit">
            <p v-if="message" class="rounded-lg bg-danger-soft px-3 py-2.5 text-[14px] text-danger" role="alert">{{ message }}</p>
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="email" class="field" required :aria-invalid="!!errors.email" />
                <p v-if="errors.email" class="mt-1.5 text-[13px] text-danger" role="alert">
                    {{ errors.email }}
                    <RouterLink to="/forgot-password" class="font-semibold underline">Request a new link</RouterLink>
                </p>
            </div>
            <div>
                <label for="password" class="label">New password</label>
                <input id="password" v-model="form.password" type="password" autocomplete="new-password" class="field" required minlength="10" autofocus aria-describedby="pw-help" :aria-invalid="!!errors.password" />
                <p id="pw-help" class="mt-1.5 text-[13px]" :class="errors.password ? 'text-danger' : 'text-ink-3'">{{ errors.password || 'At least 10 characters, with letters and numbers.' }}</p>
            </div>
            <div>
                <label for="confirm" class="label">Confirm new password</label>
                <input id="confirm" v-model="form.confirm" type="password" autocomplete="new-password" class="field" required :aria-invalid="mismatch" />
                <p v-if="mismatch" class="mt-1.5 text-[13px] text-danger">The passwords don’t match.</p>
            </div>
            <button class="btn btn-primary h-11 w-full text-[15px]" :disabled="busy || mismatch || !form.password">{{ busy ? 'Saving…' : 'Set new password' }}</button>
        </form>
    </AuthLayout>
</template>
