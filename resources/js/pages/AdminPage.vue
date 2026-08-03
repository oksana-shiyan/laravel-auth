<template>
    <ForbiddenView v-if="!allowed" />
    <v-container v-else class="py-8">
        <v-card>
            <v-card-title>Admin</v-card-title>
            <v-card-text>
                <p>Welcome, {{ authStore.user?.name }}. This is the admin area.</p>
            </v-card-text>
        </v-card>
    </v-container>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import ForbiddenView from '@/components/ForbiddenView.vue';
import { useAuthStore } from '@/stores/auth-store';
import { userHasAnyRole } from '@/utils/roles';

const route = useRoute();
const authStore = useAuthStore();

const allowed = computed(() => {
    const roles = route.meta.roles as string[] | undefined;

    return userHasAnyRole(authStore.user, roles);
});
</script>
