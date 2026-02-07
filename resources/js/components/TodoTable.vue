<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useTodos } from '@/composables/useTodos';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const props = defineProps({
    onOpenModal: {
        type: Function,
        required: true,
    },
});

const {
    todos,
    loading,
    statuses,
    updateTodo,
    deleteTodo,
    startWorking,
    stopWorking,
} = useTodos();

const editingId = ref(null);
const editContent = ref('');
const now = ref(new Date());
let timerInterval = null;

const statusConfig = {
    backlog: { label: 'ბექლოგი', variant: 'secondary', icon: 'help-circle' },
    todo: { label: 'გასაკეთებელი', variant: 'outline', icon: 'circle' },
    in_progress: { label: 'მიმდინარე', variant: 'default', icon: 'timer' },
    done: { label: 'დასრულებული', variant: 'success', icon: 'check-circle' },
};

const sections = [
    {
        key: 'in_progress',
        label: 'მიმდინარე',
        borderClass: 'border-yellow-300 dark:border-yellow-700',
        headerBg: 'bg-yellow-100 dark:bg-yellow-900/40',
        headerText: 'text-yellow-700 dark:text-yellow-400',
        rowBg: 'bg-yellow-50/50 dark:bg-yellow-900/20',
        dotColor: 'bg-yellow-500',
        pulse: true,
    },
    {
        key: 'todo',
        label: 'გასაკეთებელი',
        borderClass: 'border-blue-300 dark:border-blue-700',
        headerBg: 'bg-blue-100 dark:bg-blue-900/40',
        headerText: 'text-blue-700 dark:text-blue-400',
        rowBg: '',
        dotColor: 'bg-blue-500',
        pulse: false,
    },
    {
        key: 'backlog',
        label: 'ბექლოგი',
        borderClass: 'border-gray-300 dark:border-gray-600',
        headerBg: 'bg-gray-100 dark:bg-gray-800/40',
        headerText: 'text-gray-700 dark:text-gray-400',
        rowBg: '',
        dotColor: 'bg-gray-400',
        pulse: false,
    },
    {
        key: 'done',
        label: 'დასრულებული',
        borderClass: 'border-green-300 dark:border-green-700',
        headerBg: 'bg-green-100 dark:bg-green-900/40',
        headerText: 'text-green-700 dark:text-green-400',
        rowBg: 'bg-green-50/50 dark:bg-green-900/20',
        dotColor: 'bg-green-500',
        pulse: false,
    },
];

const collapsedSections = ref({
    in_progress: false,
    todo: false,
    backlog: false,
    done: false,
});

const toggleSection = (key) => {
    collapsedSections.value[key] = !collapsedSections.value[key];
};

const todosForStatus = (status) => {
    return todos.value.filter(todo => todo.status === status);
};

// Total count for footer
const totalCount = computed(() => {
    return todos.value.length;
});

// Check if any task is working to enable timer
const hasWorkingTask = computed(() => {
    return todos.value.some(todo => todo.is_working);
});

// Calculate elapsed time for a working task
const getElapsedMinutes = (workingStartedAt) => {
    if (!workingStartedAt) return 0;
    const start = new Date(workingStartedAt);
    const diff = now.value - start;
    return Math.floor(diff / 60000); // Convert ms to minutes
};

// Get total time including current session
const getTotalWithCurrent = (todo) => {
    const base = todo.total_time_spent || 0;
    if (todo.is_working && todo.working_started_at) {
        return base + getElapsedMinutes(todo.working_started_at);
    }
    return base;
};

const startEditing = (todo) => {
    editingId.value = todo.id;
    editContent.value = todo.content;
};

const saveEdit = async (id) => {
    if (editContent.value.trim()) {
        await updateTodo(id, { content: editContent.value });
    }
    editingId.value = null;
};

const cancelEdit = () => {
    editingId.value = null;
    editContent.value = '';
};

const handleDelete = (id) => {
    if (confirm('ნამდვილად გსურთ წაშლა?')) {
        deleteTodo(id);
    }
};

const formatTime = (minutes) => {
    if (minutes === null || minutes === undefined) return null;
    if (minutes === 0) return '00:00';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
};

const getStatusVariant = (status) => {
    const variants = {
        backlog: 'secondary',
        todo: 'outline',
        in_progress: 'default',
        done: 'success',
    };
    return variants[status] || 'secondary';
};

const getPriorityColor = (priority) => {
    switch (priority) {
        case 1: return 'bg-gray-300';
        case 2: return 'bg-gray-400';
        case 3: return 'bg-gray-400';
        case 4: return 'bg-orange-500';
        case 5: return 'bg-red-500';
        default: return 'bg-gray-300';
    }
};

const priorityLabels = {
    1: 'ძალიან დაბალი',
    2: 'დაბალი',
    3: 'საშუალო',
    4: 'მაღალი',
    5: 'ძალიან მაღალი',
};

// Start/stop timer based on working tasks
onMounted(() => {
    timerInterval = setInterval(() => {
        if (hasWorkingTask.value) {
            now.value = new Date();
        }
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>

<template>
    <div class="w-full space-y-3">
        <!-- Loading State -->
        <div v-if="loading" class="rounded-md border p-8 text-center text-muted-foreground">
            იტვირთება...
        </div>

        <!-- Empty State -->
        <div v-else-if="totalCount === 0" class="rounded-md border p-8 text-center text-muted-foreground">
            დავალებები არ მოიძებნა
        </div>

        <template v-else>
            <template v-for="section in sections" :key="section.key">
                <div v-if="todosForStatus(section.key).length > 0" class="rounded-md border overflow-hidden" :class="section.borderClass">
                    <!-- Collapsible Header -->
                    <button
                        class="w-full px-4 py-2 flex items-center gap-2 cursor-pointer select-none"
                        :class="section.headerBg"
                        @click="toggleSection(section.key)"
                    >
                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="[section.headerText, collapsedSections[section.key] ? '-rotate-90' : '']"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span
                            class="w-2 h-2 rounded-full"
                            :class="[section.dotColor, section.pulse ? 'animate-pulse' : '']"
                        ></span>
                        <span class="text-sm font-semibold" :class="section.headerText">
                            {{ section.label }} ({{ todosForStatus(section.key).length }})
                        </span>
                    </button>

                    <!-- Table Body -->
                    <Table v-show="!collapsedSections[section.key]">
                        <TableBody>
                            <TableRow v-for="todo in todosForStatus(section.key)" :key="section.key + '-' + todo.id" :class="section.rowBg">
                                <TableCell class="w-[100px] font-mono text-xs text-muted-foreground">{{ todo.display_id }}</TableCell>
                                <TableCell class="w-[60px]">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="sm" class="h-6 px-1" :title="priorityLabels[todo.priority || 3]">
                                                <div class="flex gap-px"><span v-for="i in 5" :key="i" class="w-1.5 h-1.5 rounded-full" :class="i <= (todo.priority || 3) ? getPriorityColor(todo.priority || 3) : 'bg-gray-200'"></span></div>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="start">
                                            <DropdownMenuLabel>პრიორიტეტის შეცვლა</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem v-for="p in [5, 4, 3, 2, 1]" :key="p" @click="updateTodo(todo.id, { priority: p })">
                                                <div class="flex items-center gap-2"><div class="flex gap-px"><span v-for="i in 5" :key="i" class="w-1.5 h-1.5 rounded-full" :class="i <= p ? getPriorityColor(p) : 'bg-gray-200'"></span></div><span class="text-xs">{{ priorityLabels[p] }}</span></div>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                                <TableCell>
                                    <div v-if="editingId === todo.id" class="flex items-center gap-2">
                                        <Input v-model="editContent" @keyup.enter="saveEdit(todo.id)" @keyup.esc="cancelEdit" class="h-8" autofocus />
                                        <Button size="sm" variant="ghost" @click="saveEdit(todo.id)"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></Button>
                                        <Button size="sm" variant="ghost" @click="cancelEdit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></Button>
                                    </div>
                                    <div v-else class="flex items-center gap-2">
                                        <span :class="{ 'line-through text-muted-foreground': todo.status === 'done' }" class="cursor-pointer" @dblclick="startEditing(todo)">{{ todo.content }}</span>
                                        <span v-if="todo.is_working" class="flex items-center gap-1 text-xs text-yellow-600"><span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>მუშაობს</span>
                                    </div>
                                </TableCell>
                                <TableCell class="w-[140px]">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild><Button variant="ghost" size="sm" class="h-7 px-2"><Badge :variant="getStatusVariant(todo.status)">{{ statusConfig[todo.status]?.label || todo.status }}</Badge></Button></DropdownMenuTrigger>
                                        <DropdownMenuContent align="start">
                                            <DropdownMenuLabel>სტატუსის შეცვლა</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem @click="updateTodo(todo.id, { status: 'backlog' })"><span class="w-2 h-2 rounded-full bg-gray-400 mr-2"></span>ბექლოგი</DropdownMenuItem>
                                            <DropdownMenuItem @click="updateTodo(todo.id, { status: 'todo' })"><span class="w-2 h-2 rounded-full bg-blue-400 mr-2"></span>გასაკეთებელი</DropdownMenuItem>
                                            <DropdownMenuItem @click="updateTodo(todo.id, { status: 'in_progress' })"><span class="w-2 h-2 rounded-full bg-yellow-400 mr-2"></span>მიმდინარე</DropdownMenuItem>
                                            <DropdownMenuItem @click="updateTodo(todo.id, { status: 'done' })"><span class="w-2 h-2 rounded-full bg-green-400 mr-2"></span>დასრულებული</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                                <TableCell class="w-[60px]">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild><Button variant="ghost" size="sm" class="h-8 w-8 p-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg></Button></DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem v-if="todo.is_working" @click="stopWorking(todo.id)"><svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>შეჩერება</DropdownMenuItem>
                                            <DropdownMenuItem v-if="todo.status !== 'done' && !todo.is_working" @click="startWorking(todo.id)"><svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>დაწყება</DropdownMenuItem>
                                            <DropdownMenuItem @click="startEditing(todo)"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>რედაქტირება</DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem @click="handleDelete(todo.id)" class="text-red-600"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>წაშლა</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </template>
        </template>

        <!-- Footer -->
        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span>{{ totalCount }} დავალება</span>
        </div>
    </div>
</template>
