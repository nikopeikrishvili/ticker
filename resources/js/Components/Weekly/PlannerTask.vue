<script setup>
import { ref } from 'vue';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
    isDragging: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['dragstart', 'moveToBacklog', 'toggleComplete']);

const isHovering = ref(false);

const handleDragStart = (e) => {
    e.dataTransfer.setData('text/plain', JSON.stringify({
        todoId: props.task.todo?.id || props.task.id,
        placementId: props.task.id,
        fromBacklog: !props.task.todo,
    }));
    e.dataTransfer.effectAllowed = 'move';
    emit('dragstart', props.task);
};

const handleMoveToBacklog = () => {
    emit('moveToBacklog', props.task.todo?.id || props.task.id);
};

const todoData = props.task.todo || props.task;

const handleToggleComplete = () => {
    emit('toggleComplete', {
        id: todoData.id,
        is_completed: !todoData.is_completed,
    });
};
</script>

<template>
    <div
        class="group relative bg-card rounded-lg shadow-sm border p-3 cursor-grab active:cursor-grabbing transition-all hover:shadow-md"
        :style="{
            borderColor: isHovering ? 'var(--primary-color)' : 'var(--border-color)',
            boxShadow: isHovering ? '0 0 0 2px var(--primary-color)' : undefined
        }"
        :class="{
            'opacity-50 scale-95': isDragging,
        }"
        draggable="true"
        @dragstart="handleDragStart"
        @mouseenter="isHovering = true"
        @mouseleave="isHovering = false"
    >
        <!-- Completion checkbox -->
        <div class="flex items-start gap-3">
            <button
                @click.stop="handleToggleComplete"
                class="mt-0.5 w-5 h-5 rounded border-2 flex-shrink-0 transition-colors"
                :style="todoData.is_completed
                    ? { backgroundColor: 'var(--primary-color)', borderColor: 'var(--primary-color)', color: 'white' }
                    : { borderColor: 'var(--secondary-text-color)' }"
            >
                <svg v-if="todoData.is_completed" class="w-full h-full text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </button>

            <!-- Task content -->
            <span
                class="flex-1 text-sm"
                :style="{ color: todoData.is_completed ? 'var(--secondary-text-color)' : 'var(--text-color)' }"
                :class="{ 'line-through': todoData.is_completed }"
            >
                {{ todoData.content }}
            </span>
        </div>

        <!-- Hover actions -->
        <div
            class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
        >
            <button
                @click.stop="handleMoveToBacklog"
                class="p-1 rounded transition-colors"
                style="color: var(--secondary-text-color)"
                title="ბექლოგში დაბრუნება"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
            </button>
        </div>
    </div>
</template>
