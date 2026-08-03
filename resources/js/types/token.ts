export interface TokenOwner {
    id: number;
    name: string;
    email: string;
}

export interface AdminToken {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string;
    user: TokenOwner;
}

export interface PaginationMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number | null;
    to: number | null;
}

export interface PaginatedTokens {
    data: AdminToken[];
    meta: PaginationMeta;
}

export interface AdminTokenListParams {
    page?: number;
    perPage?: number;
    search?: string;
    userId?: number | null;
    name?: string;
    expired?: boolean | null;
    ability?: string;
}
