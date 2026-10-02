import { api, clearToken, setToken } from '../../lib/api/client';
import type { AuthResponse, AuthUser } from './types';

export async function login(email: string, password: string): Promise<AuthUser> {
    const result = await api<AuthResponse>('/api/v1/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
    });

    setToken(result.token);
    return result.data;
}

export async function register(input: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}): Promise<AuthUser> {
    const result = await api<AuthResponse>('/api/v1/register', {
        method: 'POST',
        body: JSON.stringify(input),
    });

    setToken(result.token);
    return result.data;
}

export async function currentUser(): Promise<AuthUser> {
    const result = await api<{ data: AuthUser }>('/api/v1/user');
    return result.data;
}

export async function logout(): Promise<void> {
    try {
        await api<void>('/api/v1/logout', { method: 'POST' });
    } finally {
        clearToken();
    }
}