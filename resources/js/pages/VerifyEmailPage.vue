<template>
    <v-container class="py-8">
        <v-card>
            <v-card-title>Verify email</v-card-title>
            <v-card-text>
                <p class="mb-4">Please verify your email address to continue using this application.</p>
                <v-btn color="primary" :loading="sending" @click="sendLink">Resend verification email</v-btn>
                <v-btn class="ml-2" variant="text" :loading="verifying" @click="verifyFromLink">Verify now</v-btn>
            </v-card-text>
        </v-card>
    </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useNotifier } from 'vuetify-notifier';
import { authService } from '@/services/auth-service';
import { useAuthStore } from '@/stores/auth-store';
import { homeRouteFor } from '@/utils/home-route';

const notifier = useNotifier();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const sending = ref(false);
const verifying = ref(false);

const sendLink = async (): Promise<void> => {
    if (!authStore.token) {
        notifier.toast({ text: 'Please login to resend the verification email.', type: 'warning' });
        return;
    }

    sending.value = true;
    try {
        const message = await authService.sendVerificationEmail();
        notifier.toast({ text: message, type: 'success' });
    } catch (error: any) {
        notifier.toast({
            text: error?.response?.data?.message ?? 'Unable to send verification email.',
            type: 'error',
        });
    } finally {
        sending.value = false;
    }
};

const verifyFromLink = async (): Promise<void> => {
    const verifyUrl = route.query.verify_url;
    if (typeof verifyUrl !== 'string' || verifyUrl.length === 0) {
        notifier.toast({ text: 'Open this page from your email verification link.', type: 'warning' });
        return;
    }

    verifying.value = true;
    try {
        authStore.user = await authService.verifyEmailByUrl(verifyUrl);
        notifier.toast({ text: 'Email verified successfully.', type: 'success' });
        await router.push(homeRouteFor(authStore.user));
    } catch (error: any) {
        notifier.toast({
            text: error?.response?.data?.message ?? 'Verification failed.',
            type: 'error',
        });
    } finally {
        verifying.value = false;
    }
};
</script>
