<template>
    <v-app>
        <v-app-bar color="primary" density="comfortable">
            <v-app-bar-title>Auth SPA</v-app-bar-title>
            <v-spacer />
            <template v-if="isAdmin">
                <v-btn variant="text" :to="{ name: 'admin' }">Admin</v-btn>
                <v-btn variant="text" :to="{ name: 'tokens' }">Tokens</v-btn>
            </template>
            <v-btn v-else variant="text" :to="{ name: 'dashboard' }">Dashboard</v-btn>
            <v-btn variant="text" :to="{ name: 'profile' }">Profile</v-btn>
            <v-btn variant="text" :loading="isSubmitting" @click="logoutUser">Logout</v-btn>
        </v-app-bar>

        <v-main>
            <router-view />
            <VNotifierContainer />
        </v-main>
    </v-app>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import router from '@/router';
import { useAuthStore } from '@/stores/auth-store';
import { userIsAdmin } from '@/utils/roles';

const authStore = useAuthStore();
const isSubmitting = ref(false);
const isAdmin = computed(() => userIsAdmin(authStore.user));

const logoutUser = async (): Promise<void> => {
    isSubmitting.value = true;
    try {
        await authStore.logout();
        await router.push({ name: 'login' });
    } finally {
        isSubmitting.value = false;
    }
};
</script>
