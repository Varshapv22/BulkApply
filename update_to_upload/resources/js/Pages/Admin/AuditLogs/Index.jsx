import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { PageHead, IconField, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminAuditLogsIndex({ logs, filters }) {
    const [action, setAction] = useState(filters.action || '');

    const applyFilter = (v) => {
        setAction(v);
        router.get('/admin/audit-logs', { action: v }, { preserveState: true, replace: true });
    };

    return (
        <>
            <PageHead title="Audit Logs" subtitle="Append-only record of admin actions." />

            <div className="card card-pad-sm">
                <IconField icon={Icons.search} type="text" placeholder="Filter by action (e.g. user.delete)…" value={action}
                    onChange={(e) => applyFilter(e.target.value)} style={{ maxWidth: 320 }} />
            </div>

            <div className="card">
                <div style={{ overflowX: 'auto' }}>
                    <table>
                        <thead><tr><th>Admin</th><th>Action</th><th>Subject</th><th>Details</th><th>IP</th><th>When</th></tr></thead>
                        <tbody>
                            {logs.data.map((l) => (
                                <tr key={l.id}>
                                    <td>{l.admin ? `${l.admin.name}` : 'System'}</td>
                                    <td><code>{l.action}</code></td>
                                    <td>{l.subject_type ? `${l.subject_type} #${l.subject_id}` : '—'}</td>
                                    <td style={{ fontSize: 12 }} className="muted">{l.changes ? JSON.stringify(l.changes) : '—'}</td>
                                    <td>{l.ip}</td>
                                    <td>{new Date(l.created_at).toLocaleString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {logs.links && logs.links.length > 3 && (
                    <div style={{ display: 'flex', gap: 6, marginTop: 16, flexWrap: 'wrap' }}>
                        {logs.links.map((link, i) => (
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

AdminAuditLogsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
