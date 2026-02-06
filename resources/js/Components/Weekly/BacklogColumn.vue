<script setup>
import { ref } from 'vue';
import { useWeeklyPlanner } from '@/Composables/useWeeklyPlanner';
import { useTodos } from '@/Composables/useTodos';

const emit = defineEmits(['taskDropped']);

const { backlog, moveToBacklog } = useWeeklyPlanner();
const { addTodo } = useTodos();

const isDragOver = ref(false);
const newTaskContent = ref('');
const isAddingTask = ref(false);

const handleDragOver = (e) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    isDragOver.value = true;
};

const handleDragLeave = (e) => {
    if (!e.currentTarget.contains(e.relatedTarget)) {
        isDragOver.value = false;
    }
};

const handleDrop = async (e) => {
    e.preventDefault();
    isDragOver.value = false;

    try {
        const data = JSON.parse(e.dataTransfer.getData('text/plain'));
        if (!data.fromBacklog) {
            await moveToBacklog(data.todoId);
            emit('taskDropped', data.todoId);
        }
    } catch (err) {
        console.error('Failed to drop task to backlog:', err);
    }
};

const handleDragStart = (e, task) => {
    e.dataTransfer.setData('text/plain', JSON.stringify({
        todoId: task.id,
        fromBacklog: true,
    }));
    e.dataTransfer.effectAllowed = 'move';
};

const handleAddTask = async () => {
    if (!newTaskContent.value.trim()) return;

    try {
        // Pass null as date to create a backlog item (no date assigned)
        await addTodo(newTaskContent.value.trim(), 'todo', 3, null);
        newTaskContent.value = '';
        isAddingTask.value = false;
        emit('taskDropped', null);
    } catch (err) {
        console.error('Failed to add task:', err);
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
        handleAddTask();
    } else if (e.key === 'Escape') {
        isAddingTask.value = false;
        newTaskContent.value = '';
    }
};
</script>

<template>
    <div
        class="flex flex-col bg-background rounded-xl shadow-sm overflow-hidden transition-all border"
        :style="{
            borderColor: isDragOver ? 'var(--primary-color)' : 'var(--border-color)',
            boxShadow: isDragOver ? '0 0 0 2px var(--primary-color)' : undefined
        }"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
    >
        <!-- Header -->
        <div class="px-4 py-3 flex items-center justify-between" style="background-color: var(--primary-color)">
            <h3 class="text-white font-semibold text-sm">ბექლოგი</h3>
            <button
                @click="isAddingTask = true"
                class="p-1 rounded hover:bg-white/20 transition-colors"
                title="ახალი დავალება"
            >
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>

        <!-- Tasks Container -->
        <div class="flex-1 p-3 space-y-2 overflow-y-auto">
            <!-- Add Task Input -->
            <div v-if="isAddingTask" class="mb-2">
                <input
                    v-model="newTaskContent"
                    type="text"
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2"
                    :style="{ borderColor: 'var(--border-color)', color: 'var(--text-color)' }"
                    placeholder="ახალი დავალება..."
                    @keydown="handleKeyDown"
                    autofocus
                />
                <div class="flex gap-2 mt-2">
                    <button
                        @click="handleAddTask"
                        class="flex-1 px-3 py-1 text-white text-sm rounded-lg transition-colors"
                        style="background-color: var(--primary-color)"
                    >
                        დამატება
                    </button>
                    <button
                        @click="isAddingTask = false; newTaskContent = ''"
                        class="px-3 py-1 text-sm rounded-lg transition-colors"
                        style="color: var(--secondary-text-color)"
                    >
                        გაუქმება
                    </button>
                </div>
            </div>

            <!-- Backlog Tasks -->
            <div
                v-for="task in backlog"
                :key="task.id"
                class="group relative bg-card rounded-lg shadow-sm border p-3 cursor-grab active:cursor-grabbing transition-all hover:shadow-md"
                :style="{ borderColor: 'var(--border-color)' }"
                draggable="true"
                @dragstart="(e) => handleDragStart(e, task)"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="mt-0.5 w-5 h-5 rounded border-2 flex-shrink-0"
                        :style="task.is_completed
                            ? { backgroundColor: 'var(--primary-color)', borderColor: 'var(--primary-color)' }
                            : { borderColor: 'var(--secondary-text-color)' }"
                    >
                        <svg v-if="task.is_completed" class="w-full h-full text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span
                        class="flex-1 text-sm"
                        :style="{ color: task.is_completed ? 'var(--secondary-text-color)' : 'var(--text-color)' }"
                        :class="{ 'line-through': task.is_completed }"
                    >
                        {{ task.content }}
                    </span>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="backlog.length === 0 && !isAddingTask"
                class="h-full flex flex-col items-center justify-center text-sm py-8"
                style="color: var(--secondary-text-color)"
            >
                <svg class="w-8 h-8 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <span>ბექლოგი ცარიელია</span>
            </div>

            <!-- Drop Zone Indicator -->
            <div
                v-if="isDragOver"
                class="border-2 border-dashed rounded-lg p-4 text-center text-sm"
                style="border-color: var(--primary-color); color: var(--primary-color)"
            >
                აქ ჩააგდე
            </div>
        </div>

        <!-- Task Count -->
        <div
            class="px-4 py-2 border-t"
            :style="{ backgroundColor: 'var(--bg-color)', opacity: 0.5, borderColor: 'var(--border-color)' }"
        >
            <span class="text-xs" style="color: var(--secondary-text-color)">
                {{ backlog.length }} დავალება
            </span>
        </div>
    </div>
</template>
