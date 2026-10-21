<script setup>
import { computed, onMounted, ref } from 'vue';
import { api, problem } from '../lib/api';
import { ago, fileSize } from '../lib/format';
import { session } from '../lib/session';
import { toast } from '../lib/store';
import AgentChip from './AgentChip.vue';
import Icon from './Icon.vue';

const props = defineProps({
    target: { type: String, required: true, validator: (v) => ['task', 'project'].includes(v) },
    refKey: { type: String, required: true },
    canManage: { type: Boolean, default: false },
    heading: { type: Boolean, default: true },
});
const emit = defineEmits(['changed']);

const base = computed(() => `/${props.target === 'task' ? 'tasks' : 'projects'}/${props.refKey}/attachments`);
const items = ref([]);
const limits = ref({ max_bytes: 20 * 1048576, extensions: [] });
const uploads = ref([]);
const loaded = ref(false);
const dragging = ref(false);
const input = ref(null);
let dragDepth = 0;

async function load() {
    const { data } = await api.get(base.value);
    items.value = data.data;
    limits.value = data.limits;
    loaded.value = true;
}
onMounted(load);

const accept = computed(() => limits.value.extensions.map((e) => `.${e}`).join(','));
const ext = (name) => name.split('.').pop().toLowerCase();

function pick() {
    input.value?.click();
}

function onPicked(e) {
    send([...e.target.files]);
    e.target.value = '';
}

function onDrop(e) {
    dragDepth = 0;
    dragging.value = false;
    send([...(e.dataTransfer?.files ?? [])]);
}

function onDragEnter(e) {
    if (![...(e.dataTransfer?.types ?? [])].includes('Files')) return;
    dragDepth++;
    dragging.value = true;
}

function onDragLeave() {
    dragDepth = Math.max(0, dragDepth - 1);
    if (!dragDepth) dragging.value = false;
}

let uploadId = 0;
async function send(files) {
    for (const file of files) {
        // Check on the client first so people don't wait for a doomed upload.
        if (limits.value.extensions.length && !limits.value.extensions.includes(ext(file.name))) {
            toast(`“${file.name}” isn’t a supported file type.`, 'error');
            continue;
        }
        if (file.size > limits.value.max_bytes) {
            toast(`“${file.name}” is larger than ${fileSize(limits.value.max_bytes)}.`, 'error');
            continue;
        }

        const upload = { id: ++uploadId, name: file.name, progress: 0 };
        uploads.value.push(upload);
        const form = new FormData();
        form.append('file', file);

        try {
            const { data } = await api.post(base.value, form, {
                onUploadProgress: (e) => (upload.progress = e.total ? e.loaded / e.total : 0),
            });
            items.value.unshift(data.data);
            emit('changed');
        } catch (e) {
            toast(problem(e).fields.file || problem(e).message, 'error');
        } finally {
            uploads.value = uploads.value.filter((u) => u.id !== upload.id);
        }
    }
}

const confirming = ref(null);
async function remove(item) {
    if (confirming.value !== item.id) return (confirming.value = item.id);
    try {
        await api.delete(`/attachments/${item.id}`);
        items.value = items.value.filter((i) => i.id !== item.id);
        toast(`Removed ${item.name}`);
        emit('changed');
    } catch (e) {
        toast(problem(e).message, 'error');
    } finally {
        confirming.value = null;
    }
}

const canDelete = (item) => props.canManage || item.uploader?.id === session.user?.id;
const readable = (item) => item.kind === 'image' || item.has_text;
const download = (item, inline = false) => `/api/attachments/${item.id}/download${inline ? '?inline=1' : ''}`;
</script>

<template>
    <section
        class="relative rounded-xl transition-colors duration-200 ease-snap"
        :class="dragging ? 'bg-brand-soft outline-2 outline-offset-4 outline-brand outline-dashed' : ''"
        :aria-labelledby="`files-heading-${target}-${refKey}`"
        @dragenter.prevent="onDragEnter"
        @dragover.prevent
        @dragleave="onDragLeave"
        @drop.prevent="onDrop"
    >
        <div v-if="heading" class="mb-3 flex items-center justify-between">
            <h3 :id="`files-heading-${target}-${refKey}`" class="eyebrow">Attachments <span class="ml-1 text-ink-3">{{ items.length || '' }}</span></h3>
            <button type="button" class="btn btn-quiet btn-sm" @click="pick"><Icon name="upload" :size="15" /> Add files</button>
        </div>
        <h3 v-else :id="`files-heading-${target}-${refKey}`" class="sr-only">Files</h3>

        <label class="sr-only" :for="`file-input-${target}-${refKey}`">Upload files</label>
        <input :id="`file-input-${target}-${refKey}`" ref="input" type="file" multiple class="sr-only" :accept="accept" @change="onPicked" />

        <!-- In-flight uploads -->
        <ul v-if="uploads.length" class="mb-2 space-y-2" aria-live="polite">
            <li v-for="u in uploads" :key="u.id" class="rounded-lg bg-sunken px-3 py-2.5">
                <div class="flex items-center justify-between gap-3 text-[13px]">
                    <span class="truncate font-medium text-ink">{{ u.name }}</span>
                    <span class="tabular-nums text-ink-3">{{ Math.round(u.progress * 100) }}%</span>
                </div>
                <div class="mt-2 h-1 overflow-hidden rounded-full bg-line">
                    <div class="h-full origin-left rounded-full bg-brand transition-transform duration-200 ease-snap" :style="{ transform: `scaleX(${u.progress})` }" />
                </div>
            </li>
        </ul>

        <TransitionGroup v-if="items.length" name="list" tag="ul" class="relative divide-y divide-line">
            <li v-for="item in items" :key="item.id" class="flex items-center gap-3 py-2.5">
                <a :href="download(item)" class="shrink-0" :aria-label="`Download ${item.name}`">
                    <img v-if="item.kind === 'image'" :src="download(item, true)" :alt="''" loading="lazy" class="size-10 rounded-lg bg-sunken object-cover ring-1 ring-line" />
                    <span v-else class="flex size-10 items-center justify-center rounded-lg bg-sunken font-mono text-[10px] font-bold text-ink-2 uppercase ring-1 ring-line">{{ ext(item.name).slice(0, 4) }}</span>
                </a>
                <div class="min-w-0 flex-1">
                    <a :href="download(item)" class="block truncate text-[14px] font-medium text-ink hover:text-brand">{{ item.name }}</a>
                    <p class="flex flex-wrap items-center gap-x-2 text-[12px] text-ink-3">
                        <span>{{ fileSize(item.size) }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ item.uploader?.name ?? 'Someone' }}, {{ ago(item.created_at) }}</span>
                        <AgentChip v-if="item.channel === 'mcp'" :client="item.client" />
                        <span v-if="readable(item)" class="rounded bg-brand-soft px-1.5 py-px font-semibold text-brand">AI can read</span>
                        <span v-else class="rounded bg-sunken px-1.5 py-px font-medium" title="Assistants can see the file exists but can’t read its contents. Export spreadsheets as CSV.">No AI preview</span>
                    </p>
                </div>
                <button
                    v-if="canDelete(item)"
                    type="button"
                    class="btn btn-sm shrink-0"
                    :class="confirming === item.id ? 'bg-danger text-white' : 'btn-quiet w-8 px-0'"
                    :aria-label="confirming === item.id ? `Confirm removing ${item.name}` : `Remove ${item.name}`"
                    @click="remove(item)"
                    @blur="confirming = null"
                >
                    <Icon v-if="confirming !== item.id" name="trash" :size="15" /><template v-else>Remove?</template>
                </button>
            </li>
        </TransitionGroup>

        <button
            v-else-if="loaded && !uploads.length"
            type="button"
            class="w-full rounded-lg border border-dashed border-line-strong px-4 py-5 text-left transition-colors duration-150 ease-snap hover:border-ink-3"
            @click="pick"
        >
            <span class="block text-[14px] font-medium text-ink-2">Drop files here, or browse</span>
            <span class="mt-0.5 block text-[13px] text-ink-3">Specs, PDFs, screenshots, docs — up to {{ fileSize(limits.max_bytes) }}. Your AI assistant can read them.</span>
        </button>

        <div v-if="dragging" class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-xl">
            <span class="rounded-lg bg-surface px-3 py-1.5 text-[13px] font-semibold text-brand ring-1 ring-brand">Drop to upload</span>
        </div>
    </section>
</template>
