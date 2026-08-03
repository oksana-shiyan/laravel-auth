import { defineStore } from 'pinia';
import { ref } from 'vue';
import { authService } from '@/services/auth-service';
import type { LoginRequest, RegisterRequest } from '@/types/auth';
import type { User } from '@/types/user';

export const useAuthStore = defineStore('auth', () => {
    const token = ref<string>(localStorage.getItem('token') ?? '');
    const user = ref<User | null>(null);

    const setToken = (value: string): void => {
        token.value = value;
        localStorage.setItem('token', value);
    };

    const clearAuth = (): void => {
        token.value = '';
        user.value = null;
        localStorage.removeItem('token');
    };

    const login = async (payload: LoginRequest): Promise<void> => {
        const data = await authService.login(payload);
        setToken(data.token);
        user.value = data.user;
    };

    const register = async (payload: RegisterRequest): Promise<void> => {
        const data = await authService.register(payload);
        setToken(data.token);
        user.value = data.user;
    };

    const fetchUser = async (): Promise<void> => {
        user.value = await authService.user();
    };

    const logout = async (): Promise<void> => {
        try {
            if (token.value) {
                await authService.logout();
            }
        } finally {
            clearAuth();
        }
    };

    return { token, user, setToken, clearAuth, login, register, fetchUser, logout };
});
