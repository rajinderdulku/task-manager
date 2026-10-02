const TOKEN_KEY = 'auth_token';

export function getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string): void {
    localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken(): void {
    localStorage.removeItem(TOKEN_KEY);
}

export class ApiError extends Error {
    constructor(message: string, public status: number) {
        super(message);
    }
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
    const headers = new Headers(options.headers);
    headers.set('Accept', 'application/json');

    if (options.body) {
        headers.set('Content-Type', 'application/json');
    }

    const token = getToken();
    if (token) {
        headers.set('Authorization', `Bearer ${token}`);
    }

    const response = await fetch(path, { ...options, headers });

    if (response.status === 204) {
        return undefined as T;
    }

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(readError(payload), response.status);
    }

    return payload as T;
}

function readError(payload: unknown): string {
    if (!payload || typeof payload !== 'object') {
        return 'Something went wrong.';
    }

    const errors = (payload as { errors?: Record<string, string[]> }).errors;
    const first = errors ? Object.values(errors)[0]?.[0] : undefined;

    return first ?? (payload as { message?: string }).message ?? 'Something went wrong.';
}