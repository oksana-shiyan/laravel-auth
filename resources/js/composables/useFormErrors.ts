import { reactive } from 'vue';

export type FormErrors = Record<string, string[]>;

export function useFormErrors() {
    const errors = reactive<FormErrors>({});

    const clearErrors = (): void => {
        for (const key of Object.keys(errors)) {
            delete errors[key];
        }
    };

    const clearFieldError = (field: string): void => {
        delete errors[field];
    };

    const setFromAxiosError = (error: unknown): boolean => {
        const response = (error as { response?: { status?: number; data?: { errors?: FormErrors } } })
            ?.response;

        if (response?.status === 422 && response.data?.errors) {
            clearErrors();
            Object.assign(errors, response.data.errors);

            return true;
        }

        return false;
    };

    return {
        errors,
        clearErrors,
        clearFieldError,
        setFromAxiosError,
    };
}
