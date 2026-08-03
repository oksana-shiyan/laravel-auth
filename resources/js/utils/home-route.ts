import type { RouteLocationRaw } from 'vue-router';
import { userIsAdmin } from '@/utils/roles';
import type { User } from '@/types/user';

export const homeRouteFor = (user: User | null | undefined): RouteLocationRaw => {
    return userIsAdmin(user) ? { name: 'admin' } : { name: 'dashboard' };
};
