export type Project = {
    id: number;
    name: string;
    description: string | null;
    owner: {
        id: number;
        name: string | null;
    };
    tasks_count: number;
    completed_tasks_count: number;
};

export type TaskManagerOption = {
    id: number;
    name: string;
    email: string;
};

export type ProjectInput = {
    name: string;
    description: string;
    task_manager_id?: number;
};
