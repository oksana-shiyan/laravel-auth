export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    roles: string[];
    permissions: string[];
}

export interface ApiEnvelope<T> {
    message?: string;
    data: T;
}
