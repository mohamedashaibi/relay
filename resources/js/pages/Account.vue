<script setup>
import { onMounted, ref } from 'vue';
import { api, problem } from '../lib/api';
import { session } from '../lib/session';
import { toast } from '../lib/store';

const profile = ref({ name: session.user?.name ?? '', email: session.user?.email ?? '' });
const preferences = ref([]);
const savingName = ref(false);

onMounted(async () => {
    const { data } = await api.get('/account');
    profile.value = { name: data.user.name, email: data.user.email };
    preferences.value = data.email_preferences;
});

async function saveName() {
    savingName.value = true;
    try {
        const { data } = await api.patch('/account', { name: profile.value.name });
        session.user = { ...session.user, name: data.user.name };
        toast('Name updated', 'success');
    } catch (e) {
        toast(problem(e).fields.name || problem(e).message, 'error');
    } finally {
        savingName.value = false;
    }
}

// ── Password ──
const pw = ref({ current_password: '', password: '' });
const pwErrors = ref({});
const savingPw = ref(false);
async function changePassword() {
    savingPw.value = true;
    pwErrors.value = {};
    try {
        await api.put('/account/password', pw.value);
        pw.value = { current_password: '', password: '' };
        toast('Password changed — we’ve emailed you a confirmation', 'success');
    } catch (e) {
        const p = problem(e);
        pwErrors.value = Object.keys(p.fields).length ? p.fields : { current_password: p.message };
    } finally {
        savingPw.value = false;
    }
}

// ── Email preferences: each switch saves on its own ──
async function toggle(pref) {
    const next = !pref.enabled;
    pref.enabled = next;
    try {
        await api.put('/account/email-preferences', { preferences: { [pref.key]: next } });
    } catch (e) {
        pref.enabled = !next;
        toast(problem(e).message, 'error');
    }
}
</script>

<template>
    <div class="mx-auto max-w-[760px] px-5 pt-10 pb-24 sm:px-10 lg:pt-16">
        <p class="eyebrow">Account</p>
        <h1 class="mt-3 text-[clamp(36px,5vw,56px)] leading-none font-extrabold tracking-[-0.045em]">Settings</h1>

        <!-- Profile -->
        <section class="mt-14" aria-labelledby="profile-h">
            <h2 id="profile-h" class="text-xl font-bold">Profile</h2>
            <form class="mt-6 space-y-5" @submit.prevent="saveName">
                <div>
                    <label for="acc-name" class="label">Name</label>
                    <input id="acc-name" v-model="profile.name" class="field" required minlength="2" maxlength="80" autocomplete="name" />
                </div>
                <div>
                    <label for="acc-email" class="label">Email</label>
                    <input id="acc-email" :value="profile.email" class="field bg-sunken text-ink-2" readonly aria-describedby="acc-email-help" />
                    <p id="acc-email-help" class="mt-1.5 text-[13px] text-ink-3">Notifications and password resets go here.</p>
                </div>
                <button class="btn btn-primary" :disabled="savingName || profile.name.trim().length < 2">{{ savingName ? 'Saving…' : 'Save name' }}</button>
            </form>
        </section>

        <!-- Email notifications -->
        <section class="mt-16" aria-labelledby="email-h">
            <h2 id="email-h" class="text-xl font-bold">Email notifications</h2>
            <p class="mt-1 text-[14px] text-ink-2">You’re never emailed about your own changes — including ones your AI assistant makes for you. Security emails, like password changes, always send.</p>
            <ul class="mt-6 divide-y divide-line border-y border-line">
                <li v-for="pref in preferences" :key="pref.key" class="flex items-start justify-between gap-6 py-4">
                    <div>
                        <p :id="`pref-${pref.key}`" class="text-[15px] font-semibold">{{ pref.label }}</p>
                        <p class="mt-0.5 text-[13px] text-ink-3">{{ pref.description }}</p>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="pref.enabled"
                        :aria-labelledby="`pref-${pref.key}`"
                        class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 ease-snap"
                        :class="pref.enabled ? 'bg-brand' : 'bg-line-strong'"
                        @click="toggle(pref)"
                    >
                        <span class="inline-block size-5 rounded-full bg-white shadow-sm transition-transform duration-200 ease-snap" :class="pref.enabled ? 'translate-x-[22px]' : 'translate-x-0.5'" />
                    </button>
                </li>
            </ul>
        </section>

        <!-- Password -->
        <section class="mt-16" aria-labelledby="pw-h">
            <h2 id="pw-h" class="text-xl font-bold">Password</h2>
            <form class="mt-6 space-y-5" novalidate @submit.prevent="changePassword">
                <div>
                    <label for="pw-current" class="label">Current password</label>
                    <input id="pw-current" v-model="pw.current_password" type="password" autocomplete="current-password" class="field" required :aria-invalid="!!pwErrors.current_password" />
                    <p v-if="pwErrors.current_password" class="mt-1.5 text-[13px] text-danger" role="alert">{{ pwErrors.current_password }}</p>
                </div>
                <div>
                    <label for="pw-new" class="label">New password</label>
                    <input id="pw-new" v-model="pw.password" type="password" autocomplete="new-password" class="field" required minlength="10" aria-describedby="pw-new-help" :aria-invalid="!!pwErrors.password" />
                    <p id="pw-new-help" class="mt-1.5 text-[13px]" :class="pwErrors.password ? 'text-danger' : 'text-ink-3'">{{ pwErrors.password || 'At least 10 characters, with letters and numbers.' }}</p>
                </div>
                <button class="btn btn-primary" :disabled="savingPw || !pw.current_password || !pw.password">{{ savingPw ? 'Changing…' : 'Change password' }}</button>
                <p class="text-[13px] text-ink-3">Forgot your current password? Sign out and use “Forgot password?” on the sign-in page.</p>
            </form>
        </section>
    </div>
</template>
