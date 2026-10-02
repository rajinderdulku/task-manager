export type TaskStatus = 'todo' | 'in_progress' | 'completed';
export type TaskPriority = 'low' | 'medium' | 'high';

export type Task = {
    id: number;
    project_id: number;
    title: string;
    description: string | null;
    status: TaskStatus;
    priority: TaskPriority;
};

export type ActivityEntry = {
    id: number;
    action: 'created' | 'updated' | 'deleted' | 'status_changed';
    properties: {
        title?: string;
        from?: string | null;
        to?: string | null;
        changes?: Record<string, { from: string | null; to: string | null }>;
    } | null;
    user: {
        id: number;
        name: string;
    } | null;
    created_at: string;
};

export type TaskInput = {
    title: string;
    description: string;
    priority: TaskPriority;
    status?: TaskStatus;
};
