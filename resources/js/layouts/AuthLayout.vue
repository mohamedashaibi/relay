<script setup>
const LINES = [
    { who: 'you', text: 'What’s on my plate today?' },
    { who: 'tool', text: 'get_my_work' },
    { who: 'ai', text: 'Three tasks. WEB-12 “Ship pricing page” is in progress and due tomorrow — want to start there?' },
    { who: 'you', text: 'Yes. When it’s done, send it to Sam for review.' },
    { who: 'tool', text: 'request_review  WEB-12  → Sam' },
];
</script>

<template>
    <div class="min-h-dvh lg:grid lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
        <!-- Story panel: always dark, regardless of theme -->
        <aside class="relative hidden overflow-hidden bg-[#0c1211] px-14 py-12 text-[#e9efed] lg:flex lg:flex-col">
            <a href="/" class="self-start font-display text-[28px] font-extrabold tracking-[-0.05em]">Relay<span class="text-[#2dd4bf]">.</span></a>

            <div class="my-auto max-w-xl">
                <p class="text-[11px] font-semibold tracking-[0.16em] text-[#2dd4bf] uppercase">Task management for people who work with AI</p>
                <h1 class="mt-5 font-display text-[clamp(44px,4.6vw,68px)] leading-[0.98] font-extrabold tracking-[-0.045em] text-[#f3f7f5]">
                    Your assistant already knows what’s next.
                </h1>
                <p class="mt-6 max-w-md text-[17px] leading-relaxed font-light text-[#aab6b2]">
                    Connect Claude or ChatGPT over MCP. It reads your tasks, moves them forward, and hands work back to you for review.
                </p>

                <ol class="mt-12 space-y-3 font-mono text-[13px] leading-relaxed" aria-label="Example conversation">
                    <li v-for="(l, i) in LINES" :key="i" class="rise flex gap-3" :style="{ '--i': i + 3 }">
                        <span class="w-10 shrink-0 text-right" :class="l.who === 'you' ? 'text-[#8a9692]' : l.who === 'tool' ? 'text-[#fb923c]' : 'text-[#2dd4bf]'">
                            {{ l.who === 'tool' ? '⏵' : l.who }}
                        </span>
                        <span :class="l.who === 'tool' ? 'text-[#fb923c]' : 'text-[#dbe4e1]'">{{ l.text }}</span>
                    </li>
                </ol>
            </div>

            <p class="text-[12px] text-[#6f7a76]">Works with Claude.ai, Claude Desktop, Claude Code, ChatGPT and any MCP client.</p>
        </aside>

        <main class="flex min-h-dvh flex-col px-6 py-10 sm:px-12">
            <a href="/" class="self-start font-display text-[26px] font-extrabold tracking-[-0.05em] lg:hidden">Relay<span class="text-brand">.</span></a>
            <div class="my-auto w-full max-w-sm self-center py-10">
                <slot />
            </div>
        </main>
    </div>
</template>
