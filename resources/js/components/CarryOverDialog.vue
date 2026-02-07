<script setup>
import { ref, computed, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useTodos } from '@/composables/useTodos';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    pendingTasks: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'carried-over']);

const { carryOverTasks, currentDate } = useTodos();

const selectedTaskIds = ref([]);
const isCarryingOver = ref(false);

// Reset selection when dialog opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        // Select all tasks by default
        selectedTaskIds.value = props.pendingTasks.map(t => t.id);
    }
});

const isAllSelected = computed(() => {
    return props.pendingTasks.length > 0 && selectedTaskIds.value.length === props.pendingTasks.length;
});

const toggleAll = () => {
    if (isAllSelected.value) {
        selectedTaskIds.value = [];
    } else {
        selectedTaskIds.value = props.pendingTasks.map(t => t.id);
    }
};

const toggleTask = (taskId) => {
    const index = selectedTaskIds.value.indexOf(taskId);
    if (index === -1) {
        selectedTaskIds.value.push(taskId);
    } else {
        selectedTaskIds.value.splice(index, 1);
    }
};

const isTaskSelected = (taskId) => {
    return selectedTaskIds.value.includes(taskId);
};

const handleCarryOver = async () => {
    if (selectedTaskIds.value.length === 0) return;

    isCarryingOver.value = true;
    const success = await carryOverTasks(selectedTaskIds.value, currentDate.value);
    isCarryingOver.value = false;

    if (success) {
        emit('carried-over');
        emit('close');
    }
};

const handleClose = () => {
    emit('close');
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('ka-GE', {
        day: 'numeric',
        month: 'short',
    });
};

// Group tasks by date
const tasksByDate = computed(() => {
    const groups = {};
    for (const task of props.pendingTasks) {
        const date = task.todo_date;
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(task);
    }
    // Sort by date descending (most recent first)
    return Object.entries(groups).sort((a, b) => b[0].localeCompare(a[0]));
});
</script>

<template>
    <Dialog :open="show" @update:open="(val) => !val && handleClose()">
        <DialogContent class="sm:max-w-[640px]">
            <DialogHeader>
                <DialogTitle>დაუსრულებელი დავალებები</DialogTitle>
                <DialogDescription>
                    წინა დღეებიდან გაქვთ დაუსრულებელი დავალებები. გსურთ მათი კოპირება დღევანდელ დღეზე?
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <!-- Select All -->
                <div class="flex items-center gap-2 pb-3 border-b mb-3">
                    <input
                        type="checkbox"
                        id="select-all"
                        :checked="isAllSelected"
                        @change="toggleAll"
                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                    />
                    <label for="select-all" class="text-sm font-medium cursor-pointer">
                        ყველას არჩევა ({{ selectedTaskIds.length }}/{{ pendingTasks.length }})
                    </label>
                </div>

                <!-- Task List grouped by date -->
                <div class="max-h-[300px] overflow-y-auto space-y-4">
                    <div v-for="[date, tasks] in tasksByDate" :key="date">
                        <div class="text-xs font-medium text-muted-foreground mb-2">
                            {{ formatDate(date) }}
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="task in tasks"
                                :key="task.id"
                                class="flex items-start gap-3 p-2 rounded-lg hover:bg-muted/50 transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    :id="`task-${task.id}`"
                                    :checked="isTaskSelected(task.id)"
                                    @change="toggleTask(task.id)"
                                    class="h-4 w-4 mt-0.5 rounded border-gray-300 text-primary focus:ring-primary"
                                />
                                <label :for="`task-${task.id}`" class="flex-1 cursor-pointer">
                                    <p class="text-sm text-foreground">{{ task.content }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-muted text-muted-foreground">
                                            {{ task.display_id }}
                                        </span>
                                        <span
                                            v-if="task.total_time_spent > 0"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ Math.floor(task.total_time_spent / 60) }}:{{ String(task.total_time_spent % 60).padStart(2, '0') }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="pendingTasks.length === 0" class="text-center py-8 text-muted-foreground">
                        დაუსრულებელი დავალებები არ არის
                    </div>
                </div>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button variant="outline" @click="handleClose" :disabled="isCarryingOver">
                    გამოტოვება
                </Button>
                <Button
                    @click="handleCarryOver"
                    :disabled="selectedTaskIds.length === 0 || isCarryingOver"
                >
                    <span v-if="isCarryingOver">იტვირთება...</span>
                    <span v-else>კოპირება ({{ selectedTaskIds.length }})</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
