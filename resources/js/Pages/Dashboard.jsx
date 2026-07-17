import React from 'react';
import { PageHead, Stat, Badge, Icons } from '../components';

const PIPELINE_COLORS = {
    applied: 'primary', replied: 'blue', interview: 'amber', rejected: 'red', offer: 'green',
};

export default function Dashboard({
    counts, sentRate, chartData, thisWeek, lastWeek, weekStart,
    topCompanies, recentActivity, tracking, pipelineStats, pipelineLabels,
}) {
    const diff = thisWeek - lastWeek;
    const maxVal = Math.max(...chartData.map((d) => d.total), 1);

    return (
        <>
            <PageHead title="Dashboard" subtitle="Overview of your job application activity." />

            <div className="stats">
                <Stat label="Total Jobs" value={counts.total} accent="primary" icon={Icons.briefcase} />
                <Stat label="Sent" value={counts.sent} accent="green" icon={Icons.send} />
                <Stat label="Pending" value={counts.pending + counts.queued} accent="amber" icon={Icons.clock} />
                <Stat label="Failed" value={counts.failed} accent="red" icon={Icons.alert} />
                <Stat label="Success Rate" value={`${sentRate}%`} accent="violet" icon={Icons.target} />
            </div>

            <div className="section-title">Email engagement</div>
            <div className="stats">
                <Stat label="Opened" value={tracking.opened} accent="sky" icon={Icons.eye} />
                <Stat label="Clicked" value={tracking.clicked} accent="blue" icon={Icons.click} />
                <Stat label="Open Rate" value={`${tracking.open_rate}%`} accent="violet" icon={Icons.rate} />
            </div>

            <div className="row">
                <div className="card">
                    <h2>This Week</h2>
                    <p className="hint">Applications added since {weekStart}</p>
                    <div className="num" style={{ fontSize: 34, fontWeight: 700 }}>{thisWeek}</div>
                    {diff > 0 && <span style={{ color: 'var(--green)', fontSize: 13, fontWeight: 600 }}>+{diff} vs last week</span>}
                    {diff < 0 && <span style={{ color: 'var(--red)', fontSize: 13, fontWeight: 600 }}>{diff} vs last week</span>}
                    {diff === 0 && <span className="muted" style={{ fontSize: 13 }}>Same as last week</span>}
                </div>

                <div className="card">
                    <h2>Pipeline</h2>
                    <p className="hint">Application stages breakdown</p>
                    {Object.entries(pipelineLabels).map(([key, label]) => (
                        <div className="list-row" key={key}>
                            <span className="lead">{label}</span>
                            <span style={{ fontWeight: 700, color: `var(--${PIPELINE_COLORS[key] || 'text'})` }}>
                                {pipelineStats[key] ?? 0}
                            </span>
                        </div>
                    ))}
                </div>

                <div className="card">
                    <h2>Top Companies</h2>
                    <p className="hint">Most applications by company</p>
                    {topCompanies.length === 0 ? (
                        <div className="muted" style={{ padding: '10px 0' }}>No data yet.</div>
                    ) : topCompanies.map((c) => (
                        <div className="list-row" key={c.company}>
                            <span className="lead" style={{ fontWeight: 500 }}>{c.company}</span>
                            <Badge status="sent">{c.count}</Badge>
                        </div>
                    ))}
                </div>
            </div>

            <div className="card">
                <h2>Activity (Last 30 Days)</h2>
                <p className="hint">
                    Applications added per day. <span style={{ color: 'var(--primary)' }}>Blue</span> = total,{' '}
                    <span style={{ color: 'var(--green)' }}>Green</span> = sent.
                </p>
                <div className="chart">
                    {chartData.map((day, i) => (
                        <div className="bar-col" key={i} title={`${day.label}: ${day.total} added, ${day.sent} sent`}>
                            {day.total > 0 ? (
                                <div className="bar" style={{ height: `${(day.total / maxVal) * 100}%` }}>
                                    {day.sent > 0 && (
                                        <div className="sent" style={{ height: `${(day.sent / day.total) * 100}%` }} />
                                    )}
                                </div>
                            ) : (
                                <div style={{ width: '100%', maxWidth: 22, height: 2, background: 'var(--border)', borderRadius: 1 }} />
                            )}
                        </div>
                    ))}
                </div>
                <div style={{ display: 'flex', gap: 3, marginTop: 6 }}>
                    {chartData.map((day, i) => (
                        <div key={i} style={{ flex: 1, textAlign: 'center', fontSize: 10, color: 'var(--muted)' }}>
                            {(i % 5 === 0 || i === chartData.length - 1) ? day.label : ''}
                        </div>
                    ))}
                </div>
            </div>

            <div className="card">
                <h2>Recent Activity</h2>
                <p className="hint">Last 10 sent or failed applications.</p>
                {recentActivity.length === 0 ? (
                    <div className="empty">No sent or failed applications yet.</div>
                ) : (
                    <div className="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Company / Role</th><th>Recruiter</th><th>Status</th><th>When</th></tr>
                            </thead>
                            <tbody>
                                {recentActivity.map((job) => (
                                    <tr key={job.id}>
                                        <td>
                                            <strong>{job.company}</strong><br />
                                            <span className="muted">{job.job_title || '—'}</span>
                                        </td>
                                        <td>
                                            {job.recruiter_name || '—'}<br />
                                            <span className="muted">{job.recruiter_email}</span>
                                        </td>
                                        <td><Badge status={job.status} /></td>
                                        <td className="muted">{job.when}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
