import React from 'react';
import { Link, router } from '@inertiajs/react';
import { PageHead, Pagination, Badge, ChipIcon, Icons, SUPPORT_TICKET_TYPES, SUPPORT_TICKET_STATUSES } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminSupportIndex({ tickets, filters }) {
    const setFilter = (key, value) => router.get('/admin/support', { ...filters, [key]: value }, { preserveState: true, replace: true });
    const updateStatus = (id, status) => router.post(`/admin/support/${id}/status`, { status }, { preserveScroll: true });

    return (
        <>
            <PageHead title="Support" subtitle="Contact messages, feedback, feature requests, and bug reports." />

            <div className="card card-pad-sm">
                <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
                    <select value={filters.type || ''} onChange={(e) => setFilter('type', e.target.value)} style={{ width: 'auto' }}>
                        <option value="">All types</option>
                        {Object.entries(SUPPORT_TICKET_TYPES).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
                    </select>
                    <select value={filters.status || ''} onChange={(e) => setFilter('status', e.target.value)} style={{ width: 'auto' }}>
                        <option value="">All statuses</option>
                        {Object.entries(SUPPORT_TICKET_STATUSES).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
                    </select>
                </div>
            </div>

            <div className="card">
                {tickets.data.length === 0 ? <p className="muted">No tickets.</p> : (
                    <div className="table-wrap">
                        <table>
                            <thead><tr><th>Type</th><th>From</th><th>Subject / Message</th><th>Replies</th><th>Status</th><th>Received</th><th></th></tr></thead>
                            <tbody>
                                {tickets.data.map((t) => {
                                    const typeMeta = SUPPORT_TICKET_TYPES[t.type] || { label: t.type, badge: 'neutral' };
                                    return (
                                        <tr key={t.id}>
                                            <td><Badge status={typeMeta.badge}>{typeMeta.label}</Badge></td>
                                            <td>{t.name}<div className="muted" style={{ fontSize: 12 }}>{t.email}</div></td>
                                            <td>
                                                {t.subject || t.message.slice(0, 80)}
                                                {t.has_attachment && <span title="Has attachment" style={{ marginLeft: 6 }}>📎</span>}
                                            </td>
                                            <td>{t.replies_count > 0 ? t.replies_count : '—'}</td>
                                            <td>
                                                <select value={t.status} onChange={(e) => updateStatus(t.id, e.target.value)} style={{ width: 'auto' }}>
                                                    {Object.entries(SUPPORT_TICKET_STATUSES).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
                                                </select>
                                            </td>
                                            <td>{new Date(t.created_at).toLocaleDateString()}</td>
                                            <td>
                                                <div className="cell-actions">
                                                    <Link href={`/admin/support/${t.id}`} className="icon-btn" title="View" aria-label="View">
                                                        <ChipIcon icon={Icons.eye} />
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination meta={tickets} />
            </div>
        </>
    );
}

AdminSupportIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
