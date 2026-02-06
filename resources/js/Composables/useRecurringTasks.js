import { ref } from 'vue';
import axios from 'axios';

// Shared state (singleton)
const recurringTasks = ref([]);
const loading = ref(false);
const error = ref(null);

export function useRecurringTasks() {

    const fetchRecurringTasks = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get('/api/recurring-tasks');
            recurringTasks.value = response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to fetch recurring tasks:', err);
        } finally {
            loading.value = false;
        }
    };

    const addRecurringTask = async (taskData) => {
        try {
            const response = await axios.post('/api/recurring-tasks', taskData);
            recurringTasks.value.unshift(response.data);
            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to add recurring task:', err);
            throw err;
        }
    };

    const updateRecurringTask = async (id, updates) => {
        try {
            const response = await axios.put(`/api/recurring-tasks/${id}`, updates);
            const index = recurringTasks.value.findIndex(t => t.id === id);
            if (index !== -1) {
                recurringTasks.value[index] = response.data;
            }
            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to update recurring task:', err);
            throw err;
        }
    };

    const deleteRecurringTask = async (id) => {
        try {
            await axios.delete(`/api/recurring-tasks/${id}`);
            recurringTasks.value = recurringTasks.value.filter(t => t.id !== id);
        } catch (err) {
            error.value = err.message;
            console.error('Failed to delete recurring task:', err);
            throw err;
        }
    };

    const toggleRecurringTask = async (id) => {
        try {
            const response = await axios.post(`/api/recurring-tasks/${id}/toggle`);
            const index = recurringTasks.value.findIndex(t => t.id === id);
            if (index !== -1) {
                recurringTasks.value[index].is_active = response.data.is_active;
            }
            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to toggle recurring task:', err);
            throw err;
        }
    };

    return {
        recurringTasks,
        loading,
        error,
        fetchRecurringTasks,
        addRecurringTask,
        updateRecurringTask,
        deleteRecurringTask,
        toggleRecurringTask,
    };
}
