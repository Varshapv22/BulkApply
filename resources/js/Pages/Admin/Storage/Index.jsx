import React from 'react';
import { router } from '@inertiajs/react';
import { PageHead, Stat, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminStorageIndex({ usage, diskFree_gb, diskTotal_gb }) {
    const cleanCache = () => router.post('/admin/storage/clean-cache', {}, { preserveScroll: true });

    return (
        <>
            <PageHead title="Storage" subtitle="Disk usage breakdown for app-managed storage." />

            <div className="stats">
                <Stat label="Documents (resumes + cover letters)" value={`${(usage.documents_kb / 1024).toFixed(1)} MB`} icon={Icons.upload} accent="primary" />
                <Stat label="Logs" value={`${(usage.logs_kb / 1024).toFixed(1)} MB`} icon={Icons.doc} accent="blue" />
                <Stat label="Cache" value={`${(usage.cache_kb / 1024).toFixed(1)} MB`} icon={Icons.trash} accent="amber" />
                <Stat label="Sessions" value={`${(usage.sessions_kb / 1024).toFixed(1)} MB`} icon={Icons.clock} accent="violet" />
            </div>

            <div className="card">
                <h2>Disk Space</h2>
                <p className="muted">{diskFree_gb} GB free of {diskTotal_gb} GB total.</p>
                <button className="btn btn-ghost btn-sm" onClick={cleanCache}>Clear application cache</button>
            </div>
        </>
    );
}

AdminStorageIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
