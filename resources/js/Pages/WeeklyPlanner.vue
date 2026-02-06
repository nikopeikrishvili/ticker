<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useWeeklyPlanner } from '@/Composables/useWeeklyPlanner';
import { useTodos } from '@/Composables/useTodos';
import { useSettings } from '@/Composables/useSettings';
import AppSidebar from '@/Components/AppSidebar.vue';
import DayColumn from '@/Components/Weekly/DayColumn.vue';
import BacklogColumn from '@/Components/Weekly/BacklogColumn.vue';
import CarryOverModal from '@/Components/Weekly/CarryOverModal.vue';
import SettingsModal from '@/Components/SettingsModal.vue';
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';

const props = defineProps({
    initialWeekKey: {
        type: String,
        default: null,
    },
});

const {
    fetchWeekData,
    navigateWeek,
    loading,
    currentWeekKey,
    weekDisplay,
    isCurrentWeek,
} = useWeeklyPlanner();

const { fetchSettings, appearance, updateSetting, applyTheme } = useSettings();
const showSettingsModal = ref(false);

// Dynamic CSS variables based on settings
const cssVars = computed(() => ({
    '--dot-opacity': appearance.value.dotOpacity,
    '--primary-color': appearance.value.primaryColor,
    '--bg-color': appearance.value.backgroundColor,
    '--text-color': appearance.value.textColor,
    '--secondary-text-color': appearance.value.secondaryTextColor,
    '--border-color': appearance.value.borderColor,
}));

const { updateTodo: updateTodoApi } = useTodos();

const showCarryOverModal = ref(false);

const handleTaskDropped = async () => {
    await fetchWeekData(currentWeekKey.value);
};

const handleToggleComplete = async (data) => {
    try {
        await updateTodoApi(data.id, { is_completed: data.is_completed });
        await fetchWeekData(currentWeekKey.value);
    } catch (err) {
        console.error('Failed to toggle task:', err);
    }
};

const handleSidebarAction = (action) => {
    switch (action) {
        case 'prevWeek':
            navigateWeek(-1);
            break;
        case 'currentWeek':
            navigateWeek(0);
            break;
        case 'nextWeek':
            navigateWeek(1);
            break;
        case 'carryOver':
            showCarryOverModal.value = true;
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

    if (e.altKey) {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            navigateWeek(-1);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            navigateWeek(1);
        }
        return;
    }

    if (e.key === 'Escape') {
        if (showCarryOverModal.value) {
            showCarryOverModal.value = false;
        } else if (showSettingsModal.value) {
            showSettingsModal.value = false;
        }
        return;
    }

    if (showCarryOverModal.value || showSettingsModal.value) {
        return;
    }

    // Ignore shortcuts when modifier keys are pressed (allow browser shortcuts like Ctrl+R)
    if (e.ctrlKey || e.metaKey) {
        return;
    }

    if (e.key === 'c' || e.key === 'C') {
        e.preventDefault();
        navigateWeek(0);
    } else if (e.key === 'w' || e.key === 'W') {
        e.preventDefault();
        window.location.href = '/';
    } else if (e.key === 'm' || e.key === 'M') {
        e.preventDefault();
        showCarryOverModal.value = true;
    } else if (e.key === ',') {
        e.preventDefault();
        showSettingsModal.value = true;
    } else if (e.key === 'd' || e.key === 'D') {
        e.preventDefault();
        toggleDarkMode();
    }
};

onMounted(async () => {
    document.addEventListener('keydown', handleKeyDown, true);
    await Promise.all([
        fetchWeekData(props.initialWeekKey),
        fetchSettings(),
    ]);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown, true);
});

const handleCarriedOver = () => {
    // Data is already refreshed by the carryOver function
};
</script>

<template>
    <SidebarProvider :style="{ '--sidebar-width': '16rem', ...cssVars }">
        <AppSidebar
            current-page="weekly"
            :show-settings="() => showSettingsModal = true"
            @action="handleSidebarAction"
        />
        <SidebarInset>
            <header class="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                <SidebarTrigger class="-ml-1" />
                <Separator orientation="vertical" class="mr-2 h-4" />
                <div class="flex items-center gap-2">
                    <span class="font-medium">{{ weekDisplay }}</span>
                    <span
                        v-if="isCurrentWeek"
                        class="px-2 py-0.5 text-xs rounded-full bg-primary text-primary-foreground"
                    >
                        მიმდინარე
                    </span>
                </div>
            </header>

            <div class="flex-1 p-4">
                <!-- Loading Overlay -->
                <div
                    v-if="loading"
                    class="fixed inset-0 bg-background/50 z-40 flex items-center justify-center"
                >
                    <div class="flex items-center gap-3 bg-card rounded-lg shadow-lg px-6 py-4 border">
                        <svg class="w-6 h-6 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-foreground">იტვირთება...</span>
                    </div>
                </div>

                <!-- Main Grid: 5 Days + Backlog -->
                <div class="grid grid-cols-6 gap-4 h-[calc(100vh-88px)]">
                    <!-- Day Columns (Mon-Fri) -->
                    <DayColumn
                        v-for="day in 5"
                        :key="day"
                        :day-of-week="day"
                        @task-dropped="handleTaskDropped"
                        @toggle-complete="handleToggleComplete"
                    />

                    <!-- Backlog Column -->
                    <BacklogColumn @task-dropped="handleTaskDropped" />
                </div>
            </div>
        </SidebarInset>

        <!-- Carry Over Modal -->
        <CarryOverModal
            :show="showCarryOverModal"
            @close="showCarryOverModal = false"
            @carried-over="handleCarriedOver"
        />

        <!-- Settings Modal -->
        <SettingsModal :show="showSettingsModal" @close="showSettingsModal = false" />
    </SidebarProvider>
</template>

<style scoped>
kbd {
    font-family: monospace;
}
</style>
