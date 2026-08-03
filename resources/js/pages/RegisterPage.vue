<template>
    <v-card>
        <v-card-title>Register</v-card-title>
        <v-card-text>
            <v-form @submit.prevent="submit">
                <v-text-field
                    v-model="form.name"
                    label="Name"
                    required
                    :error-messages="errors.name"
                    @update:model-value="clearFieldError('name')"
                />
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
                <v-text-field
                    v-model="form.password_confirmation"
                    label="Confirm password"
                    type="password"
                    required
                    :error-messages="errors.password_confirmation"
                    @update:model-value="clearFieldError('password_confirmation')"
                />
                <v-btn class="mt-2" color="primary" type="submit" :loading="loading">Register</v-btn>
            </v-form>
            <div class="mt-4">
                <router-link :to="{ name: 'login' }">Already have an account?</router-link>
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
const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = async (): Promise<void> => {
    loading.value = true;
    clearErrors();
    try {
        await authStore.register(form);
        await router.push(homeRouteFor(authStore.user));
    } catch (error: any) {
        if (setFromAxiosError(error)) {
            return;
        }

        notifier.toast({
            text: error?.response?.data?.message ?? 'Registration failed.',
            type: 'error',
        });
    } finally {
        loading.value = false;
    }
};
</script>
