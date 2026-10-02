import { FormEvent, useEffect, useState } from 'react';
import '../../../css/projects.css';
import '../../../css/tasks.css';
import { ApiError } from '../../lib/api/client';
import { logout } from '../auth/api';
import type { AuthUser } from '../auth/types';
import { dashboardStats, type DashboardStats } from '../dashboard/api';
import { DashboardPanels } from '../dashboard/dashboard-panels';
import { ConfirmDialog } from '../shared/confirm-dialog';
import { BoardScreen } from '../tasks/board-screen';
import { createProject, deleteProject, listProjects, listTaskManagers, updateProject, type ProjectPage } from './api';
import { DeleteIcon, EditIcon, ViewIcon } from './action-icons';
import type { Project, TaskManagerOption } from './types';

const DESCRIPTION_PREVIEW_WORDS = 12;

function descriptionPreview(description: string): string {
    const words = description.trim().split(/\s+/).filter(Boolean);

    if (words.length <= DESCRIPTION_PREVIEW_WORDS) {
        return words.join(' ');
    }

    return `${words.slice(0, DESCRIPTION_PREVIEW_WORDS).join(' ')}…`;
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function pageNumbers(current: number, last: number): number[] {
    const start = Math.max(1, Math.min(current - 2, last - 4));
    const end = Math.min(last, start + 4);

    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
}

function ProjectProgress({ completed, total }: { completed: number; total: number }) {
    const percent = total === 0 ? 0 : Math.round((completed / total) * 100);

    return (
        <div className="row-progress">
            <div className="progress-track">
                <div className="progress-value" style={{ width: `${percent}%` }} />
            </div>
            <span className="row-progress-label">{percent}%</span>
        </div>
    );
}

function roleLabel(role: string): string {
    if (role === 'admin') {
        return 'Administrator';
    }

    if (role === 'task_manager') {
        return 'Task manager';
    }

    return role;
}

export function ProjectsScreen({ user, onLoggedOut }: { user: AuthUser; onLoggedOut: () => void }) {
    const [projects, setProjects] = useState<Project[]>([]);
    const [page, setPage] = useState(1);
    const [listMeta, setListMeta] = useState<Pick<ProjectPage, 'currentPage' | 'lastPage' | 'total' | 'from' | 'to'>>({
        currentPage: 1,
        lastPage: 1,
        total: 0,
        from: null,
        to: null,
    });
    const [loading, setLoading] = useState(true);
    const [pending, setPending] = useState(false);
    const [signingOut, setSigningOut] = useState(false);
    const [projectToDelete, setProjectToDelete] = useState<Project | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [formOpen, setFormOpen] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [taskManagerId, setTaskManagerId] = useState('');
    const [taskManagers, setTaskManagers] = useState<TaskManagerOption[]>([]);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [screen, setScreen] = useState<'dashboard' | 'projects' | 'board'>('dashboard');
    const [selectedProject, setSelectedProject] = useState<Project | null>(null);
    const [stats, setStats] = useState<DashboardStats | null>(null);

    useEffect(() => {
        if (screen !== 'projects') {
            return;
        }

        let active = true;

        listProjects(page)
            .then((next) => {
                if (active) {
                    setProjects(next.projects);
                    setListMeta(next);
                }
            })
            .catch((caught: unknown) => {
                if (active) {
                    setError(caught instanceof ApiError ? caught.message : 'Could not load projects.');
                }
            })
            .finally(() => {
                if (active) {
                    setLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, [screen, page]);

    useEffect(() => {
        if (screen !== 'dashboard') {
            return;
        }

        let active = true;

        dashboardStats()
            .then((next) => {
                if (active) {
                    setStats(next);
                }
            })
            .catch((caught: unknown) => {
                if (active) {
                    setError(caught instanceof ApiError ? caught.message : 'Could not load the dashboard.');
                }
            });

        return () => {
            active = false;
        };
    }, [screen]);

    useEffect(() => {
        if (user.role !== 'admin') {
            return;
        }

        listTaskManagers()
            .then(setTaskManagers)
            .catch((caught: unknown) => {
                setError(caught instanceof ApiError ? caught.message : 'Could not load task managers.');
            });
    }, [user.role]);

    function resetForm() {
        setName('');
        setDescription('');
        setTaskManagerId('');
        setEditingId(null);
        setFormOpen(false);
    }

    function openCreate() {
        setEditingId(null);
        setName('');
        setDescription('');
        setTaskManagerId('');
        setError(null);
        setFormOpen(true);
    }

    async function onSubmit(event: FormEvent) {
        event.preventDefault();
        setPending(true);
        setError(null);

        const input = {
            name,
            description,
            ...(user.role === 'admin' ? { task_manager_id: Number(taskManagerId) } : {}),
        };

        try {
            if (editingId === null) {
                await createProject(input);
            } else {
                await updateProject(editingId, input);
            }

            if (editingId === null && page !== 1) {
                setPage(1);
            } else {
                const next = await listProjects(editingId === null ? 1 : page);
                setProjects(next.projects);
                setListMeta(next);
            }

            resetForm();
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Could not save the project.');
        } finally {
            setPending(false);
        }
    }

    function startEdit(project: Project) {
        setEditingId(project.id);
        setName(project.name);
        setDescription(project.description ?? '');
        setTaskManagerId(String(project.owner.id));
        setError(null);
        setFormOpen(true);
    }

    async function confirmDeleteProject() {
        if (projectToDelete === null) {
            return;
        }

        setDeleting(true);
        setError(null);

        try {
            await deleteProject(projectToDelete.id);
            const next = await listProjects(page);

            if (next.projects.length === 0 && page > 1) {
                setPage(page - 1);
            } else {
                setProjects(next.projects);
                setListMeta(next);
            }

            if (editingId === projectToDelete.id) {
                resetForm();
            }

            setProjectToDelete(null);
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Could not delete the project.');
            setProjectToDelete(null);
        } finally {
            setDeleting(false);
        }
    }

    async function onLogout() {
        setSigningOut(true);
        await logout();
        onLoggedOut();
    }

    return (
        <div className="shell">
            <aside className="sidebar">
                <div className="logo">
                    <div className="logo-mark">T</div>
                    <span>TaskFlow</span>
                </div>

                <div className="nav-section">
                    <div className="nav-title">Workspace</div>
                    <button
                        className={screen === 'dashboard' ? 'nav-item active' : 'nav-item'}
                        type="button"
                        onClick={() => {
                            setScreen('dashboard');
                            resetForm();
                        }}
                    >
                        <span className="nav-icon">▦</span>
                        <span>Dashboard</span>
                    </button>
                    <button
                        className={screen === 'projects' || screen === 'board' ? 'nav-item active' : 'nav-item'}
                        type="button"
                        onClick={() => setScreen('projects')}
                    >
                        <span className="nav-icon">□</span>
                        <span>Projects</span>
                    </button>
                </div>

                <div className="sidebar-bottom">
                    <div className="user">
                        <div className="avatar">{initials(user.name)}</div>
                        <div className="user-info">
                            <div className="user-name">{user.name}</div>
                            <div className="user-role">{roleLabel(user.role)}</div>
                        </div>
                    </div>
                </div>
            </aside>

            <div className="shell-main">
                <header className="shell-topbar">
                    <div className="breadcrumb">
                        {screen === 'board' && selectedProject
                            ? <>Workspace / Projects / <strong>{selectedProject.name}</strong></>
                            : <>Workspace / <strong>{screen === 'dashboard' ? 'Dashboard' : 'Projects'}</strong></>}
                    </div>
                    <button className="btn btn-secondary" type="button" onClick={onLogout} disabled={signingOut}>
                        {signingOut ? 'Signing out' : 'Log out'}
                    </button>
                </header>

                <section className="shell-content">
                    {screen === 'board' && selectedProject ? (
                        <BoardScreen
                            project={selectedProject}
                            onBack={() => setScreen('projects')}
                        />
                    ) : screen === 'dashboard' ? (
                        <div className="page-header">
                            <div>
                                <h1 className="page-title">Dashboard</h1>
                                <p className="page-subtitle">
                                    {user.role === 'admin'
                                        ? 'Every project and task in the workspace.'
                                        : 'Only the projects you own, and the tasks on them.'}
                                </p>
                            </div>
                        </div>
                    ) : (
                        <div className="page-header">
                            <div>
                                <h1 className="page-title">Projects</h1>
                                <p className="page-subtitle">Create and manage your projects.</p>
                            </div>
                            <button className="btn btn-primary" type="button" onClick={openCreate}>
                                + New Project
                            </button>
                        </div>
                    )}

                    {error ? <p className="error">{error}</p> : null}

                    {screen === 'projects' && formOpen ? (
                        <form className="panel project-editor" onSubmit={onSubmit}>
                            <h2>{editingId === null ? 'New project' : 'Edit project'}</h2>
                            <label>
                                Name
                                <input value={name} onChange={(event) => setName(event.target.value)} required />
                            </label>
                            <label>
                                Description
                                <textarea value={description} onChange={(event) => setDescription(event.target.value)} />
                            </label>
                            {user.role === 'admin' ? (
                                <label>
                                    Task manager
                                    <select
                                        value={taskManagerId}
                                        onChange={(event) => setTaskManagerId(event.target.value)}
                                        required
                                    >
                                        <option value="">Select a task manager</option>
                                        {taskManagers.map((manager) => (
                                            <option key={manager.id} value={manager.id}>
                                                {manager.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            ) : null}
                            <div className="form-actions">
                                <button className="btn btn-primary" type="submit" disabled={pending}>
                                    {pending ? 'Please wait' : editingId === null ? 'Create project' : 'Save changes'}
                                </button>
                                <button className="btn btn-secondary" type="button" onClick={resetForm}>
                                    Cancel
                                </button>
                            </div>
                        </form>
                    ) : null}

                    {screen === 'dashboard' ? (
                    <DashboardPanels stats={stats} scope={user.role === 'admin' ? 'all' : 'own'} />
                    ) : screen === 'projects' ? (
                    <section className="panel projects-panel">
                        <div className="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Tasks</th>
                                        <th>Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {loading ? (
                                        <tr>
                                            <td className="empty-row" colSpan={4}>Loading projects</td>
                                        </tr>
                                    ) : null}
                                    {!loading && projects.length === 0 ? (
                                        <tr>
                                            <td className="empty-row" colSpan={4}>No projects yet.</td>
                                        </tr>
                                    ) : null}
                                    {projects.map((project) => (
                                        <tr key={project.id}>
                                            <td>
                                                <div className="project-name">{project.name}</div>
                                                {project.description ? (
                                                    <div className="project-description">{descriptionPreview(project.description)}</div>
                                                ) : null}
                                                {project.owner.id !== user.id ? (
                                                    <div className="project-description">Owner {project.owner.name}</div>
                                                ) : null}
                                            </td>
                                            <td>{project.tasks_count}</td>
                                            <td>
                                                <ProjectProgress
                                                    completed={project.completed_tasks_count}
                                                    total={project.tasks_count}
                                                />
                                            </td>
                                            <td>
                                                <div className="actions">
                                                    <button
                                                        className="icon-button"
                                                        type="button"
                                                        aria-label={`View ${project.name}`}
                                                        onClick={() => {
                                                            setSelectedProject(project);
                                                            setScreen('board');
                                                            resetForm();
                                                        }}
                                                    >
                                                        <ViewIcon />
                                                    </button>
                                                    <button className="icon-button" type="button" aria-label={`Edit ${project.name}`} onClick={() => startEdit(project)}>
                                                        <EditIcon />
                                                    </button>
                                                    <button className="icon-button danger" type="button" aria-label={`Delete ${project.name}`} onClick={() => setProjectToDelete(project)}>
                                                        <DeleteIcon />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {listMeta.total > 0 ? (
                            <div className="pager">
                                <div className="pager-summary">
                                    Showing {listMeta.from}–{listMeta.to} of {listMeta.total}
                                </div>
                                <div className="pager-controls">
                                    <button
                                        className="pager-page"
                                        type="button"
                                        disabled={page <= 1}
                                        onClick={() => setPage(page - 1)}
                                    >
                                        Previous
                                    </button>
                                    {pageNumbers(listMeta.currentPage, listMeta.lastPage).map((number) => (
                                        <button
                                            key={number}
                                            className={number === listMeta.currentPage ? 'pager-page active' : 'pager-page'}
                                            type="button"
                                            onClick={() => setPage(number)}
                                        >
                                            {number}
                                        </button>
                                    ))}
                                    <button
                                        className="pager-page"
                                        type="button"
                                        disabled={page >= listMeta.lastPage}
                                        onClick={() => setPage(page + 1)}
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>
                        ) : null}
                    </section>
                    ) : null}
                </section>
            </div>
            {projectToDelete ? (
                <ConfirmDialog
                    title="Delete project"
                    message={`Delete ${projectToDelete.name}? This cannot be undone.`}
                    confirmLabel="Delete"
                    pending={deleting}
                    onConfirm={confirmDeleteProject}
                    onCancel={() => setProjectToDelete(null)}
                />
            ) : null}
        </div>
    );
}
