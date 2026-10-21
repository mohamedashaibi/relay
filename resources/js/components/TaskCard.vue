<script setup>
import { computed } from 'vue';
import { due } from '../lib/format';
import Avatar from './Avatar.vue';
import PriorityMark from './PriorityMark.vue';

const props = defineProps({
    task: { type: Object, required: true },
    dragging: { type: Boolean, default: false },
});
const dueInfo = computed(() => due(props.task.due_date, props.task.status));
</script>

<template>
    <RouterLink
        :to="{ query: { task: task.key } }"
        class="block rounded-xl bg-surface p-3.5 ring-1 ring-line transition-[box-shadow,transform,opacity] duration-200 ease-snap hover:ring-line-strong"
        :class="dragging ? 'scale-[0.98] opacity-40' : ''"
        draggable="false"
    >
        <div class="flex items-center gap-2">
            <span class="task-key">{{ task.key }}</span>
            <PriorityMark :priority="task.priority" class="ml-auto" />
        </div>
        <p class="mt-1.5 text-[14px] leading-snug font-semibold text-ink" :class="task.status === 'done' ? 'text-ink-2 line-through decoration-ink-3/50' : ''">{{ task.title }}</p>
        <div v-if="task.labels.length" class="mt-2 flex flex-wrap gap-1">
            <span v-for="l in task.labels.slice(0, 3)" :key="l" class="rounded bg-sunken px-1.5 py-0.5 text-[11px] font-medium text-ink-2">{{ l }}</span>
        </div>
        <div class="mt-3 flex items-center gap-3 text-[12px] text-ink-3">
            <span v-if="dueInfo" class="font-medium" :class="dueInfo.overdue ? 'text-danger' : dueInfo.soon ? 'text-signal' : ''">{{ dueInfo.label }}</span>
            <span v-if="task.comment_count" class="inline-flex items-center gap-1">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-11 6.6L4 19.5l1.4-4.6A7.5 7.5 0 1 1 20 11.5z" /></svg>
                {{ task.comment_count }}<span class="sr-only">comments</span>
            </span>
            <span v-if="task.attachment_count" class="inline-flex items-center gap-1">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M20.5 11.5 12 20a5 5 0 0 1-7-7l8.5-8.5a3.3 3.3 0 0 1 4.7 4.7l-8.5 8.5a1.7 1.7 0 0 1-2.4-2.4L15 7.6" /></svg>
                {{ task.attachment_count }}<span class="sr-only">attachments</span>
            </span>
            <span v-if="task.status === 'in_review' && task.reviewer" class="truncate">→ {{ task.reviewer.name }}</span>
            <Avatar :person="task.assignee" :size="22" class="ml-auto" />
        </div>
    </RouterLink>
</template>
