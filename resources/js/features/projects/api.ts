import { api } from '../../lib/api/client';
import type { Project, ProjectInput, TaskManagerOption } from './types';

export async function listTaskManagers(): Promise<TaskManagerOption[]> {
    const result = await api<{ data: TaskManagerOption[] }>('/api/v1/task-managers');

    return result.data;
}

export type ProjectPage = {
    projects: Project[];
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
};

export async function listProjects(page = 1): Promise<ProjectPage> {
    const result = await api<{
        data: Project[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number | null;
            to: number | null;
        };
    }>(`/api/v1/projects?page=${page}`);

    return {
        projects: result.data,
        currentPage: result.meta.current_page,
        lastPage: result.meta.last_page,
        perPage: result.meta.per_page,
        total: result.meta.total,
        from: result.meta.from,
        to: result.meta.to,
    };
}

export async function createProject(input: ProjectInput): Promise<Project> {
    const result = await api<{ data: Project }>('/api/v1/projects', {
        method: 'POST',
        body: JSON.stringify(input),
    });

    return result.data;
}

export async function updateProject(id: number, input: ProjectInput): Promise<Project> {
    const result = await api<{ data: Project }>(`/api/v1/projects/${id}`, {
        method: 'PUT',
        body: JSON.stringify(input),
    });

    return result.data;
}

export async function deleteProject(id: number): Promise<void> {
    await api<void>(`/api/v1/projects/${id}`, { method: 'DELETE' });
}
