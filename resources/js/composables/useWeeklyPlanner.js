import { ref, computed } from 'vue';
import axios from 'axios';

// Shared state (singleton)
const currentWeekKey = ref(null);
const weekDisplay = ref('');
const prevWeekKey = ref(null);
const nextWeekKey = ref(null);
const isCurrentWeek = ref(false);
const days = ref({
    1: { current: [], ghosts: [] },
    2: { current: [], ghosts: [] },
    3: { current: [], ghosts: [] },
    4: { current: [], ghosts: [] },
    5: { current: [], ghosts: [] },
});
const backlog = ref([]);
const loading = ref(false);
const error = ref(null);

// Georgian day names
const dayNames = {
    1: 'ორშაბათი',
    2: 'სამშაბათი',
    3: 'ოთხშაბათი',
    4: 'ხუთშაბათი',
    5: 'პარასკევი',
};

// Short day names
const shortDayNames = {
    1: 'ორშ',
    2: 'სამ',
    3: 'ოთხ',
    4: 'ხუთ',
    5: 'პარ',
};

export function useWeeklyPlanner() {
    const fetchWeekData = async (weekKey = null) => {
        loading.value = true;
        error.value = null;
        try {
            const params = weekKey ? { week: weekKey } : {};
            const response = await axios.get('/api/weekly', { params });
            const data = response.data;

            currentWeekKey.value = data.weekKey;
            weekDisplay.value = data.weekDisplay;
            prevWeekKey.value = data.prevWeekKey;
            nextWeekKey.value = data.nextWeekKey;
            isCurrentWeek.value = data.isCurrentWeek;
            days.value = data.days;
            backlog.value = data.backlog;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to fetch week data:', err);
        } finally {
            loading.value = false;
        }
    };

    const navigateWeek = async (direction) => {
        // direction: -1 for prev, 0 for today, 1 for next
        let targetWeek;
        if (direction === 0) {
            targetWeek = null; // Will default to current week
        } else if (direction === -1) {
            targetWeek = prevWeekKey.value;
        } else {
            targetWeek = nextWeekKey.value;
        }
        await fetchWeekData(targetWeek);
    };

    const assignToDay = async ({ todoId, weekKey, dayOfWeek }) => {
        try {
            const response = await axios.post('/api/weekly/assign', {
                todo_id: todoId,
                week_key: weekKey || currentWeekKey.value,
                day_of_week: dayOfWeek,
            });

            // Refresh week data to get updated state
            await fetchWeekData(currentWeekKey.value);

            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to assign task to day:', err);
            throw err;
        }
    };

    const moveToBacklog = async (todoId) => {
        try {
            const response = await axios.post('/api/weekly/backlog', {
                todo_id: todoId,
            });

            // Refresh week data
            await fetchWeekData(currentWeekKey.value);

            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to move task to backlog:', err);
            throw err;
        }
    };

    const reorderDay = async (dayOfWeek, placements) => {
        try {
            await axios.post('/api/weekly/reorder', {
                week_key: currentWeekKey.value,
                day_of_week: dayOfWeek,
                placements: placements.map((p, index) => ({
                    id: p.id,
                    order: index,
                })),
            });
        } catch (err) {
            error.value = err.message;
            console.error('Failed to reorder tasks:', err);
            throw err;
        }
    };

    const carryOverToNextWeek = async () => {
        try {
            const response = await axios.post('/api/weekly/carry-over', {
                week_key: currentWeekKey.value,
            });

            // Navigate to next week to show results
            await fetchWeekData(response.data.nextWeekKey);

            return response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Failed to carry over tasks:', err);
            throw err;
        }
    };

    const getDayTasks = (dayOfWeek) => {
        return computed(() => days.value[dayOfWeek]?.current || []);
    };

    const getDayGhosts = (dayOfWeek) => {
        return computed(() => days.value[dayOfWeek]?.ghosts || []);
    };

    return {
        // State
        currentWeekKey,
        weekDisplay,
        prevWeekKey,
        nextWeekKey,
        isCurrentWeek,
        days,
        backlog,
        loading,
        error,

        // Constants
        dayNames,
        shortDayNames,

        // Methods
        fetchWeekData,
        navigateWeek,
        assignToDay,
        moveToBacklog,
        reorderDay,
        carryOverToNextWeek,
        getDayTasks,
        getDayGhosts,
    };
}
