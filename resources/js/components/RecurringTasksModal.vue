<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRecurringTasks } from '@/composables/useRecurringTasks';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['close']);

const { recurringTasks, loading, fetchRecurringTasks, addRecurringTask, updateRecurringTask, deleteRecurringTask, toggleRecurringTask } = useRecurringTasks();

const isAddingTask = ref(false);
const editingTaskId = ref(null);
const taskForm = ref({
    content: '',
    frequency_type: 'daily',
    weekdays: [],
});

const weekdayOptions = [
    { value: 1, label: 'ორშ', fullLabel: 'ორშაბათი' },
    { value: 2, label: 'სამ', fullLabel: 'სამშაბათი' },
    { value: 3, label: 'ოთხ', fullLabel: 'ოთხშაბათი' },
    { value: 4, label: 'ხუთ', fullLabel: 'ხუთშაბათი' },
    { value: 5, label: 'პარ', fullLabel: 'პარასკევი' },
    { value: 6, label: 'შაბ', fullLabel: 'შაბათი' },
    { value: 7, label: 'კვი', fullLabel: 'კვირა' },
];

watch(() => props.show, async (newVal) => {
    if (newVal) {
        await fetchRecurringTasks();
    }
});

const resetForm = () => {
    taskForm.value = {
        content: '',
        frequency_type: 'daily',
        weekdays: [],
    };
    isAddingTask.value = false;
    editingTaskId.value = null;
};

const handleClose = () => {
    resetForm();
    emit('close');
};

const startAddingTask = () => {
    resetForm();
    isAddingTask.value = true;
};

const startEditingTask = (task) => {
    taskForm.value = {
        content: task.content,
        frequency_type: task.frequency_type,
        weekdays: task.weekdays || [],
    };
    editingTaskId.value = task.id;
    isAddingTask.value = false;
};

const cancelEdit = () => {
    resetForm();
};

const toggleWeekday = (day) => {
    const index = taskForm.value.weekdays.indexOf(day);
    if (index === -1) {
        taskForm.value.weekdays.push(day);
        taskForm.value.weekdays.sort((a, b) => a - b);
    } else {
        taskForm.value.weekdays.splice(index, 1);
    }
};

const handleSaveTask = async () => {
    if (!taskForm.value.content.trim()) return;

    const data = {
        content: taskForm.value.content.trim(),
        frequency_type: taskForm.value.frequency_type,
        weekdays: taskForm.value.frequency_type === 'weekly' ? taskForm.value.weekdays : null,
    };

    try {
        if (editingTaskId.value) {
            await updateRecurringTask(editingTaskId.value, data);
        } else {
            await addRecurringTask(data);
        }
        resetForm();
    } catch (err) {
        console.error('Failed to save task:', err);
    }
};

const handleDeleteTask = async (id) => {
    if (confirm('ნამდვილად გსურთ წაშლა?')) {
        await deleteRecurringTask(id);
    }
};

const handleToggleTask = async (id) => {
    await toggleRecurringTask(id);
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="handleClose"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-[#1f2937]/50"></div>

                <!-- Modal -->
                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden border border-[#e5e7eb]">
                    <!-- Header -->
                    <div class="bg-[#1f2937] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">განმეორებადი დავალებები</h3>
                        <button
                            @click="handleClose"
                            class="text-white/60 hover:text-white transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="p-6 max-h-[60vh] overflow-y-auto">
                        <!-- Add/Edit Form -->
                        <div v-if="isAddingTask || editingTaskId" class="mb-6 p-4 bg-[#e5e7eb]/30 rounded-lg border border-[#e5e7eb]">
                            <h4 class="text-sm font-semibold text-[#1f2937] mb-3">
                                {{ editingTaskId ? 'რედაქტირება' : 'ახალი განმეორებადი დავალება' }}
                            </h4>

                            <!-- Content Input -->
                            <div class="mb-4">
                                <input
                                    v-model="taskForm.content"
                                    type="text"
                                    class="w-full px-3 py-2 border border-[#e5e7eb] rounded-lg text-sm text-[#1f2937] focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                    placeholder="დავალების ტექსტი..."
                                    @keyup.enter="handleSaveTask"
                                />
                            </div>

                            <!-- Frequency Type -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-[#1f2937] mb-2">სიხშირე</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input
                                            v-model="taskForm.frequency_type"
                                            type="radio"
                                            value="daily"
                                            class="text-[#346ec9] focus:ring-[#346ec9]"
                                        />
                                        <span class="text-sm text-[#1f2937]">ყოველდღე</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input
                                            v-model="taskForm.frequency_type"
                                            type="radio"
                                            value="weekly"
                                            class="text-[#346ec9] focus:ring-[#346ec9]"
                                        />
                                        <span class="text-sm text-[#1f2937]">კვირის დღეები</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Weekday Selection -->
                            <div v-if="taskForm.frequency_type === 'weekly'" class="mb-4">
                                <label class="block text-sm font-medium text-[#1f2937] mb-2">აირჩიეთ დღეები</label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="day in weekdayOptions"
                                        :key="day.value"
                                        @click="toggleWeekday(day.value)"
                                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                                        :class="taskForm.weekdays.includes(day.value)
                                            ? 'bg-[#346ec9] text-white'
                                            : 'bg-[#e5e7eb] text-[#1f2937] hover:bg-[#d1d5db]'"
                                        :title="day.fullLabel"
                                    >
                                        {{ day.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex gap-2">
                                <button
                                    @click="handleSaveTask"
                                    class="px-4 py-2 bg-[#346ec9] text-white rounded-lg text-sm hover:bg-[#2d5eb0] transition-colors"
                                >
                                    {{ editingTaskId ? 'შენახვა' : 'დამატება' }}
                                </button>
                                <button
                                    @click="cancelEdit"
                                    class="px-4 py-2 text-[#9ca3af] rounded-lg text-sm hover:bg-[#e5e7eb] transition-colors"
                                >
                                    გაუქმება
                                </button>
                            </div>
                        </div>

                        <!-- Add Button -->
                        <div v-if="!isAddingTask && !editingTaskId" class="mb-4">
                            <button
                                @click="startAddingTask"
                                class="flex items-center gap-2 px-4 py-2 bg-[#346ec9] text-white rounded-lg text-sm hover:bg-[#2d5eb0] transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>ახალი დავალება</span>
                            </button>
                        </div>

                        <!-- Loading -->
                        <div v-if="loading" class="text-center py-8 text-[#9ca3af]">
                            იტვირთება...
                        </div>

                        <!-- Task List -->
                        <div v-else class="space-y-2">
                            <div
                                v-for="task in recurringTasks"
                                :key="task.id"
                                class="group flex items-center justify-between p-3 bg-white rounded-lg border border-[#e5e7eb] hover:border-[#346ec9] transition-colors"
                                :class="{ 'opacity-50': !task.is_active }"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm text-[#1f2937] truncate" :class="{ 'line-through': !task.is_active }">
                                            {{ task.content }}
                                        </p>
                                    </div>
                                    <p class="text-xs text-[#9ca3af] mt-1">
                                        {{ task.schedule_description }}
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        @click="handleToggleTask(task.id)"
                                        class="p-1.5 rounded transition-colors"
                                        :class="task.is_active ? 'text-[#9ca3af] hover:text-orange-500' : 'text-orange-500 hover:text-[#346ec9]'"
                                        :title="task.is_active ? 'გამორთვა' : 'ჩართვა'"
                                    >
                                        <svg v-if="task.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="startEditingTask(task)"
                                        class="p-1.5 text-[#9ca3af] hover:text-[#346ec9] rounded transition-colors"
                                        title="რედაქტირება"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="handleDeleteTask(task.id)"
                                        class="p-1.5 text-[#9ca3af] hover:text-red-500 rounded transition-colors"
                                        title="წაშლა"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="recurringTasks.length === 0" class="text-center py-8 text-[#9ca3af]">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <p>განმეორებადი დავალებები არ არის</p>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="mt-6 p-3 bg-[#e5e7eb]/30 rounded-lg border border-[#e5e7eb]">
                            <div class="flex items-start gap-2 text-xs text-[#9ca3af]">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p>განმეორებადი დავალებები ავტომატურად დაემატება ყოველდღე შუადღის 12:00 საათზე.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-[#e5e7eb]/30 flex justify-end border-t border-[#e5e7eb]">
                        <button
                            @click="handleClose"
                            class="px-4 py-2 bg-[#346ec9] text-white rounded-lg hover:bg-[#2d5eb0] transition-colors"
                        >
                            დახურვა
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
}
</style>
