<script setup>
import { ref, computed } from 'vue';
import { useWeeklyPlanner } from '@/composables/useWeeklyPlanner';
import PlannerTask from './PlannerTask.vue';
import GhostTask from './GhostTask.vue';

const props = defineProps({
    dayOfWeek: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['taskDropped', 'moveToBacklog', 'toggleComplete']);

const { dayNames, days, currentWeekKey, assignToDay, moveToBacklog } = useWeeklyPlanner();

const isDragOver = ref(false);
const draggingTask = ref(null);

const dayName = computed(() => dayNames[props.dayOfWeek]);
const tasks = computed(() => days.value[props.dayOfWeek]?.current || []);
const ghosts = computed(() => days.value[props.dayOfWeek]?.ghosts || []);

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
        await assignToDay({
            todoId: data.todoId,
            weekKey: currentWeekKey.value,
            dayOfWeek: props.dayOfWeek,
        });
        emit('taskDropped', data.todoId);
    } catch (err) {
        console.error('Failed to drop task:', err);
    }
};

const handleMoveToBacklog = async (todoId) => {
    await moveToBacklog(todoId);
    emit('moveToBacklog', todoId);
};

const handleToggleComplete = (data) => {
    emit('toggleComplete', data);
};

const handleDragStart = (task) => {
    draggingTask.value = task;
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
        <!-- Day Header -->
        <div class="px-4 py-3" style="background-color: var(--text-color)">
            <h3 class="text-white font-semibold text-center text-sm">{{ dayName }}</h3>
        </div>

        <!-- Tasks Container -->
        <div class="flex-1 p-3 space-y-2 overflow-y-auto">
            <!-- Current Tasks -->
            <PlannerTask
                v-for="task in tasks"
                :key="task.id"
                :task="task"
                :is-dragging="draggingTask?.id === task.id"
                @dragstart="handleDragStart"
                @move-to-backlog="handleMoveToBacklog"
                @toggle-complete="handleToggleComplete"
            />

            <!-- Ghost Tasks (moved away) -->
            <GhostTask
                v-for="ghost in ghosts"
                :key="`ghost-${ghost.id}`"
                :ghost="ghost"
            />

            <!-- Empty State -->
            <div
                v-if="tasks.length === 0 && ghosts.length === 0"
                class="h-full flex items-center justify-center text-sm py-8"
                style="color: var(--secondary-text-color)"
            >
                <span>ცარიელია</span>
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
                {{ tasks.length }} დავალება
            </span>
        </div>
    </div>
</template>
