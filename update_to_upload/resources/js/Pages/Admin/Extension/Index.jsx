import React from 'react';
import { PageHead, Stat, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminExtensionIndex({ version, zipUpdatedAt, sourceCounts, applyTypeCounts, easyApplyStatusCounts, recentTokenUsage }) {
    return (
        <>
            <PageHead title="Chrome Extension" subtitle="Version, usage, and Easy Apply statistics." />

            <div className="stats">
                <Stat label="Extension Version" value={version || '—'} icon={Icons.tag} accent="primary" />
                <Stat label="Zip Last Rebuilt" value={zipUpdatedAt || '—'} icon={Icons.clock} accent="blue" />
                <Stat label="Link Saves" value={applyTypeCounts.link || 0} icon={Icons.send} accent="green" />
                <Stat label="Easy Apply Saves" value={applyTypeCounts.easy_apply || 0} icon={Icons.sparkle} accent="violet" />
            </div>

            <div className="card">
                <h2>Saves by Source</h2>
                {sourceCounts.length === 0 ? <p className="muted">No jobs saved via the extension yet.</p> : (
                    <div className="table-wrap">
                        <table>
                            <thead><tr><th>Source</th><th>Count</th></tr></thead>
                            <tbody>{sourceCounts.map((s) => <tr key={s.source}><td>{s.source}</td><td>{s.count}</td></tr>)}</tbody>
                        </table>
                    </div>
                )}
            </div>

            <div className="card">
                <h2>Easy Apply Statistics</h2>
                {Object.keys(easyApplyStatusCounts).length === 0 ? <p className="muted">No Easy Apply activity yet.</p> : (
                    <div className="table-wrap">
                        <table>
                            <thead><tr><th>Status</th><th>Count</th></tr></thead>
                            <tbody>
                                {Object.entries(easyApplyStatusCounts).map(([status, count]) => (
                                    <tr key={status || 'none'}><td>{status || 'Not reported'}</td><td>{count}</td></tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <div className="card">
                <h2>Recent Extension Logins (API Token Usage)</h2>
                {recentTokenUsage.length === 0 ? <p className="muted">No extension activity recorded yet.</p> : (
                    <div className="table-wrap">
                        <table>
                            <thead><tr><th>User</th><th>Last Used</th></tr></thead>
                            <tbody>
                                {recentTokenUsage.map((t, i) => <tr key={i}><td>{t.name} ({t.email})</td><td>{t.last_used_at}</td></tr>)}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

AdminExtensionIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
