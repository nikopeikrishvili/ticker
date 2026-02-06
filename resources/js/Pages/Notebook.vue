<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import TodoList from '@/Components/TodoList.vue';
import TimeLogList from '@/Components/TimeLogList.vue';
import TodoModal from '@/Components/TodoModal.vue';
import TimeLogModal from '@/Components/TimeLogModal.vue';
import SettingsModal from '@/Components/SettingsModal.vue';
import RecurringTasksModal from '@/Components/RecurringTasksModal.vue';
import CarryOverDialog from '@/Components/CarryOverDialog.vue';
import AppSidebar from '@/Components/AppSidebar.vue';
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { Card } from '@/components/ui/card';
import { useTodos } from '@/Composables/useTodos';
import { useTimeLogs } from '@/Composables/useTimeLogs';
import { useSettings } from '@/Composables/useSettings';

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

// Notification system for active timers
let notificationInterval = null;
const lastNotifiedHour = ref({});
const notificationPermission = ref('default');

const requestNotificationPermission = async () => {
    if (!('Notification' in window)) {
        console.log('Notifications not supported');
        return;
    }

    console.log('Current notification permission:', Notification.permission);

    // If already granted, just update state
    if (Notification.permission === 'granted') {
        notificationPermission.value = 'granted';
        return;
    }

    // Try to request permission
    const result = await Notification.requestPermission();
    notificationPermission.value = result;
    console.log('Notification permission result:', result);

    if (result === 'granted') {
        // Test notification
        new Notification('შეტყობინებები ჩართულია!', {
            body: 'თქვენ მიიღებთ შეტყობინებას ყოველ საათში აქტიური ტაიმერისთვის.',
            icon: '/favicon.ico',
        });
    }
};

const getElapsedMinutesForLog = (log) => {
    if (!log.start_time || log.end_time) return 0;
    const [startHour, startMin] = log.start_time.split(':').map(Number);
    const startTotal = startHour * 60 + startMin;
    const now = new Date();
    const nowTotal = now.getHours() * 60 + now.getMinutes();
    return Math.max(0, nowTotal - startTotal);
};

const sendTimerNotification = (log, elapsedMinutes) => {
    if ('Notification' in window && Notification.permission === 'granted') {
        const hours = Math.floor(elapsedMinutes / 60);
        const mins = elapsedMinutes % 60;
        const timeStr = `${hours}:${mins.toString().padStart(2, '0')}`;

        console.log('Sending notification for', log.description, timeStr);

        new Notification('ტაიმერი აქტიურია', {
            body: `${log.description || 'დავალება'} - ${timeStr} გავიდა`,
            icon: '/favicon.ico',
            tag: `timer-${log.id}-${hours}h`,
            requireInteraction: true,
        });
    }
};

const checkHourlyNotifications = () => {
    const runningLogs = timeLogs.value.filter(log => !log.end_time);
    console.log('Checking notifications, running logs:', runningLogs.length);

    for (const log of runningLogs) {
        const elapsedMinutes = getElapsedMinutesForLog(log);
        const currentHour = Math.floor(elapsedMinutes / 60);

        console.log(`Log ${log.id}: ${elapsedMinutes} mins, hour ${currentHour}, last notified: ${lastNotifiedHour.value[log.id]}`);

        // Send notification when a new hour is reached (and at least 1 hour has passed)
        if (currentHour > 0 && lastNotifiedHour.value[log.id] !== currentHour) {
            lastNotifiedHour.value[log.id] = currentHour;
            sendTimerNotification(log, elapsedMinutes);
        }
    }

    // Clean up entries for stopped logs
    const runningIds = new Set(runningLogs.map(l => l.id));
    for (const id in lastNotifiedHour.value) {
        if (!runningIds.has(Number(id))) {
            delete lastNotifiedHour.value[id];
        }
    }
};

const startNotificationChecker = () => {
    if (notificationInterval) return;

    // Check every minute
    notificationInterval = setInterval(checkHourlyNotifications, 60000);
    // Also check immediately
    checkHourlyNotifications();
    console.log('Notification checker started');
};

onMounted(async () => {
    document.addEventListener('keydown', handleKeyDown, true);

    // Request notification permission immediately on page load
    await requestNotificationPermission();

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

        // Start notification checker after data is loaded
        startNotificationChecker();
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown, true);
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
});
</script>

<template>
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
                <div class="flex-1"></div>
                <!-- Notification permission button -->
                <button
                    v-if="notificationPermission === 'denied' || notificationPermission === 'default'"
                    @click="requestNotificationPermission"
                    class="flex items-center gap-1.5 px-2 py-1 rounded text-xs transition-colors"
                    :class="notificationPermission === 'denied'
                        ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50'
                        : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 hover:bg-yellow-200 dark:hover:bg-yellow-900/50'"
                    :title="notificationPermission === 'denied'
                        ? 'დააჭირეთ ან ბრაუზერის პარამეტრებში ჩართეთ'
                        : 'დააჭირეთ შეტყობინებების ჩასართავად'"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span v-if="notificationPermission === 'denied'">შეტყობინებები დაბლოკილია</span>
                    <span v-else>შეტყობინებების ჩართვა</span>
                </button>
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
