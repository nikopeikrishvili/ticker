import { ref, computed } from 'vue';
import axios from 'axios';
import { useTodos } from './useTodos';

// Shared categories state (singleton)
const categories = ref([]);
const categoriesLoaded = ref(false);

export function useTimeLogs() {
    // Share state with useTodos to avoid circular dependency
    const { currentDate, timeLogs, timeLogsLoading, fetchTodos, setDate: setTodosDate, refreshTimeLogs } = useTodos();

    // Use shared loading state, local error state
    const loading = timeLogsLoading;
    const error = ref(null);

    const fetchCategories = async (force = false) => {
        if (categoriesLoaded.value && !force) return;
        try {
            const response = await axios.get('/api/time-logs/categories');
            categories.value = response.data;
            categoriesLoaded.value = true;
        } catch (err) {
            console.error('Failed to fetch categories:', err);
        }
    };

    const addCategory = async (categoryData) => {
        try {
            const response = await axios.post('/api/time-logs/categories', categoryData);
            categories.value.push(response.data);
            return response.data;
        } catch (err) {
            console.error('Failed to add category:', err);
            throw err;
        }
    };

    const updateCategoryData = async (id, updates) => {
        try {
            const response = await axios.put(`/api/time-logs/categories/${id}`, updates);
            const index = categories.value.findIndex(c => c.id === id);
            if (index !== -1) {
                categories.value[index] = response.data;
            }
            return response.data;
        } catch (err) {
            console.error('Failed to update category:', err);
            throw err;
        }
    };

    const deleteCategory = async (id) => {
        try {
            await axios.delete(`/api/time-logs/categories/${id}`);
            categories.value = categories.value.filter(c => c.id !== id);
        } catch (err) {
            console.error('Failed to delete category:', err);
            throw err;
        }
    };

    const fetchTimeLogs = async (date = null) => {
        const fetchDate = date || currentDate.value;
        if (!fetchDate) return; // Don't fetch if no date set yet
        await refreshTimeLogs(fetchDate);
        // Also fetch categories if not loaded
        if (!categoriesLoaded.value) {
            await fetchCategories();
        }
    };

    const addTimeLog = async (logData) => {
        try {
            // Include current date when adding
            const response = await axios.post('/api/time-logs', {
                ...logData,
                log_date: currentDate.value,
            });
            timeLogs.value.unshift(response.data); // Add to beginning (most recent first)
        } catch (err) {
            error.value = err.message;
            console.error('Failed to add time log:', err);
        }
    };

    const updateTimeLog = async (id, updates) => {
        try {
            const response = await axios.put(`/api/time-logs/${id}`, updates);
            const index = timeLogs.value.findIndex(log => log.id === id);
            if (index !== -1) {
                timeLogs.value[index] = response.data;
            }
        } catch (err) {
            error.value = err.message;
            console.error('Failed to update time log:', err);
        }
    };

    const deleteTimeLog = async (id) => {
        try {
            await axios.delete(`/api/time-logs/${id}`);
            timeLogs.value = timeLogs.value.filter(log => log.id !== id);
        } catch (err) {
            error.value = err.message;
            console.error('Failed to delete time log:', err);
        }
    };

    const stopTimeLog = async (id) => {
        try {
            // Get current time in HH:mm format
            const now = new Date();
            const endTime = now.toTimeString().slice(0, 5);
            const response = await axios.put(`/api/time-logs/${id}`, {
                end_time: endTime,
            });
            const index = timeLogs.value.findIndex(log => log.id === id);
            if (index !== -1) {
                timeLogs.value[index] = response.data;
            }
            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to stop time log:', err);
        }
    };

    const setDate = async (date) => {
        setTodosDate(date);
        await Promise.all([
            fetchTodos(date),
            fetchTimeLogs(date),
        ]);
    };

    const navigateDay = async (direction) => {
        const current = new Date(currentDate.value);
        current.setDate(current.getDate() + direction);
        await setDate(current.toISOString().split('T')[0]);
    };

    const goToToday = async () => {
        // Fetch current server date (respects timezone)
        try {
            const response = await axios.get('/api/settings');
            if (response.data.server_date) {
                await setDate(response.data.server_date);
            }
        } catch (err) {
            console.error('Failed to get server date:', err);
        }
    };

    const isToday = computed(() => {
        // This will be compared with server date after initialization
        // For now, it's a simple check - will be accurate after goToToday is called
        if (!currentDate.value) return false;
        return true; // Assume current view is "today" if we just loaded
    });

    // Format date for display
    const formattedDate = computed(() => {
        if (!currentDate.value) return 'იტვირთება...';
        const date = new Date(currentDate.value + 'T00:00:00'); // Parse as local date
        const days = ['კვირა', 'ორშაბათი', 'სამშაბათი', 'ოთხშაბათი', 'ხუთშაბათი', 'პარასკევი', 'შაბათი'];
        const months = ['იანვარი', 'თებერვალი', 'მარტი', 'აპრილი', 'მაისი', 'ივნისი', 'ივლისი', 'აგვისტო', 'სექტემბერი', 'ოქტომბერი', 'ნოემბერი', 'დეკემბერი'];

        const dayName = days[date.getDay()];
        const day = date.getDate();
        const month = months[date.getMonth()];

        return `${dayName}, ${day} ${month}`;
    });

    // Group time logs by category
    const timeLogsByCategory = computed(() => {
        const groups = {};

        for (const log of timeLogs.value) {
            const key = log.category_id || 'no-category';
            if (!groups[key]) {
                groups[key] = {
                    category_id: log.category_id,
                    category: log.category,
                    total_minutes: 0,
                    logs: [],
                };
            }
            groups[key].total_minutes += log.duration_minutes || 0;
            groups[key].logs.push(log);
        }

        return Object.values(groups).sort((a, b) => b.total_minutes - a.total_minutes);
    });

    return {
        timeLogs,
        categories,
        loading,
        error,
        currentDate,
        formattedDate,
        isToday,
        timeLogsByCategory,
        fetchTimeLogs,
        fetchCategories,
        addTimeLog,
        updateTimeLog,
        deleteTimeLog,
        stopTimeLog,
        addCategory,
        updateCategoryData,
        deleteCategory,
        setDate,
        navigateDay,
        goToToday,
    };
}
