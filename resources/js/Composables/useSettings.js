import { ref, computed } from 'vue';
import axios from 'axios';

// Default settings (must match backend)
const DEFAULTS = {
    'general.timezone': 'Asia/Tbilisi',
    'appearance.theme': 'light',
    'appearance.dot_opacity': '0.3',
    'appearance.primary_color': '#346ec9',
    'appearance.background_color': '#e5e7eb',
    'appearance.text_color': '#1f2937',
    'appearance.secondary_text_color': '#9ca3af',
    'appearance.border_color': '#e5e7eb',
    'integrations.jira.enabled': 'false',
    'integrations.jira.url': '',
    'integrations.jira.email': '',
    'integrations.jira.api_token': '',
    'integrations.jira.project_key': '',
    'integrations.jira.last_sync_at': '',
};

// Shared state (singleton)
const settings = ref({ ...DEFAULTS });
const loading = ref(false);
const error = ref(null);
const initialized = ref(false);
const serverDate = ref(null);

export function useSettings() {
    const fetchSettings = async () => {
        if (initialized.value) return;

        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get('/api/settings');
            settings.value = { ...DEFAULTS, ...response.data.settings };
            serverDate.value = response.data.server_date;
            initialized.value = true;
            // Apply theme after fetching
            const theme = settings.value['appearance.theme'] || DEFAULTS['appearance.theme'];
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        } catch (err) {
            error.value = err.message;
            console.error('Failed to fetch settings:', err);
        } finally {
            loading.value = false;
        }
    };

    const getSetting = (key) => {
        return computed(() => settings.value[key] ?? DEFAULTS[key] ?? null);
    };

    const updateSetting = async (key, value) => {
        try {
            await axios.post('/api/settings', { key, value });
            settings.value[key] = value;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to update setting:', err);
            throw err;
        }
    };

    const updateSettings = async (updates) => {
        try {
            const settingsArray = Object.entries(updates).map(([key, value]) => ({ key, value }));
            await axios.post('/api/settings/batch', { settings: settingsArray });
            Object.assign(settings.value, updates);
        } catch (err) {
            error.value = err.message;
            console.error('Failed to update settings:', err);
            throw err;
        }
    };

    const resetSetting = async (key) => {
        try {
            const response = await axios.post('/api/settings/reset', { key });
            settings.value[key] = response.data.value ?? DEFAULTS[key];
        } catch (err) {
            error.value = err.message;
            console.error('Failed to reset setting:', err);
            throw err;
        }
    };

    const resetAllSettings = async () => {
        try {
            await axios.post('/api/settings/reset-all');
            settings.value = { ...DEFAULTS };
        } catch (err) {
            error.value = err.message;
            console.error('Failed to reset all settings:', err);
            throw err;
        }
    };

    // Computed getters for general settings
    const general = computed(() => ({
        timezone: settings.value['general.timezone'] || DEFAULTS['general.timezone'],
    }));

    // Computed getters for appearance settings
    const appearance = computed(() => ({
        theme: settings.value['appearance.theme'] || DEFAULTS['appearance.theme'],
        dotOpacity: parseFloat(settings.value['appearance.dot_opacity'] || DEFAULTS['appearance.dot_opacity']),
        primaryColor: settings.value['appearance.primary_color'] || DEFAULTS['appearance.primary_color'],
        backgroundColor: settings.value['appearance.background_color'] || DEFAULTS['appearance.background_color'],
        textColor: settings.value['appearance.text_color'] || DEFAULTS['appearance.text_color'],
        secondaryTextColor: settings.value['appearance.secondary_text_color'] || DEFAULTS['appearance.secondary_text_color'],
        borderColor: settings.value['appearance.border_color'] || DEFAULTS['appearance.border_color'],
    }));

    // Apply theme to document
    const applyTheme = (theme) => {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    // Computed getters for integrations settings
    const integrations = computed(() => ({
        jira: {
            enabled: settings.value['integrations.jira.enabled'] === 'true',
            url: settings.value['integrations.jira.url'] || '',
            email: settings.value['integrations.jira.email'] || '',
            apiToken: settings.value['integrations.jira.api_token'] || '',
            projectKey: settings.value['integrations.jira.project_key'] || '',
            lastSyncAt: settings.value['integrations.jira.last_sync_at'] || '',
        },
    }));

    return {
        settings,
        loading,
        error,
        initialized,
        serverDate,
        DEFAULTS,
        fetchSettings,
        getSetting,
        updateSetting,
        updateSettings,
        resetSetting,
        resetAllSettings,
        general,
        appearance,
        integrations,
        applyTheme,
    };
}
