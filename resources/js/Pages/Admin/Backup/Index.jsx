import React from 'react';
import { router } from '@inertiajs/react';
import { PageHead, Stat, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminBackupIndex({ backups }) {
    const runBackup = () => router.post('/admin/backup/run', {}, { preserveScroll: true });
    const destroy = (name) => { if (confirm(`Delete backup "${name}"?`)) router.delete(`/admin/backup/${name}`, { preserveScroll: true }); };

    return (
        <>
            <PageHead title="Backup" subtitle="Database backups (manual trigger + history)." />

            <div className="stats">
                <Stat label="Backups" value={backups.length} icon={Icons.save} accent="primary" />
            </div>

            <div className="card">
                <button className="btn btn-primary btn-sm" onClick={runBackup} style={{ marginBottom: 16 }}>Run backup now</button>
                <p className="muted" style={{ fontSize: 12 }}>Restoring is intentionally not a one-click UI action — download the backup and restore it manually (or via CLI) to avoid an accidental overwrite of live data.</p>

                {backups.length === 0 ? <p className="muted">No backups yet.</p> : (
                    <table>
                        <thead><tr><th>File</th><th>Size</th><th>Created</th><th></th></tr></thead>
                        <tbody>
                            {backups.map((b) => (
                                <tr key={b.name}>
                                    <td>{b.name}</td>
                                    <td>{(b.size_kb / 1024).toFixed(1)} MB</td>
                                    <td>{b.created_at}</td>
                                    <td style={{ display: 'flex', gap: 6 }}>
                                        <a className="btn btn-ghost btn-sm" href={`/admin/backup/${b.name}/download`}>Download</a>
                                        <button className="btn btn-danger btn-sm" onClick={() => destroy(b.name)}>Delete</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </>
    );
}

AdminBackupIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
