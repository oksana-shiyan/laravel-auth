import type { RouteRecordRaw } from 'vue-router';
import authGuard from '@/middleware/auth';
import guestGuard from '@/middleware/guest';
import AuthLayout from '@/layouts/AuthLayout.vue';
import LoginPage from '@/pages/LoginPage.vue';
import RegisterPage from '@/pages/RegisterPage.vue';
import ForgotPasswordPage from '@/pages/ForgotPasswordPage.vue';
import ResetPasswordPage from '@/pages/ResetPasswordPage.vue';
import VerifyEmailPage from '@/pages/VerifyEmailPage.vue';
import DashboardPage from '@/pages/DashboardPage.vue';
import AdminPage from '@/pages/AdminPage.vue';
import TokensPage from '@/pages/TokensPage.vue';
import ProfilePage from '@/pages/ProfilePage.vue';
import { Role } from '@/constants/roles';
import { useAuthStore } from '@/stores/auth-store';
import { homeRouteFor } from '@/utils/home-route';

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'home',
        component: { template: '<div></div>' },
        beforeEnter: async (_to, _from, next) => {
            const authStore = useAuthStore();

            if (!authStore.token) {
                next({ name: 'login' });
                return;
            }

            if (!authStore.user) {
                try {
                    await authStore.fetchUser();
                } catch {
                    authStore.clearAuth();
                    next({ name: 'login' });
                    return;
                }
            }

            if (!authStore.user) {
                authStore.clearAuth();
                next({ name: 'login' });
                return;
            }

            next(homeRouteFor(authStore.user));
        },
    },
    {
        path: '/login',
        name: 'login',
        component: LoginPage,
        meta: { layout: AuthLayout },
        beforeEnter: [guestGuard],
    },
    {
        path: '/register',
        name: 'register',
        component: RegisterPage,
        meta: { layout: AuthLayout },
        beforeEnter: [guestGuard],
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: ForgotPasswordPage,
        meta: { layout: AuthLayout },
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: ResetPasswordPage,
        meta: { layout: AuthLayout },
    },
    {
        path: '/verify-email',
        name: 'verify-email',
        component: VerifyEmailPage,
        beforeEnter: [authGuard],
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: DashboardPage,
        beforeEnter: [authGuard],
    },
    {
        path: '/admin',
        name: 'admin',
        component: AdminPage,
        meta: { roles: [Role.Admin] },
        beforeEnter: [authGuard],
    },
    {
        path: '/tokens',
        name: 'tokens',
        component: TokensPage,
        meta: { roles: [Role.Admin] },
        beforeEnter: [authGuard],
    },
    {
        path: '/profile',
        name: 'profile',
        component: ProfilePage,
        beforeEnter: [authGuard],
    },
];

export default routes;
