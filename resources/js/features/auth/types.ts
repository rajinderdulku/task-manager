export type AuthUser = {
    id: number;
    name: string;
    email: string;
    role: string;
};

export type AuthResponse = {
    data: AuthUser;
    token: string;
};