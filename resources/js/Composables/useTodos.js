import { ref } from 'vue';
import axios from 'axios';

// Shared state (singleton)
const todos = ref([]);
const loading = ref(false);
const error = ref(null);
const currentDate = ref(null); // Will be set from server
const statuses = ref({
    backlog: 'ბექლოგი',
    todo: 'გასაკეთებელი',
    in_progress: 'მიმდინარე',
    done: 'დასრულებული',
});

// Shared time logs state (to avoid circular dependency)
const timeLogs = ref([]);
const timeLogsLoading = ref(false);

const refreshTimeLogs = async (date) => {
    timeLogsLoading.value = true;
    try {
        const response = await axios.get('/api/time-logs', {
            params: { date }
        });
        timeLogs.value = response.data;
    } catch (err) {
        console.error('Failed to refresh time logs:', err);
    } finally {
        timeLogsLoading.value = false;
    }
};

export function useTodos() {

    const fetchTodos = async (date = null) => {
        const fetchDate = date || currentDate.value;
        if (!fetchDate) return; // Don't fetch if no date set yet
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get('/api/todos', {
                params: { date: fetchDate }
            });
            todos.value = response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to fetch todos:', err);
        } finally {
            loading.value = false;
        }
    };

    const fetchStatuses = async () => {
        try {
            const response = await axios.get('/api/todos/statuses');
            statuses.value = response.data;
        } catch (err) {
            console.error('Failed to fetch statuses:', err);
        }
    };

    const addTodo = async (content, status = 'todo', priority = 3, todoDate = undefined) => {
        try {
            // If todoDate is undefined, use currentDate; if null, send null (backlog)
            const dateToSend = todoDate === undefined ? currentDate.value : todoDate;

            const response = await axios.post('/api/todos', {
                content,
                todo_date: dateToSend,
                status,
                priority,
            });
            // Re-fetch to get proper ordering (only if we have a date)
            if (currentDate.value) {
                await fetchTodos(currentDate.value);
            }
            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to add todo:', err);
        }
    };

    const updateTodo = async (id, updates) => {
        try {
            const response = await axios.put(`/api/todos/${id}`, updates);
            // Re-fetch to get proper ordering when status or priority changes
            if (updates.status !== undefined || updates.priority !== undefined) {
                await fetchTodos(currentDate.value);
                // Also refresh time logs when status changes (timer may start/stop)
                if (updates.status !== undefined) {
                    await refreshTimeLogs(currentDate.value);
                }
            } else {
                const index = todos.value.findIndex(t => t.id === id);
                if (index !== -1) {
                    todos.value[index] = response.data;
                }
            }
            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to update todo:', err);
        }
    };

    const deleteTodo = async (id) => {
        try {
            await axios.delete(`/api/todos/${id}`);
            todos.value = todos.value.filter(t => t.id !== id);
        } catch (err) {
            error.value = err.message;
            console.error('Failed to delete todo:', err);
        }
    };

    const startWorking = async (id) => {
        try {
            const response = await axios.post(`/api/todos/${id}/start`);
            // Refresh all todos since other tasks may have been stopped
            await fetchTodos(currentDate.value);
            // Refresh time logs to show the new entry
            await refreshTimeLogs(currentDate.value);
            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to start working:', err);
        }
    };

    const stopWorking = async (id) => {
        try {
            const response = await axios.post(`/api/todos/${id}/stop`);
            const index = todos.value.findIndex(t => t.id === id);
            if (index !== -1) {
                todos.value[index] = response.data.todo;
            }
            // Refresh time logs to show the updated entry
            await refreshTimeLogs(currentDate.value);
            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to stop working:', err);
        }
    };

    const setDate = (date) => {
        currentDate.value = date;
    };

    const initializeDate = (serverDate) => {
        if (!currentDate.value && serverDate) {
            currentDate.value = serverDate;
        }
    };

    const fetchPendingFromPrevious = async (date = null) => {
        const fetchDate = date || currentDate.value;
        if (!fetchDate) return [];
        try {
            const response = await axios.get('/api/todos/pending-previous', {
                params: { date: fetchDate }
            });
            return response.data;
        } catch (err) {
            console.error('Failed to fetch pending tasks:', err);
            return [];
        }
    };

    const carryOverTasks = async (taskIds, targetDate = null) => {
        const date = targetDate || currentDate.value;
        if (!date || !taskIds.length) return false;
        try {
            await axios.post('/api/todos/carry-over', {
                task_ids: taskIds,
                target_date: date,
            });
            // Refresh todos after carry-over
            await fetchTodos(date);
            return true;
        } catch (err) {
            console.error('Failed to carry over tasks:', err);
            return false;
        }
    };

    // Helper to format minutes as HH:MM
    const formatTime = (minutes) => {
        if (!minutes || minutes === 0) return '00:00';
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
    };

    // Export currentDate and timeLogs for sharing with other composables
    return {
        todos,
        timeLogs,
        timeLogsLoading,
        loading,
        error,
        currentDate,
        statuses,
        fetchTodos,
        fetchStatuses,
        addTodo,
        updateTodo,
        deleteTodo,
        startWorking,
        stopWorking,
        setDate,
        initializeDate,
        formatTime,
        refreshTimeLogs,
        fetchPendingFromPrevious,
        carryOverTasks,
    };
}
