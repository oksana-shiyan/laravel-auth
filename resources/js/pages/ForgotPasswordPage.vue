<template>
    <v-card>
        <v-card-title>Forgot password</v-card-title>
        <v-card-text>
            <v-form @submit.prevent="submit">
                <v-text-field
                    v-model="email"
                    label="Email"
                    type="email"
                    required
                    :error-messages="errors.email"
                    @update:model-value="clearFieldError('email')"
                />
                <v-btn class="mt-2" color="primary" type="submit" :loading="loading">Send reset link</v-btn>
            </v-form>
        </v-card-text>
    </v-card>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useNotifier } from 'vuetify-notifier';
import { useFormErrors } from '@/composables/useFormErrors';
import { authService } from '@/services/auth-service';

const notifier = useNotifier();
const { errors, clearErrors, clearFieldError, setFromAxiosError } = useFormErrors();
const email = ref('');
const loading = ref(false);

const submit = async (): Promise<void> => {
    loading.value = true;
    clearErrors();
    try {
        const message = await authService.forgotPassword({ email: email.value });
        notifier.toast({ text: message, type: 'success' });
    } catch (error: any) {
        if (setFromAxiosError(error)) {
            return;
        }

        notifier.toast({
            text: error?.response?.data?.message ?? 'Unable to send reset email.',
            type: 'error',
        });
    } finally {
        loading.value = false;
    }
};
</script>
