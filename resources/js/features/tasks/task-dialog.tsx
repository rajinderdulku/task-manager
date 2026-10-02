import { FormEvent, UIEvent, useEffect, useRef, useState } from 'react';
import { ApiError } from '../../lib/api/client';
import { listActivity, updateTask } from './api';
import type { ActivityEntry, Task, TaskPriority, TaskStatus } from './types';

const statusOptions: { value: TaskStatus; label: string }[] = [
    { value: 'todo', label: 'To do' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'completed', label: 'Done' },
];

const priorityOptions: { value: TaskPriority; label: string }[] = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
];

function statusLabel(value: string | null | undefined): string {
    return statusOptions.find((option) => option.value === value)?.label ?? 'Unknown';
}

function describe(entry: ActivityEntry): string {
    if (entry.action === 'created') {
        return 'created this task';
    }

    if (entry.action === 'deleted') {
        return 'deleted this task';
    }

    if (entry.action === 'status_changed') {
        return `changed the status from ${statusLabel(entry.properties?.from)} to ${statusLabel(entry.properties?.to)}`;
    }

    const fields = Object.keys(entry.properties?.changes ?? {});

    if (fields.length === 0) {
        return 'updated this task';
    }

    return `updated the ${fields.join(' and ')}`;
}

function formatWhen(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export function TaskDialog({
    projectId,
    task,
    onClose,
    onSaved,
}: {
    projectId: number;
    task: Task;
    onClose: () => void;
    onSaved: (task: Task) => void;
}) {
    const [title, setTitle] = useState(task.title);
    const [description, setDescription] = useState(task.description ?? '');
    const [status, setStatus] = useState<TaskStatus>(task.status);
    const [priority, setPriority] = useState<TaskPriority>(task.priority);
    const [pending, setPending] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [entries, setEntries] = useState<ActivityEntry[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loadingActivity, setLoadingActivity] = useState(true);
    const [loadingMore, setLoadingMore] = useState(false);
    const [revision, setRevision] = useState(0);
    const loadingMoreRef = useRef(false);

    useEffect(() => {
        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape' && !pending) {
                onClose();
            }
        }

        document.addEventListener('keydown', onKeyDown);

        return () => document.removeEventListener('keydown', onKeyDown);
    }, [onClose, pending]);

    useEffect(() => {
        let active = true;

        setLoadingActivity(true);

        listActivity(projectId, task.id)
            .then((next) => {
                if (!active) {
                    return;
                }

                setEntries(next.entries);
                setPage(next.currentPage);
                setLastPage(next.lastPage);
            })
            .catch((caught: unknown) => {
                if (active) {
                    setError(caught instanceof ApiError ? caught.message : 'Could not load activity.');
                }
            })
            .finally(() => {
                if (active) {
                    setLoadingActivity(false);
                }
            });

        return () => {
            active = false;
        };
    }, [projectId, task.id, revision]);

    async function onScroll(event: UIEvent<HTMLElement>) {
        const element = event.currentTarget;
        const nearBottom = element.scrollHeight - element.scrollTop - element.clientHeight < 48;

        if (!nearBottom || loadingMoreRef.current || loadingActivity || page >= lastPage) {
            return;
        }

        loadingMoreRef.current = true;
        setLoadingMore(true);

        try {
            const next = await listActivity(projectId, task.id, page + 1);
            setEntries((current) => [...current, ...next.entries]);
            setPage(next.currentPage);
            setLastPage(next.lastPage);
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Could not load more activity.');
        } finally {
            loadingMoreRef.current = false;
            setLoadingMore(false);
        }
    }

    async function onSubmit(event: FormEvent) {
        event.preventDefault();
        setPending(true);
        setError(null);

        try {
            const saved = await updateTask(projectId, task.id, {
                title,
                description,
                priority,
                status,
            });
            onSaved(saved);
            setRevision((current) => current + 1);
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Could not save the task.');
        } finally {
            setPending(false);
        }
    }

    return (
        <div className="task-dialog-backdrop" onClick={pending ? undefined : onClose}>
            <div
                className="task-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="task-dialog-title"
                onClick={(event) => event.stopPropagation()}
            >
                <form onSubmit={onSubmit}>
                    <header className="task-dialog-header">
                        <input
                            id="task-dialog-title"
                            className="task-dialog-title"
                            value={title}
                            onChange={(event) => setTitle(event.target.value)}
                            required
                            aria-label="Title"
                        />
                        <button className="task-dialog-close" type="button" onClick={onClose} aria-label="Close">
                            ×
                        </button>
                    </header>

                    <div className="task-dialog-body">
                        <div className="task-dialog-main">
                            {error ? <p className="error">{error}</p> : null}
                            <label>
                                Status
                                <select value={status} onChange={(event) => setStatus(event.target.value as TaskStatus)}>
                                    {statusOptions.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                            </label>
                            <label>
                                Priority
                                <select value={priority} onChange={(event) => setPriority(event.target.value as TaskPriority)}>
                                    {priorityOptions.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="task-dialog-description">
                                Description
                                <textarea
                                    value={description}
                                    onChange={(event) => setDescription(event.target.value)}
                                    placeholder="Write something"
                                />
                            </label>
                            <div className="form-actions">
                                <button className="btn btn-primary" type="submit" disabled={pending}>
                                    {pending ? 'Saving' : 'Save changes'}
                                </button>
                            </div>
                        </div>

                        <aside className="task-activity" onScroll={onScroll}>
                            <h2>Activity</h2>
                            {loadingActivity ? <p className="quiet">Loading activity</p> : null}
                            {!loadingActivity && entries.length === 0 ? <p className="quiet">No activity yet.</p> : null}
                            <ol>
                                {entries.map((entry) => (
                                    <li key={entry.id}>
                                        <div className="activity-name">{entry.user?.name ?? 'Someone'}</div>
                                        <div className="activity-text">{describe(entry)}</div>
                                        <time dateTime={entry.created_at}>{formatWhen(entry.created_at)}</time>
                                    </li>
                                ))}
                            </ol>
                            {loadingMore ? <p className="quiet">Loading more</p> : null}
                        </aside>
                    </div>
                </form>
            </div>
        </div>
    );
}
