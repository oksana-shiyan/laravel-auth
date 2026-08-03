<template>
    <ForbiddenView v-if="!allowed" />
    <v-container v-else class="py-8">
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between">
                <span>Tokens</span>
                <v-btn
                    variant="text"
                    icon="mdi-refresh"
                    :loading="loading"
                    aria-label="Refresh tokens"
                    @click="loadTokens"
                />
            </v-card-title>

            <v-card-text>
                <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
                    {{ error }}
                </v-alert>

                <v-row class="mb-2" dense>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="search"
                            label="Search name or email"
                            prepend-inner-icon="mdi-magnify"
                            density="compact"
                            hide-details
                            clearable
                            @keyup.enter="applyFilters"
                            @click:clear="clearSearch"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field
                            v-model="userId"
                            label="User ID"
                            type="number"
                            density="compact"
                            hide-details
                            clearable
                            @keyup.enter="applyFilters"
                        />
                    </v-col>
                    <v-col cols="12" md="3" class="d-flex align-center ga-2">
                        <v-btn color="primary" variant="tonal" @click="applyFilters">
                            Filter
                        </v-btn>
                        <v-btn variant="text" @click="resetFilters">
                            Reset
                        </v-btn>
                    </v-col>
                </v-row>

                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="tokens"
                    :items-length="total"
                    :loading="loading"
                    item-value="id"
                    class="elevation-0"
                    @update:options="onOptionsUpdate"
                >
                    <template #item.user="{ item }">
                        <div>
                            <div>{{ item.user.name }}</div>
                            <div class="text-medium-emphasis text-caption">{{ item.user.email }}</div>
                        </div>
                    </template>

                    <template #item.abilities="{ item }">
                        <v-chip
                            v-for="ability in item.abilities"
                            :key="ability"
                            size="small"
                            class="ma-1"
                            variant="tonal"
                        >
                            {{ ability }}
                        </v-chip>
                        <span v-if="!item.abilities?.length" class="text-medium-emphasis">—</span>
                    </template>

                    <template #item.last_used_at="{ item }">
                        {{ formatDate(item.last_used_at) }}
                    </template>

                    <template #item.expires_at="{ item }">
                        {{ formatDate(item.expires_at) }}
                    </template>

                    <template #item.created_at="{ item }">
                        {{ formatDate(item.created_at) }}
                    </template>

                    <template #item.actions="{ item }">
                        <v-btn
                            color="error"
                            variant="text"
                            size="small"
                            :loading="revokingId === item.id"
                            @click="confirmRevoke(item)"
                        >
                            Reject
                        </v-btn>
                    </template>

                    <template #no-data>
                        <div class="text-center py-6 text-medium-emphasis">
                            {{ loading ? 'Loading tokens…' : 'No tokens found.' }}
                        </div>
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>
    </v-container>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useNotifier } from 'vuetify-notifier';
import ForbiddenView from '@/components/ForbiddenView.vue';
import { adminTokenService } from '@/services/admin-token-service';
import { useAuthStore } from '@/stores/auth-store';
import type { AdminToken } from '@/types/token';
import { userHasAnyRole } from '@/utils/roles';

const route = useRoute();
const authStore = useAuthStore();
const notifier = useNotifier();

const allowed = computed(() => {
    const roles = route.meta.roles as string[] | undefined;

    return userHasAnyRole(authStore.user, roles);
});

const tokens = ref<AdminToken[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const revokingId = ref<number | null>(null);

const page = ref(1);
const itemsPerPage = ref(15);
const total = ref(0);

const search = ref('');
const userId = ref<string>('');
const appliedSearch = ref('');
const appliedUserId = ref<number | null>(null);

const headers = [
    { title: 'ID', key: 'id', sortable: false },
    { title: 'Name', key: 'name', sortable: false },
    { title: 'User', key: 'user', sortable: false },
    { title: 'Abilities', key: 'abilities', sortable: false },
    { title: 'Last used', key: 'last_used_at', sortable: false },
    { title: 'Expires', key: 'expires_at', sortable: false },
    { title: 'Created', key: 'created_at', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false },
];

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
};

const parseUserId = (value: string): number | null => {
    if (!value) {
        return null;
    }

    const parsed = Number(value);

    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
};

const loadTokens = async (): Promise<void> => {
    if (!allowed.value) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        const result = await adminTokenService.list({
            page: page.value,
            perPage: itemsPerPage.value,
            search: appliedSearch.value || undefined,
            userId: appliedUserId.value,
        });

        tokens.value = result.data;
        total.value = result.meta.total;

        if (result.data.length === 0 && page.value > 1 && result.meta.last_page < page.value) {
            page.value = Math.max(1, result.meta.last_page);
            await loadTokens();

            return;
        }
    } catch (err: any) {
        error.value = err?.response?.data?.message ?? 'Failed to load tokens.';
        tokens.value = [];
        total.value = 0;
    } finally {
        loading.value = false;
    }
};

const onOptionsUpdate = (options: { page: number; itemsPerPage: number }): void => {
    page.value = options.page;
    itemsPerPage.value = options.itemsPerPage;
    void loadTokens();
};

const applyFilters = (): void => {
    appliedSearch.value = search.value.trim();
    appliedUserId.value = parseUserId(userId.value);
    page.value = 1;
    void loadTokens();
};

const clearSearch = (): void => {
    search.value = '';
    appliedSearch.value = '';
    page.value = 1;
    void loadTokens();
};

const resetFilters = (): void => {
    search.value = '';
    userId.value = '';
    appliedSearch.value = '';
    appliedUserId.value = null;
    page.value = 1;
    void loadTokens();
};

const revokeToken = async (token: AdminToken): Promise<void> => {
    revokingId.value = token.id;

    try {
        const message = await adminTokenService.revoke(token.id);
        notifier.toast({ text: message, type: 'success' });

        if (tokens.value.length === 1 && page.value > 1) {
            page.value -= 1;
        }

        await loadTokens();
    } catch (err: any) {
        notifier.toast({
            text: err?.response?.data?.message ?? 'Failed to revoke token.',
            type: 'error',
        });
    } finally {
        revokingId.value = null;
    }
};

const confirmRevoke = (token: AdminToken): void => {
    notifier.confirm({
        title: 'Revoke token?',
        text: `Revoke token “${token.name}” for ${token.user.email}? This cannot be undone.`,
        onSubmit: () => {
            void revokeToken(token);
        },
    });
};
</script>
