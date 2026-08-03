<template>
    <v-card>
        <v-card-title>Reset password</v-card-title>
        <v-card-text>
            <v-form @submit.prevent="submit">
                <v-text-field
                    v-model="form.email"
                    label="Email"
                    type="email"
                    required
                    :error-messages="errors.email"
                    @update:model-value="clearFieldError('email')"
                />
                <v-text-field
                    v-model="form.token"
                    label="Token"
                    required
                    :error-messages="errors.token"
                    @update:model-value="clearFieldError('token')"
                />
                <v-text-field
                    v-model="form.password"
                    label="Password"
                    type="password"
                    required
                    :error-messages="errors.password"
                    @update:model-value="clearFieldError('password')"
                />
                <v-text-field
                    v-model="form.password_confirmation"
                    label="Confirm password"
                    type="password"
                    required
                    :error-messages="errors.password_confirmation"
                    @update:model-value="clearFieldError('password_confirmation')"
                />
                <v-btn class="mt-2" color="primary" type="submit" :loading="loading">Reset password</v-btn>
            </v-form>
        </v-card-text>
    </v-card>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useNotifier } from 'vuetify-notifier';
import { useFormErrors } from '@/composables/useFormErrors';
import { authService } from '@/services/auth-service';

const route = useRoute();
const router = useRouter();
const notifier = useNotifier();
const { errors, clearErrors, clearFieldError, setFromAxiosError } = useFormErrors();
const loading = ref(false);
const form = reactive({
    email: String(route.query.email ?? ''),
    token: String(route.query.token ?? ''),
    password: '',
    password_confirmation: '',
});

const submit = async (): Promise<void> => {
    loading.value = true;
    clearErrors();
    try {
        const message = await authService.resetPassword(form);
        notifier.toast({ text: message, type: 'success' });
        await router.push({ name: 'login' });
    } catch (error: any) {
        if (setFromAxiosError(error)) {
            return;
        }

        notifier.toast({
            text: error?.response?.data?.message ?? 'Reset password failed.',
            type: 'error',
        });
    } finally {
        loading.value = false;
    }
};
</script>
