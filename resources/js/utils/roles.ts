import { Role } from '@/constants/roles';
import type { User } from '@/types/user';

export const userHasRole = (user: User | null | undefined, role: string): boolean => {
    return Boolean(user?.roles?.includes(role));
};

export const userIsAdmin = (user: User | null | undefined): boolean => {
    return userHasRole(user, Role.Admin);
};

export const userHasAnyRole = (
    user: User | null | undefined,
    roles: string[] | undefined
): boolean => {
    if (!roles?.length) {
        return true;
    }

    return roles.some((role) => userHasRole(user, role));
};
