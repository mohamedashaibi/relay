<script setup>
import { ref } from 'vue';
import AuthLayout from '../layouts/AuthLayout.vue';
import { api, problem } from '../lib/api';

const email = ref('');
const busy = ref(false);
const sent = ref('');
const error = ref('');

async function submit() {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await api.post('/auth/forgot-password', { email: email.value });
        sent.value = data.message;
    } catch (e) {
        const p = problem(e);
        error.value = e.response?.status === 429 ? 'Too many requests. Wait a minute and try again.' : p.fields.email || p.message;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <AuthLayout>
        <h2 class="text-[34px] font-extrabold tracking-[-0.04em]">Forgot your password?</h2>
        <p class="mt-2 text-ink-2">Enter your email and we’ll send you a link to choose a new one.</p>

        <Transition name="pop" mode="out-in">
            <div v-if="sent" key="sent" class="mt-9 rounded-xl bg-brand-soft p-5" role="status">
                <p class="font-semibold text-ink">Check your email</p>
                <p class="mt-1 text-[14px] text-ink-2">{{ sent }}</p>
                <p class="mt-3 text-[13px] text-ink-3">The link expires in 60 minutes.</p>
            </div>
            <form v-else key="form" class="mt-9 space-y-5" novalidate @submit.prevent="submit">
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" v-model="email" type="email" autocomplete="email" class="field" required autofocus :aria-invalid="!!error" aria-describedby="email-error" />
                    <p v-if="error" id="email-error" class="mt-1.5 text-[13px] text-danger" role="alert">{{ error }}</p>
                </div>
                <button class="btn btn-primary h-11 w-full text-[15px]" :disabled="busy || !email">{{ busy ? 'Sending…' : 'Send reset link' }}</button>
            </form>
        </Transition>

        <p class="mt-8 text-[14px] text-ink-2">
            Remembered it?
            <RouterLink to="/login" class="font-semibold text-brand hover:underline">Back to sign in</RouterLink>
        </p>
    </AuthLayout>
</template>
