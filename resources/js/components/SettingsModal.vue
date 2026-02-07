<script setup>
import { ref, watch } from 'vue';
import { useSettings } from '@/composables/useSettings';
import { useAuth } from '@/composables/useAuth';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['close']);

const { settings, general, appearance, integrations, updateSettings, resetAllSettings, applyTheme, DEFAULTS } = useSettings();
const { user, updateProfile, updatePassword } = useAuth();

const activeTab = ref('profile');
const localSettings = ref({});
const jiraTesting = ref(false);
const jiraTestResult = ref(null);
const jiraSyncing = ref(false);
const jiraSyncResult = ref(null);
const syncTaskModal = ref(false);
const syncTaskId = ref('');
const syncingTask = ref(false);
const syncTaskResult = ref(null);

// Profile state
const profileForm = ref({ name: '', email: '', current_password: '' });
const profileSaving = ref(false);
const profileMessage = ref(null);
const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' });
const passwordSaving = ref(false);
const passwordMessage = ref(null);

// Common timezones
const timezones = [
    { value: 'Asia/Tbilisi', label: 'თბილისი (GMT+4)' },
    { value: 'Europe/London', label: 'ლონდონი (GMT+0)' },
    { value: 'Europe/Paris', label: 'პარიზი (GMT+1)' },
    { value: 'Europe/Berlin', label: 'ბერლინი (GMT+1)' },
    { value: 'Europe/Moscow', label: 'მოსკოვი (GMT+3)' },
    { value: 'America/New_York', label: 'ნიუ-იორკი (GMT-5)' },
    { value: 'America/Los_Angeles', label: 'ლოს-ანჯელესი (GMT-8)' },
    { value: 'Asia/Dubai', label: 'დუბაი (GMT+4)' },
    { value: 'Asia/Tokyo', label: 'ტოკიო (GMT+9)' },
    { value: 'Australia/Sydney', label: 'სიდნეი (GMT+11)' },
];

const tabs = [
    { id: 'profile', label: 'პროფილი' },
    { id: 'general', label: 'ზოგადი' },
    { id: 'appearance', label: 'გარეგნობა' },
    { id: 'integrations', label: 'ინტეგრაციები' },
];

// Sync local settings when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        activeTab.value = 'profile';
        jiraTestResult.value = null;
        jiraSyncResult.value = null;
        syncTaskResult.value = null;
        // Reset profile forms
        profileForm.value = { name: user.value?.name || '', email: user.value?.email || '', current_password: '' };
        profileMessage.value = null;
        passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
        passwordMessage.value = null;
        localSettings.value = {
            timezone: general.value.timezone,
            theme: appearance.value.theme,
            dotOpacity: appearance.value.dotOpacity,
            primaryColor: appearance.value.primaryColor,
            backgroundColor: appearance.value.backgroundColor,
            textColor: appearance.value.textColor,
            secondaryTextColor: appearance.value.secondaryTextColor,
            borderColor: appearance.value.borderColor,
            // Jira
            jiraEnabled: integrations.value.jira.enabled,
            jiraUrl: integrations.value.jira.url,
            jiraEmail: integrations.value.jira.email,
            jiraApiToken: integrations.value.jira.apiToken,
            jiraProjectKey: integrations.value.jira.projectKey,
        };
    }
});

const saveProfile = async () => {
    profileSaving.value = true;
    profileMessage.value = null;
    try {
        await updateProfile(profileForm.value);
        profileForm.value.current_password = '';
        profileMessage.value = { success: true, text: 'პროფილი წარმატებით განახლდა.' };
    } catch (error) {
        const errors = error.response?.data?.errors;
        profileMessage.value = {
            success: false,
            text: errors ? Object.values(errors).flat().join(' ') : 'შეცდომა მოხდა.',
        };
    } finally {
        profileSaving.value = false;
    }
};

const savePassword = async () => {
    passwordSaving.value = true;
    passwordMessage.value = null;
    try {
        await updatePassword(passwordForm.value);
        passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
        passwordMessage.value = { success: true, text: 'პაროლი წარმატებით შეიცვალა.' };
    } catch (error) {
        const errors = error.response?.data?.errors;
        passwordMessage.value = {
            success: false,
            text: errors ? Object.values(errors).flat().join(' ') : 'შეცდომა მოხდა.',
        };
    } finally {
        passwordSaving.value = false;
    }
};

const handleSave = async () => {
    await updateSettings({
        'general.timezone': localSettings.value.timezone,
        'appearance.theme': localSettings.value.theme,
        'appearance.dot_opacity': String(localSettings.value.dotOpacity),
        'appearance.primary_color': localSettings.value.primaryColor,
        'appearance.background_color': localSettings.value.backgroundColor,
        'appearance.text_color': localSettings.value.textColor,
        'appearance.secondary_text_color': localSettings.value.secondaryTextColor,
        'appearance.border_color': localSettings.value.borderColor,
        // Jira
        'integrations.jira.enabled': localSettings.value.jiraEnabled ? 'true' : 'false',
        'integrations.jira.url': localSettings.value.jiraUrl,
        'integrations.jira.email': localSettings.value.jiraEmail,
        'integrations.jira.api_token': localSettings.value.jiraApiToken,
        'integrations.jira.project_key': localSettings.value.jiraProjectKey,
    });
    applyTheme(localSettings.value.theme);
    emit('close');
};

const handleReset = async () => {
    await resetAllSettings();
    localSettings.value = {
        timezone: DEFAULTS['general.timezone'],
        theme: DEFAULTS['appearance.theme'],
        dotOpacity: parseFloat(DEFAULTS['appearance.dot_opacity']),
        primaryColor: DEFAULTS['appearance.primary_color'],
        backgroundColor: DEFAULTS['appearance.background_color'],
        textColor: DEFAULTS['appearance.text_color'],
        secondaryTextColor: DEFAULTS['appearance.secondary_text_color'],
        borderColor: DEFAULTS['appearance.border_color'],
        jiraEnabled: false,
        jiraUrl: '',
        jiraEmail: '',
        jiraApiToken: '',
        jiraProjectKey: '',
    };
    applyTheme(DEFAULTS['appearance.theme']);
};

const handleClose = () => {
    emit('close');
};

// Jira actions
const testJiraConnection = async () => {
    jiraTesting.value = true;
    jiraTestResult.value = null;

    // Save settings first
    await updateSettings({
        'integrations.jira.enabled': localSettings.value.jiraEnabled ? 'true' : 'false',
        'integrations.jira.url': localSettings.value.jiraUrl,
        'integrations.jira.email': localSettings.value.jiraEmail,
        'integrations.jira.api_token': localSettings.value.jiraApiToken,
        'integrations.jira.project_key': localSettings.value.jiraProjectKey,
    });

    try {
        const response = await axios.post('/api/integrations/jira/test');
        jiraTestResult.value = response.data;
    } catch (error) {
        jiraTestResult.value = {
            success: false,
            message: error.response?.data?.message || 'Connection failed',
        };
    } finally {
        jiraTesting.value = false;
    }
};

const syncJira = async () => {
    jiraSyncing.value = true;
    jiraSyncResult.value = null;

    try {
        const response = await axios.post('/api/integrations/jira/sync');
        jiraSyncResult.value = response.data;
    } catch (error) {
        jiraSyncResult.value = {
            success: false,
            message: error.response?.data?.message || 'Sync failed',
        };
    } finally {
        jiraSyncing.value = false;
    }
};

const openSyncTaskModal = () => {
    syncTaskId.value = '';
    syncTaskResult.value = null;
    syncTaskModal.value = true;
};

const closeSyncTaskModal = () => {
    syncTaskModal.value = false;
};

const syncSingleTask = async () => {
    if (!syncTaskId.value.trim()) return;

    syncingTask.value = true;
    syncTaskResult.value = null;

    try {
        const response = await axios.post('/api/integrations/jira/sync-task', {
            ticket_id: syncTaskId.value.trim(),
        });
        syncTaskResult.value = response.data;
        if (response.data.success) {
            setTimeout(() => {
                closeSyncTaskModal();
            }, 1500);
        }
    } catch (error) {
        syncTaskResult.value = {
            success: false,
            message: error.response?.data?.message || 'Sync failed',
        };
    } finally {
        syncingTask.value = false;
    }
};

const formatLastSync = (isoDate) => {
    if (!isoDate) return 'არასდროს';
    const date = new Date(isoDate);
    return date.toLocaleString('ka-GE');
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
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl overflow-hidden border border-[#e5e7eb] dark:border-gray-700">
                    <!-- Header -->
                    <div class="bg-[#1f2937] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">პარამეტრები</h3>
                        <button
                            @click="handleClose"
                            class="text-white/60 hover:text-white transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="border-b border-[#e5e7eb] dark:border-gray-700 px-6">
                        <div class="flex gap-1">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                class="px-4 py-3 text-sm font-medium transition-colors border-b-2 -mb-px"
                                :class="activeTab === tab.id
                                    ? 'text-[#346ec9] border-[#346ec9]'
                                    : 'text-[#9ca3af] border-transparent hover:text-[#1f2937] dark:hover:text-gray-200'"
                            >
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6 max-h-[60vh] overflow-y-auto">
                        <!-- Profile Tab -->
                        <div v-if="activeTab === 'profile'">
                            <!-- User Info Section -->
                            <div class="mb-6">
                                <h4 class="text-sm font-semibold text-[#1f2937] dark:text-gray-200 mb-3 uppercase tracking-wide">მომხმარებლის ინფორმაცია</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-[#1f2937] dark:text-gray-200 mb-1">სახელი</label>
                                        <input
                                            v-model="profileForm.name"
                                            type="text"
                                            class="w-full px-3 py-2 border border-[#e5e7eb] dark:border-gray-600 rounded-lg text-sm text-[#1f2937] dark:text-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-[#1f2937] dark:text-gray-200 mb-1">ელ-ფოსტა</label>
                                        <input
                                            v-model="profileForm.email"
                                            type="email"
                                            class="w-full px-3 py-2 border border-[#e5e7eb] dark:border-gray-600 rounded-lg text-sm text-[#1f2937] dark:text-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                    <div v-if="profileForm.email !== (user?.email || '')">
                                        <label class="block text-sm font-medium text-[#1f2937] dark:text-gray-200 mb-1">მიმდინარე პაროლი</label>
                                        <input
                                            v-model="profileForm.current_password"
                                            type="password"
                                            placeholder="საჭიროა ელ-ფოსტის შეცვლისთვის"
                                            class="w-full px-3 py-2 border border-[#e5e7eb] dark:border-gray-600 rounded-lg text-sm text-[#1f2937] dark:text-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button
                                            @click="saveProfile"
                                            :disabled="profileSaving"
                                            class="px-4 py-2 bg-[#346ec9] text-white rounded-lg hover:bg-[#2d5eb0] transition-colors text-sm disabled:opacity-50"
                                        >
                                            {{ profileSaving ? 'ინახება...' : 'შენახვა' }}
                                        </button>
                                        <span
                                            v-if="profileMessage"
                                            class="text-sm"
                                            :class="profileMessage.success ? 'text-green-600' : 'text-red-600'"
                                        >
                                            {{ profileMessage.text }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Password Section -->
                            <div class="pt-6 border-t border-[#e5e7eb] dark:border-gray-700">
                                <h4 class="text-sm font-semibold text-[#1f2937] dark:text-gray-200 mb-3 uppercase tracking-wide">პაროლის შეცვლა</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-[#1f2937] dark:text-gray-200 mb-1">მიმდინარე პაროლი</label>
                                        <input
                                            v-model="passwordForm.current_password"
                                            type="password"
                                            class="w-full px-3 py-2 border border-[#e5e7eb] dark:border-gray-600 rounded-lg text-sm text-[#1f2937] dark:text-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-[#1f2937] dark:text-gray-200 mb-1">ახალი პაროლი</label>
                                        <input
                                            v-model="passwordForm.password"
                                            type="password"
                                            class="w-full px-3 py-2 border border-[#e5e7eb] dark:border-gray-600 rounded-lg text-sm text-[#1f2937] dark:text-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-[#1f2937] dark:text-gray-200 mb-1">ახალი პაროლი (ხელახლა)</label>
                                        <input
                                            v-model="passwordForm.password_confirmation"
                                            type="password"
                                            class="w-full px-3 py-2 border border-[#e5e7eb] dark:border-gray-600 rounded-lg text-sm text-[#1f2937] dark:text-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button
                                            @click="savePassword"
                                            :disabled="passwordSaving"
                                            class="px-4 py-2 bg-[#346ec9] text-white rounded-lg hover:bg-[#2d5eb0] transition-colors text-sm disabled:opacity-50"
                                        >
                                            {{ passwordSaving ? 'ინახება...' : 'პაროლის შეცვლა' }}
                                        </button>
                                        <span
                                            v-if="passwordMessage"
                                            class="text-sm"
                                            :class="passwordMessage.success ? 'text-green-600' : 'text-red-600'"
                                        >
                                            {{ passwordMessage.text }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- General Tab -->
                        <div v-else-if="activeTab === 'general'">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-[#1f2937] dark:text-gray-200 mb-2">დროის ზონა</label>
                                <select
                                    v-model="localSettings.timezone"
                                    class="w-full px-3 py-2 border border-[#e5e7eb] dark:border-gray-600 rounded-lg text-sm text-[#1f2937] dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-[#346ec9] bg-white dark:bg-gray-700"
                                >
                                    <option v-for="tz in timezones" :key="tz.value" :value="tz.value">
                                        {{ tz.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Appearance Tab -->
                        <div v-else-if="activeTab === 'appearance'">
                            <!-- Theme Toggle -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-[#1f2937] dark:text-gray-200 mb-3">თემა</label>
                                <div class="flex gap-3">
                                    <button
                                        @click="localSettings.theme = 'light'"
                                        class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg border-2 transition-all"
                                        :class="localSettings.theme === 'light'
                                            ? 'border-[#346ec9] bg-blue-50 dark:bg-blue-900/30'
                                            : 'border-[#e5e7eb] dark:border-gray-600 hover:border-gray-300'"
                                    >
                                        <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z" />
                                        </svg>
                                        <span class="text-sm font-medium text-[#1f2937] dark:text-gray-200">ნათელი</span>
                                    </button>
                                    <button
                                        @click="localSettings.theme = 'dark'"
                                        class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg border-2 transition-all"
                                        :class="localSettings.theme === 'dark'
                                            ? 'border-[#346ec9] bg-blue-50 dark:bg-blue-900/30'
                                            : 'border-[#e5e7eb] dark:border-gray-600 hover:border-gray-300'"
                                    >
                                        <svg class="w-5 h-5 text-indigo-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-sm font-medium text-[#1f2937] dark:text-gray-200">ბნელი</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Dot Opacity -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-[#1f2937] dark:text-gray-200 mb-2">
                                    წერტილების გამჭვირვალობა
                                    <span class="text-[#9ca3af] ml-2">{{ localSettings.dotOpacity }}</span>
                                </label>
                                <input
                                    v-model.number="localSettings.dotOpacity"
                                    type="range"
                                    min="0"
                                    max="1"
                                    step="0.1"
                                    class="w-full h-2 bg-[#e5e7eb] rounded-lg appearance-none cursor-pointer accent-[#346ec9]"
                                />
                            </div>

                            <!-- Colors Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Primary Color -->
                                <div>
                                    <label class="block text-sm font-medium text-[#1f2937] mb-2">მთავარი ფერი</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="localSettings.primaryColor"
                                            type="color"
                                            class="w-10 h-10 rounded border border-[#e5e7eb] cursor-pointer"
                                        />
                                        <input
                                            v-model="localSettings.primaryColor"
                                            type="text"
                                            class="flex-1 px-3 py-2 border border-[#e5e7eb] rounded-lg text-sm text-[#1f2937] focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                </div>

                                <!-- Background Color -->
                                <div>
                                    <label class="block text-sm font-medium text-[#1f2937] mb-2">ფონის ფერი</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="localSettings.backgroundColor"
                                            type="color"
                                            class="w-10 h-10 rounded border border-[#e5e7eb] cursor-pointer"
                                        />
                                        <input
                                            v-model="localSettings.backgroundColor"
                                            type="text"
                                            class="flex-1 px-3 py-2 border border-[#e5e7eb] rounded-lg text-sm text-[#1f2937] focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                </div>

                                <!-- Text Color -->
                                <div>
                                    <label class="block text-sm font-medium text-[#1f2937] mb-2">ტექსტის ფერი</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="localSettings.textColor"
                                            type="color"
                                            class="w-10 h-10 rounded border border-[#e5e7eb] cursor-pointer"
                                        />
                                        <input
                                            v-model="localSettings.textColor"
                                            type="text"
                                            class="flex-1 px-3 py-2 border border-[#e5e7eb] rounded-lg text-sm text-[#1f2937] focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                </div>

                                <!-- Secondary Text Color -->
                                <div>
                                    <label class="block text-sm font-medium text-[#1f2937] mb-2">მეორადი ტექსტი</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="localSettings.secondaryTextColor"
                                            type="color"
                                            class="w-10 h-10 rounded border border-[#e5e7eb] cursor-pointer"
                                        />
                                        <input
                                            v-model="localSettings.secondaryTextColor"
                                            type="text"
                                            class="flex-1 px-3 py-2 border border-[#e5e7eb] rounded-lg text-sm text-[#1f2937] focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                </div>

                                <!-- Border Color -->
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-[#1f2937] mb-2">საზღვრის ფერი</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="localSettings.borderColor"
                                            type="color"
                                            class="w-10 h-10 rounded border border-[#e5e7eb] cursor-pointer"
                                        />
                                        <input
                                            v-model="localSettings.borderColor"
                                            type="text"
                                            class="flex-1 px-3 py-2 border border-[#e5e7eb] rounded-lg text-sm text-[#1f2937] focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div class="mt-6">
                                <h4 class="text-sm font-semibold text-[#1f2937] mb-3 uppercase tracking-wide">გადახედვა</h4>
                                <div
                                    class="rounded-lg p-4 border"
                                    :style="{
                                        backgroundColor: localSettings.backgroundColor,
                                        borderColor: localSettings.borderColor,
                                    }"
                                >
                                    <div
                                        class="rounded-lg p-3"
                                        :style="{
                                            backgroundColor: '#ffffff',
                                            backgroundImage: `radial-gradient(circle, rgba(156, 163, 175, ${localSettings.dotOpacity}) 1px, transparent 1px)`,
                                            backgroundSize: '16px 16px',
                                        }"
                                    >
                                        <p :style="{ color: localSettings.textColor }" class="font-medium mb-1">მთავარი ტექსტი</p>
                                        <p :style="{ color: localSettings.secondaryTextColor }" class="text-sm">მეორადი ტექსტი</p>
                                        <button
                                            class="mt-2 px-3 py-1 text-white rounded text-sm"
                                            :style="{ backgroundColor: localSettings.primaryColor }"
                                        >
                                            ღილაკი
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Integrations Tab -->
                        <div v-else-if="activeTab === 'integrations'">
                            <!-- Jira Section -->
                            <div class="border border-[#e5e7eb] rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M11.571 11.513H0a5.218 5.218 0 0 0 5.232 5.215h2.13v2.057A5.215 5.215 0 0 0 12.575 24V12.518a1.005 1.005 0 0 0-1.005-1.005zm5.723-5.756H5.736a5.215 5.215 0 0 0 5.215 5.214h2.129v2.058a5.218 5.218 0 0 0 5.215 5.214V6.758a1.001 1.001 0 0 0-1.001-1.001zM23.013 0H11.455a5.215 5.215 0 0 0 5.215 5.215h2.129v2.057A5.215 5.215 0 0 0 24 12.483V1.005A1.005 1.005 0 0 0 23.013 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-[#1f2937]">Jira</h4>
                                            <p class="text-xs text-[#9ca3af]">დავალებების სინქრონიზაცია</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="localSettings.jiraEnabled"
                                            class="sr-only peer"
                                        />
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#346ec9] rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#346ec9]"></div>
                                    </label>
                                </div>

                                <div v-if="localSettings.jiraEnabled" class="space-y-4">
                                    <!-- Jira URL -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#1f2937] mb-1">Jira URL</label>
                                        <input
                                            v-model="localSettings.jiraUrl"
                                            type="url"
                                            placeholder="https://your-domain.atlassian.net"
                                            class="w-full px-3 py-2 border border-[#e5e7eb] rounded-lg text-sm text-[#1f2937] focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#1f2937] mb-1">ელ-ფოსტა</label>
                                        <input
                                            v-model="localSettings.jiraEmail"
                                            type="email"
                                            placeholder="your@email.com"
                                            class="w-full px-3 py-2 border border-[#e5e7eb] rounded-lg text-sm text-[#1f2937] focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>

                                    <!-- API Token -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#1f2937] mb-1">API Token</label>
                                        <input
                                            v-model="localSettings.jiraApiToken"
                                            type="password"
                                            placeholder="API Token"
                                            class="w-full px-3 py-2 border border-[#e5e7eb] rounded-lg text-sm text-[#1f2937] focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                        <p class="mt-1 text-xs text-[#9ca3af]">
                                            <a href="https://id.atlassian.com/manage-profile/security/api-tokens" target="_blank" class="text-[#346ec9] hover:underline">
                                                API Token-ის შექმნა
                                            </a>
                                        </p>
                                    </div>

                                    <!-- Project Key -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#1f2937] mb-1">პროექტის გასაღები</label>
                                        <input
                                            v-model="localSettings.jiraProjectKey"
                                            type="text"
                                            placeholder="PROJECT"
                                            class="w-full px-3 py-2 border border-[#e5e7eb] rounded-lg text-sm text-[#1f2937] focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                        />
                                    </div>

                                    <!-- Test Connection -->
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="testJiraConnection"
                                            :disabled="jiraTesting"
                                            class="px-3 py-1.5 text-sm bg-[#e5e7eb] text-[#1f2937] rounded-lg hover:bg-[#d1d5db] transition-colors disabled:opacity-50"
                                        >
                                            {{ jiraTesting ? 'იტვირთება...' : 'კავშირის ტესტი' }}
                                        </button>
                                        <span
                                            v-if="jiraTestResult"
                                            class="text-sm"
                                            :class="jiraTestResult.success ? 'text-green-600' : 'text-red-600'"
                                        >
                                            {{ jiraTestResult.success ? 'წარმატებული!' : jiraTestResult.message }}
                                        </span>
                                    </div>

                                    <!-- Sync Actions -->
                                    <div class="pt-4 border-t border-[#e5e7eb]">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm text-[#9ca3af]">
                                                ბოლო სინქრონიზაცია: {{ formatLastSync(integrations.jira.lastSyncAt) }}
                                            </span>
                                        </div>
                                        <div class="flex gap-2">
                                            <button
                                                @click="syncJira"
                                                :disabled="jiraSyncing"
                                                class="px-3 py-1.5 text-sm bg-[#346ec9] text-white rounded-lg hover:bg-[#2d5eb0] transition-colors disabled:opacity-50"
                                            >
                                                {{ jiraSyncing ? 'სინქრონიზაცია...' : 'სინქრონიზაცია' }}
                                            </button>
                                            <button
                                                @click="openSyncTaskModal"
                                                class="px-3 py-1.5 text-sm bg-[#e5e7eb] text-[#1f2937] rounded-lg hover:bg-[#d1d5db] transition-colors"
                                            >
                                                ტიკეტის სინქრონიზაცია
                                            </button>
                                        </div>
                                        <div
                                            v-if="jiraSyncResult"
                                            class="mt-3 p-3 rounded-lg text-sm"
                                            :class="jiraSyncResult.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                                        >
                                            <template v-if="jiraSyncResult.success">
                                                სინქრონიზებულია: {{ jiraSyncResult.synced }} (ახალი: {{ jiraSyncResult.created }}, განახლებული: {{ jiraSyncResult.updated }})
                                            </template>
                                            <template v-else>
                                                {{ jiraSyncResult.message }}
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer (hidden on profile tab which has its own save buttons) -->
                    <div v-if="activeTab !== 'profile'" class="px-6 py-4 bg-[#e5e7eb]/30 dark:bg-gray-900/30 flex justify-between border-t border-[#e5e7eb] dark:border-gray-700">
                        <button
                            @click="handleReset"
                            class="px-4 py-2 text-[#9ca3af] hover:text-[#1f2937] dark:hover:text-gray-200 hover:bg-[#e5e7eb] dark:hover:bg-gray-700 rounded-lg transition-colors text-sm"
                        >
                            საწყისზე დაბრუნება
                        </button>
                        <div class="flex gap-3">
                            <button
                                @click="handleClose"
                                class="px-4 py-2 text-[#1f2937] dark:text-gray-200 hover:bg-[#e5e7eb] dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                გაუქმება
                            </button>
                            <button
                                @click="handleSave"
                                class="px-4 py-2 bg-[#346ec9] text-white rounded-lg hover:bg-[#2d5eb0] transition-colors"
                            >
                                შენახვა
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sync Task Modal -->
                <Transition name="modal">
                    <div
                        v-if="syncTaskModal"
                        class="fixed inset-0 z-[60] flex items-center justify-center p-4"
                        @click.self="closeSyncTaskModal"
                    >
                        <div class="absolute inset-0 bg-[#1f2937]/50"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-[#e5e7eb] dark:border-gray-700">
                            <div class="bg-[#1f2937] px-6 py-4 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-white">ტიკეტის სინქრონიზაცია</h3>
                                <button
                                    @click="closeSyncTaskModal"
                                    class="text-white/60 hover:text-white transition-colors"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="p-6">
                                <label class="block text-sm font-medium text-[#1f2937] dark:text-gray-200 mb-2">ტიკეტის ID</label>
                                <input
                                    v-model="syncTaskId"
                                    type="text"
                                    placeholder="PROJECT-123"
                                    @keyup.enter="syncSingleTask"
                                    class="w-full px-3 py-2 border border-[#e5e7eb] dark:border-gray-600 rounded-lg text-sm text-[#1f2937] dark:text-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-[#346ec9]"
                                />
                                <div
                                    v-if="syncTaskResult"
                                    class="mt-3 p-3 rounded-lg text-sm"
                                    :class="syncTaskResult.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                                >
                                    {{ syncTaskResult.message }}
                                </div>
                            </div>
                            <div class="px-6 py-4 bg-[#e5e7eb]/30 dark:bg-gray-900/30 flex justify-end gap-3 border-t border-[#e5e7eb] dark:border-gray-700">
                                <button
                                    @click="closeSyncTaskModal"
                                    class="px-4 py-2 text-[#1f2937] dark:text-gray-200 hover:bg-[#e5e7eb] dark:hover:bg-gray-700 rounded-lg transition-colors"
                                >
                                    გაუქმება
                                </button>
                                <button
                                    @click="syncSingleTask"
                                    :disabled="syncingTask || !syncTaskId.trim()"
                                    class="px-4 py-2 bg-[#346ec9] text-white rounded-lg hover:bg-[#2d5eb0] transition-colors disabled:opacity-50"
                                >
                                    {{ syncingTask ? 'იტვირთება...' : 'სინქრონიზაცია' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
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

input[type="color"] {
    -webkit-appearance: none;
    padding: 0;
}

input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 0;
}

input[type="color"]::-webkit-color-swatch {
    border: none;
    border-radius: 4px;
}
</style>
