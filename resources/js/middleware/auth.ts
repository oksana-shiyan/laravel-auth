import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router';
import { useAuthStore } from '@/stores/auth-store';

/**
 * Ensures the visitor is authenticated (and email-verified when required).
 * Route `meta.roles` is not enforced here — pages render Forbidden in place
 * when the user lacks a required role (URL stays the same).
 */
export default async function authGuard(
    to: RouteLocationNormalized,
    _from: RouteLocationNormalized,
    next: NavigationGuardNext
): Promise<void> {
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

    if (!authStore.user.email_verified_at && to.name !== 'verify-email') {
        next({ name: 'verify-email' });
        return;
    }

    next();
}
