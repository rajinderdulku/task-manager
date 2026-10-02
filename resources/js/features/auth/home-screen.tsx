import { useState } from 'react';
import { logout } from './api';
import type { AuthUser } from './types';

export function HomeScreen({ user, onLoggedOut }: { user: AuthUser; onLoggedOut: () => void }) {
    const [pending, setPending] = useState(false);

    async function onLogout() {
        setPending(true);
        await logout();
        onLoggedOut();
    }

    return (
        <section className="panel">
            <div className="topbar">
                <p className="wordmark">Task manager</p>
                <button className="ghost" type="button" onClick={onLogout} disabled={pending}>
                    {pending ? 'Signing out' : 'Log out'}
                </button>
            </div>
            <h1 className="greeting">Hi {user.name}</h1>
        </section>
    );
}