<template>
    <v-container class="fill-height">
        <v-row align="center" justify="center">
            <v-col cols="12" sm="8" md="6" lg="4">
                <v-card>
                    <v-card-title>403 — Forbidden</v-card-title>
                    <v-card-text>
                        <p>You do not have permission to access this page.</p>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn color="primary" :to="homeRoute">Go home</v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth-store';
import { homeRouteFor } from '@/utils/home-route';

const authStore = useAuthStore();
const homeRoute = computed(() => homeRouteFor(authStore.user));
</script>
