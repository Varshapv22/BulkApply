import React from 'react';
import { Link } from '@inertiajs/react';
import { PageHead, Badge, Icons } from '../components';

const PIPELINE_COLORS = {
    applied: 'primary', replied: 'blue', interview: 'amber', rejected: 'red', offer: 'green',
};

// Week-over-week movement pill. `null` renders nothing — a delta of 0 still
// renders, because "no change" is itself a reading.
function Delta({ value, suffix = '' }) {
    if (value === null || value === undefined) return null;
    const dir = value > 0 ? 'up' : value < 0 ? 'down' : 'flat';
    return (
        <span className={`stat-delta ${dir}`} title={`${value > 0 ? '+' : ''}${value} vs last week`}>
            {dir !== 'flat' && (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                    {dir === 'up'
                        ? <><path d="M12 19V5" /><path d="m5 12 7-7 7 7" /></>
                        : <><path d="M12 5v14" /><path d="m19 12-7 7-7-7" /></>}
                </svg>
            )}
            {value > 0 ? '+' : ''}{value}{suffix}
        </span>
    );
}

// KPI card: label → figure (+ movement) → meter / supporting line.
function StatBento({ label, value, accent = 'primary', icon, delta, meter, foot, className = '' }) {
    return (
        <div className={`stat-bento sb-${accent} ${className}`}>
            <div className="stat-head">
                <span className="lbl">{label}</span>
                {icon && (
                    <span className="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            {icon}
                        </svg>
                    </span>
                )}
            </div>

            <div className="stat-value-row">
                <span className="num">{value}</span>
                <Delta value={delta} />
            </div>

            {meter !== undefined && meter !== null && (
                <div className="stat-meter">
                    <i style={{ width: `${Math.max(0, Math.min(100, meter))}%` }} />
                </div>
            )}

            {foot && <div className="stat-foot">{foot}</div>}
        </div>
    );
}

export default function Dashboard({
    counts, sentRate, chartData, thisWeek, lastWeek, weekStart,
    topCompanies, recentActivity, tracking, pipelineStats, pipelineLabels,
}) {
    const diff = thisWeek - lastWeek;
    const maxVal = Math.max(...chartData.map((d) => d.total), 1);

    return (
        <>
            <PageHead title="Dashboard" subtitle="Overview of your job application activity." />

            <div className="stat-grid">
                <StatBento
                    label="Total Jobs" value={counts.total} accent="primary" icon={Icons.briefcase}
                    delta={diff}
                    foot={<><b>{thisWeek}</b> added this week</>}
                    className="animate-delay-1"
                />
                <StatBento
                    label="Sent" value={counts.sent} accent="green" icon={Icons.send}
                    foot={counts.queued > 0
                        ? <><b>{counts.queued}</b> queued to go out</>
                        : <><b>{counts.pending}</b> waiting to be sent</>}
                    className="animate-delay-1"
                />
                <StatBento
                    label="Success Rate" value={`${sentRate}%`} accent="violet" icon={Icons.target}
                    meter={sentRate}
                    foot={<><b>{counts.sent}</b> of {counts.total} delivered{counts.failed > 0 ? <> · <b>{counts.failed}</b> failed</> : null}</>}
                    className="animate-delay-1"
                />
                <StatBento
                    label="Open Rate" value={`${tracking.open_rate}%`} accent="sky" icon={Icons.eye}
                    meter={tracking.open_rate}
                    foot={<><b>{tracking.opened}</b> opened · <b>{tracking.clicked}</b> clicked</>}
                    className="animate-delay-1"
                />
            </div>

            {/* BENTO GRID */}
            <div className="bento-grid">

                {/* Main Content Area */}
                <div className="card bento-col-8 animate-delay-2">
                    <h2>Activity Overview</h2>
                    <p className="hint">
                        Applications added per day. <span style={{ color: 'var(--primary)' }}>Blue</span> = total,{' '}
                        <span style={{ color: 'var(--green)' }}>Green</span> = sent.
                    </p>
                    <div className="chart" style={{ marginTop: '20px' }}>
                        {chartData.map((day, i) => (
                            <div className="bar-col" key={i} title={`${day.label}: ${day.total} added, ${day.sent} sent`}>
                                {day.total > 0 ? (
                                    <div className="bar" style={{ height: `${(day.total / maxVal) * 100}%` }}>
                                        {day.sent > 0 && (
                                            <div className="sent" style={{ height: `${(day.sent / day.total) * 100}%` }} />
                                        )}
                                    </div>
                                ) : (
                                    <div style={{ width: '100%', maxWidth: 22, height: 4, background: 'var(--border-strong)', borderRadius: 2 }} />
                                )}
                            </div>
                        ))}
                    </div>
                    <div style={{ display: 'flex', gap: 3, marginTop: 12 }}>
                        {chartData.map((day, i) => (
                            <div key={i} style={{ flex: 1, textAlign: 'center', fontSize: 11, color: 'var(--muted)', fontWeight: 500 }}>
                                {(i % 5 === 0 || i === chartData.length - 1) ? day.label : ''}
                            </div>
                        ))}
                    </div>
                </div>

                <div className="card bento-col-4 animate-delay-2 stat-hero">
                    <div className="stat-head">
                        <span className="lbl">This Week</span>
                        <span className="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                {Icons.calendar}
                            </svg>
                        </span>
                    </div>
                    <p className="stat-hero-sub">Applications added since {weekStart}</p>

                    <div className="stat-value-row" style={{ marginTop: 'auto' }}>
                        <span className="num">{thisWeek}</span>
                    </div>
                    <div className="stat-foot stat-hero-foot">
                        <Delta value={diff} />
                        <span>vs last week</span>
                    </div>
                </div>

                {/* Lower Row */}
                <div className="card bento-col-6 animate-delay-3">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', gap: 12 }}>
                        <h2>Pipeline Status</h2>
                        <Link href="/pipeline" className="btn-link" style={{ fontSize: 12.5 }}>Open board →</Link>
                    </div>
                    <p className="hint">Application stages breakdown</p>
                    <div className="list-fill">
                        {Object.entries(pipelineLabels).map(([key, label]) => (
                            <div className="list-row" key={key}>
                                <span className="lead">{label}</span>
                                <span style={{ fontWeight: 700, fontSize: 16, color: `var(--${PIPELINE_COLORS[key] || 'text'})` }}>
                                    {pipelineStats[key] ?? 0}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="card bento-col-6 animate-delay-3">
                    <h2>Recent Activity</h2>
                    <p className="hint">Latest applications tracked.</p>
                    {recentActivity.length === 0 ? (
                        <div className="empty">No sent or failed applications yet.</div>
                    ) : (
                        <div className="activity-list">
                            {recentActivity.slice(0, 5).map((job) => (
                                <div key={job.id} className="activity-row">
                                    <span className="activity-avatar">{job.company.charAt(0)}</span>
                                    <div className="activity-info">
                                        <div className="activity-company">{job.company}</div>
                                        <div className="activity-title">{job.job_title || 'Application'}</div>
                                    </div>
                                    <Badge status={job.status} />
                                </div>
                            ))}
                        </div>
                    )}
                </div>

            </div>
        </>
    );
}
