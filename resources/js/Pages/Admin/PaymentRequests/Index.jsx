import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PageHead, Badge } from '../../../components';
import AdminLayout from '../../../AdminLayout';

function statusVariant(status) {
    if (status === 'approved') return 'sent';
    if (status === 'rejected') return 'failed';
    return 'neutral';
}

export default function AdminPaymentRequestsIndex({ requests, filters }) {
    const { props } = usePage();
    const flash = props.flash || {};
    const currencySymbol = props.currencySymbol || '₹';
    const [status, setStatus] = useState(filters.status || '');
    const [busyId, setBusyId] = useState(null);

    const applyFilter = (next) => {
        setStatus(next);
        router.get('/admin/payment-requests', { status: next }, { preserveState: true, replace: true });
    };

    const approve = (r) => {
        if (!confirm(`Approve payment from ${r.user?.name} and activate "${r.plan?.name}"?`)) return;
        setBusyId(r.id);
        router.post(`/admin/payment-requests/${r.id}/approve`, {}, { preserveScroll: true, onFinish: () => setBusyId(null) });
    };

    const reject = (r) => {
        const note = prompt(`Reject payment from ${r.user?.name}? Optionally add a reason:`, '');
        if (note === null) return;
        setBusyId(r.id);
        router.post(`/admin/payment-requests/${r.id}/reject`, { admin_note: note }, { preserveScroll: true, onFinish: () => setBusyId(null) });
    };

    return (
        <>
            <PageHead title="Payment Requests" subtitle="UPI payments submitted by users, awaiting verification." />

            {flash.status && (
                <div className="alert alert-success"><div className="alert-body">{flash.status}</div></div>
            )}
            {flash.error && (
                <div className="alert alert-error"><div className="alert-body">{flash.error}</div></div>
            )}

            <div className="card card-pad-sm">
                <select value={status} onChange={(e) => applyFilter(e.target.value)} style={{ width: 'auto' }}>
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div className="card">
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th><th>Plan</th><th>Amount</th><th>UTR / Ref</th><th>Proof</th><th>Status</th><th>Submitted</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {requests.data.map((r) => (
                                <tr key={r.id}>
                                    <td>
                                        {r.user?.name || '—'}
                                        <div className="muted" style={{ fontSize: 12 }}>{r.user?.email}</div>
                                    </td>
                                    <td>{r.plan?.name || '—'}</td>
                                    <td>{currencySymbol}{r.amount}</td>
                                    <td>{r.transaction_ref}</td>
                                    <td>
                                        {r.screenshot_path ? (
                                            <a href={`/admin/payment-requests/${r.id}/screenshot`} target="_blank" rel="noopener">View</a>
                                        ) : '—'}
                                    </td>
                                    <td>
                                        <Badge status={statusVariant(r.status)}>{r.status}</Badge>
                                        {r.status !== 'pending' && r.admin_note && (
                                            <div className="muted" style={{ fontSize: 12, marginTop: 2 }}>{r.admin_note}</div>
                                        )}
                                    </td>
                                    <td>{new Date(r.created_at).toLocaleString()}</td>
                                    <td>
                                        {r.status === 'pending' && (
                                            <div style={{ display: 'flex', gap: 6 }}>
                                                <button className="btn btn-primary btn-sm" disabled={busyId === r.id} onClick={() => approve(r)}>Approve</button>
                                                <button className="btn btn-danger btn-sm" disabled={busyId === r.id} onClick={() => reject(r)}>Reject</button>
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {requests.data.length === 0 && (
                                <tr><td colSpan={8} className="empty">No payment requests match these filters.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {requests.links && requests.links.length > 3 && (
                    <div style={{ display: 'flex', gap: 6, marginTop: 16, flexWrap: 'wrap' }}>
                        {requests.links.map((link, i) => (
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

AdminPaymentRequestsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
