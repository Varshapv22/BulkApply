import React from 'react';
import { PageHead, Stat, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminReportsIndex({ registrations, byCompany, byPipeline, emailStats, resumesByDay, autoApply }) {
    const regTotal = registrations.reduce((s, r) => s + r.count, 0);
    const resumeTotal = resumesByDay.reduce((s, r) => s + r.count, 0);

    return (
        <>
            <PageHead title="Reports" subtitle="Exportable reports across users, applications, and emails." />

            <div className="card">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <h2>User Registrations (last 30 days)</h2>
                    <a className="btn btn-ghost btn-sm" href="/admin/reports/export/registrations">Export CSV</a>
                </div>
                <p className="muted">{regTotal} new users in the last 30 days.</p>
            </div>

            <div className="card">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <h2>Applications by Company (top 10)</h2>
                    <a className="btn btn-ghost btn-sm" href="/admin/reports/export/companies">Export CSV</a>
                </div>
                <table>
                    <thead><tr><th>Company</th><th>Applications</th></tr></thead>
                    <tbody>{byCompany.map((c) => <tr key={c.company}><td>{c.company}</td><td>{c.count}</td></tr>)}</tbody>
                </table>
            </div>

            <div className="card">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <h2>Pipeline Report</h2>
                    <a className="btn btn-ghost btn-sm" href="/admin/reports/export/pipeline">Export CSV</a>
                </div>
                <table>
                    <thead><tr><th>Stage</th><th>Count</th></tr></thead>
                    <tbody>{Object.entries(byPipeline).map(([stage, count]) => <tr key={stage}><td>{stage}</td><td>{count}</td></tr>)}</tbody>
                </table>
            </div>

            <div className="card">
                <h2>Email Report</h2>
                <div className="stats">
                    <Stat label="Sent" value={emailStats.sent} icon={Icons.send} accent="green" />
                    <Stat label="Failed" value={emailStats.failed} icon={Icons.alert} accent="red" />
                    <Stat label="Opened" value={emailStats.opened} icon={Icons.eye} accent="blue" />
                    <Stat label="Clicked" value={emailStats.clicked} icon={Icons.click} accent="violet" />
                </div>
            </div>

            <div className="card">
                <h2>Resume Uploads (last 30 days)</h2>
                <p className="muted">{resumeTotal} resumes uploaded in the last 30 days.</p>
            </div>

            <div className="card">
                <h2>Search &amp; Auto-Apply Report</h2>
                <p className="muted">Search volume isn't logged anywhere in the app today, so it can't be reported on here. The figures below are jobs imported via the Find Jobs auto-apply flow (identifiable from their auto-generated notes).</p>
                <div className="stats">
                    <Stat label="Imported via Search" value={autoApply.imported} icon={Icons.search} accent="primary" />
                    <Stat label="Auto Email-Applied" value={autoApply.emailApplied} icon={Icons.send} accent="green" />
                    <Stat label="Link-Only (Manual Apply)" value={autoApply.linkOnly} icon={Icons.globe} accent="blue" />
                </div>
            </div>
        </>
    );
}

AdminReportsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
