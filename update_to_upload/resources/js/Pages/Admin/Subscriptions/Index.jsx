import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { PageHead, Badge, IconField, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminSubscriptionsIndex({ subscriptions, filters }) {
    const [f, setF] = useState({
        search: filters.search || '',
        status: filters.status || '',
    });

    const applyFilters = (next) => {
        const merged = { ...f, ...next };
        setF(merged);
        router.get('/admin/subscriptions', merged, { preserveState: true, replace: true });
    };

    const cancel = (sub) => {
        if (!confirm(`Cancel ${sub.user?.name}'s "${sub.plan?.name}" subscription?`)) return;
        router.delete(`/admin/users/${sub.user.id}/subscription`, { preserveScroll: true });
    };

    return (
        <>
            <PageHead title="Subscriptions" subtitle="Every subscription across all users, in one place." />

            <div className="card card-pad-sm">
                <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'center' }}>
                    <IconField
                        icon={Icons.search}
                        type="text"
                        placeholder="Search user name or email…"
                        value={f.search}
                        onChange={(e) => applyFilters({ search: e.target.value })}
                        style={{ minWidth: 220 }}
                    />
                    <select value={f.status} onChange={(e) => applyFilters({ status: e.target.value })} style={{ width: 'auto' }}>
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
            </div>

            <div className="card">
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th><th>Plan</th><th>Status</th><th>Starts</th><th>Ends</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {subscriptions.data.map((s) => (
                                <tr key={s.id}>
                                    <td>
                                        {s.user ? <Link href={`/admin/users/${s.user.id}`}>{s.user.name}</Link> : '—'}
                                        <div className="muted" style={{ fontSize: 12 }}>{s.user?.email}</div>
                                    </td>
                                    <td>{s.plan ? `${s.plan.name} ($${s.plan.price}/${s.plan.billing_interval === 'monthly' ? 'mo' : 'yr'})` : '—'}</td>
                                    <td>
                                        <Badge status={s.status === 'active' ? 'sent' : s.status === 'cancelled' ? 'failed' : 'neutral'}>
                                            {s.status}
                                        </Badge>
                                    </td>
                                    <td>{s.starts_at ? new Date(s.starts_at).toLocaleDateString() : '—'}</td>
                                    <td>{s.ends_at ? new Date(s.ends_at).toLocaleDateString() : '—'}</td>
                                    <td>
                                        {s.status === 'active' && s.user && (
                                            <button className="btn btn-danger btn-sm" onClick={() => cancel(s)}>Cancel</button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {subscriptions.data.length === 0 && (
                                <tr><td colSpan={6} className="empty">No subscriptions match these filters.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {subscriptions.links && subscriptions.links.length > 3 && (
                    <div style={{ display: 'flex', gap: 6, marginTop: 16, flexWrap: 'wrap' }}>
                        {subscriptions.links.map((link, i) => (
                            <button
                                key={i}
                                className={`btn btn-sm ${link.active ? 'btn-primary' : 'btn-ghost'}`}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

AdminSubscriptionsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
