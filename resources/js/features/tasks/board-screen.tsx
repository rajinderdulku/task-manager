import { DragEvent, FormEvent, useEffect, useRef, useState } from 'react';
import '../../../css/tasks.css';
import { ApiError } from '../../lib/api/client';
import type { Project } from '../projects/types';
import { DeleteIcon } from '../projects/action-icons';
import { ConfirmDialog } from '../shared/confirm-dialog';
import { createTask, deleteTask, listTasks, updateTask } from './api';
import { TaskDialog } from './task-dialog';
import type { Task, TaskPriority, TaskStatus } from './types';

const columns: { status: TaskStatus; label: string }[] = [
    { status: 'todo', label: 'To do' },
    { status: 'in_progress', label: 'In progress' },
    { status: 'completed', label: 'Done' },
];

const priorityLabel: Record<TaskPriority, string> = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
};

export function BoardScreen({ project, onBack }: { project: Project; onBack: () => void }) {
    const [tasks, setTasks] = useState<Task[]>([]);
    const [loading, setLoading] = useState(true);
    const [pending, setPending] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [priority, setPriority] = useState<TaskPriority>('medium');
    const [over, setOver] = useState<TaskStatus | null>(null);
    const [openTask, setOpenTask] = useState<Task | null>(null);
    const ignoreClick = useRef(false);
    const [taskToDelete, setTaskToDelete] = useState<Task | null>(null);
    const [deleting, setDeleting] = useState(false);

    useEffect(() => {
        listTasks(project.id)
            .then(setTasks)
            .catch((caught: unknown) => {
                setError(caught instanceof ApiError ? caught.message : 'Could not load tasks.');
            })
            .finally(() => setLoading(false));
    }, [project.id]);

    function resetForm() {
        setTitle('');
        setDescription('');
        setPriority('medium');
    }

    async function onSubmit(event: FormEvent) {
        event.preventDefault();
        setPending(true);
        setError(null);

        try {
            await createTask(project.id, { title, description, priority });
            setTasks(await listTasks(project.id));
            resetForm();
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Could not save the task.');
        } finally {
            setPending(false);
        }
    }

    async function confirmDeleteTask() {
        if (taskToDelete === null) {
            return;
        }

        setDeleting(true);
        setError(null);

        try {
            await deleteTask(project.id, taskToDelete.id);
            setTasks(await listTasks(project.id));

            if (openTask?.id === taskToDelete.id) {
                setOpenTask(null);
            }

            setTaskToDelete(null);
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Could not delete the task.');
            setTaskToDelete(null);
        } finally {
            setDeleting(false);
        }
    }

    async function onDrop(status: TaskStatus, event: DragEvent) {
        event.preventDefault();
        setOver(null);

        const id = Number(event.dataTransfer.getData('text/plain'));
        const task = tasks.find((item) => item.id === id);

        if (!task || task.status === status) {
            return;
        }

        const previous = tasks;
        setTasks(tasks.map((item) => (item.id === id ? { ...item, status } : item)));

        try {
            await updateTask(project.id, task.id, {
                title: task.title,
                description: task.description ?? '',
                priority: task.priority,
                status,
            });
        } catch (caught) {
            setTasks(previous);
            setError(caught instanceof ApiError ? caught.message : 'Could not move the task.');
        }
    }

    return (
        <div>
            <button className="btn btn-secondary board-back" type="button" onClick={onBack}>
                Back to projects
            </button>

            <div className="project-summary">
                <h1>{project.name}</h1>
                <p>{project.description || 'No description.'}</p>
            </div>

            {error ? <p className="error">{error}</p> : null}

            <form className="panel project-editor" onSubmit={onSubmit}>
                <h2>New task</h2>
                <label>
                    Title
                    <input value={title} onChange={(event) => setTitle(event.target.value)} required />
                </label>
                <label>
                    Description
                    <textarea value={description} onChange={(event) => setDescription(event.target.value)} />
                </label>
                <label>
                    Priority
                    <select value={priority} onChange={(event) => setPriority(event.target.value as TaskPriority)}>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </label>
                <div className="form-actions">
                    <button className="btn btn-primary" type="submit" disabled={pending}>
                        {pending ? 'Please wait' : 'Add task'}
                    </button>
                </div>
            </form>

            {loading ? <p className="quiet">Loading tasks</p> : null}

            <div className="board">
                {columns.map((column) => {
                    const cards = tasks.filter((task) => task.status === column.status);

                    return (
                        <section
                            className={over === column.status ? 'board-column over' : 'board-column'}
                            key={column.status}
                            onDragOver={(event) => {
                                event.preventDefault();
                                setOver(column.status);
                            }}
                            onDragLeave={() => setOver((current) => (current === column.status ? null : current))}
                            onDrop={(event) => onDrop(column.status, event)}
                        >
                            <header className="board-column-header">
                                <span>{column.label}</span>
                                <span className="board-count">{cards.length}</span>
                            </header>
                            {cards.length === 0 ? <p className="column-empty">Drop a task here</p> : null}
                            {cards.map((task) => (
                                <article
                                    className="task-card"
                                    key={task.id}
                                    draggable
                                    onDragStart={(event) => {
                                        ignoreClick.current = true;
                                        event.dataTransfer.setData('text/plain', String(task.id));
                                        event.dataTransfer.effectAllowed = 'move';
                                    }}
                                    onClick={() => {
                                        if (ignoreClick.current) {
                                            ignoreClick.current = false;
                                            return;
                                        }

                                        setOpenTask(task);
                                    }}
                                >
                                    <div className="task-card-top">
                                        <span className={`priority priority-${task.priority}`}>{priorityLabel[task.priority]}</span>
                                        <div className="actions">
                                            <button
                                                className="icon-button danger"
                                                type="button"
                                                aria-label={`Delete ${task.title}`}
                                                onClick={(event) => {
                                                    event.stopPropagation();
                                                    setTaskToDelete(task);
                                                }}
                                            >
                                                <DeleteIcon />
                                            </button>
                                        </div>
                                    </div>
                                    <h3>{task.title}</h3>
                                    {task.description ? <p>{task.description}</p> : null}
                                </article>
                            ))}
                        </section>
                    );
                })}
            </div>
            {openTask ? (
                <TaskDialog
                    projectId={project.id}
                    task={openTask}
                    onClose={() => setOpenTask(null)}
                    onSaved={(saved) => {
                        setTasks((current) => current.map((item) => (item.id === saved.id ? saved : item)));
                        setOpenTask(saved);
                    }}
                />
            ) : null}
            {taskToDelete ? (
                <ConfirmDialog
                    title="Delete task"
                    message={`Delete ${taskToDelete.title}? This cannot be undone.`}
                    confirmLabel="Delete"
                    pending={deleting}
                    onConfirm={confirmDeleteTask}
                    onCancel={() => setTaskToDelete(null)}
                />
            ) : null}
        </div>
    );
}
