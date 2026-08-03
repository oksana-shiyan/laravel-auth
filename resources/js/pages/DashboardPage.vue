<template>
    <v-container class="py-8">
        <v-card>
            <v-card-title>Dashboard</v-card-title>
            <v-card-text>
                <p>Welcome, {{ authStore.user?.name }}.</p>
            </v-card-text>
        </v-card>
    </v-container>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth-store';

const authStore = useAuthStore();

onMounted(async () => {
    if (!authStore.user) {
        await authStore.fetchUser();
    }
});
</script>
