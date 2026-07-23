import React from 'react';
import { router } from '@inertiajs/react';
import { PageHead } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminDatabaseToolsIndex({ actions, migrationStatus }) {
    const run = (action) => { if (confirm(`Run "${actions[action]}"?`)) router.post(`/admin/database-tools/${action}`, {}, { preserveScroll: true }); };

    return (
        <>
            <PageHead title="Database Tools" subtitle="Common Artisan maintenance commands." />

            <div className="card">
                <h2>Actions</h2>
                <p className="muted" style={{ fontSize: 12, marginBottom: 12 }}>
                    Caching commands (route:cache/config:cache/optimize) are intentionally not offered here — this app has a closure-based route that route:cache cannot serialize, so running it would break the app until cleared again.
                </p>
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                    {Object.entries(actions).map(([action, label]) => (
                        <button key={action} className="btn btn-ghost btn-sm" onClick={() => run(action)}>{label}</button>
                    ))}
                </div>
            </div>

            <div className="card">
                <h2>Migration Status</h2>
                <pre style={{ whiteSpace: 'pre-wrap', fontSize: 12, background: 'var(--hover)', padding: 10, borderRadius: 8 }}>{migrationStatus}</pre>
            </div>
        </>
    );
}

AdminDatabaseToolsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
