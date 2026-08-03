export interface LoginRequest {
    email: string;
    password: string;
}

export interface RegisterRequest {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export interface ForgotPasswordRequest {
    email: string;
}

export interface ResetPasswordRequest {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export interface UpdateProfileRequest {
    name: string;
    email: string;
}

export interface UpdatePasswordRequest {
    current_password: string;
    password: string;
    password_confirmation: string;
}
