import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router';
import { useAuthStore } from '@/stores/auth-store';
import { homeRouteFor } from '@/utils/home-route';

export default async function guestGuard(
    _to: RouteLocationNormalized,
    _from: RouteLocationNormalized,
    next: NavigationGuardNext
): Promise<void> {
    const authStore = useAuthStore();

    if (!authStore.token) {
        next();
        return;
    }

    if (!authStore.user) {
        try {
            await authStore.fetchUser();
        } catch {
            authStore.clearAuth();
            next();
            return;
        }
    }

    if (!authStore.user) {
        authStore.clearAuth();
        next();
        return;
    }

    next(homeRouteFor(authStore.user));
}
