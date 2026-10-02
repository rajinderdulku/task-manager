import { FormEvent, useState } from 'react';
import { ApiError } from '../../lib/api/client';
import { login, register } from './api';
import type { AuthUser } from './types';

type Mode = 'login' | 'register';

export function AuthScreen({ onAuthenticated }: { onAuthenticated: (user: AuthUser) => void }) {
    const [mode, setMode] = useState<Mode>('login');
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [pending, setPending] = useState(false);

    async function onSubmit(event: FormEvent) {
        event.preventDefault();
        setPending(true);
        setError(null);

        try {
            const user = mode === 'login'
                ? await login(email, password)
                : await register({
                    name,
                    email,
                    password,
                    password_confirmation: passwordConfirmation,
                });

            onAuthenticated(user);
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Something went wrong.');
        } finally {
            setPending(false);
        }
    }

    return (
        <div className="auth-layout">
            <aside className="auth-brand">
                <div className="auth-logo">
                    <div className="auth-mark">T</div>
                    <span>TaskFlow</span>
                </div>
                <p>Manage projects and tasks in one workspace.</p>
            </aside>
            <div className="auth-main">
            <form className="auth-card" onSubmit={onSubmit}>
                <h1>{mode === 'login' ? 'Sign in' : 'Create account'}</h1>
                <p className="auth-subtitle">
                    {mode === 'login'
                        ? 'Use your account to open the workspace.'
                        : 'Register as a task manager.'}
                </p>
                {error ? <p className="error">{error}</p> : null}
                {mode === 'register' ? (
                    <label>
                        Name
                        <input value={name} onChange={(event) => setName(event.target.value)} required />
                    </label>
                ) : null}
                <label>
                    Email
                    <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
                </label>
                <label>
                    Password
                    <input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required />
                </label>
                {mode === 'register' ? (
                    <label>
                        Confirm password
                        <input type="password" value={passwordConfirmation} onChange={(event) => setPasswordConfirmation(event.target.value)} required />
                    </label>
                ) : null}
                <button className="auth-submit" type="submit" disabled={pending}>
                    {pending ? 'Please wait' : mode === 'login' ? 'Sign in' : 'Create account'}
                </button>
                <button
                    className="auth-switch"
                    type="button"
                    onClick={() => {
                        setMode(mode === 'login' ? 'register' : 'login');
                        setError(null);
                    }}
                >
                    {mode === 'login' ? 'Need an account?' : 'Already have an account?'}
                </button>
            </form>
            </div>
        </div>
    );
}