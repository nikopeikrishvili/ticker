<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useTimeLogs } from '@/composables/useTimeLogs';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const props = defineProps({
    onOpenModal: {
        type: Function,
        required: true,
    },
});

const { timeLogs, categories, loading, updateTimeLog, deleteTimeLog, stopTimeLog, fetchCategories } = useTimeLogs();

// Timer state for running logs
const now = ref(new Date());
let timerInterval = null;

// Check if there are any running logs (no end_time)
const hasRunningLogs = computed(() => {
    return timeLogs.value.some(log => !log.end_time);
});

// Start/stop timer based on whether we have running logs
watch(hasRunningLogs, (hasRunning) => {
    if (hasRunning && !timerInterval) {
        timerInterval = setInterval(() => {
            now.value = new Date();
        }, 1000);
    } else if (!hasRunning && timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}, { immediate: true });

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});

// Calculate elapsed minutes for a running log
const getElapsedMinutes = (log) => {
    if (!log.start_time || log.end_time) return 0;
    const [startHour, startMin] = log.start_time.split(':').map(Number);
    const startTotal = startHour * 60 + startMin;
    const nowTotal = now.value.getHours() * 60 + now.value.getMinutes();
    return Math.max(0, nowTotal - startTotal);
};

// Handle stopping a time log
const handleStopTimeLog = async (id) => {
    await stopTimeLog(id);
};

const groupBy = ref('none'); // 'none', 'task', 'category'
const showActivityModal = ref(false);
const editingId = ref(null);
const editLog = ref({});

onMounted(() => {
    fetchCategories();
});

const groupedTimeLogs = computed(() => {
    if (groupBy.value === 'task') {
        const groups = {};
        for (const log of timeLogs.value) {
            const key = log.task_id || 'no-task';
            if (!groups[key]) {
                groups[key] = {
                    key,
                    label: log.task_id || '-',
                    total_minutes: 0,
                    logs: [],
                };
            }
            // Include elapsed time for running logs
            const minutes = log.end_time ? (log.duration_minutes || 0) : getElapsedMinutes(log);
            groups[key].total_minutes += minutes;
            groups[key].logs.push(log);
        }
        return Object.values(groups).sort((a, b) => b.total_minutes - a.total_minutes);
    }

    if (groupBy.value === 'category') {
        const groups = {};
        for (const log of timeLogs.value) {
            const key = log.category_id || 'no-category';
            if (!groups[key]) {
                groups[key] = {
                    key,
                    label: log.category?.name || 'არ აქვს',
                    category: log.category,
                    total_minutes: 0,
                    logs: [],
                };
            }
            // Include elapsed time for running logs
            const minutes = log.end_time ? (log.duration_minutes || 0) : getElapsedMinutes(log);
            groups[key].total_minutes += minutes;
            groups[key].logs.push(log);
        }
        return Object.values(groups).sort((a, b) => b.total_minutes - a.total_minutes);
    }

    return null;
});

const formatMinutes = (minutes) => {
    if (!minutes || minutes === 0) return '00:00';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
};

const totalMinutes = computed(() => {
    return timeLogs.value.reduce((sum, log) => {
        if (log.end_time) {
            // Completed log - use stored duration
            return sum + (log.duration_minutes || 0);
        } else {
            // Running log - calculate elapsed time
            return sum + getElapsedMinutes(log);
        }
    }, 0);
});

const totalTime = computed(() => {
    return formatMinutes(totalMinutes.value);
});

// 8 hours = 480 minutes = 100%
const TARGET_MINUTES = 480;

const progressPercent = computed(() => {
    return Math.round((totalMinutes.value / TARGET_MINUTES) * 100);
});

const progressWidth = computed(() => {
    return Math.min(progressPercent.value, 100);
});

const isOvertime = computed(() => {
    return progressPercent.value > 100;
});

const remainingTime = computed(() => {
    const remaining = TARGET_MINUTES - totalMinutes.value;
    if (remaining <= 0) return '00:00';
    const hours = Math.floor(remaining / 60);
    const mins = remaining % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
});

const progressColor = computed(() => {
    const percent = progressPercent.value;
    if (percent <= 50) return 'bg-blue-500';
    if (percent <= 80) return 'bg-blue-600';
    if (percent <= 100) return 'bg-green-500';
    if (percent <= 120) return 'bg-amber-500';
    return 'bg-red-500';
});

// Editing functions
const startEditing = (log) => {
    editingId.value = log.id;
    editLog.value = {
        start_time: log.start_time,
        end_time: log.end_time,
        description: log.description || '',
    };
};

const saveEdit = async (id) => {
    await updateTimeLog(id, {
        start_time: editLog.value.start_time,
        end_time: editLog.value.end_time,
        description: editLog.value.description,
    });
    editingId.value = null;
};

const cancelEdit = () => {
    editingId.value = null;
    editLog.value = {};
};

const handleDelete = (id) => {
    if (confirm('ნამდვილად გსურთ წაშლა?')) {
        deleteTimeLog(id);
    }
};

const updateCategory = async (logId, categoryId) => {
    await updateTimeLog(logId, { category_id: categoryId });
};

// Icon mapping for categories
const getCategoryIcon = (iconName) => {
    const icons = {
        'phone': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />`,
        'message-square': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />`,
        'code': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />`,
        'git-pull-request': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 21a3 3 0 100-6 3 3 0 000 6zM6 9a3 3 0 100-6 3 3 0 000 6zM6 9v12M18 15V9a3 3 0 00-3-3H9" />`,
        'search': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />`,
        'list-todo': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />`,
        'tag': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />`,
        'folder': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />`,
        'file': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />`,
        'users': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />`,
        'mail': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />`,
        'coffee': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zm4-4h8m-4 0v4" />`,
    };
    return icons[iconName] || icons['tag'];
};

// Activity heatmap data - 24 hours x 60 minutes
const activityGrid = computed(() => {
    const grid = Array.from({ length: 24 }, () => Array(60).fill(false));

    for (const log of timeLogs.value) {
        if (!log.start_time || !log.end_time) continue;

        const [startHour, startMin] = log.start_time.split(':').map(Number);
        const [endHour, endMin] = log.end_time.split(':').map(Number);

        const startTotal = startHour * 60 + startMin;
        const endTotal = endHour * 60 + endMin;

        for (let m = startTotal; m < endTotal; m++) {
            const h = Math.floor(m / 60);
            const min = m % 60;
            if (h >= 0 && h < 24 && min >= 0 && min < 60) {
                grid[h][min] = true;
            }
        }
    }

    return grid;
});

const workHoursRange = computed(() => {
    let firstHour = 24;
    let lastHour = 0;

    for (let h = 0; h < 24; h++) {
        if (activityGrid.value[h].some(m => m)) {
            firstHour = Math.min(firstHour, h);
            lastHour = Math.max(lastHour, h);
        }
    }

    if (firstHour > lastHour) {
        return { start: 8, end: 18 };
    }

    return {
        start: Math.max(0, firstHour - 1),
        end: Math.min(23, lastHour + 1),
    };
});

const getHourWorkedMinutes = (hour) => {
    return activityGrid.value[hour].filter(m => m).length;
};
</script>

<template>
    <div class="h-full flex flex-col">
        <!-- Controls -->
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <label class="text-sm text-muted-foreground">დაჯგუფება:</label>
                <select
                    v-model="groupBy"
                    class="text-sm border rounded px-2 py-1 bg-background text-foreground"
                >
                    <option value="none">არცერთი</option>
                    <option value="task">დავალება</option>
                    <option value="category">კატეგორია</option>
                </select>
            </div>
            <div class="text-sm font-medium text-muted-foreground">
                სულ: {{ totalTime }}
            </div>
        </div>

        <!-- Grouped View -->
        <div v-if="groupBy !== 'none' && groupedTimeLogs" class="flex-1 overflow-y-auto">
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[140px]">{{ groupBy === 'task' ? 'დავალება' : 'კატეგორია' }}</TableHead>
                            <TableHead class="w-[80px]">დრო</TableHead>
                            <TableHead class="w-[80px]">ჩანაწერი</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="loading">
                            <TableRow>
                                <TableCell colspan="3" class="text-center py-8 text-muted-foreground">
                                    იტვირთება...
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else-if="groupedTimeLogs.length === 0">
                            <TableRow>
                                <TableCell colspan="3" class="text-center py-8 text-muted-foreground">
                                    ჩანაწერები არ მოიძებნა
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else>
                            <TableRow v-for="group in groupedTimeLogs" :key="group.key">
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <template v-if="groupBy === 'category' && group.category">
                                            <svg
                                                class="w-4 h-4 flex-shrink-0"
                                                :style="{ color: group.category.color }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                                v-html="getCategoryIcon(group.category.icon)"
                                            ></svg>
                                            <span class="text-sm">{{ group.category.name }}</span>
                                        </template>
                                        <template v-else>
                                            <span class="font-mono text-xs text-muted-foreground">{{ group.label }}</span>
                                        </template>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span class="px-2 py-0.5 bg-[#346ec9]/10 text-[#346ec9] text-xs rounded font-medium">
                                        {{ formatMinutes(group.total_minutes) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground">
                                    {{ group.logs.length }}
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Regular Time Log Table -->
        <div v-else class="flex-1 overflow-y-auto">
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[100px]">დავალება</TableHead>
                            <TableHead class="w-[50px]">კატ.</TableHead>
                            <TableHead class="w-[140px]">დრო</TableHead>
                            <TableHead class="w-[70px]">ხანგრძ.</TableHead>
                            <TableHead>აღწერა</TableHead>
                            <TableHead class="w-[60px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="loading">
                            <TableRow>
                                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                                    იტვირთება...
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else-if="timeLogs.length === 0">
                            <TableRow>
                                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                                    ჩანაწერები არ მოიძებნა
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else>
                            <TableRow v-for="log in timeLogs" :key="log.id">
                                <!-- Task ID -->
                                <TableCell class="font-mono text-xs text-muted-foreground">
                                    {{ log.task_id || '-' }}
                                </TableCell>

                                <!-- Category -->
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="sm" class="h-7 w-7 p-0">
                                                <template v-if="log.category">
                                                    <svg
                                                        class="w-4 h-4"
                                                        :style="{ color: log.category.color }"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                        v-html="getCategoryIcon(log.category.icon)"
                                                    ></svg>
                                                </template>
                                                <template v-else>
                                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                </template>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="start">
                                            <DropdownMenuLabel>კატეგორია</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                v-for="cat in categories"
                                                :key="cat.id"
                                                @click="updateCategory(log.id, cat.id)"
                                            >
                                                <svg
                                                    class="w-4 h-4 mr-2"
                                                    :style="{ color: cat.color }"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                    v-html="getCategoryIcon(cat.icon)"
                                                ></svg>
                                                {{ cat.name }}
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem @click="updateCategory(log.id, null)">
                                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                წაშლა
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>

                                <!-- Time Range -->
                                <TableCell>
                                    <div v-if="editingId === log.id" class="flex items-center gap-1">
                                        <input
                                            v-model="editLog.start_time"
                                            type="time"
                                            class="w-20 px-1 py-0.5 text-xs border rounded"
                                        />
                                        <span class="text-muted-foreground">→</span>
                                        <input
                                            v-model="editLog.end_time"
                                            type="time"
                                            class="w-20 px-1 py-0.5 text-xs border rounded"
                                        />
                                    </div>
                                    <div v-else class="flex items-center gap-1 text-sm font-medium">
                                        <span :class="!log.end_time ? 'text-green-600' : 'text-[#346ec9]'">{{ log.start_time }}</span>
                                        <span class="text-muted-foreground">→</span>
                                        <template v-if="log.end_time">
                                            <span class="text-[#346ec9]">{{ log.end_time }}</span>
                                        </template>
                                        <template v-else>
                                            <span class="relative flex items-center">
                                                <span class="animate-pulse text-green-600 font-semibold">მიმდინარე</span>
                                                <span class="absolute -right-1.5 -top-1.5 flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                                </span>
                                            </span>
                                        </template>
                                    </div>
                                </TableCell>

                                <!-- Duration -->
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <template v-if="!log.end_time">
                                            <!-- Running log - show elapsed time with stop button -->
                                            <span class="px-2 py-0.5 bg-green-500/10 text-green-600 text-xs rounded font-medium animate-pulse">
                                                {{ formatMinutes(getElapsedMinutes(log)) }}
                                            </span>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="h-6 w-6 p-0 text-red-500 hover:text-red-600 hover:bg-red-50"
                                                @click="handleStopTimeLog(log.id)"
                                                title="შეჩერება"
                                            >
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <rect x="6" y="6" width="12" height="12" rx="1" />
                                                </svg>
                                            </Button>
                                        </template>
                                        <template v-else>
                                            <span class="px-2 py-0.5 bg-[#346ec9]/10 text-[#346ec9] text-xs rounded font-medium">
                                                {{ log.formatted_duration }}
                                            </span>
                                        </template>
                                    </div>
                                </TableCell>

                                <!-- Description -->
                                <TableCell>
                                    <div v-if="editingId === log.id" class="flex items-center gap-1">
                                        <Input
                                            v-model="editLog.description"
                                            class="h-7 text-sm"
                                            @keyup.enter="saveEdit(log.id)"
                                            @keyup.esc="cancelEdit"
                                        />
                                        <Button size="sm" variant="ghost" class="h-7 w-7 p-0" @click="saveEdit(log.id)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </Button>
                                        <Button size="sm" variant="ghost" class="h-7 w-7 p-0" @click="cancelEdit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </Button>
                                    </div>
                                    <span
                                        v-else
                                        class="text-sm cursor-pointer"
                                        @dblclick="startEditing(log)"
                                    >
                                        {{ log.description || '' }}
                                    </span>
                                </TableCell>

                                <!-- Actions -->
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                </svg>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="startEditing(log)">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                                რედაქტირება
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="handleDelete(log.id)" class="text-red-600">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                წაშლა
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4 pt-4 border-t border-border">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <span class="text-xs text-muted-foreground">სამუშაო დღე (8 სთ)</span>
                    <span class="text-xs text-muted-foreground">
                        დარჩა: <span :class="isOvertime ? 'text-green-600 font-medium' : 'text-foreground font-medium'">{{ remainingTime }}</span>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="text-sm font-semibold"
                        :class="isOvertime ? 'text-amber-600' : hasRunningLogs ? 'text-green-600' : 'text-foreground'"
                    >
                        {{ totalTime }}
                    </span>
                    <span
                        class="text-xs font-medium"
                        :class="isOvertime ? 'text-amber-600' : 'text-muted-foreground'"
                    >
                        ({{ progressPercent }}%)
                    </span>
                    <button
                        @click="showActivityModal = true"
                        class="p-1 rounded hover:bg-muted transition-colors"
                        title="აქტივობის გრაფიკი"
                    >
                        <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="h-2 bg-muted rounded-full overflow-hidden">
                <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="progressColor"
                    :style="{ width: `${progressWidth}%` }"
                ></div>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-[10px] text-muted-foreground">0:00</span>
                <span class="text-[10px] text-muted-foreground">8:00</span>
            </div>
        </div>

        <!-- Activity Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showActivityModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="showActivityModal = false"
                >
                    <div class="absolute inset-0 bg-[#1f2937]/50"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden border border-[#e5e7eb]">
                        <!-- Header -->
                        <div class="bg-[#1f2937] px-6 py-4 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white">დღის აქტივობა</h3>
                            <button
                                @click="showActivityModal = false"
                                class="text-white/60 hover:text-white transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <!-- Legend -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="text-sm text-muted-foreground">
                                    სულ: <span class="font-medium text-foreground">{{ totalTime }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <span>ნაკლები</span>
                                    <div class="flex gap-0.5">
                                        <span class="w-3 h-3 rounded-sm bg-[#ebedf0]"></span>
                                        <span class="w-3 h-3 rounded-sm bg-[#9be9a8]"></span>
                                        <span class="w-3 h-3 rounded-sm bg-[#40c463]"></span>
                                        <span class="w-3 h-3 rounded-sm bg-[#30a14e]"></span>
                                        <span class="w-3 h-3 rounded-sm bg-[#216e39]"></span>
                                    </div>
                                    <span>მეტი</span>
                                </div>
                            </div>

                            <!-- Activity Grid -->
                            <div class="overflow-x-auto">
                                <div class="min-w-[500px]">
                                    <!-- Minutes header (show every 10 minutes) -->
                                    <div class="flex mb-1 ml-12">
                                        <div
                                            v-for="m in [0, 10, 20, 30, 40, 50]"
                                            :key="m"
                                            class="text-[10px] text-muted-foreground"
                                            :style="{ width: '60px', marginLeft: m === 0 ? '0' : '0' }"
                                        >
                                            :{{ m.toString().padStart(2, '0') }}
                                        </div>
                                    </div>

                                    <!-- Hour rows -->
                                    <div
                                        v-for="hour in (workHoursRange.end - workHoursRange.start + 1)"
                                        :key="workHoursRange.start + hour - 1"
                                        class="flex items-center gap-1 mb-0.5"
                                    >
                                        <!-- Hour label -->
                                        <div class="w-10 text-right text-[10px] text-muted-foreground pr-1">
                                            {{ (workHoursRange.start + hour - 1).toString().padStart(2, '0') }}:00
                                        </div>

                                        <!-- 60 minute boxes -->
                                        <div class="flex gap-px">
                                            <div
                                                v-for="minute in 60"
                                                :key="minute"
                                                class="w-1.5 h-3 rounded-sm transition-colors"
                                                :class="activityGrid[workHoursRange.start + hour - 1][minute - 1]
                                                    ? 'bg-[#40c463]'
                                                    : 'bg-[#ebedf0]'"
                                                :title="`${(workHoursRange.start + hour - 1).toString().padStart(2, '0')}:${(minute - 1).toString().padStart(2, '0')}`"
                                            ></div>
                                        </div>

                                        <!-- Hour total -->
                                        <div class="w-12 text-[10px] text-muted-foreground pl-2">
                                            {{ getHourWorkedMinutes(workHoursRange.start + hour - 1) }} წთ
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="mt-4 pt-4 border-t border-border flex items-center justify-between text-sm text-muted-foreground">
                                <span>{{ timeLogs.length }} ჩანაწერი</span>
                                <span>{{ workHoursRange.start }}:00 - {{ workHoursRange.end + 1 }}:00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
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
