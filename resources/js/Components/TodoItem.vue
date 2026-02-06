<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    todo: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['update', 'delete', 'start-working', 'stop-working']);

const isEditing = ref(false);
const editContent = ref(props.todo.content);

const statusColors = {
    backlog: 'bg-gray-100 text-gray-700',
    todo: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    done: 'bg-green-100 text-green-700',
};

const statusLabels = {
    backlog: 'ბექლოგი',
    todo: 'გასაკეთებელი',
    in_progress: 'მიმდინარე',
    done: 'დასრულებული',
};

const formattedTime = computed(() => {
    const minutes = props.todo.total_time_spent || 0;
    if (minutes === 0) return null;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
});

const toggleComplete = () => {
    emit('update', props.todo.id, { is_completed: !props.todo.is_completed });
};

const updateStatus = (status) => {
    emit('update', props.todo.id, { status });
};

const startEditing = () => {
    isEditing.value = true;
    editContent.value = props.todo.content;
};

const saveEdit = () => {
    if (editContent.value.trim()) {
        emit('update', props.todo.id, { content: editContent.value });
        isEditing.value = false;
    }
};

const cancelEdit = () => {
    isEditing.value = false;
    editContent.value = props.todo.content;
};

const handleDelete = () => {
    if (confirm('ნამდვილად გსურთ წაშლა?')) {
        emit('delete', props.todo.id);
    }
};

const toggleWorking = () => {
    if (props.todo.is_working) {
        emit('stop-working', props.todo.id);
    } else {
        emit('start-working', props.todo.id);
    }
};
</script>

<template>
    <div
        class="group flex items-start gap-3 py-2 px-3 rounded-lg border transition-all"
        :class="[
            todo.is_working
                ? 'bg-yellow-50 border-yellow-300 shadow-sm'
                : 'bg-white border-gray-200 hover:border-blue-300'
        ]"
    >
        <!-- Checkbox -->
        <button
            @click="toggleComplete"
            class="mt-0.5 w-5 h-5 rounded border-2 flex-shrink-0 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100"
            :class="[
                todo.is_completed
                    ? 'bg-primary border-primary text-white opacity-100'
                    : 'border-gray-400 hover:border-primary'
            ]"
        >
            <svg v-if="todo.is_completed" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
        </button>

        <!-- Content Area -->
        <div class="flex-1 min-w-0">
            <!-- Display Mode -->
            <div v-if="!isEditing">
                <!-- Top row: Task ID + Status + Time -->
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-mono text-muted-foreground">{{ todo.display_id }}</span>
                    <span
                        class="px-1.5 py-0.5 text-xs rounded-full font-medium"
                        :class="statusColors[todo.status]"
                    >
                        {{ statusLabels[todo.status] }}
                    </span>
                    <span v-if="formattedTime" class="text-xs text-muted-foreground flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ formattedTime }}
                    </span>
                    <span v-if="todo.is_working" class="flex items-center gap-1 text-xs text-yellow-600 font-medium">
                        <span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>
                        მუშაობს
                    </span>
                </div>

                <!-- Content row -->
                <div class="flex items-start justify-between gap-2">
                    <p
                        :class="[
                            'flex-1 break-words cursor-pointer text-sm',
                            todo.is_completed ? 'line-through text-muted-foreground' : 'text-foreground'
                        ]"
                        @dblclick="startEditing"
                    >
                        {{ todo.content }}
                    </p>

                    <!-- Actions (visible on hover) -->
                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <!-- Start/Stop Working Button -->
                        <button
                            v-if="!todo.is_completed"
                            @click="toggleWorking"
                            class="p-1 rounded transition-colors"
                            :class="todo.is_working
                                ? 'text-red-500 hover:text-red-600 hover:bg-red-50'
                                : 'text-green-500 hover:text-green-600 hover:bg-green-50'"
                            :title="todo.is_working ? 'შეჩერება' : 'დაწყება'"
                        >
                            <svg v-if="todo.is_working" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                        <button
                            @click="startEditing"
                            class="p-1 text-muted-foreground hover:text-primary transition-colors"
                            title="რედაქტირება"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                        <button
                            @click="handleDelete"
                            class="p-1 text-muted-foreground hover:text-red-500 transition-colors"
                            title="წაშლა"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Edit Mode -->
            <div v-else class="flex gap-2">
                <input
                    v-model="editContent"
                    @keyup.enter="saveEdit"
                    @keyup.esc="cancelEdit"
                    type="text"
                    class="flex-1 px-3 py-1 border border-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                    autofocus
                />
                <button
                    @click="saveEdit"
                    class="px-3 py-1 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 text-sm"
                >
                    შენახვა
                </button>
                <button
                    @click="cancelEdit"
                    class="px-3 py-1 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 text-sm"
                >
                    გაუქმება
                </button>
            </div>
        </div>
    </div>
</template>
