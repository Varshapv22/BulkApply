import React from 'react';
import { Link } from '@inertiajs/react';
import { PageHead, ChipIcon, Icons, Badge, Pagination, SUPPORT_TICKET_TYPES, SUPPORT_TICKET_STATUSES } from '../../../components';

export default function SupportTicketsIndex({ tickets }) {
    return (
        <>
            <PageHead title="My Tickets" subtitle="Questions, feedback, feature requests, and bug reports you've sent us." />

            <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 14 }}>
                <Link href="/contact" className="btn btn-primary btn-sm">
                    <ChipIcon icon={Icons.plus} /> New ticket
                </Link>
            </div>

            <div className="card">
                {tickets.data.length === 0 ? (
                    <p className="muted">You haven't contacted support yet.</p>
                ) : (
                    <div className="table-wrap">
                        <table>
                            <thead><tr><th>Type</th><th>Subject</th><th>Status</th><th>Sent</th><th></th></tr></thead>
                            <tbody>
                                {tickets.data.map((t) => {
                                    const typeMeta = SUPPORT_TICKET_TYPES[t.type] || { label: t.type, icon: 'chat', badge: 'neutral' };
                                    const statusMeta = SUPPORT_TICKET_STATUSES[t.status] || { label: t.status, badge: 'neutral' };
                                    return (
                                        <tr key={t.id}>
                                            <td><Badge status={typeMeta.badge}>{typeMeta.label}</Badge></td>
                                            <td>{t.subject || t.message.slice(0, 60)}</td>
                                            <td><Badge status={statusMeta.badge}>{statusMeta.label}</Badge></td>
                                            <td>{new Date(t.created_at).toLocaleDateString()}</td>
                                            <td className="cell-actions">
                                                <Link href={`/support/tickets/${t.id}`} className="btn btn-ghost btn-sm">View</Link>
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
