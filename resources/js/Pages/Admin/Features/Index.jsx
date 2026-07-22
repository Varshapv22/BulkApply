import React from 'react';
import { router } from '@inertiajs/react';
import { PageHead, Badge } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminFeaturesIndex({ features }) {
    const toggle = (feature) => router.post(`/admin/features/${feature.id}/toggle`, {}, { preserveScroll: true });

    return (
        <>
            <PageHead title="Feature Management" subtitle="Enable or disable platform-wide features." />

            <div className="card">
                <div className="table-wrap">
                    <table>
                        <thead><tr><th>Feature</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            {features.map((f) => (
                                <tr key={f.id}>
                                    <td>{f.label}</td>
                                    <td><Badge status={f.enabled ? 'sent' : 'failed'}>{f.enabled ? 'Enabled' : 'Disabled'}</Badge></td>
                                    <td><button className="btn btn-ghost btn-sm" onClick={() => toggle(f)}>{f.enabled ? 'Disable' : 'Enable'}</button></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

AdminFeaturesIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
