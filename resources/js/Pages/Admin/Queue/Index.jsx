import React from 'react';
import { router } from '@inertiajs/react';
import { PageHead, Stat, Badge, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminQueueIndex({ batches, failedJobs, pendingCount }) {
    const cancelBatch = (id) => { if (confirm('Cancel this batch?')) router.post(`/admin/queue/batches/${id}/cancel`, {}, { preserveScroll: true }); };
    const deleteFailed = (id) => router.delete(`/admin/queue/failed/${id}`, { preserveScroll: true });

    return (
        <>
            <PageHead title="Queue" subtitle="Bulk-send batches and failed background jobs." />

            <div className="stats">
                <Stat label="Pending Jobs" value={pendingCount} icon={Icons.clock} accent="amber" />
                <Stat label="Recent Batches" value={batches.length} icon={Icons.send} accent="primary" />
                <Stat label="Failed Jobs" value={failedJobs.length} icon={Icons.alert} accent="red" />
            </div>

            <div className="card">
                <h2>Recent Batches</h2>
                {batches.length === 0 ? <p className="muted">No batches yet.</p> : (
                    <table>
                        <thead><tr><th>Name</th><th>Total</th><th>Pending</th><th>Failed</th><th>Status</th><th>Created</th><th></th></tr></thead>
                        <tbody>
                            {batches.map((b) => (
                                <tr key={b.id}>
                                    <td>{b.name}</td>
                                    <td>{b.total_jobs}</td>
                                    <td>{b.pending_jobs}</td>
                                    <td>{b.failed_jobs}</td>
                                    <td>
                                        {b.cancelled ? <Badge status="failed">Cancelled</Badge>
                                            : b.finished ? <Badge status="sent">Finished</Badge>
                                            : <Badge status="queued">Running</Badge>}
                                    </td>
                                    <td>{b.created_at}</td>
                                    <td>{!b.cancelled && !b.finished && (
                                        <button className="btn btn-ghost btn-sm" onClick={() => cancelBatch(b.id)}>Cancel</button>
                                    )}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            <div className="card">
                <h2>Failed Jobs</h2>
                {failedJobs.length === 0 ? <p className="muted">No failed jobs.</p> : (
                    <table>
                        <thead><tr><th>Queue</th><th>Error</th><th>Failed At</th><th></th></tr></thead>
                        <tbody>
                            {failedJobs.map((f) => (
                                <tr key={f.id}>
                                    <td>{f.queue}</td>
                                    <td>{f.exception_short}</td>
                                    <td>{f.failed_at}</td>
                                    <td><button className="btn btn-ghost btn-sm" onClick={() => deleteFailed(f.id)}>Dismiss</button></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </>
    );
}

AdminQueueIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
