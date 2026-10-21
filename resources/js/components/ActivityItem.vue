<script setup>
import { computed } from 'vue';
import { ago, statusMeta } from '../lib/format';
import AgentChip from './AgentChip.vue';
import Avatar from './Avatar.vue';

const props = defineProps({
    item: { type: Object, required: true },
    showTask: { type: Boolean, default: false },
});

const text = computed(() => {
    const m = props.item.meta ?? {};
    switch (props.item.type) {
        case 'created':
            return 'created';
        case 'status_changed':
            return `moved to ${statusMeta(m.to).label}`;
        case 'assigned':
            return m.to ? `assigned to ${m.to}` : 'unassigned';
        case 'review_requested':
            return m.reviewer ? `requested review from ${m.reviewer}` : 'requested review';
        case 'approved':
            return 'approved';
        case 'changes_requested':
            return 'requested changes';
        case 'commented':
            return 'commented';
        case 'attached':
            return `attached ${m.name ?? 'a file'}`;
        case 'updated':
            return `updated ${(m.fields ?? []).map((f) => f.replace('_id', '').replace('_', ' ')).join(', ')}`;
        default:
            return props.item.type;
    }
});

const quote = computed(() => props.item.meta?.summary || props.item.meta?.excerpt || props.item.meta?.comment || null);
</script>

<template>
    <div class="flex gap-3">
        <Avatar :person="item.actor" :size="24" class="mt-0.5" />
        <div class="min-w-0 flex-1">
            <p class="text-[14px] leading-snug text-ink-2">
                <span class="font-semibold text-ink">{{ item.actor?.name ?? 'Someone' }}</span>
                {{ text }}
                <template v-if="showTask && item.task">
                    <RouterLink :to="{ query: { task: item.task.key } }" class="font-medium text-ink hover:text-brand">
                        <span class="task-key mr-1">{{ item.task.key }}</span>{{ item.task.title }}
                    </RouterLink>
                </template>
            </p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <AgentChip v-if="item.channel === 'mcp'" :client="item.client" />
                <time class="text-[12px] text-ink-3" :datetime="item.created_at">{{ ago(item.created_at) }}</time>
            </div>
            <p v-if="quote" class="mt-1.5 line-clamp-2 border-l-2 border-line-strong pl-2.5 text-[13px] text-ink-2">{{ quote }}</p>
        </div>
    </div>
</template>
