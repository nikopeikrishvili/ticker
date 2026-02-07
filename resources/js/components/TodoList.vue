<script setup>
import TodoTable from './TodoTable.vue';

const props = defineProps({
    onOpenModal: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <div class="h-full flex flex-col" data-tour="todo-list">
        <!-- Todo Table -->
        <div class="flex-1 overflow-y-auto">
            <TodoTable :on-open-modal="onOpenModal" />
        </div>
    </div>
</template>
