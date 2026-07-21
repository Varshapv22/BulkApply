import React from 'react';
import { router } from '@inertiajs/react';
import { PageHead, Badge } from '../../../components';
import AdminLayout from '../../../AdminLayout';

const TYPE_LABELS = {
    new_registration: 'New Registration',
    email_failed: 'Email Failed',
    queue_failed: 'Queue Failure',
    webhook_failed: 'Webhook Failure',
};

export default function AdminNotificationsIndex({ notifications, unreadCount }) {
    const markRead = (id) => router.post(`/admin/notifications/${id}/read`, {}, { preserveScroll: true });
    const markAllRead = () => router.post('/admin/notifications/mark-all-read', {}, { preserveScroll: true });

    return (
        <>
            <PageHead title="Notifications" subtitle={`${unreadCount} unread notification(s).`} />

            {unreadCount > 0 && (
                <button className="btn btn-primary btn-sm" style={{ marginBottom: 16 }} onClick={markAllRead}>Mark all read</button>
            )}

            <div className="card">
                {notifications.length === 0 ? <p className="muted">No notifications yet.</p> : (
                    <table>
                        <thead><tr><th>Type</th><th>Message</th><th>When</th><th></th></tr></thead>
                        <tbody>
                            {notifications.map((n) => (
                                <tr key={n.id} style={{ opacity: n.read_at ? 0.55 : 1 }}>
                                    <td><Badge status={n.read_at ? 'neutral' : 'failed'}>{TYPE_LABELS[n.type] || n.type}</Badge></td>
                                    <td>{n.message}</td>
                                    <td>{new Date(n.created_at).toLocaleString()}</td>
                                    <td>{!n.read_at && <button className="btn btn-ghost btn-sm" onClick={() => markRead(n.id)}>Mark read</button>}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </>
    );
}

AdminNotificationsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
