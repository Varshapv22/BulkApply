import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
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

function GmailRepliesCard({ gmailConnected, gmailReplies }) {
    const { props } = usePage();
    const flash = props.flash || {};
    const [syncing, setSyncing] = useState(false);
    const [readIds, setReadIds] = useState([]);

    const sync = () => {
        setSyncing(true);
        router.post('/gmail/sync', {}, {
            preserveScroll: true,
            onFinish: () => setSyncing(false),
        });
    };

    const markRead = (id) => {
        if (readIds.includes(id)) return;
        setReadIds((prev) => [...prev, id]);
        router.post(`/gmail/replies/${id}/read`, {}, { preserveScroll: true });
    };

    const visible = gmailReplies.filter((r) => !readIds.includes(r.id));

    return (
        <div className="card bento-col-12 animate-delay-4">
            <div className="card-head-row">
                <div>
                    <h2>Company Replies</h2>
                    <p className="hint" style={{ margin: '2px 0 0' }}>
                        Emails received from recruiters you applied to.
                    </p>
                </div>
                {gmailConnected && (
                    <button
                        type="button"
                        className="btn btn-ghost btn-sm"
                        onClick={sync}
                        disabled={syncing}
                    >
                        {syncing ? 'Syncing…' : '↻ Sync Gmail'}
                    </button>
                )}
            </div>

            {flash.gmail_status && (
                <div style={{ margin: '8px 0', padding: '8px 12px', background: 'var(--green-soft, #d1fae5)', color: 'var(--green)', borderRadius: 8, fontSize: 13 }}>
                    {flash.gmail_status}
                </div>
            )}
            {flash.gmail_error && (
                <div style={{ margin: '8px 0', padding: '8px 12px', background: 'var(--red-soft, #fee2e2)', color: 'var(--red)', borderRadius: 8, fontSize: 13 }}>
                    {flash.gmail_error}
                </div>
            )}

            {!gmailConnected ? (
                <div className="empty" style={{ padding: '24px 0', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 10 }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" style={{ width: 36, height: 36, color: 'var(--muted)' }}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6.75L2.25 6.75" />
                    </svg>
                    <p className="muted" style={{ margin: 0, fontSize: 13 }}>
                        Connect your Gmail in <a href="/profile" className="btn-link">Profile → Email Sending</a> to see company replies here.
                    </p>
                </div>
            ) : visible.length === 0 ? (
                <div className="empty" style={{ padding: '20px 0', textAlign: 'center' }}>
                    <p className="muted" style={{ margin: 0, fontSize: 13 }}>
                        No replies yet. Click <strong>↻ Sync Gmail</strong> to check your inbox.
                    </p>
                </div>
            ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 10, marginTop: 12 }}>
                    {visible.map((r) => (
                        <div
                            key={r.id}
                            onClick={() => markRead(r.id)}
                            style={{
                                display: 'flex', alignItems: 'flex-start', gap: 14,
                                padding: '12px 14px',
                                background: r.is_read ? 'var(--card-2)' : 'var(--primary-soft)',
                                borderRadius: 14,
                                border: `1px solid ${r.is_read ? 'var(--border)' : 'var(--primary)'}`,
                                cursor: 'pointer',
                                transition: 'background 0.2s',
                            }}
                        >
                            <div style={{
                                width: 40, height: 40, borderRadius: 10, flexShrink: 0,
                                background: 'var(--primary-soft)', color: 'var(--primary)',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                fontSize: 15, fontWeight: 800,
                            }}>
                                {(r.company || r.from_email)[0].toUpperCase()}
                            </div>
                            <div style={{ flex: 1, minWidth: 0 }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
                                    <span style={{ fontWeight: 700, color: 'var(--heading)', fontSize: 14 }}>
                                        {r.company || r.from_name || r.from_email}
                                    </span>
                                    {!r.is_read && (
                                        <span style={{ background: 'var(--primary)', color: '#fff', fontSize: 10, fontWeight: 700, borderRadius: 99, padding: '1px 7px' }}>NEW</span>
                                    )}
                                    <span style={{ marginLeft: 'auto', fontSize: 11, color: 'var(--muted)', whiteSpace: 'nowrap' }}>{r.received_at}</span>
                                </div>
                                <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--text)', marginTop: 2, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                    {r.subject}
                                </div>
                                {r.snippet && (
                                    <div style={{ fontSize: 12, color: 'var(--muted)', marginTop: 3, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                        {r.snippet}
                                    </div>
                                )}
                                <div style={{ fontSize: 11, color: 'var(--muted)', marginTop: 3 }}>{r.from_email}</div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function Dashboard({
    counts, sentRate, chartData, thisWeek, lastWeek, weekStart,
    topCompanies, recentActivity, tracking, pipelineStats, pipelineLabels,
    gmailConnected, gmailReplies,
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

                <div className="card bento-col-4 animate-delay-2 stat-bento-hero">
                    <h2>This Week</h2>
                    <p className="sub" style={{ fontSize: 13, marginBottom: 'auto' }}>Applications added since {weekStart}</p>

                    <div className="big-num" style={{ fontWeight: 800, letterSpacing: '-0.04em', lineHeight: 1, margin: '20px 0' }}>
                        {thisWeek}
                    </div>

                    {diff > 0 && <span style={{ color: 'var(--green)', fontSize: 14, fontWeight: 600 }}>↑ +{diff} vs last week</span>}
                    {diff < 0 && <span style={{ color: 'var(--red)', fontSize: 14, fontWeight: 600 }}>↓ {diff} vs last week</span>}
                    {diff === 0 && <span className="sub" style={{ fontSize: 14 }}>Same as last week</span>}
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

            {/* Company Replies from Gmail */}
            <div className="bento-grid" style={{ marginTop: 0 }}>
                <GmailRepliesCard gmailConnected={gmailConnected} gmailReplies={gmailReplies} />
            </div>

        </>
    );
}
