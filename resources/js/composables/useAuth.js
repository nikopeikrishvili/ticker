import { usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import axios from 'axios';

export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user);
    const isAuthenticated = computed(() => !!user.value);

    const logout = () => {
        router.post('/logout');
    };

    const updateProfile = async (data) => {
        const response = await axios.put('/api/profile', data);
        if (response.data.success) {
            page.props.auth.user = response.data.user;
        }
        return response.data;
    };

    const updatePassword = async (data) => {
        const response = await axios.put('/api/profile/password', data);
        return response.data;
    };

    return {
        user,
        isAuthenticated,
        logout,
        updateProfile,
        updatePassword,
    };
}
