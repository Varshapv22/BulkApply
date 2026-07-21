import React from 'react';
import { router } from '@inertiajs/react';
import { PageHead, Badge } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminJobSourcesIndex({ sources }) {
    const toggle = (source) => router.post(`/admin/job-sources/${source.id}/toggle`, {}, { preserveScroll: true });

    return (
        <>
            <PageHead title="Job Search Sources" subtitle="Enable or disable each job search / extension source." />

            <div className="card">
                <table>
                    <thead><tr><th>Source</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        {sources.map((s) => (
                            <tr key={s.id}>
                                <td>{s.label}</td>
                                <td><Badge status={s.enabled ? 'sent' : 'failed'}>{s.enabled ? 'Enabled' : 'Disabled'}</Badge></td>
                                <td><button className="btn btn-ghost btn-sm" onClick={() => toggle(s)}>{s.enabled ? 'Disable' : 'Enable'}</button></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}

AdminJobSourcesIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
