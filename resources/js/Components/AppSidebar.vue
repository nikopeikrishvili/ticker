<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    SidebarRail,
} from '@/components/ui/sidebar';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { useTimeLogs } from '@/Composables/useTimeLogs';
import { useAuth } from '@/Composables/useAuth';

const props = defineProps<{
    showSettings?: () => void;
    currentPage: 'notebook' | 'weekly';
}>();

const emit = defineEmits<{
    (e: 'action', action: string): void;
}>();

// Auth
const { user, logout } = useAuth();

// Category management
const { categories, fetchCategories, addCategory, updateCategoryData, deleteCategory } = useTimeLogs();
const showCategoryModal = ref(false);
const editingCategoryId = ref<number | null>(null);
const editingKeywords = ref('');

const newCategory = ref({
    name: '',
    icon: 'tag',
    color: '#6b7280',
    keywords: '',
});

const availableIcons = [
    { name: 'phone', label: 'ზარი' },
    { name: 'message-square', label: 'შეტყობინება' },
    { name: 'code', label: 'კოდი' },
    { name: 'git-pull-request', label: 'Code Review' },
    { name: 'search', label: 'ძიება' },
    { name: 'list-todo', label: 'დავალება' },
    { name: 'tag', label: 'თეგი' },
    { name: 'folder', label: 'საქაღალდე' },
    { name: 'file', label: 'ფაილი' },
    { name: 'users', label: 'შეხვედრა' },
    { name: 'mail', label: 'მეილი' },
    { name: 'coffee', label: 'შესვენება' },
];

const availableColors = [
    '#22c55e', '#3b82f6', '#8b5cf6', '#f97316', '#eab308', '#ec4899',
    '#14b8a6', '#f43f5e', '#6366f1', '#84cc16', '#06b6d4', '#6b7280',
];

const getCategoryIcon = (iconName: string) => {
    const icons: Record<string, string> = {
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

const handleAddCategory = async () => {
    if (!newCategory.value.name.trim()) return;
    try {
        await addCategory({
            name: newCategory.value.name,
            icon: newCategory.value.icon,
            color: newCategory.value.color,
            keywords: newCategory.value.keywords || null,
        });
        newCategory.value = { name: '', icon: 'tag', color: '#6b7280', keywords: '' };
    } catch (err) {
        console.error('Failed to add category:', err);
    }
};

const startEditingKeywords = (cat: any) => {
    editingCategoryId.value = cat.id;
    editingKeywords.value = cat.keywords || '';
};

const saveKeywords = async (catId: number) => {
    try {
        await updateCategoryData(catId, { keywords: editingKeywords.value || null });
        editingCategoryId.value = null;
        editingKeywords.value = '';
    } catch (err) {
        console.error('Failed to update keywords:', err);
    }
};

const cancelEditingKeywords = () => {
    editingCategoryId.value = null;
    editingKeywords.value = '';
};

const handleDeleteCategory = async (id: number) => {
    if (confirm('ნამდვილად გსურთ კატეგორიის წაშლა?')) {
        await deleteCategory(id);
    }
};

const openCategoryModal = () => {
    fetchCategories();
    showCategoryModal.value = true;
};

const navMain = computed(() => {
    if (props.currentPage === 'notebook') {
        return [
            {
                title: 'ნავიგაცია',
                items: [
                    { title: 'წინა დღე', icon: 'chevron-left', action: 'prevDay', shortcut: '←' },
                    { title: 'დღეს', icon: 'calendar', action: 'today', shortcut: 'T' },
                    { title: 'შემდეგი დღე', icon: 'chevron-right', action: 'nextDay', shortcut: '→' },
                ],
            },
            {
                title: 'მოქმედებები',
                items: [
                    { title: 'ახალი დავალება', icon: 'plus', action: 'newTodo', shortcut: 'A' },
                    { title: 'ახალი ჩანაწერი', icon: 'clock', action: 'newTimeLog', shortcut: 'S' },
                    { title: 'განმეორებადი', icon: 'refresh', action: 'recurring', shortcut: 'R' },
                    { title: 'კატეგორიები', icon: 'tag', action: 'categories', shortcut: 'K' },
                ],
            },
        ];
    } else {
        return [
            {
                title: 'ნავიგაცია',
                items: [
                    { title: 'წინა კვირა', icon: 'chevron-left', action: 'prevWeek', shortcut: 'Alt+←' },
                    { title: 'მიმდინარე კვირა', icon: 'calendar', action: 'currentWeek', shortcut: 'C' },
                    { title: 'შემდეგი კვირა', icon: 'chevron-right', action: 'nextWeek', shortcut: 'Alt+→' },
                ],
            },
            {
                title: 'მოქმედებები',
                items: [
                    { title: 'გადატანა', icon: 'arrow-right', action: 'carryOver', shortcut: 'M' },
                ],
            },
        ];
    }
});

const navSecondary = computed(() => {
    if (props.currentPage === 'notebook') {
        return [
            { title: 'კვირის დაგეგმვა', icon: 'calendar-days', href: '/weekly', shortcut: 'W' },
        ];
    } else {
        return [
            { title: 'Ticker', icon: 'book', href: '/', shortcut: 'W' },
        ];
    }
});
</script>

<template>
    <Sidebar collapsible="icon" variant="floating">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <a href="/">
                            <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <div class="flex flex-col gap-0.5 leading-none">
                                <span class="font-semibold">Ticker</span>
                                <span class="text-xs text-muted-foreground">{{ currentPage === 'notebook' ? 'რვეული' : 'კვირის დაგეგმვა' }}</span>
                            </div>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup v-for="group in navMain" :key="group.title">
                <SidebarGroupLabel>{{ group.title }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in group.items" :key="item.title">
                            <SidebarMenuButton @click="item.action === 'categories' ? openCategoryModal() : emit('action', item.action)" :tooltip="item.title">
                                <!-- Icons -->
                                <svg v-if="item.icon === 'chevron-left'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <svg v-else-if="item.icon === 'chevron-right'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <svg v-else-if="item.icon === 'calendar'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <svg v-else-if="item.icon === 'plus'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <svg v-else-if="item.icon === 'clock'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else-if="item.icon === 'refresh'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <svg v-else-if="item.icon === 'arrow-right'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                                <svg v-else-if="item.icon === 'tag'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <span>{{ item.title }}</span>
                                <kbd class="ml-auto px-1.5 py-0.5 text-xs rounded bg-muted text-muted-foreground group-data-[collapsible=icon]:hidden">{{ item.shortcut }}</kbd>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup class="mt-auto">
                <SidebarGroupLabel>გვერდები</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in navSecondary" :key="item.title">
                            <SidebarMenuButton as-child :tooltip="item.title">
                                <a :href="item.href">
                                    <svg v-if="item.icon === 'calendar-days'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'book'" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span>{{ item.title }}</span>
                                    <kbd class="ml-auto px-1.5 py-0.5 text-xs rounded bg-muted text-muted-foreground group-data-[collapsible=icon]:hidden">{{ item.shortcut }}</kbd>
                                </a>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton v-if="showSettings" @click="showSettings" tooltip="პარამეტრები">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>პარამეტრები</span>
                        <kbd class="ml-auto px-1.5 py-0.5 text-xs rounded bg-muted text-muted-foreground group-data-[collapsible=icon]:hidden">,</kbd>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <!-- User Info & Logout -->
                <SidebarMenuItem v-if="user">
                    <div class="flex items-center gap-2 px-2 py-1.5 group-data-[collapsible=icon]:justify-center">
                        <div class="flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-medium">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <span class="text-sm text-muted-foreground truncate group-data-[collapsible=icon]:hidden">{{ user.name }}</span>
                    </div>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton @click="logout" tooltip="გამოსვლა">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>გამოსვლა</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>

    <!-- Category Management Modal -->
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="showCategoryModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="showCategoryModal = false"
            >
                <div class="absolute inset-0 bg-[#1f2937]/50"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-[#e5e7eb] dark:border-gray-700">
                    <!-- Header -->
                    <div class="bg-[#1f2937] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">კატეგორიები</h3>
                        <button
                            @click="showCategoryModal = false"
                            class="text-white/60 hover:text-white transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Add New Category Form -->
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">ახალი კატეგორია</h4>
                            <div class="space-y-3">
                                <Input
                                    v-model="newCategory.name"
                                    placeholder="კატეგორიის სახელი"
                                    class="w-full"
                                    @keyup.enter="handleAddCategory"
                                />
                                <div class="flex items-center gap-3">
                                    <!-- Icon Selector -->
                                    <div class="flex-1">
                                        <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">იკონა</label>
                                        <div class="flex flex-wrap gap-1">
                                            <button
                                                v-for="icon in availableIcons"
                                                :key="icon.name"
                                                @click="newCategory.icon = icon.name"
                                                class="p-1.5 rounded border transition-colors"
                                                :class="newCategory.icon === icon.name ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'"
                                                :title="icon.label"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    :style="{ color: newCategory.color }"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                    v-html="getCategoryIcon(icon.name)"
                                                ></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <!-- Color Selector -->
                                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">ფერი</label>
                                    <div class="flex flex-wrap gap-1">
                                        <button
                                            v-for="color in availableColors"
                                            :key="color"
                                            @click="newCategory.color = color"
                                            class="w-6 h-6 rounded-full border-2 transition-transform"
                                            :class="newCategory.color === color ? 'scale-110 border-gray-800' : 'border-transparent hover:scale-105'"
                                            :style="{ backgroundColor: color }"
                                        ></button>
                                    </div>
                                </div>
                                <div>
                                    <!-- Keywords -->
                                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">საკვანძო სიტყვები (მძიმით გამოყოფილი)</label>
                                    <Input
                                        v-model="newCategory.keywords"
                                        placeholder="მაგ: call,ზარი,phone"
                                        class="w-full text-sm"
                                    />
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">აღწერაში ამ სიტყვების პოვნისას კატეგორია ავტომატურად მიენიჭება</p>
                                </div>
                                <Button
                                    @click="handleAddCategory"
                                    :disabled="!newCategory.name.trim()"
                                    class="w-full"
                                    size="sm"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    დამატება
                                </Button>
                            </div>
                        </div>

                        <!-- Existing Categories List -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">არსებული კატეგორიები</h4>
                            <div class="space-y-2 max-h-[300px] overflow-y-auto">
                                <div
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    class="p-2 rounded-lg border border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg
                                                class="w-5 h-5"
                                                :style="{ color: cat.color }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                                v-html="getCategoryIcon(cat.icon)"
                                            ></svg>
                                            <span class="text-sm font-medium dark:text-gray-200">{{ cat.name }}</span>
                                        </div>
                                        <button
                                            @click="handleDeleteCategory(cat.id)"
                                            class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                                            title="წაშლა"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!-- Keywords display/edit -->
                                    <div class="mt-1 pl-7">
                                        <div v-if="editingCategoryId === cat.id" class="flex items-center gap-2">
                                            <Input
                                                v-model="editingKeywords"
                                                placeholder="მაგ: call,ზარი,phone"
                                                class="flex-1 h-7 text-xs"
                                                @keyup.enter="saveKeywords(cat.id)"
                                                @keyup.escape="cancelEditingKeywords"
                                            />
                                            <button
                                                @click="saveKeywords(cat.id)"
                                                class="p-1 text-green-600 hover:text-green-700"
                                                title="შენახვა"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="cancelEditingKeywords"
                                                class="p-1 text-gray-400 hover:text-gray-600"
                                                title="გაუქმება"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div v-else class="flex items-center gap-1">
                                            <span v-if="cat.keywords" class="text-xs text-gray-500 dark:text-gray-400">{{ cat.keywords }}</span>
                                            <span v-else class="text-xs text-gray-400 dark:text-gray-500 italic">საკვანძო სიტყვები არ არის</span>
                                            <button
                                                @click="startEditingKeywords(cat)"
                                                class="p-0.5 text-gray-400 hover:text-blue-500 transition-colors"
                                                title="რედაქტირება"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="categories.length === 0" class="text-center py-4 text-gray-400 dark:text-gray-500 text-sm">
                                    კატეგორიები არ არის
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
kbd {
    font-family: monospace;
}

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
