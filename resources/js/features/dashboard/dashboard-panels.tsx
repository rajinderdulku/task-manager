import type { DashboardStats } from './api';

function barHeight(count: number, max: number): number {
    if (count === 0) {
        return 0;
    }

    return Math.max(16, Math.round((count / max) * 120));
}

export function DashboardPanels({ stats, scope }: { stats: DashboardStats | null; scope: 'all' | 'own' }) {
    const tasks = stats?.tasks ?? 0;
    const completed = stats?.completed ?? 0;
    const todo = stats?.todo ?? 0;
    const inProgress = stats?.in_progress ?? 0;
    const percent = tasks === 0 ? 0 : Math.round((completed / tasks) * 100);
    const max = Math.max(todo, inProgress, completed, 1);
    const value = (count: number | undefined) => (stats === null || count === undefined ? '—' : count);

    return (
        <>
            <div className="stats">
                <article className="stat-card">
                    <div className="stat-header">
                        <span className="stat-label">Total Projects</span>
                        <div className="stat-icon">□</div>
                    </div>
                    <div className="stat-value">{value(stats?.projects)}</div>
                    <div className="stat-change">{scope === 'all' ? 'All projects' : 'Your projects'}</div>
                </article>
                <article className="stat-card">
                    <div className="stat-header">
                        <span className="stat-label">Total Tasks</span>
                        <div className="stat-icon">✓</div>
                    </div>
                    <div className="stat-value">{value(stats?.tasks)}</div>
                    <div className="stat-change">{scope === 'all' ? 'All tasks' : 'On your projects'}</div>
                </article>
                <article className="stat-card">
                    <div className="stat-header">
                        <span className="stat-label">Completed Tasks</span>
                        <div className="stat-icon">✓</div>
                    </div>
                    <div className="stat-value">{value(stats?.completed)}</div>
                    <div className="stat-change">
                        {stats === null ? '—' : tasks === 0 ? 'No tasks yet' : `${percent}% completed`}
                    </div>
                </article>
            </div>

            <div className="dashboard-grid">
                <section className="panel">
                    <div className="panel-header">
                        <div className="panel-title">Task Overview</div>
                    </div>
                    <div className="chart">
                        <div className="chart-area">
                            <div className="bar-group">
                                <div className="bar-count">{value(stats?.todo)}</div>
                                <div className="bar todo" style={{ height: barHeight(todo, max) }} />
                                <div className="bar-label">To do</div>
                            </div>
                            <div className="bar-group">
                                <div className="bar-count">{value(stats?.in_progress)}</div>
                                <div className="bar progress" style={{ height: barHeight(inProgress, max) }} />
                                <div className="bar-label">In progress</div>
                            </div>
                            <div className="bar-group">
                                <div className="bar-count">{value(stats?.completed)}</div>
                                <div className="bar completed" style={{ height: barHeight(completed, max) }} />
                                <div className="bar-label">Done</div>
                            </div>
                        </div>
                        <div className="chart-legend">
                            <div className="legend"><span className="legend-dot" /> To do</div>
                            <div className="legend"><span className="legend-dot blue" /> In progress</div>
                            <div className="legend"><span className="legend-dot dark" /> Done</div>
                        </div>
                    </div>
                </section>

                <section className="panel">
                    <div className="panel-header">
                        <div className="panel-title">Overall Progress</div>
                    </div>
                    <div className="completion">
                        <div className="completion-number">{stats === null ? '—' : `${percent}%`}</div>
                        <div className="completion-text">
                            {stats === null
                                ? 'Loading'
                                : tasks === 0
                                    ? 'No tasks yet'
                                    : `${completed} of ${tasks} tasks completed`}
                        </div>
                        <div className="progress-track">
                            <div className="progress-value" style={{ width: `${percent}%` }} />
                        </div>
                        <div className="completion-stats">
                            <span>Completed: {value(stats?.completed)}</span>
                            <span>Remaining: {stats === null ? '—' : tasks - completed}</span>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}
