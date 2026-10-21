<script setup>
import { useRoute, useRouter } from 'vue-router';
import TaskDetail from '../components/TaskDetail.vue';

// Full-page task view: the link AI assistants share (…/tasks/WEB-12).
const route = useRoute();
const router = useRouter();
</script>

<template>
    <div class="mx-auto max-w-[760px] lg:pt-6">
        <div class="lg:rounded-2xl lg:bg-surface lg:ring-1 lg:ring-line">
            <TaskDetail :key="route.params.key" :task-key="String(route.params.key)" @close="router.push('/')" />
        </div>
    </div>
</template>
