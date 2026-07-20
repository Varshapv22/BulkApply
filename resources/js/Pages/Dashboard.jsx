import React from 'react';
import { PageHead, Badge, Icons } from '../components';

const PIPELINE_COLORS = {
    applied: 'primary', replied: 'blue', interview: 'amber', rejected: 'red', offer: 'green',
};

// A specialized stat component for the Bento box
function StatBento({ label, value, accent, icon, className = "" }) {
    return (
        <div className={`stat-bento accent-${accent} ${className}`}>
            <div className="stat-icon">
                {icon && (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        {icon}
                    </svg>
                )}
            </div>
            <div className="num">{value}</div>
            <div className="lbl">{label}</div>
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

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '20px', marginBottom: '20px' }}>
                <StatBento label="Total Jobs" value={counts.total} accent="primary" icon={Icons.briefcase} className="animate-delay-1" />
                <StatBento label="Sent" value={counts.sent} accent="green" icon={Icons.send} className="animate-delay-1" />
                <StatBento label="Success Rate" value={`${sentRate}%`} accent="violet" icon={Icons.target} className="animate-delay-1" />
                <StatBento label="Open Rate" value={`${tracking.open_rate}%`} accent="sky" icon={Icons.eye} className="animate-delay-1" />
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
                    <div className="chart" style={{ flex: 1, marginTop: '20px' }}>
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

                <div className="card bento-col-4 animate-delay-2" style={{ background: 'var(--primary-grad)', color: '#fff' }}>
                    <h2 style={{ color: '#fff' }}>This Week</h2>
                    <p style={{ color: 'rgba(255,255,255,0.8)', fontSize: 13, marginBottom: 'auto' }}>Applications added since {weekStart}</p>
                    
                    <div style={{ fontSize: 64, fontWeight: 800, letterSpacing: '-0.04em', lineHeight: 1, margin: '20px 0' }}>
                        {thisWeek}
                    </div>
                    
                    {diff > 0 && <span style={{ color: 'rgba(255,255,255,0.9)', fontSize: 14, fontWeight: 600 }}>↑ +{diff} vs last week</span>}
                    {diff < 0 && <span style={{ color: 'rgba(255,255,255,0.7)', fontSize: 14, fontWeight: 600 }}>↓ {diff} vs last week</span>}
                    {diff === 0 && <span style={{ color: 'rgba(255,255,255,0.6)', fontSize: 14 }}>Same as last week</span>}
                </div>

                {/* Lower Row */}
                <div className="card bento-col-6 animate-delay-3">
                    <h2>Pipeline Status</h2>
                    <p className="hint">Application stages breakdown</p>
                    <div style={{ marginTop: 'auto' }}>
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
                        <div className="empty" style={{ padding: '20px' }}>No sent or failed applications yet.</div>
                    ) : (
                        <div style={{ marginTop: 'auto', display: 'flex', flexDirection: 'column', gap: '12px' }}>
                            {recentActivity.slice(0, 5).map((job) => (
                                <div key={job.id} style={{ display: 'flex', alignItems: 'center', gap: '14px', padding: '12px', background: 'var(--card-2)', borderRadius: '14px', border: '1px solid var(--border)' }}>
                                    <div style={{ width: 40, height: 40, borderRadius: 10, background: 'var(--primary-soft)', color: 'var(--primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16, fontWeight: 800 }}>
                                        {job.company.charAt(0)}
                                    </div>
                                    <div style={{ flex: 1 }}>
                                        <div style={{ fontWeight: 700, color: 'var(--heading)' }}>{job.company}</div>
                                        <div style={{ fontSize: 12, color: 'var(--muted)', marginTop: 2 }}>{job.job_title || 'Application'}</div>
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
