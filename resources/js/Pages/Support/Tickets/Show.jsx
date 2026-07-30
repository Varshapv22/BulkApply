import React from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { PageHead, Badge, TicketThread, SUPPORT_TICKET_TYPES, SUPPORT_TICKET_STATUSES } from '../../../components';

export default function SupportTicketShow({ ticket, replies }) {
    const { props } = usePage();
    const flash = props.flash || {};
    const form = useForm({ message: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(`/support/tickets/${ticket.id}/reply`, { preserveScroll: true, onSuccess: () => form.reset('message') });
    };

    const typeMeta = SUPPORT_TICKET_TYPES[ticket.type] || { label: ticket.type, badge: 'neutral' };
    const statusMeta = SUPPORT_TICKET_STATUSES[ticket.status] || { label: ticket.status, badge: 'neutral' };

    return (
        <>
            <PageHead title={ticket.subject || 'Support Ticket'} subtitle={`Ticket #${ticket.id} — ${typeMeta.label}`} />

            <Link href="/support/tickets" className="btn btn-ghost btn-sm" style={{ marginBottom: 14 }}>← Back to My Tickets</Link>

            {flash.status && (
                <div className="alert alert-success" style={{ marginBottom: 16 }}><div className="alert-body">{flash.status}</div></div>
            )}

            <div className="card">
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap', marginBottom: 6 }}>
                    <Badge status={typeMeta.badge}>{typeMeta.label}</Badge>
                    <Badge status={statusMeta.badge}>{statusMeta.label}</Badge>
                    <span className="muted" style={{ fontSize: 12 }}>Sent {new Date(ticket.created_at).toLocaleString()}</span>
                </div>
                {ticket.subject && <h2 style={{ marginTop: 8 }}>{ticket.subject}</h2>}
                <p style={{ whiteSpace: 'pre-wrap', marginTop: 8 }}>{ticket.message}</p>
                {ticket.attachment_name && (
                    <a className="btn btn-ghost btn-sm" style={{ marginTop: 10 }} href={`/support/tickets/${ticket.id}/attachment`}>
                        📎 {ticket.attachment_name}
                    </a>
                )}
            </div>

            <div className="card" style={{ marginTop: 16 }}>
                <h2>Conversation</h2>
                <div style={{ marginTop: 12 }}>
                    <TicketThread replies={replies} viewerIsAdmin={false} />
                </div>

                <form onSubmit={submit} style={{ marginTop: 20, borderTop: '1px solid var(--border)', paddingTop: 16 }}>
                    <textarea placeholder="Write a reply…" value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} style={{ minHeight: 90, width: '100%' }} required />
                    {form.errors.message && <div style={{ color: 'var(--red)', fontSize: 12.5, marginTop: 4 }}>{form.errors.message}</div>}
                    <button type="submit" className="btn btn-primary btn-sm" style={{ marginTop: 10 }} disabled={form.processing}>
                        {form.processing ? 'Sending…' : 'Send reply'}
                    </button>
                </form>
            </div>
        </>
    );
}
