import React from 'react';
import { router } from '@inertiajs/react';
import { PageHead, Stat, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

function GrowthChart({ title, series }) {
    const max = Math.max(...series.map((d) => d.count), 1);
    return (
        <div className="card">
            <h2>{title}</h2>
            <div className="chart" style={{ marginTop: 20 }}>
                {series.map((d, i) => (
                    <div className="bar-col" key={i} title={`${d.label}: ${d.count}`}>
                        {d.count > 0
                            ? <div className="bar" style={{ height: `${(d.count / max) * 100}%` }} />
                            : <div style={{ width: '100%', maxWidth: 22, height: 4, background: 'var(--border-strong)', borderRadius: 2 }} />}
                    </div>
                ))}
            </div>
            <div style={{ display: 'flex', gap: 3, marginTop: 12 }}>
                {series.map((d, i) => (
                    <div key={i} style={{ flex: 1, textAlign: 'center', fontSize: 11, color: 'var(--muted)', fontWeight: 500 }}>
                        {(i % Math.ceil(series.length / 8) === 0) ? d.label : ''}
                    </div>
                ))}
            </div>
        </div>
    );
}

export default function AdminAnalyticsIndex({ days, userGrowth, applicationGrowth, resumeGrowth, emailPerformance }) {
    return (
        <>
            <PageHead title="Analytics" subtitle="Platform growth and email performance over time." />

            <div className="card card-pad-sm">
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                    {[7, 30, 90].map((d) => (
                        <button key={d} className={`btn btn-sm ${days === d ? 'btn-primary' : 'btn-ghost'}`}
                            onClick={() => router.get('/admin/analytics', { days: d })}>
                            {d} days
                        </button>
                    ))}
                </div>
            </div>

            <div className="stats">
                <Stat label="Emails Sent" value={emailPerformance.sent} icon={Icons.send} accent="green" />
                <Stat label="Emails Failed" value={emailPerformance.failed} icon={Icons.alert} accent="red" />
                <Stat label="Opened" value={emailPerformance.opened} icon={Icons.eye} accent="blue" />
                <Stat label="Clicked" value={emailPerformance.clicked} icon={Icons.click} accent="violet" />
            </div>

            <GrowthChart title="User Growth" series={userGrowth} />
            <GrowthChart title="Application Growth" series={applicationGrowth} />
            <GrowthChart title="Resume Upload Trend" series={resumeGrowth} />
        </>
    );
}

AdminAnalyticsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
