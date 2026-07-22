import React from 'react';
import { router } from '@inertiajs/react';
import { PageHead } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminLogsIndex({ lines, level, exists }) {
    const setLevel = (l) => router.get('/admin/logs', l ? { level: l } : {}, { preserveState: true });

    return (
        <>
            <PageHead title="Logs" subtitle="Tail of storage/logs/laravel.log (most recent 200 entries)." />

            <div className="card card-pad-sm">
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                    {['', 'ERROR', 'WARNING', 'INFO'].map((l) => (
                        <button key={l} className={`btn btn-sm ${level === l || (!level && !l) ? 'btn-primary' : 'btn-ghost'}`} onClick={() => setLevel(l)}>
                            {l || 'All'}
                        </button>
                    ))}
                </div>
            </div>

            <div className="card">
                {!exists ? <p className="muted">Log file not found.</p> : lines.length === 0 ? <p className="muted">No matching log entries.</p> : (
                    <div style={{ maxHeight: 600, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: 4 }}>
                        {lines.map((line, i) => (
                            <pre key={i} style={{ whiteSpace: 'pre-wrap', fontSize: 12, background: 'var(--hover)', padding: 10, borderRadius: 8, margin: 0 }}>{line}</pre>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

AdminLogsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
