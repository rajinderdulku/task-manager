import { api } from '../../lib/api/client';

export type DashboardStats = {
    projects: number;
    tasks: number;
    todo: number;
    in_progress: number;
    completed: number;
};

export async function dashboardStats(): Promise<DashboardStats> {
    const result = await api<{ data: DashboardStats }>('/api/v1/dashboard');

    return result.data;
}
