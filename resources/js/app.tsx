import { useEffect, useState } from 'react';
import '../css/auth.css';
import { clearToken, getToken } from './lib/api/client';
import { currentUser } from './features/auth/api';
import { AuthScreen } from './features/auth/auth-screen';
import { ProjectsScreen } from './features/projects/projects-screen';
import type { AuthUser } from './features/auth/types';

export function App() {
    const [user, setUser] = useState<AuthUser | null>(null);
    const [ready, setReady] = useState(false);

    useEffect(() => {
        if (!getToken()) {
            setReady(true);
            return;
        }

        currentUser()
            .then(setUser)
            .catch(() => {
                clearToken();
                setUser(null);
            })
            .finally(() => setReady(true));
    }, []);

    if (!ready) {
        return <main className="auth-page" />;
    }

    if (user) {
        return <ProjectsScreen user={user} onLoggedOut={() => setUser(null)} />;
    }

    return (
        <main className="auth-page">
            <AuthScreen onAuthenticated={setUser} />
        </main>
    );
}