import React from 'react';
import { Link, router } from '@inertiajs/react';
import { PageHead, EmptyState } from '../components';

const STATUS = {
    pass: { color: 'var(--green)', bg: 'var(--green-bg)', icon: '✓', label: 'Good' },
    warn: { color: 'var(--amber)', bg: 'var(--amber-bg)', icon: '!', label: 'Improve' },
    fail: { color: 'var(--red)', bg: 'var(--red-bg)', icon: '✕', label: 'Fix' },
};

function ScoreRing({ score, grade }) {
    const r = 62, c = 2 * Math.PI * r;
    const color = score >= 85 ? 'var(--green)' : score >= 70 ? 'var(--primary)' : score >= 50 ? 'var(--amber)' : 'var(--red)';
    return (
        <div className="score-ring">
            <svg width="150" height="150" viewBox="0 0 150 150">
                <circle cx="75" cy="75" r={r} fill="none" stroke="var(--hover-strong)" strokeWidth="11" />
                <circle cx="75" cy="75" r={r} fill="none" stroke={color} strokeWidth="11" strokeLinecap="round"
                    strokeDasharray={c} strokeDashoffset={c * (1 - score / 100)}
                    transform="rotate(-90 75 75)" style={{ transition: 'stroke-dashoffset .8s ease' }} />
            </svg>
            <div className="ring-txt">
                <div className="ring-num" style={{ color }}>{score}</div>
                <div className="ring-grade">{grade}</div>
            </div>
        </div>
    );
}

function CheckRow({ check }) {
    const s = STATUS[check.status];
    return (
        <div className="check-row">
            <span className="check-ico" style={{ background: s.bg, color: s.color }}>{s.icon}</span>
            <div style={{ minWidth: 0, flex: 1 }}>
                <div className="check-label">{check.label}</div>
                <div className="check-tip">{check.tip}</div>
            </div>
        </div>
    );
}

export default function ResumeCheck({ hasResume, resumeName, targetRole, report, error }) {
    return (
        <>
            <PageHead title="Resume ATS Check"
                subtitle="See how well your resume survives Applicant Tracking Systems — and exactly what to improve." />

            {!hasResume && (
                <div className="card">
                    <EmptyState icon="briefcase" title="No resume uploaded yet">
                        Upload your resume on the <Link href="/profile">Settings</Link> page,
                        then come back here for an instant ATS report.
                    </EmptyState>
                </div>
            )}

            {error && <div className="alert alert-error"><div className="alert-body">{error}</div></div>}

            {report && (
                <>
                    <div className="card ats-head">
                        <ScoreRing score={report.score} grade={report.grade} />
                        <div className="ats-head-txt">
                            <h2 style={{ fontSize: 20 }}>ATS Compatibility Score</h2>
                            <p className="hint" style={{ marginBottom: 10 }}>
                                Analyzed <strong>{report.meta.file}</strong> (~{report.meta.words} words)
                                {targetRole ? <> against your target role “<strong>{targetRole}</strong>”.</> : <>.
                                    {' '}Set a preferred role in <Link href="/profile">Settings</Link> to also get keyword matching.</>}
                            </p>
                            <div className="legend">
                                <span><i style={{ background: 'var(--green)' }} /> Good</span>
                                <span><i style={{ background: 'var(--amber)' }} /> Could improve</span>
                                <span><i style={{ background: 'var(--red)' }} /> Needs fixing</span>
                            </div>
                            <button className="btn btn-ghost btn-sm" style={{ marginTop: 14 }} onClick={() => router.reload()}>
                                Re-analyze
                            </button>
                        </div>
                    </div>

                    {report.improvements.length > 0 && (
                        <div className="card">
                            <h2>What to improve first</h2>
                            <p className="hint">Ranked by impact — fix the top ones for the biggest score gain.</p>
                            {report.improvements.map((c) => <CheckRow key={c.id} check={c} />)}
                        </div>
                    )}

                    <div className="row" style={{ alignItems: 'stretch' }}>
                        {Object.entries(report.categories).map(([cat, checks]) => (
                            <div className="card" key={cat} style={{ minWidth: 280 }}>
                                <h2 style={{ fontSize: 15 }}>{cat}</h2>
                                <p className="hint" style={{ marginBottom: 10 }}>
                                    {checks.filter((c) => c.status === 'pass').length}/{checks.length} passed
                                </p>
                                {checks.map((c) => <CheckRow key={c.id} check={c} />)}
                            </div>
                        ))}
                    </div>

                    <div className="card" style={{ background: 'var(--primary-soft)', borderColor: 'transparent' }}>
                        <h2 style={{ fontSize: 15 }}>Fixed something?</h2>
                        <p className="hint" style={{ margin: 0 }}>
                            Upload the updated resume on <Link href="/profile">Settings</Link> and this report
                            refreshes automatically the next time you open it.
                        </p>
                    </div>
                </>
            )}
        </>
    );
}
