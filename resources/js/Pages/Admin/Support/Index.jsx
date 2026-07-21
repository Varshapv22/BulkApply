import React from 'react';
import { router } from '@inertiajs/react';
import { PageHead } from '../../../components';
import AdminLayout from '../../../AdminLayout';

const TYPE_LABELS = { contact: 'Contact', feedback: 'Feedback', feature_request: 'Feature Request', bug_report: 'Bug Report' };

export default function AdminSupportIndex({ tickets, filters }) {
    const setFilter = (key, value) => router.get('/admin/support', { ...filters, [key]: value }, { preserveState: true, replace: true });
    const updateStatus = (id, status) => router.post(`/admin/support/${id}/status`, { status }, { preserveScroll: true });

    return (
        <>
            <PageHead title="Support" subtitle="Contact messages, feedback, feature requests, and bug reports." />

            <div className="card card-pad-sm">
                <div style={{ display: 'flex', gap: 10 }}>
                    <select value={filters.type || ''} onChange={(e) => setFilter('type', e.target.value)} style={{ width: 'auto' }}>
                        <option value="">All types</option>
                        {Object.entries(TYPE_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                    </select>
                    <select value={filters.status || ''} onChange={(e) => setFilter('status', e.target.value)} style={{ width: 'auto' }}>
                        <option value="">All statuses</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
            </div>

            <div className="card">
                {tickets.length === 0 ? <p className="muted">No tickets.</p> : (
                    <table>
                        <thead><tr><th>Type</th><th>From</th><th>Subject / Message</th><th>Status</th><th>Received</th></tr></thead>
                        <tbody>
                            {tickets.map((t) => (
                                <tr key={t.id}>
                                    <td>{TYPE_LABELS[t.type] || t.type}</td>
                                    <td>{t.name}<div className="muted" style={{ fontSize: 12 }}>{t.email}</div></td>
                                    <td>{t.subject || t.message.slice(0, 80)}</td>
                                    <td>
                                        <select value={t.status} onChange={(e) => updateStatus(t.id, e.target.value)} style={{ width: 'auto' }}>
                                            <option value="open">Open</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="resolved">Resolved</option>
                                        </select>
                                    </td>
                                    <td>{new Date(t.created_at).toLocaleDateString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </>
    );
}

AdminSupportIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
