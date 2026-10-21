<script setup>
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { api, problem } from '../lib/api';
import { SWATCHES } from '../lib/format';
import { loadProjects, store, toast } from '../lib/store';
import Modal from './Modal.vue';

const router = useRouter();
const form = ref({ name: '', key: '', description: '', color: SWATCHES[0] });
const keyTouched = ref(false);
const errors = ref({});
const saving = ref(false);

watch(
    () => store.newProject,
    (open) => {
        if (!open) return;
        form.value = { name: '', key: '', description: '', color: SWATCHES[store.projects.length % SWATCHES.length] };
        keyTouched.value = false;
        errors.value = {};
    },
);

// Mirrors the server's key derivation so the user sees what their task keys will look like.
const suggestedKey = computed(() => {
    const words = form.value.name.split(/[^A-Za-z0-9]+/).filter(Boolean);
    if (!words.length) return '';
    const base = words.length > 1 ? words.slice(0, 4).map((w) => w[0]).join('') : words[0].slice(0, 3);
    return (/^[A-Za-z]/.test(base) ? base : `P${base}`).toUpperCase().slice(0, 5).padEnd(2, 'X');
});
watch(suggestedKey, (k) => {
    if (!keyTouched.value) form.value.key = k;
});

async function submit() {
    saving.value = true;
    errors.value = {};
    try {
        const { data } = await api.post('/projects', { ...form.value, key: form.value.key || null, description: form.value.description || null });
        await loadProjects();
        store.newProject = false;
        toast(`Project ${data.data.name} created`, 'success');
        router.push(`/projects/${data.data.key}`);
    } catch (e) {
        const p = problem(e);
        errors.value = p.fields;
        if (!Object.keys(p.fields).length) errors.value = { key: p.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Modal :open="store.newProject" title="New project" @close="store.newProject = false">
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="np-name" class="label">Name</label>
                <input id="np-name" v-model="form.name" class="field text-base font-medium" required maxlength="120" autofocus placeholder="Website relaunch" :aria-invalid="!!errors.name" />
                <p v-if="errors.name" class="mt-1 text-[13px] text-danger">{{ errors.name }}</p>
            </div>
            <div class="grid grid-cols-[1fr_auto] items-start gap-4">
                <div>
                    <label for="np-key" class="label">Key</label>
                    <input
                        id="np-key"
                        v-model="form.key"
                        class="field font-mono uppercase"
                        maxlength="6"
                        pattern="[A-Za-z][A-Za-z0-9]{1,5}"
                        aria-describedby="np-key-help"
                        :aria-invalid="!!errors.key"
                        @input="keyTouched = true"
                    />
                    <p id="np-key-help" class="mt-1 text-[12px]" :class="errors.key ? 'text-danger' : 'text-ink-3'">
                        {{ errors.key || `Tasks will be numbered ${(form.key || 'KEY').toUpperCase()}-1, ${(form.key || 'KEY').toUpperCase()}-2…` }}
                    </p>
                </div>
                <fieldset>
                    <legend class="label">Color</legend>
                    <div class="grid grid-cols-4 gap-1.5">
                        <label v-for="c in SWATCHES" :key="c" class="relative size-7 rounded-md" :style="{ background: c }">
                            <input v-model="form.color" type="radio" name="np-color" :value="c" class="peer absolute inset-0 cursor-pointer opacity-0" :aria-label="`Color ${c}`" />
                            <span class="pointer-events-none absolute inset-0 rounded-md ring-2 ring-transparent ring-offset-2 ring-offset-surface peer-checked:ring-ink peer-focus-visible:ring-brand" />
                        </label>
                    </div>
                </fieldset>
            </div>
            <div>
                <label for="np-desc" class="label">Description <span class="font-normal text-ink-3">— optional</span></label>
                <textarea id="np-desc" v-model="form.description" rows="3" class="field" maxlength="2000" placeholder="What is this project for? AI agents see this when they list projects." />
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" class="btn btn-quiet" @click="store.newProject = false">Cancel</button>
                <button class="btn btn-primary" :disabled="saving || !form.name.trim()">{{ saving ? 'Creating…' : 'Create project' }}</button>
            </div>
        </form>
    </Modal>
</template>
