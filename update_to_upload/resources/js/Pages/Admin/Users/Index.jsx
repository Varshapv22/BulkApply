import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { PageHead, Badge, IconField, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminUsersIndex({ users, roles, filters }) {
    const [f, setF] = useState({
        search: filters.search || '',
        status: filters.status || '',
        role: filters.role || '',
        sort: filters.sort || 'created_at',
        direction: filters.direction || 'desc',
    });

    const applyFilters = (next) => {
        const merged = { ...f, ...next };
        setF(merged);
        router.get('/admin/users', merged, { preserveState: true, replace: true });
    };

    return (
        <>
            <PageHead title="Users" subtitle="Search, filter, and manage every account on the platform." />

            <div className="card card-pad-sm">
                <div className="filters-row" style={{ display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'center' }}>
                    <IconField
                        icon={Icons.search}
                        type="text"
                        placeholder="Search name or email…"
                        value={f.search}
                        onChange={(e) => applyFilters({ search: e.target.value })}
                        style={{ minWidth: 220 }}
                    />
                    <select value={f.status} onChange={(e) => applyFilters({ status: e.target.value })} style={{ width: 'auto' }}>
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    <select value={f.role} onChange={(e) => applyFilters({ role: e.target.value })} style={{ width: 'auto' }}>
                        <option value="">All roles</option>
                        {roles.map((r) => <option key={r} value={r}>{r}</option>)}
                    </select>
                    <select value={f.sort} onChange={(e) => applyFilters({ sort: e.target.value })} style={{ width: 'auto' }}>
                        <option value="created_at">Sort: Joined</option>
                        <option value="name">Sort: Name</option>
                        <option value="email">Sort: Email</option>
                        <option value="last_login_at">Sort: Last login</option>
                    </select>
                    <div style={{ flex: 1 }} />
                    <a href="/admin/users/export" className="btn btn-ghost btn-sm">Export CSV</a>
                </div>
            </div>

            <div className="card">
                <div style={{ overflowX: 'auto' }}>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Roles</th>
                                <th>Resumes</th>
                                <th>Last Login</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.map((u) => (
                                <tr key={u.id}>
                                    <td><Link href={`/admin/users/${u.id}`}>{u.name}</Link></td>
                                    <td>{u.email}</td>
                                    <td><Badge status={u.is_active ? 'sent' : 'failed'}>{u.is_active ? 'Active' : 'Suspended'}</Badge></td>
                                    <td>{u.roles.length ? u.roles.join(', ') : '—'}</td>
                                    <td>{u.resumes_count}</td>
                                    <td>{u.last_login_at ? new Date(u.last_login_at).toLocaleString() : 'Never'}</td>
                                    <td>{new Date(u.created_at).toLocaleDateString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {users.links && users.links.length > 3 && (
                    <div style={{ display: 'flex', gap: 6, marginTop: 16, flexWrap: 'wrap' }}>
                        {users.links.map((link, i) => (
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

AdminUsersIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
