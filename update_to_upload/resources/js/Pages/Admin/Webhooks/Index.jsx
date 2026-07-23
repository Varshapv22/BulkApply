import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { PageHead, Badge } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminWebhooksIndex({ logs }) {
    const [expanded, setExpanded] = useState(null);
    const retry = (id) => router.post(`/admin/webhooks/${id}/retry`, {}, { preserveScroll: true });

    return (
        <>
            <PageHead title="Webhooks" subtitle="Outgoing webhook call history." />

            <div className="card">
                {logs.length === 0 ? <p className="muted">No webhook calls logged yet.</p> : (
                    <div className="table-wrap">
                        <table>
                            <thead><tr><th>URL</th><th>Status</th><th>When</th><th></th></tr></thead>
                            <tbody>
                                {logs.map((l) => (
                                    <React.Fragment key={l.id}>
                                        <tr>
                                            <td>{l.url}</td>
                                            <td>
                                                <Badge status={l.success ? 'sent' : 'failed'}>{l.success ? 'Success' : 'Failed'}</Badge>
                                                {l.response_code && <span className="muted" style={{ marginLeft: 6, fontSize: 12 }}>HTTP {l.response_code}</span>}
                                            </td>
                                            <td>{new Date(l.created_at).toLocaleString()}</td>
                                            <td style={{ display: 'flex', gap: 6 }}>
                                                <button className="btn btn-ghost btn-sm" onClick={() => setExpanded(expanded === l.id ? null : l.id)}>
                                                    {expanded === l.id ? 'Hide' : 'View'} payload
                                                </button>
                                                <button className="btn btn-ghost btn-sm" onClick={() => retry(l.id)}>Retry</button>
                                            </td>
                                        </tr>
                                        {expanded === l.id && (
                                            <tr>
                                                <td colSpan={4}>
                                                    <pre style={{ whiteSpace: 'pre-wrap', fontSize: 12, background: 'var(--hover)', padding: 10, borderRadius: 8 }}>
                                                        {JSON.stringify(l.payload, null, 2)}
                                                        {l.error && `\n\nError: ${l.error}`}
                                                    </pre>
                                                </td>
                                            </tr>
                                        )}
                                    </React.Fragment>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

AdminWebhooksIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
