<template>
    <component :is="layout">
        <router-view />
    </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import BlankLayout from '@/layouts/BlankLayout.vue';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { useAuthStore } from '@/stores/auth-store';
import { userHasAnyRole } from '@/utils/roles';

const route = useRoute();
const authStore = useAuthStore();

const layout = computed(() => {
    const requiredRoles = route.meta.roles;

    if (
        Array.isArray(requiredRoles) &&
        requiredRoles.length > 0 &&
        authStore.user &&
        !userHasAnyRole(authStore.user, requiredRoles)
    ) {
        return BlankLayout;
    }

    return route.meta.layout ?? DefaultLayout;
});
</script>
