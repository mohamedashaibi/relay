<script setup>
import { computed } from 'vue';
import { due } from '../lib/format';
import Avatar from './Avatar.vue';
import PriorityMark from './PriorityMark.vue';
import StatusDot from './StatusDot.vue';

const props = defineProps({
    task: { type: Object, required: true },
    showProject: { type: Boolean, default: true },
});
const dueInfo = computed(() => due(props.task.due_date, props.task.status));
</script>

<template>
    <RouterLink
        :to="{ query: { task: task.key } }"
        class="group -mx-3 flex min-h-12 items-center gap-3 rounded-lg px-3 py-2 transition-colors duration-150 ease-snap hover:bg-sunken"
    >
        <StatusDot :status="task.status" />
        <span class="task-key w-16 shrink-0">{{ task.key }}</span>
        <span class="min-w-0 flex-1 truncate font-medium text-ink">{{ task.title }}</span>
        <span v-if="task.comment_count" class="hidden items-center gap-1 text-[12px] text-ink-3 sm:inline-flex">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-11 6.6L4 19.5l1.4-4.6A7.5 7.5 0 1 1 20 11.5z" /></svg>
            {{ task.comment_count }}<span class="sr-only">comments</span>
        </span>
        <span v-if="task.attachment_count" class="hidden items-center gap-1 text-[12px] text-ink-3 sm:inline-flex">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M20.5 11.5 12 20a5 5 0 0 1-7-7l8.5-8.5a3.3 3.3 0 0 1 4.7 4.7l-8.5 8.5a1.7 1.7 0 0 1-2.4-2.4L15 7.6" /></svg>
            {{ task.attachment_count }}<span class="sr-only">attachments</span>
        </span>
        <span v-if="dueInfo" class="hidden text-[12px] font-medium sm:inline" :class="dueInfo.overdue ? 'text-danger' : dueInfo.soon ? 'text-signal' : 'text-ink-3'">
            {{ dueInfo.label }}
        </span>
        <PriorityMark :priority="task.priority" class="hidden sm:inline-flex" />
        <span v-if="showProject" class="hidden w-12 truncate text-right font-mono text-[11px] text-ink-3 md:inline" :title="task.project.name">{{ task.project.key }}</span>
        <Avatar :person="task.assignee" :size="22" />
    </RouterLink>
</template>
