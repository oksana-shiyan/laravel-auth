import axios from 'axios';
import { apiClient } from '@/services/api';
import type {
    ForgotPasswordRequest,
    LoginRequest,
    RegisterRequest,
    ResetPasswordRequest,
    UpdatePasswordRequest,
    UpdateProfileRequest,
} from '@/types/auth';
import type { ApiEnvelope, User } from '@/types/user';

type AuthPayload = {
    user: User;
    token: string;
};

export const authService = {
    async login(payload: LoginRequest): Promise<AuthPayload> {
        const response = await apiClient.post<ApiEnvelope<AuthPayload>>('/login', payload);
        return response.data.data;
    },

    async register(payload: RegisterRequest): Promise<AuthPayload> {
        const response = await apiClient.post<ApiEnvelope<AuthPayload>>('/register', payload);
        return response.data.data;
    },

    async logout(): Promise<void> {
        await apiClient.post('/logout');
    },

    async user(): Promise<User> {
        const response = await apiClient.get<ApiEnvelope<User>>('/user');
        return response.data.data;
    },

    async forgotPassword(payload: ForgotPasswordRequest): Promise<string> {
        const response = await apiClient.post<{ message: string }>('/forgot-password', payload);
        return response.data.message;
    },

    async resetPassword(payload: ResetPasswordRequest): Promise<string> {
        const response = await apiClient.post<{ message: string }>('/reset-password', payload);
        return response.data.message;
    },

    async verificationStatus(): Promise<User> {
        const response = await apiClient.get<ApiEnvelope<{ user: User }>>('/email/verification-status');
        return response.data.data.user;
    },

    async sendVerificationEmail(): Promise<string> {
        const response = await apiClient.post<{ message: string }>('/email/verification-notification');
        return response.data.message;
    },

    async verifyEmailByUrl(url: string): Promise<User> {
        const response = await axios.get<ApiEnvelope<{ user: User }>>(url);
        return response.data.data.user;
    },

    async updateProfile(payload: UpdateProfileRequest): Promise<User> {
        const response = await apiClient.patch<ApiEnvelope<User>>('/profile', payload);
        return response.data.data;
    },

    async updatePassword(payload: UpdatePasswordRequest): Promise<string> {
        const response = await apiClient.put<{ message: string }>('/profile/password', payload);
        return response.data.message;
    },
};
