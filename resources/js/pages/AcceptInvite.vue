<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AuthLayout from '../layouts/AuthLayout.vue';
import { problem } from '../lib/api';
import { acceptInvite } from '../lib/session';

const route = useRoute();
const router = useRouter();
const email = typeof route.query.email === 'string' ? route.query.email : '';
const form = ref({ name: '', password: '', confirm: '' });
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
        await acceptInvite({ token: route.params.token, email, name: form.value.name, password: form.value.password });
        router.replace('/');
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
        <h2 class="text-[34px] font-extrabold tracking-[-0.04em]">You’re invited.</h2>
        <p class="mt-2 text-ink-2">Choose the name your teammates will see and a password for <strong class="font-semibold text-ink">{{ email || 'your account' }}</strong>.</p>

        <form class="mt-9 space-y-5" novalidate @submit.prevent="submit">
            <p v-if="message" class="rounded-lg bg-danger-soft px-3 py-2.5 text-[14px] text-danger" role="alert">{{ message }}</p>
            <p v-if="errors.email || errors.token" class="rounded-lg bg-danger-soft px-3 py-2.5 text-[14px] text-danger" role="alert">{{ errors.email || errors.token }}</p>
            <div>
                <label for="name" class="label">Your name</label>
                <input id="name" v-model="form.name" type="text" autocomplete="name" class="field" required minlength="2" maxlength="80" autofocus aria-describedby="name-error" :aria-invalid="!!errors.name" />
                <p v-if="errors.name" id="name-error" class="mt-1.5 text-[13px] text-danger" role="alert">{{ errors.name }}</p>
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" v-model="form.password" type="password" autocomplete="new-password" class="field" required minlength="10" aria-describedby="pw-help" :aria-invalid="!!errors.password" />
                <p id="pw-help" class="mt-1.5 text-[13px]" :class="errors.password ? 'text-danger' : 'text-ink-3'">{{ errors.password || 'At least 10 characters, with letters and numbers.' }}</p>
            </div>
            <div>
                <label for="confirm" class="label">Confirm password</label>
                <input id="confirm" v-model="form.confirm" type="password" autocomplete="new-password" class="field" required aria-describedby="confirm-error" :aria-invalid="mismatch" />
                <p v-if="mismatch" id="confirm-error" class="mt-1.5 text-[13px] text-danger">The passwords don’t match.</p>
            </div>
            <button class="btn btn-primary h-11 w-full text-[15px]" :disabled="busy || mismatch || !form.password || form.name.trim().length < 2">{{ busy ? 'Setting up…' : 'Join Relay' }}</button>
        </form>
    </AuthLayout>
</template>
