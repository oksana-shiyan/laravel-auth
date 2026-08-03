import { apiClient } from '@/services/api';
import type { AdminTokenListParams, PaginatedTokens } from '@/types/token';

export const adminTokenService = {
    async list(params: AdminTokenListParams = {}): Promise<PaginatedTokens> {
        const response = await apiClient.get<{ data: PaginatedTokens['data']; meta: PaginatedTokens['meta'] }>(
            '/admin/tokens',
            {
                params: {
                    page: params.page,
                    per_page: params.perPage,
                    search: params.search || undefined,
                    user_id: params.userId || undefined,
                    name: params.name || undefined,
                    expired: params.expired === null || params.expired === undefined
                        ? undefined
                        : params.expired,
                    ability: params.ability || undefined,
                },
            },
        );

        return {
            data: response.data.data,
            meta: response.data.meta,
        };
    },

    async revoke(tokenId: number): Promise<string> {
        const response = await apiClient.delete<{ message: string }>(`/admin/tokens/${tokenId}`);
        return response.data.message;
    },
};
