<script setup>
import { computed, onMounted, ref } from 'vue';
import Icon from '../components/Icon.vue';
import { api, problem } from '../lib/api';
import { ago } from '../lib/format';
import { toast } from '../lib/store';

const connections = ref([]);
const mcpUrl = ref(`${location.origin}/mcp`);
const loaded = ref(false);

async function load() {
    const { data } = await api.get('/connections');
    connections.value = data.data;
    mcpUrl.value = data.mcp_url;
    loaded.value = true;
}
onMounted(load);

const isLocal = computed(() => /localhost|127\.0\.0\.1|\.test\b/.test(mcpUrl.value));

// ── Personal token ──
const tokenName = ref('Claude Code');
const issued = ref(null);
const issuing = ref(false);
async function issue() {
    issuing.value = true;
    try {
        const { data } = await api.post('/connections', { name: tokenName.value });
        issued.value = data.data;
        await load();
    } catch (e) {
        toast(problem(e).message, 'error');
    } finally {
        issuing.value = false;
    }
}

const revoking = ref(null);
async function revoke(c) {
    if (revoking.value !== c.id) return (revoking.value = c.id);
    try {
        await api.delete(`/connections/${encodeURIComponent(c.id)}`);
        toast(`${c.name} disconnected`);
        revoking.value = null;
        await load();
    } catch (e) {
        toast(problem(e).message, 'error');
    }
}

const copied = ref(null);
function copy(text, id) {
    navigator.clipboard?.writeText(text);
    copied.value = id;
    setTimeout(() => copied.value === id && (copied.value = null), 1600);
}

const tab = ref('claude');
const TABS = [
    { id: 'claude', label: 'Claude.ai & Desktop' },
    { id: 'chatgpt', label: 'ChatGPT' },
    { id: 'code', label: 'Claude Code' },
    { id: 'other', label: 'Cursor & others' },
];
const token = computed(() => issued.value?.token ?? '<your-token>');
const claudeCodeCmd = computed(() => `claude mcp add --transport http relay ${mcpUrl.value} \\\n  --header "Authorization: Bearer ${token.value}"`);
const jsonConfig = computed(() =>
    JSON.stringify({ mcpServers: { relay: { url: mcpUrl.value, headers: { Authorization: `Bearer ${token.value}` } } } }, null, 2),
);

const PROMPTS = [
    'What’s on my plate today? Start with anything urgent.',
    'Pick up WEB-12, post a plan as a comment, then move it to in progress.',
    'I’ve finished the pricing page — send WEB-4 for review with a summary of what changed.',
    'Create tasks in OPS for each item in this incident report and assign them to me.',
];
</script>

<template>
    <div class="mx-auto max-w-[900px] px-5 pt-10 pb-24 sm:px-10 lg:pt-16">
        <p class="eyebrow">AI connections</p>
        <h1 class="mt-3 max-w-2xl text-[clamp(36px,5.2vw,60px)] leading-[0.98] font-extrabold tracking-[-0.045em]">Let your assistant run the board with you.</h1>
        <p class="mt-5 max-w-xl text-[17px] font-light text-ink-2">
            Relay speaks the Model Context Protocol. Connected assistants act as you: they see your projects, fetch work assigned to you, update statuses, and hand tasks back for review.
        </p>

        <!-- Endpoint -->
        <section class="mt-12" aria-labelledby="endpoint-h">
            <h2 id="endpoint-h" class="eyebrow">Your MCP server URL</h2>
            <div class="mt-3 flex items-center gap-2 rounded-xl bg-surface p-2 pl-4 ring-1 ring-line">
                <code class="min-w-0 flex-1 truncate font-mono text-[15px] font-medium">{{ mcpUrl }}</code>
                <button class="btn btn-outline btn-sm" @click="copy(mcpUrl, 'url')">
                    <Icon :name="copied === 'url' ? 'check' : 'copy'" :size="15" /> {{ copied === 'url' ? 'Copied' : 'Copy' }}
                </button>
            </div>
            <p v-if="isLocal" class="mt-3 flex gap-2 text-[13px] text-ink-2">
                <Icon name="alert" :size="16" class="mt-0.5 text-signal" />
                <span>Claude.ai and ChatGPT connect from the internet, so they need a public HTTPS address. Locally, expose this app with a tunnel (e.g. <code class="font-mono">herd share</code> or ngrok) and set <code class="font-mono">APP_URL</code> to it. Claude Code works with localhost as-is.</span>
            </p>
        </section>

        <!-- Setup guides -->
        <section class="mt-14" aria-labelledby="setup-h">
            <h2 id="setup-h" class="text-[24px] font-bold">Connect an assistant</h2>
            <div class="mt-5 flex gap-1 overflow-x-auto border-b border-line" role="tablist" aria-label="Assistant">
                <button
                    v-for="t in TABS"
                    :id="`tab-${t.id}`"
                    :key="t.id"
                    role="tab"
                    :aria-selected="tab === t.id"
                    :aria-controls="`panel-${t.id}`"
                    class="relative -mb-px h-11 shrink-0 px-3 text-[14px] font-semibold transition-colors duration-150 ease-snap"
                    :class="tab === t.id ? 'text-ink' : 'text-ink-3 hover:text-ink-2'"
                    @click="tab = t.id"
                >
                    {{ t.label }}
                    <span class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-brand transition-transform duration-300 ease-snap" :class="tab === t.id ? 'scale-x-100' : 'scale-x-0'" />
                </button>
            </div>

            <Transition name="page" mode="out-in">
                <div :id="`panel-${tab}`" :key="tab" role="tabpanel" :aria-labelledby="`tab-${tab}`" class="pt-7">
                    <ol v-if="tab === 'claude'" class="max-w-2xl list-decimal space-y-3 pl-5 text-[15px] marker:font-semibold marker:text-ink-3">
                        <li>In Claude, open <strong>Settings → Connectors</strong> and choose <strong>Add custom connector</strong>.</li>
                        <li>Name it <strong>Relay</strong> and paste the server URL above. Leave the OAuth fields empty.</li>
                        <li>Click <strong>Connect</strong>. You’ll land on a Relay consent screen — sign in if asked, then approve.</li>
                        <li>In a chat, enable Relay from the tools menu and ask: <em>“What’s assigned to me?”</em></li>
                    </ol>
                    <ol v-else-if="tab === 'chatgpt'" class="max-w-2xl list-decimal space-y-3 pl-5 text-[15px] marker:font-semibold marker:text-ink-3">
                        <li>In ChatGPT, open <strong>Settings → Apps &amp; Connectors → Advanced</strong> and turn on <strong>Developer mode</strong>.</li>
                        <li>Choose <strong>Create</strong>, name it <strong>Relay</strong>, paste the server URL, and pick <strong>OAuth</strong> for authentication.</li>
                        <li>Approve access on the Relay consent screen that opens.</li>
                        <li>Start a chat, add Relay from the <strong>+</strong> menu, and ask it to fetch your tasks.</li>
                    </ol>
                    <div v-else-if="tab === 'code'" class="max-w-2xl">
                        <p class="text-[15px] text-ink-2">Claude Code uses a personal token. Create one below — the command fills in automatically.</p>
                        <div class="relative mt-4">
                            <pre class="overflow-x-auto rounded-xl bg-[#0c1211] p-4 pr-24 font-mono text-[13px] leading-relaxed text-[#dbe4e1]">{{ claudeCodeCmd }}</pre>
                            <button class="btn btn-sm absolute top-2.5 right-2.5 bg-white/10 text-white hover:bg-white/20" @click="copy(claudeCodeCmd, 'cc')">{{ copied === 'cc' ? 'Copied' : 'Copy' }}</button>
                        </div>
                    </div>
                    <div v-else class="max-w-2xl">
                        <p class="text-[15px] text-ink-2">Any client that supports remote MCP servers over HTTP works. Most accept a JSON config like this (with a personal token):</p>
                        <div class="relative mt-4">
                            <pre class="overflow-x-auto rounded-xl bg-[#0c1211] p-4 pr-24 font-mono text-[13px] leading-relaxed text-[#dbe4e1]">{{ jsonConfig }}</pre>
                            <button class="btn btn-sm absolute top-2.5 right-2.5 bg-white/10 text-white hover:bg-white/20" @click="copy(jsonConfig, 'json')">{{ copied === 'json' ? 'Copied' : 'Copy' }}</button>
                        </div>
                        <p class="mt-3 text-[13px] text-ink-3">Clients with OAuth support (Cursor, VS Code) can use the URL alone and sign in through the consent screen.</p>
                    </div>
                </div>
            </Transition>
        </section>

        <!-- Personal tokens -->
        <section class="mt-16" aria-labelledby="token-h">
            <h2 id="token-h" class="text-[24px] font-bold">Personal tokens</h2>
            <p class="mt-1 max-w-xl text-[14px] text-ink-2">For clients that send an <code class="font-mono text-[13px]">Authorization</code> header. Tokens act as you, last a year, and can be revoked any time.</p>
            <form class="mt-5 flex flex-wrap gap-2" @submit.prevent="issue">
                <label for="token-name" class="sr-only">Token name</label>
                <input id="token-name" v-model="tokenName" class="field max-w-xs" required minlength="2" maxlength="60" placeholder="e.g. Claude Code — laptop" />
                <button class="btn btn-primary" :disabled="issuing"><Icon name="key" :size="16" /> {{ issuing ? 'Creating…' : 'Create token' }}</button>
            </form>

            <Transition name="pop">
                <div v-if="issued" class="mt-5 rounded-xl bg-signal-soft p-5" role="status">
                    <p class="font-semibold text-signal">Copy “{{ issued.name }}” now — it won’t be shown again.</p>
                    <div class="mt-3 flex items-center gap-2 rounded-lg bg-surface p-2 pl-3">
                        <code class="min-w-0 flex-1 truncate font-mono text-[12px]">{{ issued.token }}</code>
                        <button class="btn btn-outline btn-sm" @click="copy(issued.token, 'tok')"><Icon :name="copied === 'tok' ? 'check' : 'copy'" :size="15" /> {{ copied === 'tok' ? 'Copied' : 'Copy' }}</button>
                    </div>
                </div>
            </Transition>
        </section>

        <!-- Active connections -->
        <section class="mt-16" aria-labelledby="active-h">
            <h2 id="active-h" class="text-[24px] font-bold">Active connections</h2>
            <TransitionGroup v-if="connections.length" name="list" tag="ul" class="relative mt-5 divide-y divide-line border-y border-line">
                <li v-for="c in connections" :key="c.id" class="flex flex-wrap items-center gap-4 py-4">
                    <span class="flex size-9 items-center justify-center rounded-lg" :class="c.kind === 'oauth' ? 'bg-signal-soft text-signal' : 'bg-sunken text-ink-2'">
                        <Icon :name="c.kind === 'oauth' ? 'sparkles' : 'key'" :size="17" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ c.name }}</p>
                        <p class="text-[13px] text-ink-3">
                            {{ c.kind === 'oauth' ? 'Connected app' : 'Personal token' }} · added {{ ago(c.created_at) }}<template v-if="c.expires_at"> · expires {{ ago(c.expires_at) }}</template>
                        </p>
                    </div>
                    <button class="btn btn-sm" :class="revoking === c.id ? 'bg-danger text-white' : 'btn-danger'" @click="revoke(c)" @blur="revoking = null">
                        {{ revoking === c.id ? 'Confirm revoke' : 'Revoke' }}
                    </button>
                </li>
            </TransitionGroup>
            <p v-else-if="loaded" class="mt-4 text-[14px] text-ink-3">No assistants connected yet.</p>
        </section>

        <!-- Prompts -->
        <section class="mt-16" aria-labelledby="prompts-h">
            <h2 id="prompts-h" class="text-[24px] font-bold">Things to ask</h2>
            <ul class="mt-5 space-y-2">
                <li v-for="(p, i) in PROMPTS" :key="i">
                    <button class="group flex w-full items-center gap-3 rounded-lg py-2.5 text-left text-[16px] text-ink-2 transition-colors duration-150 ease-snap hover:text-ink" @click="copy(p, `p${i}`)">
                        <span class="font-display text-[22px] leading-none text-ink-3 transition-colors group-hover:text-brand" aria-hidden="true">“</span>
                        <span class="flex-1">{{ p }}</span>
                        <span class="text-[12px] font-semibold text-brand opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">{{ copied === `p${i}` ? 'Copied' : 'Copy' }}</span>
                    </button>
                </li>
            </ul>
        </section>
    </div>
</template>
