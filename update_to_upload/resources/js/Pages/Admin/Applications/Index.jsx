import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { PageHead, Badge, Stat, IconField, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminApplicationsIndex({ jobs, filters, stats }) {
    const [search, setSearch] = useState(filters.search || '');

    const applyFilters = (next) => {
        const merged = { search, status: filters.status || '', ...next };
        router.get('/admin/applications', merged, { preserveState: true, replace: true });
    };

    const retry = (id) => router.post(`/admin/applications/${id}/retry`, {}, { preserveScroll: true });
    const destroy = (id) => { if (confirm('Delete this application?')) router.delete(`/admin/applications/${id}`, { preserveScroll: true }); };

    return (
        <>
            <PageHead title="Applications" subtitle="Every job application across all users." />

            <div className="stats">
                <Stat label="Success Rate" value={`${stats.successRate}%`} icon={Icons.rate} accent="green" />
                <Stat label="Top Company" value={stats.byCompany[0]?.company || '—'} icon={Icons.building} accent="primary" />
                <Stat label="Most Active User" value={stats.byUser[0]?.name || '—'} icon={Icons.user} accent="violet" />
            </div>

            <div className="card card-pad-sm">
                <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'center' }}>
                    <IconField icon={Icons.search} type="text" placeholder="Search company or title…" value={search}
                        onChange={(e) => { setSearch(e.target.value); applyFilters({ search: e.target.value }); }} style={{ minWidth: 220 }} />
                    <select value={filters.status || ''} onChange={(e) => applyFilters({ status: e.target.value })} style={{ width: 'auto' }}>
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="queued">Queued</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                    </select>
                    <div style={{ flex: 1 }} />
                    <a href="/admin/applications/export" className="btn btn-ghost btn-sm">Export CSV</a>
                </div>
            </div>

            <div className="card">
                <div style={{ overflowX: 'auto' }}>
                    <table>
                        <thead>
                            <tr><th>Company</th><th>Title</th><th>User</th><th>Status</th><th>Sent</th><th></th></tr>
                        </thead>
                        <tbody>
                            {jobs.data.map((j) => (
                                <tr key={j.id}>
                                    <td>{j.company}</td>
                                    <td>{j.job_title || '—'}</td>
                                    <td>{j.user ? <Link href={`/admin/users/${j.user.id}`}>{j.user.name}</Link> : '—'}</td>
                                    <td><Badge status={j.status}>{j.status}</Badge>{j.error_short && <div className="muted" style={{ fontSize: 12 }}>{j.error_short}</div>}</td>
                                    <td>{j.sent_at || '—'}</td>
                                    <td style={{ display: 'flex', gap: 6 }}>
                                        {(j.status === 'failed' || j.status === 'pending') && (
                                            <button className="btn btn-ghost btn-sm" onClick={() => retry(j.id)}>Retry</button>
                                        )}
                                        <button className="btn btn-danger btn-sm" onClick={() => destroy(j.id)}>Delete</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {jobs.links && jobs.links.length > 3 && (
                    <div style={{ display: 'flex', gap: 6, marginTop: 16, flexWrap: 'wrap' }}>
                        {jobs.links.map((link, i) => (
                            <button key={i} className={`btn btn-sm ${link.active ? 'btn-primary' : 'btn-ghost'}`} disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

AdminApplicationsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
