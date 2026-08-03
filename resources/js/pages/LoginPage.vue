<template>
    <v-card>
        <v-card-title>Login</v-card-title>
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
                    v-model="form.password"
                    label="Password"
                    type="password"
                    required
                    :error-messages="errors.password"
                    @update:model-value="clearFieldError('password')"
                />
                <v-btn class="mt-2" color="primary" type="submit" :loading="loading">Login</v-btn>
            </v-form>
            <div class="mt-4">
                <router-link :to="{ name: 'register' }">Create account</router-link>
                |
                <router-link :to="{ name: 'forgot-password' }">Forgot password?</router-link>
            </div>
        </v-card-text>
    </v-card>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useNotifier } from 'vuetify-notifier';
import { useFormErrors } from '@/composables/useFormErrors';
import { useAuthStore } from '@/stores/auth-store';
import { homeRouteFor } from '@/utils/home-route';

const router = useRouter();
const authStore = useAuthStore();
const notifier = useNotifier();
const { errors, clearErrors, clearFieldError, setFromAxiosError } = useFormErrors();
const loading = ref(false);
const form = reactive({ email: '', password: '' });

const submit = async (): Promise<void> => {
    loading.value = true;
    clearErrors();
    try {
        await authStore.login(form);
        await router.push(homeRouteFor(authStore.user));
    } catch (error: any) {
        if (setFromAxiosError(error)) {
            return;
        }

        notifier.toast({
            text: error?.response?.data?.message ?? 'Login failed.',
            type: 'error',
        });
    } finally {
        loading.value = false;
    }
};
</script>
