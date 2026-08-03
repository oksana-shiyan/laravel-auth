export const Role = {
    Admin: 'admin',
    User: 'user',
} as const;

export type RoleValue = (typeof Role)[keyof typeof Role];
