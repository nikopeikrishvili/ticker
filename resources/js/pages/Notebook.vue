<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import TodoList from '@/components/TodoList.vue';
import TimeLogList from '@/components/TimeLogList.vue';
import TodoModal from '@/components/TodoModal.vue';
import TimeLogModal from '@/components/TimeLogModal.vue';
import SettingsModal from '@/components/SettingsModal.vue';
import RecurringTasksModal from '@/components/RecurringTasksModal.vue';
import CarryOverDialog from '@/components/CarryOverDialog.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { Card } from '@/components/ui/card';
import { useTodos } from '@/composables/useTodos';
import { useTimeLogs } from '@/composables/useTimeLogs';
import { useSettings } from '@/composables/useSettings';

const { addTodo, initializeDate, fetchTodos, fetchPendingFromPrevious, startWorking } = useTodos();
const { timeLogs, addTimeLog, navigateDay, goToToday, formattedDate, isToday, fetchTimeLogs, stopTimeLog } = useTimeLogs();
const { fetchSettings, serverDate, appearance, updateSetting, applyTheme } = useSettings();

const showTodoModal = ref(false);
const showTimeLogModal = ref(false);
const showSettingsModal = ref(false);
const showRecurringTasksModal = ref(false);
const showCarryOverDialog = ref(false);
const pendingTasks = ref([]);

const openTodoModal = () => {
    showTodoModal.value = true;
};

const closeTodoModal = () => {
    showTodoModal.value = false;
};

const openTimeLogModal = () => {
    showTimeLogModal.value = true;
};

const closeTimeLogModal = () => {
    showTimeLogModal.value = false;
};

const handleSaveTodo = async (data) => {
    const { content, priority } = typeof data === 'string' ? { content: data, priority: 3 } : data;
    await addTodo(content, 'todo', priority);
    closeTodoModal();
};

const handleSaveAndStartTodo = async (data) => {
    const { content, priority } = typeof data === 'string' ? { content: data, priority: 3 } : data;

    // Stop any running time logs first
    const runningLogs = timeLogs.value.filter(log => !log.end_time);
    for (const log of runningLogs) {
        await stopTimeLog(log.id);
    }

    // Create the task and start working on it
    const todo = await addTodo(content, 'in_progress', priority);
    if (todo && todo.id) {
        await startWorking(todo.id);
    }

    closeTodoModal();
};

const handleSaveTimeLog = async (logData) => {
    // Stop any running time logs first
    const runningLogs = timeLogs.value.filter(log => !log.end_time);
    for (const log of runningLogs) {
        await stopTimeLog(log.id);
    }

    await addTimeLog(logData);
    closeTimeLogModal();
};

const handleSidebarAction = (action) => {
    switch (action) {
        case 'prevDay':
            navigateDay(-1);
            break;
        case 'today':
            goToToday();
            break;
        case 'nextDay':
            navigateDay(1);
            break;
        case 'newTodo':
            openTodoModal();
            break;
        case 'newTimeLog':
            openTimeLogModal();
            break;
        case 'recurring':
            showRecurringTasksModal.value = true;
            break;
    }
};

const toggleDarkMode = async () => {
    const newTheme = appearance.value.theme === 'dark' ? 'light' : 'dark';
    await updateSetting('appearance.theme', newTheme);
    applyTheme(newTheme);
};

const handleKeyDown = (e) => {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }

    if (e.key === 'Escape') {
        if (showTodoModal.value) {
            closeTodoModal();
        } else if (showTimeLogModal.value) {
            closeTimeLogModal();
        } else if (showSettingsModal.value) {
            showSettingsModal.value = false;
        } else if (showRecurringTasksModal.value) {
            showRecurringTasksModal.value = false;
        } else if (showCarryOverDialog.value) {
            showCarryOverDialog.value = false;
        }
        return;
    }

    if (showTodoModal.value || showTimeLogModal.value || showSettingsModal.value || showRecurringTasksModal.value || showCarryOverDialog.value) {
        return;
    }

    // Ignore shortcuts when modifier keys are pressed (allow browser shortcuts like Ctrl+R)
    if (e.ctrlKey || e.metaKey || e.altKey) {
        return;
    }

    if (e.key === 'ArrowLeft') {
        e.preventDefault();
        navigateDay(-1);
    } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        navigateDay(1);
    } else if (e.key === 't' || e.key === 'T') {
        e.preventDefault();
        goToToday();
    } else if (e.key === 'a' || e.key === 'A') {
        e.preventDefault();
        e.stopPropagation();
        openTodoModal();
    } else if (e.key === 's' || e.key === 'S') {
        e.preventDefault();
        e.stopPropagation();
        openTimeLogModal();
    } else if (e.key === 'w' || e.key === 'W') {
        e.preventDefault();
        window.location.href = '/weekly';
    } else if (e.key === 'r' || e.key === 'R') {
        e.preventDefault();
        showRecurringTasksModal.value = true;
    } else if (e.key === ',') {
        e.preventDefault();
        showSettingsModal.value = true;
    } else if (e.key === 'd' || e.key === 'D') {
        e.preventDefault();
        toggleDarkMode();
    }
};

// Dynamic CSS variables based on settings
const cssVars = computed(() => ({
    '--dot-opacity': appearance.value.dotOpacity,
    '--primary-color': appearance.value.primaryColor,
    '--bg-color': appearance.value.backgroundColor,
    '--text-color': appearance.value.textColor,
    '--secondary-text-color': appearance.value.secondaryTextColor,
    '--border-color': appearance.value.borderColor,
}));

onMounted(async () => {
    document.addEventListener('keydown', handleKeyDown, true);

    await fetchSettings();
    // Initialize date from server (respects timezone setting)
    if (serverDate.value) {
        initializeDate(serverDate.value);
        // Fetch data after date is initialized
        await Promise.all([fetchTodos(), fetchTimeLogs()]);

        // Check for pending tasks from previous dates
        const pending = await fetchPendingFromPrevious(serverDate.value);
        if (pending.length > 0) {
            pendingTasks.value = pending;
            showCarryOverDialog.value = true;
        }
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown, true);
});
</script>

<template>
    <Head title="რვეული" />
    <SidebarProvider :style="{ '--sidebar-width': '16rem', ...cssVars }">
        <AppSidebar
            current-page="notebook"
            :show-settings="() => showSettingsModal = true"
            @action="handleSidebarAction"
        />
        <SidebarInset>
            <header class="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                <SidebarTrigger class="-ml-1" />
                <Separator orientation="vertical" class="mr-2 h-4" />
                <div class="flex items-center gap-2">
                    <span class="font-medium">{{ formattedDate }}</span>
                    <span
                        v-if="isToday"
                        class="px-2 py-0.5 text-xs rounded-full bg-primary text-primary-foreground"
                    >
                        დღეს
                    </span>
                </div>
            </header>

            <div class="flex-1 p-4">
                <!-- Notebook Container -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-[calc(100vh-88px)]">
                    <!-- LEFT PAGE: Todo List -->
                    <Card class="p-6 overflow-hidden flex flex-col">
                        <TodoList :on-open-modal="openTodoModal" />
                    </Card>

                    <!-- RIGHT PAGE: Time Log -->
                    <Card class="p-6 overflow-hidden flex flex-col">
                        <TimeLogList :on-open-modal="openTimeLogModal" />
                    </Card>
                </div>
            </div>
        </SidebarInset>

        <!-- Modals -->
        <TodoModal :show="showTodoModal" @close="closeTodoModal" @save="handleSaveTodo" @save-and-start="handleSaveAndStartTodo" />
        <TimeLogModal :show="showTimeLogModal" @close="closeTimeLogModal" @save="handleSaveTimeLog" />
        <SettingsModal :show="showSettingsModal" @close="showSettingsModal = false" />
        <RecurringTasksModal :show="showRecurringTasksModal" @close="showRecurringTasksModal = false" />
        <CarryOverDialog
            :show="showCarryOverDialog"
            :pending-tasks="pendingTasks"
            @close="showCarryOverDialog = false"
            @carried-over="pendingTasks = []"
        />
    </SidebarProvider>
</template>

<style scoped>
kbd {
    font-family: monospace;
}
</style>
