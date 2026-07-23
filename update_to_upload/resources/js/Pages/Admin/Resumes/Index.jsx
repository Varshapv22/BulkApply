import React from 'react';
import { Link, router } from '@inertiajs/react';
import { PageHead, Stat, Badge, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminResumesIndex({ resumes, totalStorageKb }) {
    const destroy = (id) => { if (confirm('Delete this resume?')) router.delete(`/admin/resumes/${id}`, { preserveScroll: true }); };

    return (
        <>
            <PageHead title="Resume Manager" subtitle="All resumes uploaded across the platform." />

            <div className="stats">
                <Stat label="Total Resumes" value={resumes.length} icon={Icons.upload} accent="primary" />
                <Stat label="Total Storage" value={`${(totalStorageKb / 1024).toFixed(1)} MB`} icon={Icons.doc} accent="blue" />
            </div>

            <div className="card">
                {resumes.length === 0 ? <p className="muted">No resumes uploaded yet.</p> : (
                    <div className="table-wrap">
                        <table>
                            <thead><tr><th>Name</th><th>User</th><th>Default</th><th>Size</th><th>Uploaded</th><th></th></tr></thead>
                            <tbody>
                                {resumes.map((r) => (
                                    <tr key={r.id}>
                                        <td>{r.name}</td>
                                        <td>{r.user ? <Link href={`/admin/users/${r.user.id}`}>{r.user.name}</Link> : '—'}</td>
                                        <td>{r.is_default ? <Badge status="sent">Default</Badge> : '—'}</td>
                                        <td>{r.size_kb !== null ? `${r.size_kb} KB` : 'missing'}</td>
                                        <td>{new Date(r.created_at).toLocaleDateString()}</td>
                                        <td style={{ display: 'flex', gap: 6 }}>
                                            <a className="btn btn-ghost btn-sm" href={`/admin/resumes/${r.id}/download`}>Download</a>
                                            <button className="btn btn-danger btn-sm" onClick={() => destroy(r.id)}>Delete</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

AdminResumesIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
