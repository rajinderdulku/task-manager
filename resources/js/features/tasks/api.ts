import { api } from '../../lib/api/client';
import type { ActivityEntry, Task, TaskInput } from './types';

export type ActivityPage = {
    entries: ActivityEntry[];
    currentPage: number;
    lastPage: number;
};

export async function listTasks(projectId: number): Promise<Task[]> {
    const result = await api<{ data: Task[] }>(`/api/v1/projects/${projectId}/tasks`);

    return result.data;
}

export async function createTask(projectId: number, input: TaskInput): Promise<Task> {
    const result = await api<{ data: Task }>(`/api/v1/projects/${projectId}/tasks`, {
        method: 'POST',
        body: JSON.stringify(input),
    });

    return result.data;
}

export async function updateTask(projectId: number, id: number, input: TaskInput): Promise<Task> {
    const result = await api<{ data: Task }>(`/api/v1/projects/${projectId}/tasks/${id}`, {
        method: 'PUT',
        body: JSON.stringify(input),
    });

    return result.data;
}

export async function listActivity(projectId: number, taskId: number, page = 1): Promise<ActivityPage> {
    const result = await api<{
        data: ActivityEntry[];
        meta: { current_page: number; last_page: number };
    }>(`/api/v1/projects/${projectId}/tasks/${taskId}/activity?page=${page}`);

    return {
        entries: result.data,
        currentPage: result.meta.current_page,
        lastPage: result.meta.last_page,
    };
}

export async function deleteTask(projectId: number, id: number): Promise<void> {
    await api<void>(`/api/v1/projects/${projectId}/tasks/${id}`, { method: 'DELETE' });
}
