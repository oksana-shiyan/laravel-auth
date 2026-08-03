<template>
    <v-container class="py-8">
        <v-row>
            <v-col cols="12" md="6">
                <v-card>
                    <v-card-title>Update profile</v-card-title>
                    <v-card-text>
                        <v-form @submit.prevent="updateProfile">
                            <v-text-field
                                v-model="profileForm.name"
                                label="Name"
                                required
                                :error-messages="profileErrors.name"
                                @update:model-value="clearProfileFieldError('name')"
                            />
                            <v-text-field
                                v-model="profileForm.email"
                                label="Email"
                                type="email"
                                required
                                :error-messages="profileErrors.email"
                                @update:model-value="clearProfileFieldError('email')"
                            />
                            <v-btn color="primary" type="submit" :loading="profileLoading">Save profile</v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="6">
                <v-card>
                    <v-card-title>Change password</v-card-title>
                    <v-card-text>
                        <v-form @submit.prevent="updatePassword">
                            <v-text-field
                                v-model="passwordForm.current_password"
                                label="Current password"
                                type="password"
                                required
                                :error-messages="passwordErrors.current_password"
                                @update:model-value="clearPasswordFieldError('current_password')"
                            />
                            <v-text-field
                                v-model="passwordForm.password"
                                label="New password"
                                type="password"
                                required
                                :error-messages="passwordErrors.password"
                                @update:model-value="clearPasswordFieldError('password')"
                            />
                            <v-text-field
                                v-model="passwordForm.password_confirmation"
                                label="Confirm new password"
                                type="password"
                                required
                                :error-messages="passwordErrors.password_confirmation"
                                @update:model-value="clearPasswordFieldError('password_confirmation')"
                            />
                            <v-btn color="primary" type="submit" :loading="passwordLoading">Update password</v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useNotifier } from 'vuetify-notifier';
import { useFormErrors } from '@/composables/useFormErrors';
import { authService } from '@/services/auth-service';
import { useAuthStore } from '@/stores/auth-store';

const notifier = useNotifier();
const authStore = useAuthStore();
const profileLoading = ref(false);
const passwordLoading = ref(false);

const {
    errors: profileErrors,
    clearErrors: clearProfileErrors,
    clearFieldError: clearProfileFieldError,
    setFromAxiosError: setProfileErrorsFromAxios,
} = useFormErrors();

const {
    errors: passwordErrors,
    clearErrors: clearPasswordErrors,
    clearFieldError: clearPasswordFieldError,
    setFromAxiosError: setPasswordErrorsFromAxios,
} = useFormErrors();

const profileForm = reactive({
    name: '',
    email: '',
});

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

onMounted(async () => {
    if (!authStore.user) {
        await authStore.fetchUser();
    }

    if (authStore.user) {
        profileForm.name = authStore.user.name;
        profileForm.email = authStore.user.email;
    }
});

const updateProfile = async (): Promise<void> => {
    profileLoading.value = true;
    clearProfileErrors();
    try {
        authStore.user = await authService.updateProfile(profileForm);
        notifier.toast({ text: 'Profile updated successfully.', type: 'success' });
    } catch (error: any) {
        if (setProfileErrorsFromAxios(error)) {
            return;
        }

        notifier.toast({
            text: error?.response?.data?.message ?? 'Profile update failed.',
            type: 'error',
        });
    } finally {
        profileLoading.value = false;
    }
};

const updatePassword = async (): Promise<void> => {
    passwordLoading.value = true;
    clearPasswordErrors();
    try {
        const message = await authService.updatePassword(passwordForm);
        notifier.toast({ text: message, type: 'success' });
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
    } catch (error: any) {
        if (setPasswordErrorsFromAxios(error)) {
            return;
        }

        notifier.toast({
            text: error?.response?.data?.message ?? 'Password update failed.',
            type: 'error',
        });
    } finally {
        passwordLoading.value = false;
    }
};
</script>
